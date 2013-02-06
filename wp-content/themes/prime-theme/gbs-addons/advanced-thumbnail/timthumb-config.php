<?php
/**
 * Copy this file to your wp-content/ folder on your server. It will be used to configure timthumb to use a new location
 * to store cached images and allow you change it's default settings.
 * 
 * The example below expects a folder relative to the timthumb script in the gbs theme ( /wp-content/themes/[gbs-theme]/gbs-addons/advanced-thumbnail ). Example below would need a folder called thumb-cache under wp-content/uploads (i.e. wp-content/uploads/thumb-cache).
 * 
 * Notes: 
 * Make sure to set the permissions to this new cache folder correctly.
 * WordPress constants or functions will not work (i.e. TEMPLATEPATH since WP is not loaded).
 * 
 *  
 */

// define ('DEBUG_ON', false);				// Enable debug logging to web server error log (STDERR)
// define ('DEBUG_LEVEL', 1);				// Debug level 1 is less noisy and 3 is the most noisy
// define ('MEMORY_LIMIT', '30M');				// Set PHP memory limit
// define ('BLOCK_EXTERNAL_LEECHERS', false);		// If the image or webshot is being loaded on an external site, display a red "No Hotlinking" gif.

//Image fetching and caching
// define ('ALLOW_EXTERNAL', TRUE);			// Allow image fetching from external websites. Will check against ALLOWED_SITES if ALLOW_ALL_EXTERNAL_SITES is false
// define ('ALLOW_ALL_EXTERNAL_SITES', false);		// Less secure. 
// define ('FILE_CACHE_ENABLED', TRUE);			// Should we store resized/modified images on disk to speed things up?
// define ('FILE_CACHE_TIME_BETWEEN_CLEANS', 86400);	// How often the cache is cleaned 
// define ('FILE_CACHE_MAX_FILE_AGE', 86400);		// How old does a file have to be to be deleted from the cache
// define ('FILE_CACHE_SUFFIX', '.timthumb.txt');		// What to put at the end of all files in the cache directory so we can identify them
define ('FILE_CACHE_DIRECTORY', '../../../../uploads/thumb-cache');		// Directory where images are cached. Left blank it will use the system temporary directory (which is better for security). 
// define ('MAX_FILE_SIZE', 10485760);			// 10 Megs is 10485760. This is the max internal or external file size that we'll process.  
// define ('CURL_TIMEOUT', 20);				// Timeout duration for Curl. This only applies if you have Curl installed and aren't using PHP's default URL fetching mechanism.
// define ('WAIT_BETWEEN_FETCH_ERRORS', 3600);		//Time to wait between errors fetching remote file
//Browser caching
// define ('BROWSER_CACHE_MAX_AGE', 864000);		// Time to cache in the browser
// define ('BROWSER_CACHE_DISABLE', false);		// Use for testing if you want to disable all browser caching

//Image size and defaults
define ('MAX_WIDTH', 2500);				// Maximum image width
define ('MAX_HEIGHT', 2500);				// Maximum image height
// define ('NOT_FOUND_IMAGE', 'themes/[gbs-theme]/img/logo.png');				//Image to serve if any 404 occurs 
// define ('ERROR_IMAGE', '');				//Image to serve if an error occurs instead of showing error message 

//Image compression is enabled if either of these point to valid paths

//These are now disabled by default because the file sizes of PNGs (and GIFs) are much smaller than we used to generate. 
//They only work for PNGs. GIFs and JPEGs are not affected.
// define ('OPTIPNG_ENABLED', false);  
// define ('OPTIPNG_PATH', '/usr/bin/optipng'); //This will run first because it gives better compression than pngcrush. 
// define ('PNGCRUSH_ENABLED', false); 
// define ('PNGCRUSH_PATH', '/usr/bin/pngcrush'); //This will only run if OPTIPNG_PATH is not set or is not valid