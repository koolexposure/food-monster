<?php

//ADD TWITTER JS
function wptf_js() {
	$options = get_option('wptf');
	if ($options['twitterJS'] == 'yes' || $options['twitterJS'] == 'on') {
?>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");twitterWidgets.onload = _ga.trackTwitter;</script>
<?php
	}
}
add_action('wp_head', 'wptf_js');


function wptfshortcode($wptfatts) {
	if (!empty($wptfatts)) {
        foreach ($wptfatts as $key => $option)
            $wptf[$key] = $option;
	}
	$options = get_option('wptf');
	if ($options['linklove']=='' || $wptf[linklove]=='off' || $wptf[linklove]=='no') {$linklove='no';}

	if ($wptf[mode] == "fav")	{
		$twitter_add = "favorites/".$wptf[username];
		$twitter_subject = "Favorite Tweets by ".$wptf[username];
	}
	elseif ($wptf[mode] == "hashtag") {
		$twitter_add = "search?q=%23".$wptf[hashtag];
		$twitter_subject = "Tweets about #".$wptf[hashtag];
	}
	elseif ($wptf[mode] == "search") {
		$twitter_add = "search?q=".$wptf[term];
		$twitter_subject = "Tweets about ".$wptf[term];
	} else {
		$twitter_add = $wptf[username];
		$twitter_subject = "Tweets by ".$wptf[username];
	}
	$wptfbox = "\n<!-- Twitter Feed for WordPress: http://3doordigital.com/wordpress/plugins/wp-twitter-feed/ -->\n<div class=\"twitter-feed\"><a class=\"twitter-timeline\" href=\"https://twitter.com/".$twitter_add."\" data-widget-id=\"".$wptf[id]."\">".$twitter_subject."</a>\n";

if ($options['followbutton'] == "yes") {
	if ($options['largebutton'] == "yes") {
		$large=' data-size="large"';
	}
	if ($options['lang'] != "en") {
		$language=' data-lang="'.$lang.'"';
	}
		if ($options['followercount'] != "yes") {
			$count=' data-show-count="false"';
		} else {
			$count='';
		}
		$wptfbox .=  '<p><a href="https://twitter.com/'.$wptf[username].'" class="twitter-follow-button"'.$language.$count.$large.'>Follow @'.$wptf[username].'</a></p>';
}
    if ($linklove != 'no') {
       $wptfbox .= '<p>Powered by <a href="http://3doordigital.com/wordpress/plugins/wp-twitter-feed/">Twitter Feed</a></p>';
    }
	global $user_ID; if( $user_ID ) {
	if(current_user_can('level_10') && ($wptf[id]=='' || $wptf[mode]=='')) {
		$wptfbox .= '<p style="color:red;text-shadow: 1px 1px #cecece;">Twitter Feed is not set up properly, <a href="'.get_admin_url().'options-general.php?page=wptf">click here</a> to configure and read the new instructions</p>';
	}}
	$wptfbox .= '</div>';
return $wptfbox;
}
add_filter('widget_text', 'do_shortcode');
add_shortcode('twitter-feed', 'wptfshortcode');

?>