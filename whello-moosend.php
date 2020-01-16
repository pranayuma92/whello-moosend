<?php

/*
Plugin Name: Whello Moosend
Description: Optimize marketing campaign using Moosend
Version: 1.5.0
Author: Whello Indonesia
License: GPLv2 or later
Text Domain: whello-moosend
*/

/**
 * Define constant
 * @package whello-moosend
 * @since 1.0.0
 */

define( 'WM_VERSION', '1.5.0' );
define( 'WM_SLUG', 'whello-moosend' );
define( 'WM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


//Including required file
require_once WM_PLUGIN_DIR . 'inc/class-cache.php';
require_once WM_PLUGIN_DIR . 'inc/class-main.php';
require_once WM_PLUGIN_DIR . 'inc/class-moosend-api.php';


//Instance main class
( new WHMainFunction() );