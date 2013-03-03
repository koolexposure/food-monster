=== Twitter Feed for WordPress ===
Contributors: alexmoss
Donate link:  http://3doordigital.com/go/twitter-paypal/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Tags: twitter, tweets, twitter feed, twitter updates, seo, plugin, widget, sidebar, page, post
Requires at least: 2.7
Tested up to: 3.5.1
Stable tag: 1.2.2

A simple Twitter feed that outputs your latest tweets in HTML into any post, page, template or sidebar widget. Customisable and easy to install!

== Description ==

The WordPress Twitter Feed Plugin lets you simply output any user's tweets into your WordPress page, template or sidebar! You can customise the username, number of tweets, and style of ouput.


* [Twitter Feed](http://3doordigital.com/wordpress/plugins/wp-twitter-feed/) WordPress Plugin homepage.
* More [WordPress Plugins](http://3doordigital.com/wordpress/plugins/).

== Installation ==

You can install the plugin using the following steps:

1. Download the plugin direct from the Plugin Page at WordPress.
2. Upload the whole plugin folder to your /wp-content/plugins/ folder.
3. Go to the Plugins page and activate the plugin
4. Insert the shortcode!

For full configuration and options please visit the [Twitter Feed WordPress Plugin](http://3doordigital.com/wordpress/plugins/wp-twitter-feed/) plugin page.


== Changelog ==

= 1.2.2 =

* Added decode option

= 1.2.1 =

* Remove "nofollow" from rel tag

= 1.2 =

* Added Web Intents
* Added Follow Button

= 1.1.2 =

* Output error message if Twitter's RSS service is down

= 1.1.1 =

* Bug fixes for management of alternative RSS feeds and outputting authour links
* Added options for timeline prefix and suffix for different languages

= 1.1 =

* Some code tidying
* Bug fixes for management of alternative RSS feeds and outputting authors
* Set caching only to Twitter feed RSS instead of all
* Updated old links

= 1.0.1 =

* Some people were reporting that their main twitter feed was not outputting any tweets. This is due to Twitter's RSS feed publishing only 'recent' tweets. Instead I have updated the plugin with a new option:
* other="yes" will enable the backup RSS feed from Twitter. This will output tweets but will not be able to output your profile image.

= 1.0 =

* New Features:
	* Limit of tweets increased from 15 to 60
	* Profile images/avatars now available
	* Mentions and public retweets now available

* Updates:
	* Removed need to obtain Twitter ID
	* Stopped using Magpie as a third party. This plugin now only uses SimplePie embedded within the WordPress core
	* Linklove now inserted with a line break!

= 0.3.1 =

* Updates:
	* Fixed some issues with linking to profiles based on feed modes
	* Added non-conditional timelines with option to choose PHP date format
	* Added a link to the plugin (which can be removed if you add linklove="no" into the shortcode).

= 0.3 =

* New features:
	* Replaced [LINK] anchor text with original URL. Anchor text can still be used.
	* Added search and public feeds
	* Added support for incorrect HTML characters that were output in languages other than English
	* Removed the DIV that surrounded the plugin, but can still be added it back in.

= 0.2 =

* New features:
	* Removed RSS caching by WordPress so that updates to twitter are immediate.
	* Added extra attributes userlinks, hashlinks, linktotweet