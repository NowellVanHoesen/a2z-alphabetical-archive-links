=== A-2-Z Alphabetical Archive Links ===
Contributors: nvwd
Donate link: http://nvwebdev.com
Tags: post title, custom post type title, cpt title, title, alphabetical, alphabatized
Requires at least: 4.6.0
Tested up to: 5.0.3
Requires PHP: 5.6
Stable tag: 2.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create and display a list of first characters for post/cpt titles which link to an archive of the posts/cpts that begin with that character.

== Description ==

This widget will get a list of post/cpt title first character. The list is then displayed as an unordered list of links. The links take the user to an archive page for the specific post type and display the posts where its title begins with the character.

If the title begins with the following words, the first character of the second word will be used:

* A
* An
* And
* The

*Examples*

* 'A Cup of Joe' will be listed under 'C'
* 'The Pony' will be listed under 'P'
* 'Android Green is Nice' will be listed under 'A'
* 'The 10 Strangest Foods' will be places under '#' (number sign)


== Installation ==

1. Upload the entire 'a2z-alphabetical-archive-links' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to the settings page to activate which post type(s) to have
1. Add the 'A2Z Alphabetical Archive Links' widget to a widget area

== Frequently Asked Questions ==

None at this time

== Screenshots ==

1. Widget config
1. Links without count
1. Links with count (top), hover example ( bottom )

== Changelog ==

= 2.0.2 =

cleaning up version numbers

= 2.0.1 =

* fixed bug with earlier versions of php and version checking

= 2.0.0 =

Complete rewrite. In an effort to make the code scale better.
* Removed slow query
* Added minimum WordPress version check
* Added minimum php version check
* Added grouping for titles that have a number as their first character
* Added custom rewrites the handle better archive links

= 1.0.2 =

Changed link builder again to account for modified front page settings

= 1.0.1 =

* Changed widget option for post type to include only publically queriable post types ( Pages not an option anymore )
* Fixed links generated when using the post type of Post

= 1.0 =
Initial release

== Upgrade Notice ==

= 2.0.0 =

Complete rewrite to scale better and address performance issues

= 1.0.2 =

Changed link builder again to account for modified front page settings

= 1.0.1 =

Fixed links generated when using the post type of Post

= 1.0 =
Released
