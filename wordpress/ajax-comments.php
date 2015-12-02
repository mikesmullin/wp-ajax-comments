<?php

/**
 * @file
 * WordPress Plugin
 *
 * @cond
 * Plugin Name: AJAX Comments
 * Version: 2.09
 * Plugin URI: http://wordpress.smullindesign.com/plugins/ajax-comments
 * Description: Post comments quickly without leaving or refreshing the page.
 * Author: Smullin Design and Development, LLC
 * Author URI: http://www.smullindesign.com
 * @endcond
 */

define('AJAX_COMMENTS_PATH', dirname(__FILE__));
define('AJAX_COMMENTS_URL', get_settings('siteurl') .'/'. str_replace(str_replace('\\', '/', ABSPATH), '', str_replace('\\', '/', dirname(__FILE__))));

// Register hooks with WordPress core
add_action('wp', 'ajax_comments_wp'); // Executes after the query has been parsed and post(s) loaded, but before any template execution, inside the main WordPress function wp. Useful if you need to have access to post data but can't use templates for output. Action function argument: an array with a reference to the global $wp object.
add_action('wp_head', 'ajax_comments_wp_head'); // Runs when the template calls the wp_head function. This hook is generally placed near the top of a page template between <head> and </head>. This hook does not take any parameters.
add_filter('comments_template', 'ajax_comments_comments_template');
add_action('admin_menu', 'ajax_comments_admin_menu'); // Runs after the basic admin panel menu structure is in place.
if (function_exists('ajax_comments_wp_redirect')) {
  add_filter('wp_redirect', 'ajax_comments_wp_redirect'); // Applied to a redirect URL by the default wp_redirect function. Filter function arguments: URL, HTTP status code.
}

/**
 * Implementation of wp action.
 * Include AJAX Comments JavaScript file everywhere you would normally see comments.
 */
function ajax_comments_wp() {
  global $withcomments;

  if (function_exists('wp_enqueue_script')) { // 2.1 - when wp_enqueue_script() was implemented
    if ( ! (is_single() || is_page() || $withcomments) )
      return;

    ajax_comments_js(); // include .js
  }
}

/**
 * Implementation of admin_head action.
 * Include AJAX Comments JavaScript file in <HEAD>
 * only on pages where you would normally see comments.
 */
function ajax_comments_wp_head() {
  global $withcomments;

  if (!(is_single() || is_page() || $withcomments))
    return;

  ajax_comments_js();
}

/**
 * Include AJAX Comments JavaScript file.
 */
function ajax_comments_js() {
  global $wp_version, $withcomments;
  $version = (int) sprintf('%0-5s', str_replace('.', '', $wp_version)); // format as integer for less-than/greater-than comparison

  // only include once per page
  static $included;
  if ($included) {
    return;
  } else {
    $included = TRUE;
  }

  if ($version >= 22000) { // WP 2.2 - when jQuery was implemented
    return wp_enqueue_script('ajax_comments', AJAX_COMMENTS_URL .'/ajax-comments.js.php', array('jquery', 'jquery-form'));
  } else {
    // use the jQuery packaged with AJAX Comments
    print '<script type="text/javascript" src="'. AJAX_COMMENTS_URL .'/jquery/jquery.js"></script>'."\n";
    print '<script type="text/javascript" src="'. AJAX_COMMENTS_URL .'/jquery/jquery.form.js"></script>'."\n";
  }

  if ($version >= 21000) { // 2.1 - when wp_enqueue_script() was implemented
    return wp_enqueue_script('ajax_comments', AJAX_COMMENTS_URL .'/ajax-comments.js.php');
  } else {
    print '<script type="text/javascript" src="'. AJAX_COMMENTS_URL .'/ajax-comments.js.php"></script>'."\n";
  }
}

/**
 * Implemenation of comments_template filter.
 * Wrap comments.php template in a div with a unique id for easy manipulation.
 */
function ajax_comments_comments_template($include) {
  return AJAX_COMMENTS_PATH .'/ajax-comments-template.php';
}

/**
 * Implementation of admin_menu action.
 */
function ajax_comments_admin_menu() {
  $hook = add_submenu_page('options-general.php', __('AJAX Comments Settings'), __('AJAX Comments'), 'manage_options', 'ajax_comments', 'ajax_comments_settings');
}

/**
 * Submenu Page callback for Options > AJAX Comments.
 * Display AJAX Comments settings for custom configuration.
 */
function ajax_comments_settings() {
  global $plugin_page;

  // Read in existing option values from database
  $options = get_option('ajax_comments_options');

  // See if the user has posted some information
  if (isset($_POST['submit'])) {
    $options['performance'] = $_POST['performance'];

    // Save the posted value in the database
    update_option('ajax_comments_options', $options);

    // Notify user their settings were saved
    $output .= '<div class="updated"><p><strong>'. __('Settings saved.') .'</strong></p></div>';
  }

  $output .= '<div class="wrap">'."\n".
    '  <h2>'. __('AJAX Comments Settings') .'</h2>'."\n".
    '  <form method="post" action="options-general.php?page='. $plugin_page .'">'."\n".
    '    <h3>'. __('Performance options') .':</h3>'."\n".
    '    <p>'. __('There are a couple ways that AJAX Comments can work depending on the type of theme you have.') .'</p>'."\n".
    '    <p><label>'."\n".
    '      <input name="performance" type="radio" value="flexible"'. (!isset($options['performance']) || $options['performance'] == 'flexible'? ' checked="checked"' : '') .' />'."\n".
    '      <strong>'. __('Flexible approach') .'</strong>. '. __('replace entire comments.php template markup with each new comment.') .'</label><br />'."\n".
    '      <ul>'."\n".
    '        <li>'. __('compatible. works with 99.9% of all themes.') .'</li>'."\n".
    '        <li>'. __('flexible. supports unique template customizations.') .'</li>'."\n".
    '        <li>'. __('more bandwidth. more to download per-user, and connections to the server remain open longer depending on the post.') .'</li>'."\n".
    '        <li>'. __('slower. may time out with too many comments on some servers.') .'</li>'."\n".
    '      </ul>'."\n".
    '    </p>'."\n".
    '    <p><label>'."\n".
    '      <input name="performance" type="radio" value="conventional"'. ($options['performance'] == 'conventional'? ' checked="checked"' : '') .' />'."\n".
    '      <strong>'. __('Conventional approach') .'</strong>. '. __('append new comments only.') .'</label><br />'."\n".
    '      <ul>'."\n".
    '        <li>'. __('conventional. your theme must follow a few basic conventions originally established by Michael Kubrick in the Default blue WordPress theme') .':<br /><br />'."\n".
    '          <ol>'."\n".
    '            <li>'. __('Comment counter has') .' <code style="background:#eee. border-bottom:1px dotted #999">id="comments"</code>.</li>'."\n".
    '            <li>'. __('OL or UL elements have') .' <code style="background:#eee. border-bottom:1px dotted #999">class="commentlist"</code>.</li>'."\n".
    '            <li>'. __('LI elements have') .' <code style="background:#eee. border-bottom:1px dotted #999">id="comment-&lt.comment_id&gt."</code>.</li>'."\n".
    '          </ol>'."\n".
    '        </li>'."\n".
    '        <li>'. __('precise. only the comment counter will be updated.') .'</li>'."\n".
    '        <li>'. __('less bandwidth. minimal data transfer, and connections remain brief regardless of the post.') .'</li>'."\n".
    '      </ul>'."\n".
    '      </span>'."\n".
    '    </p>'."\n\n".
    '    <p class="submit">'."\n".
    '      <input type="submit" name="submit" value="'. __('Save Changes') .'" />'."\n".
    '    </p>'."\n".
    '  </form>'."\n".
    '</div>'."\n";

  print $output;
}