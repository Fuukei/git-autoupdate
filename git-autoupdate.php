<?php
/*
Plugin Name: Git Auto Update
Plugin URI: https://example.com/git-autoupdate
Description: A plugin that automatically updates the active theme from a git repository.
Version: 1.0.0
Author: KotoriK
Author URI: https://github.com/KotoriK
Co-author: Bing AI
Co-author URI: https://bing.com/new
License: GPL-2.0-or-later
Text Domain: git-autoupdate
*/

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GITAU_PLUGIN_NAME', 'Git Auto Update' );
define( 'GITAU_MSG_PREFIX', GITAU_PLUGIN_NAME.': ' );

// 添加额外的cron时间间隔
function gitau_add_cron_schedules( $schedules ) {
    // Add a weekly schedule
    $schedules['weekly'] = array(
        'interval' => 7 * 24 * 60 * 60, // 7 days * 24 hours * 60 minutes * 60 seconds
        'display' => __( 'Once a week', 'git-autoupdate' )
    );

    // Add a monthly schedule
    $schedules['monthly'] = array(
        'interval' => 30 * 24 * 60 * 60, // 30 days * 24 hours * 60 minutes * 60 seconds
        'display' => __( 'Once a month', 'git-autoupdate' )
    );

    // Return the modified schedules
    return $schedules;
}
add_filter( 'cron_schedules', 'gitau_add_cron_schedules' );

require_once __DIR__.'/scheduler.php';
require_once __DIR__.'/settings.php';

// Activation function
function gitau_activate() {
}

// Deactivation function
function gitau_deactivate() {
	// Clear the scheduled cron event
	gitau_cancel_schedule_cron();
}


// Register activation and deactivation hooks

register_activation_hook( __FILE__, 'gitau_activate' );
register_deactivation_hook( __FILE__, 'gitau_deactivate' );
