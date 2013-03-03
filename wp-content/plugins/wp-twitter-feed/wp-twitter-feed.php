<?php
/*
Plugin Name:  Twitter Feed for WordPress
Plugin URI:   http://3doordigital.com/wordpress/plugins/wp-twitter-feed/?utm_source=WordPress&utm_medium=Admin&utm_campaign=Twitter%2BFeed
Description:  A simple Twitter feed that outputs your latest tweets in HTML into any post, page, template or sidebar widget. Customisable and easy to install!
Version:      1.2.2
Author: Alex Moss
Author URI: http://alex-moss.co.uk/
License: GPL v3

Copyright (C) 2010-2010, Alex Moss - alex@3doordigital.com
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
Neither the name of Alex Moss or pleer nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

function Twitterfeedreader($atts) {
    extract(shortcode_atts(array(
		"username" => 'alexmoss',
		"mode" => 'feed',
		"tweetintent" => 'yes',
		"userintent" => 'yes',
		"twitterJS" => 'no',
		"other" => '',
		"decode" => '',
		"num" => '5',
		"img" => 'yes',
		"imgclass" => '',
		"auth" => 'no',
		"encoding" => '',
		"term" => 'twitter',
		"hashtag" => 'WordPress',
		"followlink" => 'yes',
		"followbutton" => 'yes',
		"scheme" => 'light',
		"followercount" => 'yes',
		"lang" => 'en',
		"searchlink" => 'yes',
		"anchor" => '',
		"userlinks" => 'yes',
		"hashlinks" => 'yes',
		"timeline" => 'yes',
		"smalltime" => 'yes',
		"smalltimeclass" => '',
		"conditional" => 'yes',
		"tprefix" => '(about',
		"tsecs" => 'seconds',
		"tmin" => 'minutes',
		"tmins" => 'minutes',
		"thour" => 'hour',
		"thours" => 'hours',
		"tday" => 'day',
		"tdays" => 'days',
		"tsuffix" => 'ago)',
		"phptime" => 'j F Y \a\t h:ia',
		"linktotweet" => 'yes',
		"divid" => '',
		"ulclass" => '',
		"liclass" => '',
		"linklove" => 'no',
    ), $atts));
	//MODES

	if ($mode == "fav") { $twitter_rss = "http://twitter.com/favorites/".$username.".atom?rpp=".$num; }
	if ($mode == "feed") {
		if ($other == "yes") {
			$twitter_rss = "http://api.twitter.com/1/statuses/user_timeline.rss?screen_name=".$username."&count=".$num;
			$img="no";
		} else {
			$twitter_rss = "http://search.twitter.com/search.rss?q=from%3A".$username."&rpp=".$num;
		}
	}
	if ($mode == "mentions") { $twitter_rss = "http://search.twitter.com/search.rss?q=%40".$username."&rpp=".$num; }
	if ($mode == "retweets") { $twitter_rss = "http://search.twitter.com/search.rss?q=RT%20%40".$username."&rpp=".$num; }
	if ($mode == "public") { $twitter_rss = "http://search.twitter.com/search.rss?q=".$username."&rpp=".$num; }
	if ($mode == "hashtag") { $twitter_rss = "http://search.twitter.com/search.rss?q=%23".$hashtag."&rpp=".$num; }
	if ($mode == "search") { $twitter_rss = "http://search.twitter.com/search.rss?q=".$term."&rpp=".$num; }

	if ($twitterJS == "yes") {
		function twitterintentjs() {
			?><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script><?php
		}
		add_action('wp_head', 'twitterintentjs');
	}



	//SETUP FEED
	include_once(ABSPATH . WPINC . '/feed.php');
	$rss = fetch_feed($twitter_rss);

	if ( is_wp_error( $rss ) ) {
		$wholetweet = 'The connection to twitter has returned an error. Please try again later.<br />';
	} else {

	$maxitems = $rss->get_item_quantity($num);
	$rss_items = $rss->get_items(0, $maxitems);
	ob_start();
	$now = time();
	$page = get_bloginfo('url');

	//START OUTPUT
	if ($divid != "") {
		$divstart = "<div id=\"".$divid."\">\n";
		$divend = "</div>";
	}

	if ($ulclass != "") {
		$ulstart = "<ul class=\"".$ulclass."\">";
	} else {
		$ulstart = "<ul>";
	}

	//POPULATE TWEET
	foreach ( $rss_items as $item ) {
		if ($mode == "fav") {
			$tweet = $item->get_description();
		} else {
			$tweet = $item->get_title();
		}
		if ($encoding == "yes") {$tweet = htmlentities($tweet);}
		if ($decode == "yes") {$tweet = htmlspecialchars_decode($tweet, ENT_QUOTES);}
		if ($page != "") {if (!strpos($tweet, $page) === false) {continue;}}
		$when = ($now - strtotime($item->get_date()));
		$posted = "";
		if ($timeline != "no") {
			$when = ($now - strtotime($item->get_date()));
			$posted = "";
			if ($conditional == "yes") {
				if ($when < 60) {
					$posted = $tprefix." ".$when." ".$tsecs." ".$tsuffix;
				}
				if (($posted == "") & ($when < 3600)) {
					$posted = $tprefix." ".(floor($when / 60))." ".$tmins." ".$tsuffix;
				}
				if (($posted == "") & ($when < 7200)) {
					$posted = $tprefix." 1 ".$thour." ".$tsuffix;
				}
				if (($posted == "") & ($when < 86400)) {
					$posted = $tprefix." ".(floor($when / 3600))." ".$thours." ".$tsuffix;
				}
				if (($posted == "") & ($when < 172800)) {
					$posted = $tprefix." 1 ".$tday." ".$tsuffix;
				}
				if ($posted == "") {
					$posted = $tprefix." ".(floor($when / 86400))." ".$tdays." ".$tsuffix;
				}
			} else {
				$date = date($phptime, strtotime($item->get_date()));
				$posted = $date;
			}
		$entry = $entry."\n<br />".$pubtext.$posted;
		}
			if ($anchor == "") {
				$tweet = preg_replace("/(http:\/\/)(.*?)\/([\w\.\/\&\=\?\-\,\:\;\#\_\~\%\+]*)/", "<a href=\"\\0\" rel=\"nofollow\">\\0</a>", $tweet);
			} else {
				$tweet = preg_replace("/(http:\/\/)(.*?)\/([\w\.\/\&\=\?\-\,\:\;\#\_\~\%\+]*)/", "<a href=\"\\0\" rel=\"nofollow\">".$anchor."</a>", $tweet);
			}
		 if ($mode != "fav") {
			//SETUP SPECIAL ATTRIBUTES
			$author_tag = $item->get_item_tags('','author');
			$author = $author_tag[0]['data'];
			$author = substr($author, 0, stripos($author, "@") );
			if ($other != "yes"){$tweet = "@".$author.": ".$tweet;}
			if ($img == "yes"){
				$avatar_tag = $item->get_item_tags('http://base.google.com/ns/1.0','image_link');
				$avatar = $avatar_tag[0]['data'];
				if ($imgclass == "") {
					$preimgclass = "style=\"";
					$imgclass = "float: left;";
				} else {
					$preimgclass = "class=\"";
				}
				$avatar = "<img src=\"".$avatar."\" height=\"48\" width=\"48\" alt=\"".$author."\" title=\"".$author."\" ".$preimgclass.$imgclass."\">";
				if ( $userlinks == "yes" ) {
				if ( $userintent == "yes" ) {
					$avatar = "<div style=\"float: left; margin: 0px 10px 10px 0px;\"><a href=\"https://twitter.com/intent/user?screen_name=".$author."\" rel=\"nofollow\">".$avatar."</a></div>";
				} else {
					$avatar = "<div style=\"float: left; margin: 0px 10px 10px 0px;\"><a href=\"http://twitter.com/".$author."\" rel=\"nofollow\">".$avatar."</a></div>";
				}
}
			}
		} else {
			$tweet = "@".$tweet;
		}
		if ($auth == "no") {
			if ($other != "yes"){
				$tweet = preg_replace("(@([a-zA-Z0-9\_]+))", "", $tweet, 1);
				$tweet =substr($tweet, 2);
			} else {
				$tweet = preg_replace("(([a-zA-Z0-9\_]+):)", "", $tweet, 1);
			}
 		} else {
 		if ($other == "yes"){$tweet = preg_replace("(([a-zA-Z0-9\_]+))", "<a href=\"http://twitter.com/\\1\" rel=\"nofollow\">\\1</a>", $tweet, 1);}
 		}
		if ( $userlinks == "yes" ) {
			if ( $userintent == "yes" ) {
				$tweet = preg_replace("(@([a-zA-Z0-9\_]+))", "<a href=\"https://twitter.com/intent/user?screen_name=\\1\" rel=\"nofollow\">\\0</a>", $tweet);
			} else {
				$tweet = preg_replace("(@([a-zA-Z0-9\_]+))", "<a href=\"http://twitter.com/\\1\" rel=\"nofollow\">\\0</a>", $tweet);
			}
		}
		if ( $hashlinks == "yes" ) {
			$tweet = preg_replace("(#([a-zA-Z0-9\_]+))", "<a href=\"http://twitter.com/search?q=%23\\1\" rel=\"nofollow\">\\0</a>", $tweet);
		}

		if ($tweetintent == "yes") {
			$tweetID = strstr($item->get_permalink(), "statuses/");
			$tweetID = substr($tweetID, 9);
			$tweet = $tweet."\n<a href=\"http://twitter.com/intent/retweet?related=".$username."&tweet_id=".$tweetID."\" rel=\"nofollow\"><img src=\"http://si0.twimg.com/images/dev/cms/intents/icons/retweet.png\" alt=\"ReTweet\"/></a>\n<a href=\"http://twitter.com/intent/tweet?related=".$username."&in_reply_to=".$tweetID."\" rel=\"nofollow\"><img src=\"http://si0.twimg.com/images/dev/cms/intents/icons/reply.png\" alt=\"Reply\"/></a>\n<a href=\"http://twitter.com/intent/favorite?related=".$username."&tweet_id=".$tweetID."\" rel=\"nofollow\"><img src=\"http://si0.twimg.com/images/dev/cms/intents/icons/favorite.png\" alt=\"Favorite\"/></a>";
		}

		if ($timeline == "yes") {
		if ($linktotweet == "yes") {
				if ($smalltime == "yes") {
					if ($smalltimeclass == "") {
						$presmalltimeclass = "style=\"";
						$smalltimeclass = "font-size: 85%;";
					} else {
						$presmalltimeclass = "class=\"";
					}
				$posted = "<br /><font ".$presmalltimeclass.$smalltimeclass."\">".$posted."</font>";
				$smalltimeclass='';
				}
				$tweet = $tweet."\n<a href=\"".$item->get_permalink()."\" rel=\"nofollow\">".$posted."</a>";
			} else {
				$tweet = $tweet."<br />(".$posted.")";
			}
		}


		if ($liclass != ""){
			$entry = "\n<li class=\"".$liclass."\">".$avatar.$tweet."</li>";
		} else {
			$entry = "\n<li style=\"display: inline-block; list-style: none; border-bottom: 1px #ccc dotted; margin-bottom: 5px; padding-bottom: 5px;\">".$avatar.$tweet."</li>";
		}
		$wholetweet = $wholetweet."".$entry;
		$imgclass='';
	}
	}


	ob_end_flush();
	if ($followlink == "yes"){
		if ($mode == "feed" || $mode == "mentions" || $mode == "retweets" || $mode == "public") {
			if ($followbutton =="yes") {
				if ($scheme == "dark") { $tfscheme = " data-button=\"grey\" data-text-color=\"#FFFFFF\" data-link-color=\"#00AEFF\""; }
				if ($followercount == "no") { $tfcount = " data-show-count=\"false\""; }
				if ($lang != "en") { $tflang = "  data-lang=\"".$lang."\""; }
				$linktofeed = "<a href=\"http://twitter.com/".$username."\" class=\"twitter-follow-button\" rel=\"nofollow\"".$tfscheme.$tfcount.$tflang.">Follow @".$username."</a>\n";
			} else {
				$linktofeed = ("<a href=\"http://twitter.com/".$username."\" rel=\"nofollow\">follow ".$username." on twitter</a><br />\n");
			}
		}
		if ($mode == "fav") {
			$linktofeed = ("<a href=\"http://twitter.com/".$username."/favorites\" rel=\"nofollow\">view all favourites for ".$username."</a><br />\n");
		}
		if ($mode == "search") {
			$linktofeed = ("<a href=\"http://twitter.com/search?q=".$term."\" rel=\"nofollow\">view search results for \"".$term."\" on twitter</a><br />\n");
		}
		if ($mode == "hashtag") {
			$linktofeed = ("<a href=\"http://twitter.com/search?q=%23".$hashtag."\" rel=\"ofollow\">view search results for \"#".$hashtag."\" on twitter</a><br />\n");
		}
	}
	if ($linklove != "no"){ $pleer = "\nPowered by <a href=\"http://3doordigital.com/wordpress/plugins/wp-twitter-feed/\">Twitter Feed</a><br />\n"; }
	$whole = "\n<!-- WordPress Twitter Feed Plugin: http://3doordigital.com/wordpress/plugins/wp-twitter-feed/ -->\n".$divstart.$ulstart.$wholetweet."\n</ul>\n".$linktofeed.$pleer.$divend."\n";
	return $whole;
	}

add_filter( 'wp_feed_cache_transient_lifetime', create_function( '$a', 'return 3;' ) );
add_filter('widget_text', 'do_shortcode');
add_shortcode('twitter-feed', 'Twitterfeedreader');
remove_filter('wp_feed_cache_transient_lifetime', create_function( '$a', 'return 3;' ));
?>