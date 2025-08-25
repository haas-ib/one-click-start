<?php
/**
 * Fired when the plugin is uninstalled.
 * Cleans up the database option.
 *
 * FILE: uninstall.php
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'ocs_saved_recipe' );