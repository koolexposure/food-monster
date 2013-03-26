Prime Child Theme
=================

More info: http://groupbuyingsite.com

This is a blank child theme so you can begin customizing the Prime Theme. It will be identical to the Prime Theme until you add modifications.  See the following post for more information:
http://groupbuyingsite.com/forum/showthread.php?3203-Setting-Up-and-Using-a-Child-Themes


What is this child theme for?
-----------------------------

http://groupbuyingsite.com/forum/showthread.php?64-What-is-a-child-theme

A WordPress child theme is a theme that inherits the functionality of another theme, called the parent theme, and allows you to modify, or add to, the functionality of that parent theme.

This child theme will allow you to modify the look and feel of the GBS theme without modifying core GBS template files and breaking your upgrade path.

How to use
----------

This child theme was created for you to start customizing the parent theme without breaking your upgrade path.

The Basics
----------

Files in the parent theme can be overriden by this child theme by simply recreating the file structure here. For example, to replace the footer.php in the parent theme you'd create a new footer.php. It will then be loaded instead. Simple huh?

Advanced
--------

Of course, you can and probably want to replace the templates that GBS provides. The parent theme already does this, look at the /gbs directory, that file structure matches the structure in the GBS plugin ( i.e. /views ).
Don't worry, just because the parent theme has already override the GBS template doesn't mean you can't do it again in the child theme.

'Public' or General Templates
-----------------------------

General/'Public'/Wrapper templates work a little differently. These templates wrap the content and are used to quickly incorporate GBS into any custom theme.

If you want to override any of the templates within /views/public of the GBS plugin you need to place those in the root /gbs directory of the child theme. An example of this in action is in the parent theme: /gbs/account.php in the parent theme overrides the plugin's /views/public/account.php.


Help?
-----

Check out the forums:
http://groupbuyingsite.com/forum/forumdisplay.php?12-Customization