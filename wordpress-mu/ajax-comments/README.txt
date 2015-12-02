=== AJAX Comments WPMUified===
Contributors: Christian Knoflach
Tags: comments, ajax, wpmu
Requires at least: 2.0.3
Tested up to: 2.3
Stable tag: trunk

add ajax-comment functionality to wordpress mu (site-wide)

== Description ==
This is a rework of an original plugin called AJAX Comments by contributors DjZoNe, Mike Smullin.

[Support](http://blogs.kno.at/doc/2007/11/03/wordpress-mu-and-ajax-comments/#respond)

Major changes:

* developed as WordPress MU site-wide plugin (/mu-plugins)
* hooks into the wordpress-comment-processing workflow
* therefore now cannot interfere with any other plugins
* therefore also slimmer as ever before
* therefore also hooks in with any exisiting flood protection
* easy adopting for various themes (as long as the ol / li -standard is kept)
* choose if you want to hide the form after a comment has been saved
* magic fairytale theme auto-detection
* replaced alerts with sexy web2.0ish scriptaculoused div
* separated files bring enlightening overview
* code comes with geekish documentation for your adopting pleasure

Original Description:
More than that, it checks if all fields filled correctly, and also makes sure to avoid comment duplication, and has flood protection capabilities as well.  
Probably one of the best ways you could spice up your WordPress Blog with AJAX; readers love it! Must see for yourself.
This plugin works well in all major Web browsers, and uses discrete AJAX.
That means if JavaScript disabled, it's using the original comment posting method.

Ajax Comments known to work well, with Authimage plugin, but I rather suggest Akismet, as it is free for personal use.

Features:

* comment form validation happens server-side without refreshing or leaving the page
* Script.aculo.us Fade In/Out Effects make readers happy
* works with AuthImage captcha word verification plug-in to prevent comment spam
* still works traditionally if browsers don't support JavaScript (or have it disabled)
* uses existing theme code to match styled comment threads when producing new comments
* 25-second server timeout ensures readers aren't left hanging
* works in current versions of Firefox, Internet Explorer, Opera, Netscape, and Safari.

Recommendations:

* perfectly compliments any well-styled comment form design-don't design without it
* best when moderation is off (seems more real-time) and AuthImage is installed (self-moderation is the best moderation)


== Frequently Asked Questions ==

= Why has the chicken crossed the road? =

== Known bugs ==

* **Comment-Headline** is not being updated with the correct comment count, due this would require quite a lot of coding for a very minor mathematical correctness
* May breaks other AJAX- or DHTML-Plugins if those are using the same JS-Library. This PlugIn is based on Prototype, so if you have other PlugIns activated using the same library you want to make sure the prototype-script is only loaded once! Sorry for the inconvinience, but it looks like there’s no better solution for this yet.

== Installation ==

1. Unzip/upload to /mu-plugins (WPMU) or /plugins (WP, or WPMU optional) directory.
2. Open /ajax-comments/functions.php to modify settings
3. If you’re running any other AJAX- or DHTML-Plugins based on Prototype please make sure the prototype-library is included only once, otherwise the plugins will interfere.