<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once __DIR__ . '/git.php';

function gitau_schedule_cron()
{
    // 检查主题是否支持更新
    if (!gitau_check_theme_support(wp_get_theme())) {
        return;
    }

    $options = get_option('gitau_settings');
    if (isset($options['gitau_cron_interval']) && !wp_next_scheduled('gitau_update_theme')) {
        $schedules =  wp_get_schedules();
        $interval = $options['gitau_cron_interval'];
        $error = wp_schedule_event(time() +  $schedules[$interval]['interval'], $interval, 'gitau_update_theme', array(), true);
        if ($error instanceof WP_Error) {
            error_log(GITAU_MSG_PREFIX . "schedule failed: " . join(';', $error->get_error_messages()));
            return $error;
        }
    }
}

function gitau_cancel_schedule_cron()
{
    wp_clear_scheduled_hook('gitau_update_theme');
}
