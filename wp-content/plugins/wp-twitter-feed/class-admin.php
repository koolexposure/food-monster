<?php
define("PLUGIN_NAME","Twitter Feed for WordPress");
define("PLUGIN_TAGLINE","The ultimate plugin for outputting tweets via shortcode :)");
define("PLUGIN_URL","http://3doordigital.com/wordpress/plugins/wp-twitter-feed/");
define("EXTEND_URL","http://wordpress.org/extend/plugins/wp-twitter-feed/");
define("AUTHOR_TWITTER","alexmoss");
define("DONATE_LINK","https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=TPB967WJCR35N");

add_action('admin_init', 'wptf_init' );
function wptf_init(){
	register_setting( 'wptf_options', 'wptf' );
	$new_options = array(
		'twitterJS' => 'yes',
		'linklove' => 'no',
		'APIwarning' => 'no',
		'followbutton' => 'yes',
		'followercount' => 'no',
		'largebutton' => 'yes',
		'lang' => 'en',
	);
	add_option( 'wptf', $new_options );
}


add_action('admin_menu', 'show_wptf_options');
function show_wptf_options() {
	add_options_page('Twitter Feed Options', 'Twitter Feed', 'manage_options', 'wptf', 'wptf_options');
}


function wptf_fetch_rss_feed() {
    include_once(ABSPATH . WPINC . '/feed.php');
	$rss = fetch_feed("http://3doordigital.com/feed");	
	if ( is_wp_error($rss) ) { return false; }	
	$rss_items = $rss->get_items(0, 3);
    return $rss_items;
}   

function wptf_admin_notice(){
$options = get_option('wptf');
if ($options['APIwarning']=="") {
	$wptfadminurl = get_admin_url()."options-general.php?page=wptf";
    echo '<div class="error">
       <p>Please read this important information about the Twitter Feed Plugin. <a href="'.$wptfadminurl.'"><input type="submit" value="Read message" class="button-secondary" /></a></p>
    </div>';
}
}
add_action('admin_notices', 'wptf_admin_notice');

// ADMIN PAGE
function wptf_options() {
$domain = get_option('siteurl');
$domain = str_replace('http://', '', $domain);
$domain = str_replace('www.', '', $domain);
?>
    <link href="<?php echo plugins_url( 'admin.css' , __FILE__ ); ?>" rel="stylesheet" type="text/css">
    <div class="pea_admin_wrap">
        <div class="pea_admin_top">
            <h1><?php echo PLUGIN_NAME?> <small> - <?php echo PLUGIN_TAGLINE?></small></h1>
        </div>

        <div class="pea_admin_main_wrap">
            <div class="pea_admin_main_left">
                <div class="pea_admin_signup">
                    Want to know about updates to this plugin without having to log into your site every time? Want to know about other cool plugins we've made? Add your email and we'll add you to our very rare mail outs.

                    <!-- Begin MailChimp Signup Form -->
                    <div id="mc_embed_signup">
                    <form action="http://peadig.us5.list-manage2.com/subscribe/post?u=e16b7a214b2d8a69e134e5b70&amp;id=eb50326bdf" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
                    <div class="mc-field-group">
                        <label for="mce-EMAIL">Email Address
                    </label>
                        <input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL"><button type="submit" name="subscribe" id="mc-embedded-subscribe" class="pea_admin_green">Sign Up!</button>
                    </div>
                        <div id="mce-responses" class="clear">
                            <div class="response" id="mce-error-response" style="display:none"></div>
                            <div class="response" id="mce-success-response" style="display:none"></div>
                        </div>	<div class="clear"></div>
                    </form>
                    </div>

                    <!--End mc_embed_signup-->
                </div>

		<form method="post" action="options.php" id="options">
<?php
settings_fields('wptf_options'); 
$options = get_option('wptf'); 
if (!isset($options['twitterJS'])) {$options['twitterJS'] = "yes";}
if (!isset($options['linklove'])) {$options['linklove'] = "off";}
if (!isset($options['APIwarning'])) {$options['APIwarning'] = "";}

if ($options['APIwarning']!="yes") { ?>
<div class="error">
			<h3 class="title">You need to read this important information about the Twitter Feed plugin!!!</h3>
			<table class="form-table">
				<tr valign="top">
					<td>In August 2012, Twitter announced that they were <a href="https://dev.twitter.com/blog/changes-coming-to-twitter-api" target="_blank">releasing v1.1 of the Twitter API</a>. In February 2013 they confirmed that v1 of the API would retire on <a href="https://dev.twitter.com/blog/planning-for-api-v1-retirement" target="_blank">5th March 2013</a>. Included in this retirement is the RSS feature that powers this plugin.<br /><br />
<a class="button button-primary button-large" style="text-align: center;font-weight: bold;font-size: 1.3em;" href="http://3doordigital.com/wordpress/plugins/wp-twitter-feed-pro/"  target="_blank">Get Twitter Feed Pro!</a><br /><br />
Because of this, the plugin has had to be completely redeveloped. As of now, there are now 2 versions of this plugin. You have updated to the free version which incorporates Twitter's own <a href="https://dev.twitter.com/docs/embedded-timelines" target="_blank">Embedded Timelines</a>. You can purchase the pro version <a href="http://3doordigital.com/wordpress/plugins/wp-twitter-feed-pro/" target="_blank">here</a> which incorporates all the old flexibility and functionality.<br><small>Click the box to acknowledge this message and hide it for the future (remember to press save)</small> <input id="APIwarning" name="wptf[APIwarning]" type="checkbox" value="yes" <?php checked('yes', $options['APIwarning']); ?> /><br><br>
</td>
				</tr>
			</table>
</div>
<?php } ?>

			<h3 class="title">Settings</h3>
			<table class="form-table">
				<tr valign="top"><th scope="row"><label for="twitterJS">Enable Twitter JS</label></th>
					<td><input id="twitterJS" name="wptf[twitterJS]" type="checkbox" value="yes" <?php checked('yes', $options['twitterJS']); ?> /> <small>only disable this if you already have Twitter's JS call enabled elsewhere</small></td>
				</tr>
				<tr valign="top"><th scope="row"><label for="followbutton">Append Twitter Button</label></th>
					<td><input id="followbutton" name="wptf[followbutton]" type="checkbox" value="yes" <?php checked('yes', $options['followbutton']); ?> /> <small>inserts a Twitter follow button beneath the Twitter feed if the Twitter feed is about a user, and a Twitter search button if using the hashtag or search modes</small></td>
				</tr>
				<tr valign="top"><th scope="row"><label for="largebutton">Large Twitter Button</label></th>
					<td><input id="largebutton" name="wptf[largebutton]" type="checkbox" value="yes" <?php checked('yes', $options['largebutton']); ?> /></td>
				</tr>
				<tr valign="top"><th scope="row"><label for="followercount">Show Follower Count</label></th>
					<td><input id="followercount" name="wptf[followercount]" type="checkbox" value="yes" <?php checked('yes', $options['followercount']); ?> /> <small>shows the number of followers by your @username for the follow button</small></td>
				</tr>
								<tr valign="top"><th scope="row"><label for="lang">Button Language</label></th>
									<td>
				              <select id="lang" name="wptf[lang]">
				                  <option <?php if ($options['lang'] == "") {echo ' selected="selected"';} ?> value="">Select Language ...&nbsp;</option>
				  <option <?php if ($options['lang'] == "en") {echo ' selected="selected"';} ?> value="en">English</option>
				  <option <?php if ($options['lang'] == "fr") {echo ' selected="selected"';} ?> value="fr">French</option>
				  <option <?php if ($options['lang'] == "ar") {echo ' selected="selected"';} ?> value="ar">Arabic</option>
				  <option <?php if ($options['lang'] == "ja") {echo ' selected="selected"';} ?> value="ja">Japanese</option>
				  <option <?php if ($options['lang'] == "es") {echo ' selected="selected"';} ?> value="es">Spanish</option>
				  <option <?php if ($options['lang'] == "de") {echo ' selected="selected"';} ?> value="de">German</option>
				  <option <?php if ($options['lang'] == "it") {echo ' selected="selected"';} ?> value="it">Italian</option>
				  <option <?php if ($options['lang'] == "id") {echo ' selected="selected"';} ?> value="id">Indonesian</option>
				  <option <?php if ($options['lang'] == "pt") {echo ' selected="selected"';} ?> value="pt">Portuguese</option>
				  <option <?php if ($options['lang'] == "ko") {echo ' selected="selected"';} ?> value="ko">Korean</option>
				  <option <?php if ($options['lang'] == "tr") {echo ' selected="selected"';} ?> value="tr">Turkish</option>
				  <option <?php if ($options['lang'] == "ru") {echo ' selected="selected"';} ?> value="ru">Russian</option>
				  <option <?php if ($options['lang'] == "nl") {echo ' selected="selected"';} ?> value="nl">Dutch</option>
				  <option <?php if ($options['lang'] == "fil") {echo ' selected="selected"';} ?> value="fil">Filipino</option>
				  <option <?php if ($options['lang'] == "msa") {echo ' selected="selected"';} ?> value="msa">Malay</option>
				  <option <?php if ($options['lang'] == "zh-tw") {echo ' selected="selected"';} ?> value="zh-tw">Traditional Chinese</option>
				  <option <?php if ($options['lang'] == "zh-cn") {echo ' selected="selected"';} ?> value="zh-cn">Simplified Chinese</option>
				  <option <?php if ($options['lang'] == "hi") {echo ' selected="selected"';} ?> value="hi">Hindi</option>
				  <option <?php if ($options['lang'] == "no") {echo ' selected="selected"';} ?> value="no">Norwegian</option>
				  <option <?php if ($options['lang'] == "sv") {echo ' selected="selected"';} ?> value="sv">Swedish</option>
				  <option <?php if ($options['lang'] == "fi") {echo ' selected="selected"';} ?> value="fi">Finnish</option>
				  <option <?php if ($options['lang'] == "da") {echo ' selected="selected"';} ?> value="da">Danish</option>
				  <option <?php if ($options['lang'] == "pl") {echo ' selected="selected"';} ?> value="pl">Polish</option>
				  <option <?php if ($options['lang'] == "hu") {echo ' selected="selected"';} ?> value="hu">Hungarian</option>
				  <option <?php if ($options['lang'] == "fa") {echo ' selected="selected"';} ?> value="fa">Farsi</option>
				  <option <?php if ($options['lang'] == "he") {echo ' selected="selected"';} ?> value="he">Hebrew</option>
				  <option <?php if ($options['lang'] == "ur") {echo ' selected="selected"';} ?> value="ur">Urdu</option>
				  <option <?php if ($options['lang'] == "th") {echo ' selected="selected"';} ?> value="th">Thai</option>
				  <option <?php if ($options['lang'] == "uk") {echo ' selected="selected"';} ?> value="uk">Ukrainian</option>
				  <option <?php if ($options['lang'] == "ca") {echo ' selected="selected"';} ?> value="ca">Catalan</option>
				  <option <?php if ($options['lang'] == "el") {echo ' selected="selected"';} ?> value="el">Greek</option>
				  <option <?php if ($options['lang'] == "eu") {echo ' selected="selected"';} ?> value="eu">Basque</option>
				  <option <?php if ($options['lang'] == "cs") {echo ' selected="selected"';} ?> value="cs">Czech</option>
				  <option <?php if ($options['lang'] == "gl") {echo ' selected="selected"';} ?> value="gl">Galician</option>
				  <option <?php if ($options['lang'] == "ro") {echo ' selected="selected"';} ?> value="ro">Romanian</option>
				                </select>
				</td>
				</tr>
				<tr valign="top"><th scope="row"><label for="linklove">Credit</label></th>
					<td><input id="linklove" name="wptf[linklove]" type="checkbox" value="yes" <?php checked('yes', $options['linklove']); ?> /></td>
				</tr>
<?php if ($options['APIwarning']=="yes") { ?>
				<tr valign="top"><th scope="row"><label for="APIwarning">Hide Twitter API Warning</label></th>
					<td><input id="APIwarning" name="wptf[APIwarning]" type="checkbox" value="yes" <?php checked('yes', $options['APIwarning']); ?> /> <small>only disable this if you want to re-read the warning about Twitter's API changes in March 2013</small></td>
				</tr>
<?php } ?>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /><a class="button" style="text-align: center;font-weight: bold;margin: 0px 10px;" href="http://3doordigital.com/wordpress/plugins/wp-twitter-feed-pro/" target="_blank">Get Twitter Feed Pro!</a>
			</p>
		</form>

			<h3 class="title">Setting up the Twitter Feed Widget</h3>
			<table class="form-table">
				<tr valign="top"><th scope="row"><a href="https://twitter.com/settings/widgets" style="text-decoration:none" target="_blank">Widgets Management Page</a></th>
					<td><small>to set up for the first time, <a href="https://twitter.com/settings/widgets/new" target="_blank">create a new widget</a>. Once you click create, it will take you to a new page where you can continue to edit the widget. Ensure you enter <strong><?php echo $domain; ?></strong> in "Domains" on the left, and any other domain you want to grant access to.</small><br><br><strong>From here, note down your widget ID which is located in the URL (https://twitter.com/settings/widgets/YOUR_WIDGET_ID/edit) or in the HTML code Twitter provide for you (data-widget-id="YOUR_WIDGET_ID")</strong><br><br><br></td>
				</tr>
			</table>

               <div class="pea_admin_box">
			<h3 class="title">Using the Shortcode</h3>
			<table class="form-table">
				<tr valign="top"><td>
<p>You can insert a Twitter Feed manually in any page or post or template. Here's an example of using the shortcode:<br><code>[twitter-feed username="alexmoss" id="12345" mode="feed"]</code></p>
<p>You can also insert the shortcode directly into your theme with PHP:<br><code>&lt;?php echo do_shortcode('[twitter-feed username="alexmoss" id="12345" mode="feed"]'); ?&gt;</code></p>
<p>Have to use the following 3 options within the shortcode otherwise it will not work. All other custmisation happens within the <a href="https://twitter.com/settings/widgets" style="text-decoration:none" target="_blank">Widgets Management Page</a> of Twitter.</p>
<ul>
<li><strong>username</strong> - the chosen username that matches the username chosen in the widget management area.</li>
<li><strong>mode</strong> feed/fav/search - this needs to correlate with your choice within the widget management area.</li>
<li><strong>id</strong> -  widget ID show after creating the widget, which is located in the URL (https://twitter.com/settings/widgets/YOUR_WIDGET_ID/edit) or in the HTML code Twitter provide for you (data-widget-id="YOUR_WIDGET_ID")</li>
</ul>
<br /><br />
<a class="button button-primary button-large" style="text-align: center;font-weight: bold;font-size: 1.3em;" href="http://3doordigital.com/wordpress/plugins/wp-twitter-feed-pro/"  target="_blank">Get Twitter Feed Pro!</a><br /><br />
					</td>
				</tr>
			</table>
</div>

</div>
            <div class="pea_admin_main_right">
                <div class="pea_admin_logo">

            <a href="http://3doordigital.com/?utm_source=<?php echo $domain; ?>&utm_medium=referral&utm_campaign=Facebook%2BComments%2BAdmin" target="_blank"><img src="<?php echo plugins_url( '3dd-logo.png' , __FILE__ ); ?>" width="250" height="92" title="3 Door Digital"></a>

                </div>


                <div class="pea_admin_box">
                    <h2>Like this Plugin?</h2>
<a href="<?php echo EXTEND_URL; ?>" target="_blank"><button type="submit" class="pea_admin_green">Rate this plugin	&#9733;	&#9733;	&#9733;	&#9733;	&#9733;</button></a><br /><br />
<a class="button button-primary button-large" style="text-align: center;font-weight: bold;font-size: 1.3em;" href="http://3doordigital.com/wordpress/plugins/wp-twitter-feed-pro/"  target="_blank">Get Twitter Feed Pro!</a><br /><br />
                    <div id="fb-root"></div>
                    <script>(function(d, s, id) {
                      var js, fjs = d.getElementsByTagName(s)[0];
                      if (d.getElementById(id)) return;
                      js = d.createElement(s); js.id = id;
                      js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=181590835206577";
                      fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));</script>
                    <div class="fb-like" data-href="<?php echo PLUGIN_URL; ?>" data-send="true" data-layout="button_count" data-width="250" data-show-faces="true"></div>
                    <br>
                    <a href="https://twitter.com/share" class="twitter-share-button" data-url="<?php echo PLUGIN_URL; ?>" data-text="Just been using <?php echo PLUGIN_NAME; ?> #WordPress plugin" data-via="<?php echo AUTHOR_TWITTER; ?>" data-related="WPBrewers">Tweet</a>
                    <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
                    <br>
<a href="http://bufferapp.com/add" class="buffer-add-button" data-text="Just been using <?php echo PLUGIN_NAME; ?> #WordPress plugin" data-url="<?php echo PLUGIN_URL; ?>" data-count="horizontal" data-via="<?php echo AUTHOR_TWITTER; ?>">Buffer</a><script type="text/javascript" src="http://static.bufferapp.com/js/button.js"></script>
<br>
                    <div class="g-plusone" data-size="medium" data-href="<?php echo PLUGIN_URL; ?>"></div>
                    <script type="text/javascript">
                      window.___gcfg = {lang: 'en-GB'};

                      (function() {
                        var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
                        po.src = 'https://apis.google.com/js/plusone.js';
                        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
                      })();
                    </script>
                    <br>
                    <su:badge layout="3" location="<?php echo PLUGIN_URL?>"></su:badge>
                    <script type="text/javascript">
                      (function() {
                        var li = document.createElement('script'); li.type = 'text/javascript'; li.async = true;
                        li.src = ('https:' == document.location.protocol ? 'https:' : 'http:') + '//platform.stumbleupon.com/1/widgets.js';
                        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(li, s);
                      })();
                    </script>
                </div>

<center><a href="<?php echo DONATE_LINK; ?>" target="_blank"><img class="paypal" src="<?php echo plugins_url( 'paypal.gif' , __FILE__ ); ?>" width="147" height="47" title="Please Donate - it helps support this plugin!"></a></center>

                <div class="pea_admin_box">
                    <h2>About the Author</h2>

                    <?php
                    $default = "http://reviews.evanscycles.com/static/0924-en_gb/noAvatar.gif";
                    $size = 70;
                    $alex_url = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( "alex@peadig.com" ) ) ) . "?d=" . urlencode( $default ) . "&s=" . $size;
                    ?>

                    <p class="pea_admin_clear"><img class="pea_admin_fl" src="<?php echo $alex_url; ?>" alt="Alex Moss" /> <h3>Alex Moss</h3><br><a href="https://twitter.com/alexmoss" class="twitter-follow-button" data-show-count="false">Follow @alexmoss</a>
<div class="fb-subscribe" data-href="https://www.facebook.com/alexmoss1" data-layout="button_count" data-show-faces="false" data-width="220"></div>
</p>
                    <p class="pea_admin_clear">Alex Moss is the Co-Founder and Technical Director of 3 Door Digital. With offices based in Manchester, UK and Tel Aviv, Israel he manages WordPress development as well as technical aspects of digital consultancy. He has developed several WordPress plugins (which you can <a href="http://3doordigital.com/wordpress/plugins/?utm_source=<?php echo $domain; ?>&utm_medium=referral&utm_campaign=Facebook%2BComments%2BAdmin" target="_blank">view here</a>) totalling over 200,000 downloads.</p>
</div>

                <div class="pea_admin_box">
                    <h2>More from 3 Door Digital</h2>
    <p class="pea_admin_clear">
                    <?php
$wptffeed = wptf_fetch_rss_feed();
                echo '<ul>';
                foreach ( $wptffeed as $item ) {
			    	$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls=null, 'display' ) );
					echo '<li>';
					echo '<a href="'.$url.'?utm_source=<?php echo $domain; ?>&utm_medium=referral&utm_campaign=Facebook%2BComments%2BRSS">'. esc_html( $item->get_title() ) .'</a> ';
					echo '</li>';
			    }
                echo '</ul>'; 
                    ?></p>
                </div>


            </div>
        </div>
    </div>



<?php
}

?>