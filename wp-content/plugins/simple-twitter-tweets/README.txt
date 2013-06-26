=== Plugin Name ===
Contributors: Planet Interactive
Donate: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A9437VN7R36VN
Tags: Twitter, Stream, Tweets, Twitter OAuth, social
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple Twitter Tweets - Display your Tweets on your Wordpress site using the new Twitter OAuth API v1.1. (even when you can't connect) Because it's backed up!

== Description ==

A Simple Twitter Tweets display widget, using Twitter OAth and API v1.1 and backup up so it always displays your Tweets.

**Why?**

Our clients, especially on shared hosting were having issues with the Twitter API not displaying their tweets, too many connections from the same source (host). We solved that issue, then lo and behold Twitter changed their API so displaying your own Tweets required OAuth authentication and finally we buckled and decided to roll our own so it would be simple for them, for us and for you.

Twitter changed their API again. Removing version 1.0 of the API altogether and by forcing version 1.1 of the API and use of the OAuth authentication requirement. We wrote this plugin so everyone could have it at a click of a button.

There are a few Twitter plugins out their, but we couldn't find one simple enough, or that worked (to be honest) and so the Simple Twitter Tweets plugin was born.

Twitter users who want to display their Tweets on their website (or within an application) have to create a Twitter Application to get access to the required "Keys" and "Tokens" Twitter provides for Authentication. The instructions for this are provided below so you can be up and running with Tweets on your site in less time than it takes to make a cup of Tea.

= Features =

* Simple setup
* Twitter API v1.1 compliant (OAuth Ready)
* No passwords required or used
* Works even when Twitters down, over capacity or not connecting
* Tweets stored in the database with each call, so if your call to the Twitter API fails for whatever reason there won't be a blank space or Oops message on your site, you'll just see the last set of Tweets the system stored - sweet huh.
* Tweeted when? - In Human Time using minutes, hours and days (i.e. 5 hours ago)
* Did we say it was simple and works...

== Installation ==

Installation is as simple as 1,2,3 or maybe 4 because of Twitter :)

1. Upload `simple-twitter-tweets` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the Widget to your page (e.g. Sidebar) and update the required details
4. Note: You will need to create a Twitter Application - See below. Oh, it's really easy.

= Creating a Twitter Application =

The Twitter Widget will never ask for your password, as it gets all your required data from the Open Authentication keys and secrets you will get by creating your application at Twitter. It also means that if you change your password, you won’t need to update any of the details of your Widget.

To find these details, go to https://dev.twitter.com/ and sign in.

Once you have logged in successfully, hover over your name in the top right corner, and click "My Applications," then "Create a New Application."

Enter a unique name (anything you want), a description (again this is just for you), and your site's URL. You can leave the Callback URL empty as it is not used for this implementation.

Yay, success - OK! You will be taken to a new screen, there's one more step then you can copy all the details into correct fields of the Widget and be on your way.

OK, click the "Create my Access Token" button. This is a shortcut to authenticate your own account with your application (so you never need use your password).

Good. Now click the Details Tab as all the information you need is presented hereso you can just copy the required information into the exact corresponding inputs fields of the Widget.

Full details and screenshots of this process can seen on the [Simple Twitter Tweets page](http://www.planet-interactive.co.uk/simple-twitter-tweets "Simple Twitter Tweets page by Planet Interactive")

= The Widget Options =

Fill in your details, copy and past the Twitter Application details (as described below).

* You can select the Title of the Widget as you like.
* Enter your Twitter username (without the @) just the name.
* How many Tweets to display
* The time in minutes between updates (per the API requirement) this should be 5 but if the API changes you can alter it here.
* Consumer Key: Under the *OAuth settings* heading
* Consumer Secret: Under the *OAuth settings* heading
* Access Token: Under the *Your access token* heading
* Access Token Secret: Under the *Your access token* heading
* Choose if you want the @replies included or not
* Click Save

Enjoy!

== Frequently Asked Questions ==

= Can I change the look and feel of the Tweets =

Of course you can. It's really simple too.

The Tweets are in a widget as with all widgets, and are a simple unordered list.

* To make styling easier the &lt;ul&gt; has a class of Tweets - &lt;ul class="tweets"&gt;
* Each Tweet is a list item &lt;li&gt;
* Each Time reference is an emphasised link &lt;em&gt;&lt;a&gt;
* Done.

= Where can I get help =

If you're really stuck check out the [support portal](http://planetinteractive.freshdesk.com/support/login "Support by Planet Interactive")

= More FAQs =
As far as we know it just works! Phew, but if you have an issue or you want to propose some functionality then submit you ideas at the [support portal](http://planetinteractive.freshdesk.com/support/login "Support by Planet Interactive") and we'll update these FAQs and get onto it when we can.

== Screenshots ==

1. Go to https://dev.twitter.com and Sign In
2. Top right, hover your name/icon, go to My Application
3. Create a new Application
4. Fill a name for your App, a description (this is for you) and your website address (URL)
5. Click "Create my access token"
6. If you've already installed in the "Simple Twitter Tweets" plugin go to Appearance->Widgets (otherwise install it first then go here)
7. Drag the "Simple Twitter Tweets" widget your widget area of choice
8. Fill in the widget options and correlating Twitter Application OAuth requirements, just copy and paste
9. Style how you like, "Your Tweets your way"

See here [Simple Twitter Tweets](http://www.planet-interactive.co.uk/simple-twitter-tweets "Simple Twitter Tweets by Planet Interactive")

== Changelog ==

= 1.0 =
* Initial release
= 1.1 =
* Readme, descriptions and screenshot updates

== Upgrade Notice ==

= 1.1 =
Just some tweaks.