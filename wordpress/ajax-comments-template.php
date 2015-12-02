<?php

/**
 * @file
 * Comments.php Template Wrapper
 */

print '<div id="ajax_comments_wrapper">' . "\n";

$include = TEMPLATEPATH .'/comments.php';
if (file_exists($include)) {
  include($include);
} else {
  include(ABSPATH . 'wp-content/themes/default/comments.php');
}

print "\n" . '</div>' . "\n";