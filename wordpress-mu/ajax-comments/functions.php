<?php
/**
 * The functions in use by the plugin
 *
 * @since     1.0
 * @global    booelan $ajax_comments_error_occured
 * @global    integer $ajax_comments_count
 */

/**
 * ajax_comments_countdisplay() - keeps track of how many comments wp prints
 * 
 * This function is hooked in every time wp displays a comment. This way also
 * unapproved comments are taken into account if, for example, an admin views
 * the page. Otherwise if he'd submit another comment the even-odd rhythm
 * would possibly break.
 *
 * The comment is passed through because intentionally this should be used
 * as a filter. I am so guilty.
 *
 * @package WordPress MU
 * @since version 1.3
 *          
 * @param   string  $comment    the comment's text
 * @return  string  $comment    the comment's text, unmodified
 */
function ajax_comments_countdisplay($comment = '') {
    global $ajax_comments_count;
    $ajax_comments_count++;
    return $comment;
}

/**
 * ajax_comments_getcount() - puts out a script declaring the variable
 * 
 * This function is hooked in before </body>.
 *
 * @package WordPress MU
 * @since version 1.3
 */
function ajax_comments_getcount() {
    global $ajax_comments_count;
    echo '<script type="text/javascript">ajax_comments_odd  = '. ($ajax_comments_count % 2 == 0 ? 'true' : 'false') .';</script>' . "\n";
}

/**
 * ajax_comments_return() - returns ajax-specific stuff instead of a whole page
 * 
 * This function is hooked in when wordpress saved a comment ('comment_post')
 *
 * Note that there's no detection whether the comment is an even or odd one,
 * due this is handled by the javascript. That is because in this case the
 * server knows less about how many comments there are than the client.
 *
 * @package WordPress MU
 * @since version 1.3
 *          
 * @param   integer $passed     hopefully an integer (id of the inserted comment)
 * @return  string  $matches[1] the new comment as <li>
 * @return  string  $passed     whatever error message is available
 */
function ajax_comments_send () {
    $passed  = func_get_arg(0);
    $comment = @get_comment($passed);
    
    // if we get anything else than a valid comment id something was wrong
    if (!is_object($comment)) {
        header('HTTP/1.0 406 Not Acceptable');
        die('An error occured. Please make sure you filled in the form and try again.');
        die($passed);
    } else {
        // scrape templated comment HTML from /themes directory
        header('Content-type: text/html; charset=utf-8');        
        ob_start(); // start buffering output
        $comments = array($comment);            // make it look like there is one comment to be displayed
        global $comment;
        include(TEMPLATEPATH.'/comments.php');  // now ask comments.php from the themes directory to display it
        $commentout = ob_get_clean();           // grab buffered output
        
        preg_match('|(<li.*</li>)|ims', $commentout, $matches); // Regular Expression cuts out the LI element
            
        die($matches[1]);
    }    
}

/**
 * ajax_comments_js() - handles most javascript-specific issues
 * 
 * This function is hooked in to 'wp_head' and outputs most javascript-parts.
 * It handles theme-specific settings and the general script locations.
 *
 * There's one additional function that outputs js: ajax_comments_get_count()
 *
 * @package WordPress MU
 * @since version 1.3
 */
function ajax_comments_js() {
    require_once('themes.php');
    
    //  this block of code tries to find the correct theme-settings.
    //  it works like this:
    //
    //  1.st) is the 'hardcode' - variable set in themes.php, then
    //        use this and don't do anything else
    //
    //  2.nd) are there theme-parameters for a theme that matches
    //        the current template path, then try using those
    //
    //  3.rd) if neither succeeds, use settings for the default theme

    if (!$ajax_comments_themes['hardcode']) {
        $curTempDir = (substr(TEMPLATEPATH, strlen(dirname(TEMPLATEPATH))));
        $allThemes  = array_keys($ajax_comments_themes);
    
        // try to auto-detect theme
        for ($i=0; !is_array($theme), $i<count($allThemes); $i++) {
            if (strpos($curTempDir, $allThemes[$i])) {
                $theme = $ajax_comments_themes[$allThemes[$i]];
            }
        }
        
        // fall back to default settings
        if (!is_array($theme)) $theme = $ajax_comments_themes['default'];
    } else {
        // use hardcoded settings
        $theme = $ajax_comments_themes[$ajax_comments_themes['hardcode']];
    }
    
    // time to check if there's something else to hide than the comment form
    // and format the given id's in a way that is usable by javascript's
    // new Array() function
    $hideMore = $theme[3];
    
    // more than one id requires us to split the string, get rid of
    // any interfering whitespace and then join again enclosing every
    // single id with '
    if (strpos($hideMore, ',') !== false) { // if there's a list
        $all = explode(',', $hideMore);
        for ($i=0; $i<count($all); $i++) {
            $all[$i] = trim($all[$i]);
        }
        $hideMore = join("','", $all);
    }
    
    // it there's exactly 1 id just enclose with '
    if (!empty($hideMore)) $hideMore = "'". trim($hideMore) . "'";
    
    echo '<script type="text/javascript" src="'. PLUGIN_AJAXCOMMENTS_ROOT.PLUGIN_AJAXCOMMENTS_PATH .'ajax-comments/scriptaculous/prototype.js"></script>' . "\n";
    echo '<script type="text/javascript" src="'. PLUGIN_AJAXCOMMENTS_ROOT.PLUGIN_AJAXCOMMENTS_PATH .'ajax-comments/scriptaculous/scriptaculous.js"></script>' . "\n";
    echo '<script type="text/javascript" src="'. PLUGIN_AJAXCOMMENTS_ROOT.PLUGIN_AJAXCOMMENTS_PATH .'ajax-comments/ajax-comments.js"></script>' . "\n";
    echo '<script type="text/javascript">' . "\n"
        .'  ajax_comments_path = "'. PLUGIN_AJAXCOMMENTS_ROOT.PLUGIN_AJAXCOMMENTS_PATH .'";' . "\n"
        ."  ajax_comments_form = '${theme[0]}';\n"
        ."  ajax_comments_list = '${theme[1]}';\n"
        ."  ajax_comments_here = '${theme[2]}';\n"
        ."  ajax_comments_hide = new Array($hideMore);\n"
        ."  ajax_comments_hide_on_success = ". ($hideOnSuccess ? 'true' : 'false') .";\n"
        ."</script>\n";
}