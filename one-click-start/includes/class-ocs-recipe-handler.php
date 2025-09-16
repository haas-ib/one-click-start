<?php
/**
 * The main engine that executes the recipe tasks.
 *
 * @since      1.0.0
 * @package    One_Click_Start
 * @author     haas_ib
 *
 * FILE: includes/class-ocs-recipe-handler.php
 */
declare(strict_types=1);

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

class One_Click_Start_Recipe_Handler {

    public function execute_single_task(string $task, $value): array {
        switch ($task) {
            case 'cleanup':         return $this->do_cleanup($value);
            case 'permalink':       return $this->set_permalink($value);
            case 'setting':         return $this->set_setting($value);
            case 'content':         return $this->do_content_setup($value);
            case 'install_plugin':  return $this->install_plugin($value);
            case 'activate_plugin': return $this->activate_plugin($value);
            case 'install_theme':   return $this->install_theme($value);
            /* translators: %s: The name of the unknown task. */
            default:                return ['success' => false, 'message' => sprintf(__( 'Unknown task: %s', 'one-click-start' ), $task )];
        }
    }

    private function do_cleanup(string $type): array {
        $cleanup_map = [
            'delete_post' => fn() => $this->delete_post_by_path('hello-world', 'post', __( '"Hello World" post', 'one-click-start' )),
            'delete_page' => fn() => $this->delete_post_by_path('sample-page', 'page', __( '"Sample Page"', 'one-click-start' )),
            'delete_default_comment' => function() {
                $comment = get_comments(['number' => 1, 'author_email' => 'mrwordpress@wordpress.org']);
                if (!empty($comment)) {
                    wp_delete_comment($comment[0]->comment_ID, true);
                    return ['success' => true, 'message' => __( 'Deleted the default comment.', 'one-click-start' )];
                }
                return ['success' => true, 'message' => __( 'Skipped: Default comment not found.', 'one-click-start' )];
            },
            'delete_hello_dolly' => fn() => $this->delete_plugin('hello.php', __( 'Hello Dolly', 'one-click-start' )),
        ];
        /* translators: %s: The name of the unknown cleanup task. */
        return isset($cleanup_map[$type]) ? $cleanup_map[$type]() : ['success' => false, 'message' => sprintf( __( 'Unknown cleanup task: %s', 'one-click-start' ), $type )];
    }

    private function set_permalink(string $structure): array {
        if (get_option('permalink_structure') === $structure) {
            return ['success' => true, 'message' => __( 'Skipped: Permalink structure already set.', 'one-click-start' )];
        }
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure($structure);
        flush_rewrite_rules();
        /* translators: %s: The permalink structure (e.g., '/%postname%/'). */
        return ['success' => true, 'message' => sprintf( __( 'Permalink structure set to: %s', 'one-click-start' ), $structure )];
    }

    private function set_setting(string $setting): array {
        $settings_map = [
            'disable_comments' => function() {
				if ('closed' == get_option('default_comment_status')) return ['success' => true, 'message' => __( 'Skipped: Comments already disabled.', 'one-click-start' )];
				update_option('default_comment_status', 'closed');
				return ['success' => true, 'message' => __( 'Disabled comments globally.', 'one-click-start' )];
			},
            'disable_pingbacks' => function() {
                if (get_option('default_ping_status') === 'closed') return ['success' => true, 'message' => __( 'Skipped: Pingbacks already disabled.', 'one-click-start' )];
                update_option('default_ping_status', 'closed');
                return ['success' => true, 'message' => __( 'Disabled pingbacks on new posts.', 'one-click-start' )];
            },
            'discourage_search' => function() {
                if ('0' == get_option('blog_public')) return ['success' => true, 'message' => __( 'Skipped: Search engine indexing already discouraged.', 'one-click-start' )];
                update_option('blog_public', '0');
                return ['success' => true, 'message' => __( 'Discouraging search engines from indexing.', 'one-click-start' )];
            },
            'comment_approval' => function() {
                if ('1' == get_option('comment_moderation')) return ['success' => true, 'message' => __( 'Skipped: Comment manual approval already enabled.', 'one-click-start' )];
                update_option('comment_moderation', '1');
                return ['success' => true, 'message' => __( 'Enabled: Comments must be manually approved.', 'one-click-start' )];
            },
            'comment_moderation' => function() {
                if ('1' == get_option('comment_max_links')) return ['success' => true, 'message' => __( 'Skipped: Comment link moderation already enabled.', 'one-click-start' )];
                update_option('comment_max_links', '1');
                return ['success' => true, 'message' => __( 'Enabled: Comments with 1+ links will be held for moderation.', 'one-click-start' )];
            },
            'disable_xml_rpc' => function() {
                if (!get_option('enable_xmlrpc')) return ['success' => true, 'message' => __( 'Skipped: XML-RPC is already disabled.', 'one-click-start' )];
                update_option('enable_xmlrpc', false);
                return ['success' => true, 'message' => __( 'Disabled XML-RPC.', 'one-click-start' )];
            }
        ];
        /* translators: %s: The name of an unknown setting. */
        return isset($settings_map[$setting]) ? $settings_map[$setting]() : ['success' => false, 'message' => sprintf( __( 'Unknown setting: %s', 'one-click-start' ), $setting )];
    }
    
    private function do_content_setup(string $task): array {
        if ($task === 'create_primary_menu') {
            if (is_nav_menu('Primary')) return ['success' => true, 'message' => __( 'Skipped: A menu named "Primary" already exists.', 'one-click-start' )];
            wp_create_nav_menu('Primary');
            return ['success' => true, 'message' => __( 'Created a new menu named "Primary".', 'one-click-start' )];
        }
        /* translators: %s: The name of an unknown content task. */
        return ['success' => false, 'message' => sprintf( __( 'Unknown content task: %s', 'one-click-start' ), $task )];
    }
    
    private function install_plugin(string $slug): array {
        global $wp_filesystem;
        if (is_null($wp_filesystem)) {
            require_once (ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        if (is_multisite() && !current_user_can('manage_network_plugins')) return ['success' => false, 'message' => __( 'Plugin installation not allowed in this multisite environment.', 'one-click-start' )];
        if (!$wp_filesystem->is_writable(wp_normalize_path(WP_PLUGIN_DIR))) return ['success' => false, 'message' => __( 'Plugin directory is not writable.', 'one-click-start' )];

        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        $plugin_file = $this->find_plugin_file($slug);
        /* translators: %s: The plugin's slug or name. */
        if ($plugin_file && $wp_filesystem->exists(wp_normalize_path(WP_PLUGIN_DIR . '/' . $plugin_file))) return ['success' => true, 'message' => sprintf( __( "Skipped: Plugin '%s' already installed.", 'one-click-start' ), $slug )];

        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

        $api = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['short_description' => false, 'sections' => false]]);
        if (is_wp_error($api)) {
            /* translators: %1$s: The plugin's slug or name. %2$s: The error message from the API. */
            return ['success' => false, 'message' => sprintf( __( "Plugin '%1\$s': %2\$s", 'one-click-start' ), $slug, $api->get_error_message() )];
        }

        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        ob_start();
        $result = $upgrader->install($api->download_link);
        ob_end_clean();
        
        if (is_wp_error($result)) {
            /* translators: %1$s: The plugin's slug or name. %2$s: The installation error message. */
            return ['success' => false, 'message' => sprintf( __( "Install failed for '%1\$s': %2\$s", 'one-click-start' ), $slug, $result->get_error_message() )];
        }
        /* translators: %s: The plugin's slug or name. */
        return ['success' => true, 'message' => sprintf( __( 'Installed plugin: %s', 'one-click-start' ), $slug )];
    }

    private function activate_plugin(string $slug): array {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $plugin_file = $this->find_plugin_file($slug);
        /* translators: %s: The plugin's slug or name. */
        if (!$plugin_file) return ['success' => false, 'message' => sprintf( __( "Activation failed: Could not find main file for '%s'.", 'one-click-start' ), $slug )];
        /* translators: %s: The plugin's slug or name. */
        if (is_plugin_active($plugin_file)) return ['success' => true, 'message' => sprintf( __( "Skipped: Plugin '%s' is already active.", 'one-click-start' ), $slug )];
        
        ob_start();
        $result = activate_plugin($plugin_file, '', false, true);
        ob_end_clean();

        if (is_wp_error($result)) {
            /* translators: %1$s: The plugin's slug or name. %2$s: The activation error message. */
            return ['success' => false, 'message' => sprintf( __( "Activation failed for '%1\$s': %2\$s", 'one-click-start' ), $slug, $result->get_error_message() )];
        }
        /* translators: %s: The plugin's slug or name. */
        return ['success' => true, 'message' => sprintf( __( 'Activated plugin: %s', 'one-click-start' ), $slug )];
    }
    
    private function install_theme(string $slug): array {
        require_once(ABSPATH . 'wp-admin/includes/theme.php');
        /* translators: %s: The theme's slug or name. */
        if (wp_get_theme($slug)->exists()) return ['success' => true, 'message' => sprintf(__( "Skipped: Theme '%s' already installed.", 'one-click-start' ), $slug)];
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        
        $api = themes_api('theme_information', ['slug' => $slug]);
        if (is_wp_error($api)) {
            /* translators: %1$s: The theme's slug or name. %2$s: The error message from the API. */
            return ['success' => false, 'message' => sprintf(__( "Theme '%1\$s': %2\$s", 'one-click-start' ), $slug, $api->get_error_message())];
        }
        
        $upgrader = new Theme_Upgrader(new WP_Ajax_Upgrader_Skin());
        ob_start();
        $result = $upgrader->install($api->download_link);
        ob_end_clean();
        
        if (is_wp_error($result)) {
            /* translators: %1$s: The theme's slug or name. %2$s: The installation error message. */
            return ['success' => false, 'message' => sprintf(__( "Install failed for theme '%1\$s': %2\$s", 'one-click-start' ), $slug, $result->get_error_message())];
        }
        
        switch_theme($slug);
        /* translators: %s: The theme's slug or name. */
        return ['success' => true, 'message' => sprintf(__( 'Installed and activated theme: %s', 'one-click-start' ), $slug)];
    }

    private function delete_post_by_path(string $path, string $type, string $name): array {
        $post = get_page_by_path($path, OBJECT, $type);
        if ($post) {
            wp_delete_post($post->ID, true);
            /* translators: %s: The name of the post or page being deleted (e.g., '"Hello World" post'). */
            return ['success' => true, 'message' => sprintf(__( 'Deleted %s.', 'one-click-start' ), $name)];
        }
        /* translators: %s: The name of the post or page that was not found (e.g., '"Sample Page"'). */
        return ['success' => true, 'message' => sprintf(__( 'Skipped: %s not found.', 'one-click-start' ), $name)];
    }

    private function delete_plugin(string $plugin_path, string $name): array {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (file_exists(wp_normalize_path(WP_PLUGIN_DIR . '/' . $plugin_path))) {
            delete_plugins([$plugin_path]);
            /* translators: %s: The name of the plugin being deleted (e.g., 'Hello Dolly'). */
            return ['success' => true, 'message' => sprintf(__( 'Deleted %s plugin.', 'one-click-start' ), $name)];
        }
        /* translators: %s: The name of the plugin that was not found (e.g., 'Hello Dolly'). */
        return ['success' => true, 'message' => sprintf(__( 'Skipped: %s plugin not found.', 'one-click-start' ), $name)];
    }

    private function find_plugin_file(string $slug): ?string {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            // Check if the plugin slug matches the directory name or the main file name (without .php)
            if ( dirname( $plugin_file ) === $slug || strtok( $plugin_file, '/' ) === $slug . '.php' ) {
                return $plugin_file;
            }
        }
        return null;
    }
}