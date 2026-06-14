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

class One_Click_Start_Core {

    protected string $plugin_name;
    protected string $version;

    public function __construct() {
        $this->plugin_name = 'one-click-start';
        $this->version = ONE_CLICK_START_VERSION;
    }

    public function run(): void {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies(): void {
        $ajax_file = ONE_CLICK_START_PLUGIN_DIR . 'includes/class-ocs-ajax.php';
        $recipe_handler_file = ONE_CLICK_START_PLUGIN_DIR . 'includes/class-ocs-recipe-handler.php';

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
        add_action( 'admin_notices', [$this, 'maybe_show_review_prompt'] );

        if (class_exists('One_Click_Start_Ajax')) {
            $ajax_handler = new One_Click_Start_Ajax();
            add_action('wp_ajax_one_click_start_save_recipe', [$ajax_handler, 'save_recipe']);
            add_action('wp_ajax_one_click_start_execute_task', [$ajax_handler, 'execute_task']);
            add_action('wp_ajax_one_click_start_import_recipe', [$ajax_handler, 'import_recipe']);
            add_action('wp_ajax_one_click_start_set_review_prompt', [$ajax_handler, 'set_review_prompt']);
            add_action('wp_ajax_one_click_start_dismiss_review_prompt', [$ajax_handler, 'dismiss_review_prompt']);
        }
    }

    public function add_plugin_admin_menu(): void {
        add_menu_page(
            esc_html__( 'One Click Start', 'one-click-start' ),
            esc_html__( 'One Click Start', 'one-click-start' ),
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

			if ('one_click_start_export_recipe' === $action && wp_verify_nonce($nonce, 'one_click_start_export_nonce')) {
				if (!current_user_can('manage_options')) {
					wp_die(esc_html__('Permission Denied', 'one-click-start'));
				}

				$recipe_data = get_option('one_click_start_saved_recipe', []);
				header('Content-Type: application/json');
				header('Content-Disposition: attachment; filename=one-click-start-recipe.json');
				echo wp_json_encode($recipe_data, JSON_PRETTY_PRINT);
				exit;
			}
		}
	}

    public function display_plugin_setup_page(): void {
        $saved_recipe = get_option('one_click_start_saved_recipe', []);
        $defaults = [
            'cleanup'   => ['delete_post', 'delete_page', 'delete_hello_dolly', 'delete_default_comment'],
            'permalink' => '/%postname%/',
            'settings'  => ['disable_pingbacks', 'comment_moderation', 'comment_approval', 'disable_xml_rpc'],
            'content'   => ['create_primary_menu'],
            'plugins'   => [],
            'theme'     => ''
        ];
        $recipe_data = wp_parse_args($saved_recipe, $defaults);
        update_option('one_click_start_saved_recipe', $recipe_data);

        require_once ONE_CLICK_START_PLUGIN_DIR . 'templates/recipe-builder-page.php';
    }

    public function enqueue_styles_and_scripts(string $hook): void {
        if ('toplevel_page_' . $this->plugin_name !== $hook) {
            return;
        }
        wp_enqueue_style($this->plugin_name, ONE_CLICK_START_PLUGIN_URL . 'assets/css/ocs-admin-styles.css', [], $this->version, 'all');
        wp_enqueue_script($this->plugin_name, ONE_CLICK_START_PLUGIN_URL . 'assets/js/ocs-admin-scripts.js', ['jquery'], $this->version, true);
        
        wp_localize_script($this->plugin_name, 'one_click_start_ajax_object', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('one_click_start_ajax_nonce'),
            'i18n'     => [
                'confirm_deploy' => __( 'Are you sure you want to deploy this recipe? This will perform the saved actions on your site.', 'one-click-start' ),
                'saving' => __( 'Saving...', 'one-click-start' ),
                'error_prefix' => __( 'Error: ', 'one-click-start' ),
                'no_tasks' => __( 'No tasks selected in the recipe!', 'one-click-start' ),
                'plugin_limit_exceeded' => __( 'You can select a maximum of 5 plugins to install.', 'one-click-start' ),
                'all_tasks_complete' => __( 'All tasks completed successfully!', 'one-click-start' ),
                'deployment_halted_error' => __( 'Deployment halted due to an error.', 'one-click-start' ),
                'ajax_error_prefix' => __( 'AJAX ERROR: ', 'one-click-start' ),
                'server_error' => __( 'Deployment halted due to a server error.', 'one-click-start' ),
                'import_error' => __( 'Could not import settings. The file may be invalid.', 'one-click-start' ),
                'import_success' => __( 'Settings imported successfully! The page will now reload.', 'one-click-start' ),
                'you_may_close' => __( 'You may now close this window.', 'one-click-start' ),
                'review_thanks' => __( 'Thanks! If you enjoy One Click Start, please consider leaving a 5-star review.', 'one-click-start' ),
            ]
        ]);

        // Mark review prompt only after a successful run by detecting the success title in the modal.
        // This avoids showing the prompt on halted/error runs without editing the main JS file.
        $inline_hook = "(function(){\n  try {\n    if (window.ocsReviewPromptHooked) { return; }\n    window.ocsReviewPromptHooked = true;\n    var cfg = window.one_click_start_ajax_object || {};\n    var i18n = (cfg && cfg.i18n) || {};\n    var ajaxUrl = cfg.ajax_url;\n    var nonce = cfg.nonce;\n    if (!ajaxUrl || !nonce || !i18n || !i18n.all_tasks_complete) { return; }\n    var fired = false;\n    var check = function(){\n      var titleEl = document.querySelector('.ocs-modal-title');\n      if (!titleEl) { return; }\n      var txt = (titleEl.textContent || '').trim();\n      if (!fired && txt.indexOf(i18n.all_tasks_complete) !== -1) {\n        fired = true;\n        var fd = new FormData();\n        fd.append('action','one_click_start_set_review_prompt');\n        fd.append('nonce', nonce);\n        fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd });\n      }\n    };\n    var startObserve = function(){\n      var mo = new MutationObserver(check);\n      mo.observe(document.body, { subtree: true, childList: true, characterData: true });\n      check();\n    };\n    if (document.readyState === 'loading') {\n      document.addEventListener('DOMContentLoaded', startObserve, { once: true });\n    } else {\n      startObserve();\n    }\n  } catch(e) { /* no-op */ }\n})();";
        wp_add_inline_script($this->plugin_name, $inline_hook, 'after');
    }

    /**
     * Show a friendly review prompt once after a successful run.
     */
    public function maybe_show_review_prompt(): void {
        if ( get_option('one_click_start_show_review_prompt') ) {
            ?>
            <div class="notice notice-success is-dismissible ocs-review-notice">
                <p>
                    <strong><?php esc_html_e('🎉 You’re ready to start building!', 'one-click-start'); ?></strong>
                    <?php esc_html_e('If One Click Start saved you time, drop a quick ⭐⭐⭐⭐⭐ — it helps us keep improving.', 'one-click-start'); ?>
                    <a href="https://wordpress.org/support/plugin/one-click-start/reviews/#new-post" target="_blank" rel="noopener noreferrer" class="" style="margin-left:8px;">
                        <?php esc_html_e('Leave a Review', 'one-click-start'); ?>
                    </a>
                </p>
            </div>
            <script>
            (function(){
                // Auto-dismiss flag when the notice is dismissed by the user
                document.addEventListener('click', function(e){
                    if ( e.target && e.target.closest('.notice.is-dismissible .notice-dismiss') ) {
                        var fd = new FormData();
                        fd.append('action','one_click_start_dismiss_review_prompt');
                        fd.append('nonce','<?php echo esc_js( wp_create_nonce('one_click_start_ajax_nonce') ); ?>');
                        fetch('<?php echo esc_url( admin_url('admin-ajax.php') ); ?>', { method: 'POST', credentials: 'same-origin', body: fd });
                    }
                }, { passive: true });
            })();
            </script>
            <?php
        }
    }
}