<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    One_Click_Start
 * @author     haas_ib
 *
 * FILE: includes/class-ocs-core.php
 */
declare(strict_types=1);

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class OCS_Core {

    protected string $plugin_name;
    protected string $version;

    public function __construct() {
        $this->plugin_name = 'one-click-start';
        $this->version = OCS_VERSION;
    }

    public function run(): void {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies(): void {
        $ajax_file = OCS_PLUGIN_DIR . 'includes/class-ocs-ajax.php';
        $recipe_handler_file = OCS_PLUGIN_DIR . 'includes/class-ocs-recipe-handler.php';

        if (file_exists($ajax_file)) {
            require_once $ajax_file;
        }
        if (file_exists($recipe_handler_file)) {
            require_once $recipe_handler_file;
        }
    }

    private function define_admin_hooks(): void {
        add_action( 'admin_menu', [$this, 'add_plugin_admin_menu'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_styles_and_scripts'] );
        add_action( 'admin_init', [$this, 'handle_export'] );

        if (class_exists('OCS_Ajax')) {
            $ajax_handler = new OCS_Ajax();
            add_action('wp_ajax_ocs_save_recipe', [$ajax_handler, 'save_recipe']);
            add_action('wp_ajax_ocs_execute_task', [$ajax_handler, 'execute_task']);
            add_action('wp_ajax_ocs_import_recipe', [$ajax_handler, 'import_recipe']);
        }
    }

    public function add_plugin_admin_menu(): void {
        add_menu_page(
            esc_html__( 'One Click Start', 'ocs' ),
            esc_html__( 'One Click Start', 'ocs' ),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_plugin_setup_page'],
            'dashicons-controls-play',
            25
        );
    }
    
    public function handle_export(): void {
    if (isset($_GET['action'], $_GET['nonce'])) {
        $action = sanitize_text_field(wp_unslash($_GET['action']));
        $nonce = sanitize_text_field(wp_unslash($_GET['nonce']));

        if ('ocs_export_recipe' === $action && wp_verify_nonce($nonce, 'ocs_export_nonce')) {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('Permission Denied', 'one-click-start'));
            }

            $recipe_data = get_option('ocs_saved_recipe', []);
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename=one-click-start-recipe.json');
            echo wp_json_encode($recipe_data, JSON_PRETTY_PRINT);
            exit;
        }
    }
}


    public function display_plugin_setup_page(): void {
        $saved_recipe = get_option('ocs_saved_recipe', []);
        $defaults = [
            'cleanup'   => ['delete_post', 'delete_page', 'delete_hello_dolly', 'delete_default_comment'],
            'permalink' => '/%postname%/',
            'settings'  => ['disable_pingbacks', 'comment_moderation', 'comment_approval', 'disable_xml_rpc'],
            'content'   => ['create_primary_menu'],
            'plugins'   => [],
            'theme'     => ''
        ];
        $recipe_data = wp_parse_args($saved_recipe, $defaults);
        update_option('ocs_saved_recipe', $recipe_data);

        require_once OCS_PLUGIN_DIR . 'templates/recipe-builder-page.php';
    }

    public function enqueue_styles_and_scripts(string $hook): void {
        if ('toplevel_page_' . $this->plugin_name !== $hook) {
            return;
        }
        wp_enqueue_style($this->plugin_name, OCS_PLUGIN_URL . 'assets/css/ocs-admin-styles.css', [], $this->version, 'all');
        wp_enqueue_script($this->plugin_name, OCS_PLUGIN_URL . 'assets/js/ocs-admin-scripts.js', ['jquery'], $this->version, true);
        
        wp_localize_script($this->plugin_name, 'ocs_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('ocs_ajax_nonce'),
            'i18n'     => [
                'confirm_deploy' => __( 'Are you sure you want to deploy this recipe? This will perform the saved actions on your site.', 'ocs' ),
                'saving' => __( 'Saving...', 'ocs' ),
                'error_prefix' => __( 'Error: ', 'ocs' ),
                'no_tasks' => __( 'No tasks selected in the recipe!', 'ocs' ),
                'plugin_limit_exceeded' => __( 'You can select a maximum of 5 plugins to install.', 'ocs' ),
                'all_tasks_complete' => __( 'All tasks completed successfully!', 'ocs' ),
                'deployment_halted_error' => __( 'Deployment halted due to an error.', 'ocs' ),
                'ajax_error_prefix' => __( 'AJAX ERROR: ', 'ocs' ),
                'server_error' => __( 'Deployment halted due to a server error.', 'ocs' ),
                'import_error' => __( 'Could not import settings. The file may be invalid.', 'ocs' ),
                'import_success' => __( 'Settings imported successfully! The page will now reload.', 'ocs' ),
                'you_may_close' => __( 'You may now close this window.', 'ocs' ),
            ]
        ]);
    }
}