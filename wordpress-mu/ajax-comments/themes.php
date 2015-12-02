<?php
/**
 *  themes.php
*/

/**
 *       C O N F I G U T A T I O N   O P T I O N S  --- 
*/


$hideOnSuccess = false; 
               // set to true if you want to 
               // hide the form and other 
               // elements after a comment 
               // has successfully been saved

$ajax_comments_themes['hardcode'] = '';  
                                  // if auto detection doesn't work 
                                  // but you are using one of the themes
                                  // listed below you can specify it here.
                                  // e.g. to hardcode hemingway-reloaded-10
                                  // you would change this line to:
                                  // ... = 'hemingway-reloaded-10';

/**
 *  theme parameters
 *
 *  SUPPORTED BY CREATION OF THIS FILE:
 *
 *  default ........................ shipped with every wp-installation
 *  hemingway-reloaded-10 .......... http://themes.wordpress.net/columns/1-column/230/hemingway-reloaded-10/
 *
 *  THEMES THAT NEED comments.php-template MODIFICATION 
 *  AND ARE SUPPORTED BY CREATION OF THIS FILE:
 *  (Location of adopted comments.php + style.css in second line)
 *
 *  upstart-blogger-modio-01 ....... http://themes.wordpress.net/columns/2-columns/3187/upstart-blogger-modio-01/
 *                                   http://blogs.kno.at/doc/ajax-comments-wpmuified/templates/
*/

$ajax_comments_themes['default'] = array(
    'commentform',                          // [0] = id or classname form
    'commentlist',                          // [1] = id or classname comment-<ol>
    'commentform',                          // [2] = id or classname element comments are inserted before
    ''                                      // [3] = comma-separated list of element-id's to hide
);

$ajax_comments_themes['hemingway-reloaded-10'] = array(
    'commentform',
    'comments',   
    'comment-form',
    'comment-form'
);
