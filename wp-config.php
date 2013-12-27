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
define('WP_CACHE', true); //Added by WP-Cache Manager
define( 'WPCACHEHOME', '/home/nassau/wordpress.nassauweekly.com/wp-content/plugins/wp-super-cache/' ); //Added by WP-Cache Manager
define('DB_NAME', 'wordpress_nassauweekly_c');

/** MySQL database username */
define('DB_USER', 'wordpressnassauw');

/** MySQL database password */
define('DB_PASSWORD', 'Efcf*TuR');

/** MySQL hostname */
define('DB_HOST', 'mysql.wordpress.nassauweekly.com');

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
define('AUTH_KEY',         'UVpfNg%ymud*uCT:5vtkU"e2kUhXV&z5p~8!oXuNh8KWgRzEGpHJEjOCH"/U:!ea');
define('SECURE_AUTH_KEY',  'sQ:|T_Dq:|ebvure~kM1ULz/_AYJ!O(i7~u^RPbVnt%O)vbOT%hi8%7SLNIAJa&f');
define('LOGGED_IN_KEY',    'JmJl)5rTmN+AD0e1*U2SQB^p+?|GoZaLa"!y^m/!uDirBK;VMz2PN:01DacD)#M?');
define('NONCE_KEY',        '"XD"t2Q1IK@cKoaK(nwD9/?2VCfn8g"yn`J;PWO@m6fF)b96|WR;*g/)GL)$%dLw');
define('AUTH_SALT',        'lM;DGXQFC8DxakHlW*T0mE*1Jr++/Z;"Mz~d~5u)6_r_#Om#%Djf/Jxly6Ryf`aP');
define('SECURE_AUTH_SALT', '|c~dl2Ype2eQ4|gF6~@pR(SCb"2skL(jWqus#3n~2V#MH*?5z4j0h90|/3KvFUwl');
define('LOGGED_IN_SALT',   'vEKoHV7HHq3Fp25kiz?NEqwWCX;~s!SRdAtdmW~7o^IMuf?_E1a_t_yny2b"2y_v');
define('NONCE_SALT',       'V/a*Kmm2^`Mj5n%rlX(t7Sr(kfQ9Ol_W8etzm~3+0ZccG_^H_N%QW&|W(KtjZv4I');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_mxev3w_';

/**
 * Limits total Post Revisions saved per Post/Page.
 * Change or comment this line out if you would like to increase or remove the limit.
 */
define('WP_POST_REVISIONS',  10);

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

?>