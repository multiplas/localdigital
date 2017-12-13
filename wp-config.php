<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'db5488962_sa230757_main');

/** MySQL database username */
define('DB_USER', 'rasty');

/** MySQL database password */
define('DB_PASSWORD', 'rasty');

/** MySQL hostname */
define('DB_HOST', '127.0.0.1:3306');

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
define('AUTH_KEY',         '70KqJesZw4UV%Y!7dRCRHk6HlXClT6yZ*Z(OV^tgMX((ayuC^)XEwd11X2LketQ6');
define('SECURE_AUTH_KEY',  'xD5821DIlp#QJr5bdb7fHXb(Qre8kM579eEDddM9gIbfq3s0(3wFdm^QVp!7A)bm');
define('LOGGED_IN_KEY',    'rA02538i1*v2GHQyQX6^c6ev8bv7eWrUhud!9a&9sDH*bipZ0WAXDapdPZeaEKtU');
define('NONCE_KEY',        'iJO^Pd%as1dRdMAKF1nZ9nLrVWi)D45yr2a2Y6ecs1pwYtE^Nzs*DxwE@ySNC1xE');
define('AUTH_SALT',        'za#0LSszxk8N&OM^*2c#xNbulK9Lx^pMjnj#u7MNqPh(4reVsbONudo^3Z)Lj)Sc');
define('SECURE_AUTH_SALT', 's)rZWASLqHWqVIpk)zUs1!GLuVr4tzcvT)BZEkGaRp62J)XXVhNNeqWLZbaZhnwv');
define('LOGGED_IN_SALT',   'kMDWhLkfh^QwB#bA4xxWDY7e&CkCPHGg7Q1!QpKohSC99@Ei0Vvw7%YI1Og5aehw');
define('NONCE_SALT',       'notxoNtwwRcrSe3l8yuVpvY&8VBl9tPz350uhXlSm6IdF*vysiuEuST#s#WdMe*&');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', true);

// log php errors
@ini_set('log_errors','On'); // enable or disable php error logging (use 'On' or 'Off')
@ini_set('display_errors','Off'); // enable or disable public display of errors (use 'On' or 'Off')
@ini_set('error_log',dirname(__FILE__) . '/php-errors.log'); // path to server-writable log file

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

define( 'WP_ALLOW_MULTISITE', true );

define ('FS_METHOD', 'direct');
