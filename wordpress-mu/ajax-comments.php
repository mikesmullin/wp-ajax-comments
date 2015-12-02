<?php
/*
Plugin Name: AJAX Comments WPMUified
Version: 0.2
Plugin URI: http://blogs.kno.at/doc/ajax-comments-wpmuified/
Description: Started as adoption and ended up as a pretty much total rewrite of Mike Smullin's original AJAX Comments plugin
Author: Christian Knoflach (based on work of Mike Smullin)
Author URI: http://www.kno.at/
*/

/**
 * The main script. Just hooks in and reacts to AJAX requests and does some
 * more minor magic tricks.
 *
 * @since     1.3
 * @global    booelan $ajax_comments_error_occured
 * @global    integer $ajax_comments_count
 */
 
define('PLUGIN_AJAXCOMMENTS_FILE', 'ajax-comments.php');
define('PLUGIN_AJAXCOMMENTS_PATH', substr(dirname(__FILE__), strlen(getcwd())). '/');
define('PLUGIN_AJAXCOMMENTS_ROOT', get_option('home'));

require_once('ajax-comments/functions.php');

if(isset($_POST['ajax-comments-submit'])) {  // react to AJAX-request
    add_action('comment_post',              'ajax_comments_send');
} else {
    // The following block of code deals with the problem of the actual
    // displayed number of comments conflicting with the stored value.
    // it's being increased by 1 every time a comment is being displayed
    // utilizing the 'comment_text' filter as action-hook and finally
    // being output as javascript-variable inside a <script>-block using
    // the hook 'wp_footer'
    
    // start very clever comment count block
    global $ajax_comments_count;
    $ajax_comments_count = 0;
    add_action('wp_footer',    'ajax_comments_getcount');
    add_action('comment_text', 'ajax_comments_countdisplay');
    add_action('wp_head',      'ajax_comments_js'); 
}
?>