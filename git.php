<?php

/**
 * git操作
 */
if (!defined('ABSPATH')) {
	exit;
}
function gitau_get_current_hash()
{
	exec('git rev-parse --short HEAD', $output, $return_var);
	if ($return_var !== 0) {
		return null;
	}
	return $output;
}
function gitau_get_theme_version()
{
	$theme = wp_get_theme();
	return $theme->get('Version') . "(" . gitau_get_current_hash() . ")";
}
// Cron event callback function
function gitau_update_theme()
{
	// Get the active theme object
	$theme = wp_get_theme();

	// Get the theme directory path
	$theme_dir = $theme->get_stylesheet_directory();

	// Check if the theme directory is a git repository
	if (is_dir($theme_dir . '/.git')) {
		// Change the current working directory to the theme directory
		chdir($theme_dir);

		// Execute the git fetch command to check for updates
		exec('git fetch', $output, $return_var);

		// Check if the command was successful
		if ($return_var === 0) {
			// Execute the git status command to see if there are any changes
			exec('git status', $output, $return_var);

			// Check if the command was successful and the output contains "behind"
			if ($return_var === 0 && strpos(implode("\n", $output), 'behind') !== false) {
				$theme_version_pre = gitau_get_theme_version();
				// Execute the git pull command to update the theme
				exec('git pull', $output, $return_var);

				// Check if the command was successful
				if ($return_var === 0) {
					// Log a success message
					gitau_cron_log_success($theme_version_pre, gitau_get_theme_version());
				} else {
					// Log an error message with the output and return value
					gitau_cron_log_err(
						__(
							'Theme update failed. Output: %s. Return value: %d',
							'git-autoupdate',
							implode("\n", $output),
							$return_var
						)
					);
				}
			} else {
				// Log a message that there are no changes to update
				gitau_cron_log_err(__('Theme is up to date.', 'git-autoupdate'));
			}
		} else {
			// Log an error message with the output and return value
			gitau_cron_log_err(
				__(
					'Theme fetch failed. Output: %s. Return value: %d',
					'git-autoupdate',
					implode("\n", $output),
					$return_var
				)
			);
		}
	} else {
		// Log a message that the theme directory is not a git repository
		gitau_cron_log_err(__('Theme directory is not a git repository.', 'git-autoupdate'));
	}
}
/**
 * 检查目标主题是否支持git升级
 */
function gitau_check_theme_support(WP_Theme $theme)
{
	$root_dir = $theme->get_stylesheet_directory();
	return is_dir($root_dir . '/.git');
}

function gitau_get_upstream(string $path)
{
	exec("cd $path && git config --get remote.origin.url", $output);
	return $output;
}
