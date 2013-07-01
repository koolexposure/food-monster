=== Photospace Gallery === 
Contributors: deanoakley
Author: Dean Oakley
Author URI: http://thriveweb.com.au/ 
Plugin URI: http://thriveweb.com.au/the-lab/wordpress-gallery-plugin-photospace-2/
Tags: gallery, image gallery, website gallery, photoalbum, photogallery, photo, plugin, images, slideshow, short code, jQuery, photospace, Galleriffic, responsive
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 2.3.0

The Photospace plugin takes advantage of the built in features of WP by automatically appying the plugin to the default gallery short code.

== Description ==

The Photospace plugin takes advantage of the built in features of WP by automatically appying the plugin to the default gallery short code.

The Photospace gallery plugin allows you to:

* Upload multiple images at once
* Easily order images via drag and drop
* Add a title, caption and description

Via the options panel you can modify:

* Thumbnail number, size and shape
* Size of the main image
* The width of the gallery columns and the size of the main image

Some other features include:

* Keyboard control
* Pagination
* Supports multiple galleries (Displayed via multiple posts)

See a [demo here](http://thriveweb.com.au/blog/wordpress-gallery-plugin-photospace-2/ "Photospace") 

== Installation == 

1. Upload `/photospace/` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Upload some photos to the post or page where you want the gallery
4. Use WordPress to build your gallery and insert it in the page content

== Screenshots ==

1. Screenshot Default gallery layout
2. Screenshot Admin Area 

== Changelog ==

= 2.3.0 =
* Added support for new 3.5 gallery system
* gallery short code should be used to enable GUI gallery editing

= 2.2.7 =
* Minor update to remove notices in debug mode

= 2.2.5 =
* Removed spans from displaying in control titles
* Added title and span to download link
* Added classes to prev and next in paging

= 2.2.4 =
* Update to support responsive layouts
* Updated site_url()

= 2.2.3 =
* Proper script enqueuing
* Minor CSS update

= 2.2.2 =
* Opacity fixes for IE

= 2.2.1 =
* Update for current and hover opacity
* Nicer loading and layout for no javascript

= 2.2.0 =
* Fix for hover bug in Webkit. Removed opacityrollover.js plugin, now using css transitions. 

= 2.1.8 =
* You can now edit all text in options panel. If you upgrade you will need to add this text.
* Minor update to captions

= 2.1.7 =
* Typos
* Added spans for better css control

= 2.1.6 =
* Fixed ordering bug

= 2.1.5 =
* Added missing title attribute

= 2.1.4 =
* Added missing file jquery.history.js to repository.

= 2.1.3 =
* History plugin ie7 error fix

= 2.1.2 =
* Ordering fix

= 2.1.1 =
* CSS updates to reduce conflicts
* Paging fix

= 2.1.0 =
* Added include and exclude attributes
* Added paging option
* Added history plugin option

= 2.0.4 =
* Better horizontal thumbs

= 2.0.3 =
* Actually fixed download original button

= 2.0.2 =
* Fix download original button

= 2.1 =
* Fix for better horizontal thumbs
* Cleaned out some old code

= 2.0 =
* Now using built in WP thumnail resizing to improve speed and reduce compatibilty problems.
* New page button and CSS updates. 

= 1.6.7 =
* New buttons
* Cleaned up css in header

= 1.6.6 =
* Fix in image.php for WP in a sub directory

= 1.6.5 =
* Fix for IE8 when hiding thumbnails per instance

= 1.6.4 =
* Better reset

= 1.6.3 =
* Fixed thumnail editing thanks to Peter Molnar @ webportfolio.hu
* Fixed captions displaying on loading

= 1.6.2 = 
* Fixed hide thumbnail attribute

= 1.6.1 =
* CSS fix 

= 1.6.0 =
* Added short code attributes for all settings
* Can now display images from another post or page
* Added support for horizonal thumbnail layout
* Added option to reset formatting
* General CSS updates

= 1.5.2 =
* Added slideshow options
* Updated CSS to make it easier to override

= 1.5.1 =
* New transparent gif loader
* Fized ordering - Thanks to Afark

= 1.5.0 =
* Now enqueing all scrips and css
* Now using almost the default galleriffic script
* CSS updated to reduce conflicts
* Added thumbnail margin option
* Added background colour option for testing
* Removed title from the text below the image. Title is now only used as the image title.
* Added alt text as the alt text. Makes sense right!

= 1.4.3 =
* Added - Support for WordPress multi-site

= 1.4.2 =
* Fixed - Height option properly
* Tidied option page

= 1.4.1 =
* Fixed - Height option not working
* Tidied option page

= 1.3 =
* Added - jQuery enqueueing 

= 1.2 =
* Added - Support for multiple galleries on a page. Although each gallery must be generated from an individual post.

= 1.1 =
* Added - Controls option
* Added - Download option
* Fixed - Keyboard nav from interfering with forms

= 1.0 =
* This is the first version