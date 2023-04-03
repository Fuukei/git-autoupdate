<?php

/**
 * git操作
 */
if (!defined('ABSPATH')) {
	exit;
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
				// Execute the git pull command to update the theme
				exec('git pull', $output, $return_var);

				// Check if the command was successful
				if ($return_var === 0) {
					// Log a success message
					error_log('Git Auto Update: Theme updated successfully.');
				} else {
					// Log an error message with the output and return value
					error_log('Git Auto Update: Theme update failed. Output: ' . implode("\n", $output) . '. Return value: ' . $return_var);
				}
			} else {
				// Log a message that there are no changes to update
				error_log('Git Auto Update: Theme is up to date.');
			}
		} else {
			// Log an error message with the output and return value
			error_log('Git Auto Update: Theme fetch failed. Output: ' . implode("\n", $output) . '. Return value: ' . $return_var);
		}
	} else {
		// Log a message that the theme directory is not a git repository
		error_log('Git Auto Update: Theme directory is not a git repository.');
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

function gitau_get_upstream(string $path){
	exec("cd $path && git config --get remote.origin.url", $output);
return $output;
}