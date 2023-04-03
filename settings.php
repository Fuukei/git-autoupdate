<?php
require_once __DIR__ . '/scheduler.php';
require_once __DIR__ . '/git.php';

$_schedules =  wp_get_schedules();
$gitau_avaliable_intervals = array('daily', 'weekly', 'monthly');
$gitau_avaliable_intervals_descriptions = array_map(function ($interval) use ($_schedules) {
    return $_schedules[$interval]['display'];
}, $gitau_avaliable_intervals);

// Register a new menu item and page
function gitau_add_settings_page()
{
    add_options_page(
        'Git Auto Update Settings',
        'Git Auto Update',
        'manage_options',
        'gitau-settings',
        'gitau_render_settings_page'
    );
}
add_action('admin_menu', 'gitau_add_settings_page');

// Register a new settings group, section and fields
function gitau_register_settings()
{
    register_setting(
        'gitau_settings',
        'gitau_settings',
        array(
            "sanitize_callback" => "gitau_validate_settings",
        )
    );

    add_settings_section(
        'gitau_update_frequency',
        'Update Frequency',
        function () {
            echo '<p>' .
                esc_html__('Choose how often you want to check for theme updates from the git repository.', 'git-autoupdate')
                . '</p>';
            echo '<p>' .
                esc_html__('If you want to disable autoupdate, just deactivate this plugin.', 'git-autoupdate')
                . '</p>';
        },
        'gitau-settings'
    );
    // 定时任务执行频率设置
    add_settings_field(
        'gitau_cron_interval',
        'Select how often you want to check for updates',
        'gitau_cron_interval_callback',
        'gitau-settings',
        'gitau_update_frequency'
    );
}
add_action('admin_init', 'gitau_register_settings');

// Render the settings page
function gitau_render_settings_page()
{
?>
    <h2>Git Auto Update Settings</h2>
    <form action="options.php" method="post">
        <?php
        settings_fields('gitau_settings');
        do_settings_sections('gitau-settings');
        submit_button();
        ?>
    </form>
<?php
}

// Validate and save the settings
function gitau_validate_settings($input)
{
    global $gitau_avaliable_intervals;
    // Get the current settings
    $options = get_option('gitau_settings');
    // Validate the input for the cron interval field

    foreach ($input as $key => $value) {
        switch ($key) {
            case  'gitau_cron_interval':
                if (in_array($value, $gitau_avaliable_intervals)) {
                    $options['gitau_cron_interval'] = $value;
                    add_settings_error('gitau_messages', 'gitau_message', __('Settings saved', 'git-autoupdate'), 'success');
                } else {
                    add_settings_error('gitau_messages', 'gitau_message', __('Invalid input', 'git-autoupdate'), 'error');
                }
                break;
        }
    }
    // Return the validated and updated options
    return $options;
}

// Render the update frequency section
function gitau_update_frequency_callback()
{
    echo '<p>' . esc_html__('Choose how often you want to check for theme updates from the git repository.', 'git-autoupdate') . '</p>';
}

// Render the cron interval field
function gitau_cron_interval_callback()
{
    global $gitau_avaliable_intervals;

    global $gitau_avaliable_intervals_descriptions;
    // Get the current settings
    $options = get_option('gitau_settings');

    // Get the current value of the cron interval option, or set a default value if not set
    $value = isset($options['gitau_cron_interval']) ? $options['gitau_cron_interval'] : 'weekly';

    // Output a select element with three options: hourly, twicedaily and daily
    echo '<select id="gitau_cron_interval" name="gitau_settings[gitau_cron_interval]">';
    foreach ($gitau_avaliable_intervals as $key => $interval) {
        echo '<option value="' . $interval . '"' . selected($value, $interval, false) . '>'
            . esc_html__(ucfirst($gitau_avaliable_intervals_descriptions[$key]), 'git-autoupdate')
            . '</option>';
    }
    echo '</select>';
}

// 处理选项更新
function gitau_update_option()
{
    gitau_cancel_schedule_cron();
    gitau_schedule_cron();
}
add_action('update_option_gitau_settings', 'gitau_update_option', 10);

// 提示定时任务的状态
function gitau_show_cron_status()
{
    // Check if the current screen is the settings page
    $screen = get_current_screen();
    if ($screen->id == 'settings_page_gitau-settings') {
        // Get the next scheduled time for the cron event
        $next_time = wp_next_scheduled('gitau_update_theme');

        // Get the transient for the cron status
        $status = get_transient('gitau_cron_status');

        // Check if the transient exists
        if ($status) {
            // Calculate the elapsed time since the cron event started
            $elapsed_time = time() - $status['start_time'];

            // Output a notice that the cron event is running
            echo '<div class="notice notice-info">';
            echo '<p>' . GITAU_MSG_PREFIX . sprintf(__('The cron event is running. It started %d seconds ago.', 'git-autoupdate'), $elapsed_time) . '</p>';
            echo '</div>';
        } else {
            // Check if the next scheduled time exists
            if ($next_time) {
                // Format the next scheduled time
                $next_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_time + get_option('gmt_offset') * 3600
                    /**时区修正 */
                );

                // Output a notice that the cron event is scheduled
                echo '<div class="notice notice-success">';
                echo '<p>' . GITAU_MSG_PREFIX . sprintf(__('The cron event is scheduled. It will run at %s (timezone set in your setting).', 'git-autoupdate'), $next_time) . '</p>';
                echo '</div>';
            } else {
                // Output a notice that the cron event is not scheduled
                echo '<div class="notice notice-error">';
                echo '<p>' . GITAU_MSG_PREFIX . __('Autoupdate is not scheduled. Apply a new setting to schedule a new autoupdate event.', 'git-autoupdate') . '</p>';
                echo '</div>';
            }
        }
        $theme = wp_get_theme();
        $is_support =  gitau_check_theme_support($theme);
        // https://developer.wordpress.org/reference/hooks/admin_notices/
        echo '<div class="notice notice-' . ($is_support ? 'success' : 'warning') . '"><p>' .
            sprintf(
                _x('You are using %1$s, ', "you are using {theme_name}", 'git-autoupdate'),
                '<a href="' . $theme->get('ThemeURI') . '" rel="nofollow">'
                    . $theme->get('Name')
                    . '</a>'
            ) .
            ($is_support ?
            __('git-autoupdate support this theme.', 'git-autoupdate') :
            __('git-autoupdate is not support this theme.', 'git-autoupdate')) .
            '</p></div>';
    }
}
add_action('admin_notices', 'gitau_show_cron_status');
