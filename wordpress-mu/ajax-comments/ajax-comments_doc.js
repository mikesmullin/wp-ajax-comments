var ajax_comment_loading = false;
var ajax_comments_odd = true;
var ajax_comments_path = '';
var ajax_comments_msgc = '';
var ajax_comments_form = 'commentform';
var ajax_comments_list = 'commentlist';
var ajax_comments_here = 'commentform';
var ajax_comments_hide = new Array();

/**
  * ajax_comments_find_element(string identifier, string tag)
  *
  * @identifier ...... id or classname of element
  * @tag ............. tag to search for class
  *
  * Looks for a DOM-Element either having the class or the id specified in
  * identifier. If a tag is different to false the function will search for
  * the first element of that tag having the identifier in it's class name.
  *
  * returns: 
  *     + DOM-Element
  *     - null
 */
function ajax_comments_find_element(identifier, tag) {
    var e = $('' + identifier + '');

    if(e == null && tag != false) {
        var a = document.getElementsByTagName(tag);
        for(var i=0; i<a.length; i++) {
            if(a[i].className.toLowerCase().indexOf(ajax_comments_list) != -1) {
                return a[i];
            }
        }
    }
    
    return e;
}

/**
  * ajax_comments_find_list()
  *
  * tries to find an ordered list with classname or id matching the parameter 
  * given in the variable ajax_comments_list. If it can't find it the element
  * will be created before the element given in the variable ajax_comments_here.
  *
  * returns: DOM-Element <ol>
 */
function ajax_comments_find_list() {
    var ol = ajax_comments_find_element(ajax_comments_list, 'ol');

    if (ol == null) {
        var ct = ajax_comments_find_element(ajax_comments_here, false);
        
        if (ct != null) {
            // commentslist doesn't exist (no posts yet)
            // so create it above the commentform and return it
            ol = new Insertion.Before(ct, '<ol id="' + ajax_comments_list + '" class="' + ajax_comments_list + '"></ol>'); // create commentlist
            return $(ajax_comments_list);
        } else {
            return null;
        }
    } 
    
    return ol;
}

/**
  * ajax_comments_loading(boolean on)
  *
  * @on .............. activate / deactivate loading status
  *
  * disables form controls and displays the loading-box + icon
 */
function ajax_comments_loading(on) { 
    var f = ajax_comments_find_element(ajax_comments_form, 'form');
    if(on) {
        ajax_comment_loading = true;
        f.submit.disabled = true;
        new Insertion.Before(f, '<div id="ajax_comments_loading" style="display:none;">Submitting Comment...</div>'); // create loading

        var l = $('ajax_comments_loading');
        new Effect.Appear(l, {
            beforeStart: function() { 
                with(l.style) {
                    display = 'block';
                    margin = '0 auto';
                    width = '100px';
                    font = 'normal 12px Arial';
                    background = 'url(' + ajax_comments_path + 'ajax-comments/loading.gif) no-repeat 0 50%';
                    padding = '0 0 0 23px';
                }
            }
        });
    } else {
        Element.remove('ajax_comments_loading');
        f.submit.disabled = false;
        ajax_comment_loading = false;
    }
}

/**
  * ajax_comments_message(string message, bool succeeded)
  *
  * @message ........... the message to display
  * @succeeded ......... good or bad news?
  *
  * Displays a message as <span> inside a <div>. If <div> doesn't exist, it 
  * will be created right before ajax_comments_here. If the message is an
  * error the <div> will get the class 'error' attached
 */
function ajax_comments_message(message, succeeded) {
    if (!ajax_comments_msgc) {
        ajax_comments_msgc = $('ajax-comments-message');
  
        if (ajax_comments_msgc == null) {
            var ol = ajax_comments_find_list();
            new Insertion.After(ol, '<div id="ajax-comments-message"></div>');
            ajax_comments_msgc = $('ajax-comments-message');
        }
    }
  
    if (ajax_comments_msgc.empty()) {
        new Insertion.Bottom(ajax_comments_msgc, '<div>' + message + '</div>');
    }
    else {
        ajax_comments_msgc.firstChild.replace('<div>' + message + '</div>');
    }

    if (!succeeded) {
        ajax_comments_msgc.addClassName('error');
    } else {
        ajax_comments_msgc.removeClassName('error');
    }
}

/**
  * ajax_comments_submit()
  *
  * Handles the actual AJAX request
 */
function ajax_comments_submit() {
    if(ajax_comment_loading) return false;

    ajax_comments_loading(true);
    var ol = ajax_comments_find_list();
    var f  = ajax_comments_find_element(ajax_comments_form, 'form');
    
    new Ajax.Request(f.action, {
        method: 'post',
        asynchronous: true,
        postBody: Form.serialize(f),
        onLoading: function(request) {
            request['timeout_ID'] = window.setTimeout(function() {
                switch (request.readyState) {
                    case 1: case 2: case 3:
                        request.abort();
                        ajax_comments_message('Timeout\nThe server is taking a long time to respond. Try again in a few minutes.', false);
                        break;
                }
            }, 25000);
        },
        
        onFailure: function(request) {
            var r  = request.responseText;
            msg = r.substring(r.indexOf('</h1>') + 5, r.indexOf('</body>'));
            ajax_comments_message(msg, false);
        },
        
        onComplete: function(request) { 
            ajax_comments_loading(false);
            window.clearTimeout(request['timeout_ID']);
            if(request.status!=200) return;
      
            f.comment.value=''; // Reset comment

            if (ajax_comments_hide_on_success) {
                f.remove(); // remove form

                // remove theme-specific elements
                while (ajax_comments_hide.length > 0) {
                    Element.remove(ajax_comments_hide.pop()); 
                }
            }
      
            new Insertion.Bottom(ol, request.responseText);
            var li = ol.lastChild, className = li.className;
            li.hide();
            li.addClassName('ajax');
            if (ajax_comments_odd) {
                li.addClassName('alt');
            } else {
                li.removeClassName('alt');
            }
            ajax_comments_odd = !ajax_comments_odd;
            ajax_comments_message('Your comment has been saved.', true);

            new Effect.Appear(li, {
                duration:1.5,
                afterFinish: function() { 
                    new Effect.Highlight(ajax_comments_msgc, {
                      duration:3,
                        startcolor:'#99ff00'
                    });
                }
            });
        }
    });
    
    return false;
}

/**
  * initialize on load
 */
ac_oldLoad = window.onload;
window.onload = function () { 
    ac_oldLoad;
    f = ajax_comments_find_element(ajax_comments_form, 'form');
    if (f) {
        f.onsubmit = ajax_comments_submit;
        new Insertion.Bottom(f, '<input id="ajax-comments-submit" name="ajax-comments-submit" type="hidden" value="1" />'); // toggle ajax-catch
    }
};