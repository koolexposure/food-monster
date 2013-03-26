<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'foodmonster_db_01');

/** MySQL database username */
define('DB_USER', 'fmadmin');

/** MySQL database password */
define('DB_PASSWORD', 'ba1tFMdb');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'dcE{m@Y<E=^e:!5MeCjtQ+q{IRjN{Pv!$4P>L(_#O7ln+p:N!Vz<raMk?+wLR5YP');
define('SECURE_AUTH_KEY',  '47[`:$.f`+-P/+*;ng4ksIzFF.xbn-X1Vtw%U|:yZ7w#.7gXyJ@ c7MGuGdq*:A|');
define('LOGGED_IN_KEY',    'c$uV|`vDa80_VX2DTbv%~#5~CoawfiQjMKyScmtwz2di%}`NV)b9@b.sh<2Ouy-_');
define('NONCE_KEY',        'e wIJtgd$>C&p7p^D]5wC.AN=,*&omNFAL|:zLN$(-&wILOhp wT_1dN@#c}@1eg');
define('AUTH_SALT',        ',WjzmcXo!*]8Yz?2_0feadbD]Nq.|1+]=?4<AK4_.6#C&*Cnwlry<w&E_K:TdH:W');
define('SECURE_AUTH_SALT', 'gU+[?fn4q]+/PTa@aZ%(mDy.ar.u[<6##xU,h}wc`X0C{%u2TyE`<BGB|U1~1w0u');
define('LOGGED_IN_SALT',   ';+ZBEU+$<Y/7^q|(:LZW$4{0UN5k[@11&5+cJR%`(Ow#52>|,-0fB{@ bea^0y#x');
define('NONCE_SALT',       '17:|-k}+y<YuWP]:(z<xYZS}$M[[yYP|CMCRKU}Ff{<6CB0L1IF?f595,dmHi5Kc');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
