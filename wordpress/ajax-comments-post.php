<?php

/**
 * @file
 * AJAX Request Handler
 */

// AJAX Comments requires the use of PHP Output Control functions.
// The behavior of those functions is determined by settings in php.ini.
// See also: http://php.net/outcontrol
ini_set('output_handler', NULL); // disable any output handlers
ob_implicit_flush(FALSE); // disable implicit_flush
ob_start('ajax_commments_ob_callback'); // enable output_buffering and start buffering

// Register hooks with PHP
set_error_handler('ajax_comments_error_handler');
register_shutdown_function('ajax_comments_shutdown');

// Invoke WordPress bootstrap
// and process incoming $_POST comment data
require('../../../wp-comments-post.php');

/**
 * ob_start() callback.
 * Catch WordPress Errors and output them as plain text.
 */
function ajax_commments_ob_callback($buffer, $type) {
  global $ajax_comments_errors;

  // Catch non-errors (e.g. blank, redirect only)
  if (trim($buffer) == '') {
    return FALSE; // pass-through
  } else

  // Catch wp_die() WordPress Errors
  if (strpos($buffer, 'WordPress &rsaquo; Error') // if an error is present
      && preg_match('!<p>(.+?)</p>!is', $buffer, $errors)) { // parse it out
    $ajax_comments_errors[] = $errors[1]; // return plain-text error only
  } else

  // Catch unexpected WordPress Errors (e.g. custom plugin errors)
  if (trim($buffer) != '') {
    $ajax_comments_errors[] = ajax_comments_innertext($buffer);
  }

  return ''; // catch/filter everything
}

/**
 * set_error_handler() callback.
 * Catch PHP errors and queue them for friendly display via AJAX.
 */
function ajax_comments_error_handler($errno, $message, $filename, $line) {
  // If the @ error suppression operator was used, error_reporting is temporarily set to 0
  if (error_reporting() == 0) {
    return;
  }

  if ($errno & (E_ALL ^ E_NOTICE)) {
    $types = array(1 => 'error', 2 => 'warning', 4 => 'parse error', 8 => 'notice', 16 => 'core error', 32 => 'core warning', 64 => 'compile error', 128 => 'compile warning', 256 => 'user error', 512 => 'user warning', 1024 => 'user notice', 2048 => 'strict warning');
    $entry = $types[$errno] .': '. $message .' in '. $filename .' on line '. $line .'.';
    global $ajax_comments_errors;
    $ajax_comments_errors[] = $entry;
  }
}

/**
 * Set HTTP headers, output to browser, and die.
 *
 * @param String $output
 *   Output for XMLHTTPRequest object.
 */
function ajax_comments_die($output) {
  status_header(200); // HTTP/1.0 200 OK
  // QUIRK: Normally we would return 200 to indicate success or 500 to indicate error. However,
  //        Opera 9.x will discard the contents of XMLHttpRequest.responseText unless it receives 200.
  //        Fortunately, jQuery.ajax will also recognize errors if the responseText does not match the specified jQuery.ajax.dataType.
  //        Therefore successions are passed in JSON and errors passed in plain text.
  @header('Content-Type: text/x-json; charset=utf-8', TRUE); // set content type to JSON
  die($output); // output to browser and die
}

/**
 * Implementation of wp_redirect filter.
 * Prevent redirection from wp-comments-post.php.
 */
function ajax_comments_wp_redirect($redirect) {
  return; // cancel redirection
}

/**
 * register_shutdown_function() callback.
 * Provide response to XMLHTTPRequest object.
 */
function ajax_comments_shutdown() {
  global $comment, $commentdata;

  if (empty($comment) && !empty($commentdata)) { // before WP 2.0.10 - when $GLOBALS['comment'] was introduced
    global $wpdb;
    $comment = $wpdb->get_row("SELECT * FROM $wpdb->comments ORDER BY comment_id DESC LIMIT 1;");
  }

  ob_end_clean(); // stop buffering; which will trigger parsing of any prior wp_die() WordPress errors.
  // QUIRK: Depending on your PHP configuration, die() may or may not trigger ob_end_flush() when executed by wp_die().
  //        Therefore we explicitly stop output buffering above, in case it hasn't happened already.

  // Prepare a response for the XMLHTTPRequest object
  $options = get_option('ajax_comments_options'); // retrieve plugin settings
  $output = '{'. // prepare JSON object
    // Indicate entire template [flexible] or just one comment [conventional]
    '"comment_type":"'. ($options['performance'] = $options['performance'] == 'conventional'? 'conventional' : 'flexible') .'",'.
    // Provide selector for jQuery effect(s)
    '"comment_ID":'. ((int) $comment->comment_ID) .','.
    // Provide actual markup from current theme comments.php
    '"comments_template":'. ajax_comments_js_escape(ajax_comments_render_comments($comment, $options['performance'] == 'flexible'? TRUE : FALSE)) .
  '}';

  // Display caught errors, if any
  global $ajax_comments_errors;
  if ($n = count($ajax_comments_errors)) {
    ajax_comments_die(implode("<br />\n", $ajax_comments_errors), FALSE);
  }

  // If no errors present, assume successful comment insertion
  ajax_comments_die($output); // return new comment markup
}

/**
 * Retrieve the markup generated by the current theme
 * comments.php template for a given comment.
 *
 * @param Array $comment
 *   Comment.
 * @param Boolean $entire_template
 *   Specify whether to retrieve entire template or just one comment.
 * @return String
 *   Template markup from comments.php.
 */
function ajax_comments_render_comments(&$comment, $entire_template = TRUE) {
  if (empty($comment)) return; // abort

  global $ajax_commments_post_ID;
  $ajax_commments_post_ID = $comment->comment_post_ID; // store for ajax_comments_request()

  // Prepare cookies for wp_get_current_commenter(), if necessary
  // Normally these would have been set during the post, and retrieved as the browser reloaded the page
  // But since we are using AJAX, we have to manually insert these into the $_COOKIES global variable the first time an anonymous user comments.
  // Required in order to display posts held for moderation.
  if (!$GLOBALS['user']->ID) { // only applies for non-logged-in users...
    $_COOKIE['comment_author_'.COOKIEHASH] = $comment->comment_author;
    $_COOKIE['comment_author_email_'.COOKIEHASH] = $comment->comment_author_email;
    $_COOKIE['comment_author_url_'.COOKIEHASH] = $comment->comment_author_url;
  }

  // Here we need to load all the variables normally present when visiting the single post page
  // The only good way to prepare all of them correctly is by using the WordPress API...
  ob_start(); // start buffering
  add_filter('request', 'ajax_comments_request'); // Like query_vars, but applied after "extra" and private query variables have been added.
  wp(); // generate necessary variables
  global $post, $withcomments; $withcomments = TRUE;
  $post->ID = $ajax_commments_post_ID; // forces ID to be populated for pages as well as posts
  comments_template(); // render comments template
  remove_filter('request', 'ajax_comments_request');
  $output = ob_get_clean(); // get template, clear buffer, and end buffering
  unset($ajax_commments_post_ID);

  if ($entire_template) {
    return $output; // return entire comments.php template
  } else {
    // Parse important elements
    $wrapper_element = ajax_comments_parse_tag($output, array('id' => 'ajax_comments_wrapper'));
    $counter_element = ajax_comments_parse_tag($output, array('id' => 'comments'));
    $container_element = ajax_comments_parse_tag($output, array('class' => 'commentlist'));
    $comment_element = ajax_comments_parse_tag($output, array('id' => 'comment-'. $comment->comment_ID));

    // Return new comment only
    return $wrapper_element[0] . // wrap with <div id="ajax_comments_wrapper">
      implode("\n", (array) $counter_element) . // prepend with <h3 id="comments">
      $container_element[0] . // wrap with <ul class="commentlist">
        implode("\n", (array) $comment_element) . // single comment <li id="comment-1234"></li>
      $container_element[2] . // </ul>
    $wrapper_element[2]; // </div>
  }
}

/**
 * Implementation of request filter.
 * Make it appear as though we are on the single page of a post.
 */
function ajax_comments_request($query_vars) {
  global $ajax_commments_post_ID;
  return array('p' => $ajax_commments_post_ID);
}

/**
 * Parse a single tag from SGML markup.
 *
 * @param String $haystack
 *   SGML markup. (e.g. HTML, XML, XHTML, etc.)
 * @param Array $needle
 *   An attribute name and value. (e.g. array('id' => 'comment-1234'))
 * @return Array
 *   0 => Opening tag
 *   1 => Atomic value
 *   2 => Closing tag
 */
function ajax_comments_parse_tag($haystack, $needle) {
  if (preg_match('`(<\s*([^>]+)\s[^>]*'. array_shift(array_keys($needle)) .'\s*=\s*[\'"]?'. array_shift($needle) .'[\'"]?[^>]*>)(.+?)(<\s*/\s*\2\s*>)`is', $haystack, $element)) {
    return array($element[1], $element[3], $element[4]); // return opening tag, atomic value, and closing tag
  }
  return FALSE; // not found
}

/**
 * Convert a PHP variable into its JSON equivalent.
 *
 * @param Mixed $variable
 *   A PHP variable.
 * @return String
 *   The JSON equivalent.
 */
function ajax_comments_js_escape($variable) {
  switch (gettype($variable)) {
    case 'boolean':
      return $variable ? 'true' : 'false'; // Lowercase necessary!
    case 'integer':
    case 'double':
      return $variable;
    case 'resource':
    case 'string':
      return '"'. str_replace(array("\r", "\n", "<", ">", "&"),
                              array('\r', '\n', '\x3c', '\x3e', '\x26'),
                              addslashes($variable)) .'"';
    case 'array':
      // Arrays in JSON can't be associative. If the array is empty or if it
      // has sequential whole number keys starting with 0, it's not associative
      // so we can go ahead and convert it as an array.
      if (empty ($variable) || array_keys($variable) === range(0, sizeof($variable) - 1)) {
        $output = array();
        foreach ($variable as $v) {
          $output[] = drupal_to_js($v);
        }
        return '['. implode(', ', $output) .']';
      }
      // Otherwise, fall through to convert the array as an object.
    case 'object':
      $output = array();
      foreach ($variable as $k => $v) {
        $output[] = drupal_to_js(strval($k)) .':'. drupal_to_js($v);
      }
      return '{'. implode(', ', $output) .'}';
    default:
      return 'null';
  }
}

/**
 * Parse atomic values from SGML markup. (e.g. InnerText)
 *
 * @param String $markup
 *   SGML markup. (e.g. HTML, XML, XHTML, etc.)
 * @return String
 *   Atomic values concatenated in a single string.
 */
function ajax_comments_innertext($markup) {
  $text = preg_replace('`<\s*(head|title|style|script)(\s[^>]*)?>.*?<\s*/\s*\1\s*>`is', '', $markup); // strip head, title, style, and script tags
  $text = preg_replace('`\s+`', ' ', $text); // cleanup whitespace
  $text = preg_replace('`<br\s*/>`is', "\n", $text); // convert <BR /> to new line
  $text = preg_replace('`<[^>]*>`is', '', $text); // strip tags
  $text = trim(preg_replace('/ +/', ' ', $text)); // cleanup remaining space(s)
  return $text;
}