<?php

/**
 * @file
 * JavaScript
 */

require('../../../wp-config.php'); // invoke WordPress bootstrap

header('Content-Type: text/javascript'); ?>
var ajax_comments = {
  locked: [],

  onsubmit: function() {
    var form = this;
    if (ajax_comments.locked[form]) { return false; } // one at a time
    else { ajax_comments.locked[form] = true; } // lock this form

    jQuery.ajax({
      type: 'POST',
      url: '<?php print AJAX_COMMENTS_URL ?>/ajax-comments-post.php',
      data: jQuery(this).formSerialize(),
      dataType: 'json',
      cache: false,
      timeout: 60000,

      beforeSend: function(XMLHttpRequest) {
        jQuery('.ajax_comments_error', form).remove(); // remove any previous errors
        jQuery('input[@type=submit]', form) // select form submit button
          .attr('disabled', 'disabled') // disable it
          .hide() // hide it
          .after('<div class="ajax_comments_spinner" title="<?php print htmlspecialchars(__('Submitting your comment...')) ?>" style="background:url(<?php print AJAX_COMMENTS_URL ?>/ajax-comments-spinner.gif);width:220px;height:19px;text-indent:-9999px;font-size:0;line-height:0;"><?php print htmlspecialchars(__('Submitting...')) ?></div>'); // show AJAX spinner
      },

      success: function(data, textStatus) {
        if ( // validate server response
          typeof(data.comment_type) == 'undefined' ||
          typeof(data.comment_ID) == 'undefined' ||
          typeof(data.comments_template) == 'undefined' ||
          !data.comments_template
        ) { // if response is not as expected...
          this.error({responseText:''}, '', ''); // display unknown error
          return; // abort
        }

        var wrapper = jQuery(form).parents('div#ajax_comments_wrapper'),
            commentlist = jQuery('.commentlist', wrapper),
            new_wrapper = jQuery(data.comments_template),
            new_comment = jQuery('#comment-'+ data.comment_ID +', .commentlist *:last', new_wrapper).eq(0).hide();

        if (data.comment_type == 'conventional' && commentlist.length > 0) {
          commentlist.append(new_comment); // append new comment to existing wrapper
          jQuery('#comments', wrapper).after(jQuery('#comments', new_wrapper)).remove(); // replace comment count element
        } else { // flexible
          var new_comment_form = jQuery('textarea[@name=comment]', new_wrapper).parents('form');
          if (new_comment_form.length) {
            new_comment_form.after(form).remove(); // replace comment form in new wrapper
          } else {
            new_wrapper.append(form); // append comment form to new wrapper
          }
          wrapper.after(new_wrapper).remove(); // replace old wrapper with new
        }
        new_comment.fadeIn('slow'); // show new comment using nice effect

        // WP Ajax Edit Comments compatibility
        if (typeof(AjaxEditComments) != 'undefined') {
          AjaxEditComments.init();
        }
        
        jQuery('textarea#comment', form).val(''); // clear comment

        this.cleanup();
      },

      error: function(XMLHttpRequest, textStatus, errorThrown) {
        var error = '';
        if (typeof(XMLHttpRequest.responseText) == 'string' && XMLHttpRequest.responseText != '') {
          error = XMLHttpRequest.responseText;
        } else if (textStatus == 'timeout') {
          error = '<?php print htmlspecialchars(__('The server is taking too long to respond. Please try again later.')) ?>';
        } else {
          error = '<?php print htmlspecialchars(__('Unknown error while submitting your comment. Try again?')) ?>';
        }
        jQuery(form).prepend('<div class="ajax_comments_error" style="background:#d00d00;margin:1em 0;padding:.8em;color:#fff;line-height:1.3em;">'+ error +'</div>'); // display error above comment form
        this.cleanup();
      },

      cleanup: function() {
        jQuery('.ajax_comments_spinner', form).remove(); // remove the AJAX spinner
        jQuery('input[@type=submit]', form) // select form submit button
          .removeAttr('disabled') // enable it
          .show(); // show it
        ajax_comments.locked[form] = false; // unlock this form
      }
    });

    return false; // abort non-AJAX form submission
  }
}

// execute on document load
jQuery(function() {
  // bind to the comment form
  jQuery('#commentform').bind('submit', ajax_comments.onsubmit);
});