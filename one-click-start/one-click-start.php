<?php
/**
 * Plugin Name:       One Click Start
 * Plugin URI:        https://wordpress.org/plugins/one-click-start/
 * Description:       A simple, reliable tool to automate your initial WordPress setup tasks.
 * Version:           1.0.0
 * Author:            haas_ib
 * Author URI:        https://profiles.wordpress.org/haaas/
 * License:           GPL v2 or later
 * Text Domain:       ocs
 *
 * FILE: one-click-start.php (Main Plugin File)
 */

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

define( 'OCS_VERSION', '1.0.0' );
define( 'OCS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OCS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * Sets default options.
 */
function activate_ocs() {
    if ( false === get_option( 'ocs_saved_recipe' ) ) {
        $defaults = [
            'cleanup'   => ['delete_post', 'delete_page', 'delete_hello_dolly', 'delete_default_comment'],
            'permalink' => '/%postname%/',
            'settings'  => ['disable_pingbacks', 'comment_moderation', 'comment_approval', 'disable_xml_rpc'],
            'content'   => ['create_primary_menu'],
            'plugins'   => [],
            'theme'     => ''
        ];
        add_option( 'ocs_saved_recipe', $defaults );
    }
}
register_activation_hook( __FILE__, 'activate_ocs' );

$core_file = plugin_dir_path( __FILE__ ) . 'includes/class-ocs-core.php';
if ( file_exists( $core_file ) ) {
    require_once $core_file;
}

function run_ocs() {
    if ( class_exists( 'OCS_Core' ) ) {
        $plugin = new OCS_Core();
        $plugin->run();
    }
}
run_ocs();

/**
 * Displays an admin notice on the plugin page in a Multisite environment for non-Super Admins.
 */
function ocs_multisite_admin_notice() {
    if ( is_multisite() && ! is_network_admin() ) {
        $screen = get_current_screen();
        if ( isset($screen->id) && $screen->id === 'toplevel_page_one-click-start' ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong><?php esc_html_e( 'One Click Start Notice:', 'ocs' ); ?></strong> <?php esc_html_e( 'This plugin has limited functionality on a Multisite sub-site. Plugin and theme installation/deletion can only be performed by a Super Admin.', 'ocs' ); ?></p>
            </div>
            <?php
        }
    }
}
add_action( 'admin_notices', 'ocs_multisite_admin_notice' );