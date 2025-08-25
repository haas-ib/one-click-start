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

class OCS_Recipe_Handler {

    public function execute_single_task(string $task, $value): array {
        switch ($task) {
            case 'cleanup':         return $this->do_cleanup($value);
            case 'permalink':       return $this->set_permalink($value);
            case 'setting':         return $this->set_setting($value);
            case 'content':         return $this->do_content_setup($value);
            case 'install_plugin':  return $this->install_plugin($value);
            case 'activate_plugin': return $this->activate_plugin($value);
            case 'install_theme':   return $this->install_theme($value);
            default:                return ['success' => false, 'message' => sprintf(__( 'Unknown task: %s', 'ocs' ), $task )];
        }
    }

    private function do_cleanup(string $type): array {
        $cleanup_map = [
            'delete_post' => fn() => $this->delete_post_by_path('hello-world', 'post', __( '"Hello World" post', 'ocs' )),
            'delete_page' => fn() => $this->delete_post_by_path('sample-page', 'page', __( '"Sample Page"', 'ocs' )),
            'delete_default_comment' => function() {
                $comment = get_comments(['number' => 1, 'author_email' => 'mrwordpress@wordpress.org']);
                if (!empty($comment)) {
                    wp_delete_comment($comment[0]->comment_ID, true);
                    return ['success' => true, 'message' => __( 'Deleted the default comment.', 'ocs' )];
                }
                return ['success' => true, 'message' => __( 'Skipped: Default comment not found.', 'ocs' )];
            },
            'delete_hello_dolly' => fn() => $this->delete_plugin('hello.php', __( 'Hello Dolly', 'ocs' )),
        ];
        return isset($cleanup_map[$type]) ? $cleanup_map[$type]() : ['success' => false, 'message' => sprintf( __( 'Unknown cleanup task: %s', 'ocs' ), $type )];
    }

    private function set_permalink(string $structure): array {
        if (get_option('permalink_structure') === $structure) {
            return ['success' => true, 'message' => __( 'Skipped: Permalink structure already set.', 'ocs' )];
        }
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure($structure);
        flush_rewrite_rules();
        return ['success' => true, 'message' => sprintf( __( 'Permalink structure set to: %s', 'ocs' ), $structure )];
    }

    private function set_setting(string $setting): array {
        $settings_map = [
            'disable_comments' => function() {
    if ('closed' == get_option('default_comment_status')) return ['success' => true, 'message' => __( 'Skipped: Comments already disabled.', 'ocs' )];
    update_option('default_comment_status', 'closed');
    return ['success' => true, 'message' => __( 'Disabled comments globally.', 'ocs' )];
},
            'disable_pingbacks' => function() {
                if (get_option('default_ping_status') === 'closed') return ['success' => true, 'message' => __( 'Skipped: Pingbacks already disabled.', 'ocs' )];
                update_option('default_ping_status', 'closed');
                return ['success' => true, 'message' => __( 'Disabled pingbacks on new posts.', 'ocs' )];
            },
            'discourage_search' => function() {
                if ('0' == get_option('blog_public')) return ['success' => true, 'message' => __( 'Skipped: Search engine indexing already discouraged.', 'ocs' )];
                update_option('blog_public', '0');
                return ['success' => true, 'message' => __( 'Discouraging search engines from indexing.', 'ocs' )];
            },
            'comment_approval' => function() {
                if ('1' == get_option('comment_moderation')) return ['success' => true, 'message' => __( 'Skipped: Comment manual approval already enabled.', 'ocs' )];
                update_option('comment_moderation', '1');
                return ['success' => true, 'message' => __( 'Enabled: Comments must be manually approved.', 'ocs' )];
            },
            'comment_moderation' => function() {
                if ('1' == get_option('comment_max_links')) return ['success' => true, 'message' => __( 'Skipped: Comment link moderation already enabled.', 'ocs' )];
                update_option('comment_max_links', '1');
                return ['success' => true, 'message' => __( 'Enabled: Comments with 1+ links will be held for moderation.', 'ocs' )];
            },
            'disable_xml_rpc' => function() {
                if (!get_option('enable_xmlrpc')) return ['success' => true, 'message' => __( 'Skipped: XML-RPC is already disabled.', 'ocs' )];
                update_option('enable_xmlrpc', false);
                return ['success' => true, 'message' => __( 'Disabled XML-RPC.', 'ocs' )];
            }
        ];
        return isset($settings_map[$setting]) ? $settings_map[$setting]() : ['success' => false, 'message' => sprintf( __( 'Unknown setting: %s', 'ocs' ), $setting )];
    }
    
    private function do_content_setup(string $task): array {
        if ($task === 'create_primary_menu') {
            if (is_nav_menu('Primary')) return ['success' => true, 'message' => __( 'Skipped: A menu named "Primary" already exists.', 'ocs' )];
            wp_create_nav_menu('Primary');
            return ['success' => true, 'message' => __( 'Created a new menu named "Primary".', 'ocs' )];
        }
        return ['success' => false, 'message' => sprintf( __( 'Unknown content task: %s', 'ocs' ), $task )];
    }
    
    private function install_plugin(string $slug): array {
        global $wp_filesystem;
        if (is_null($wp_filesystem)) {
            require_once (ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        if (is_multisite() && !current_user_can('manage_network_plugins')) return ['success' => false, 'message' => __( 'Plugin installation not allowed in this multisite environment.', 'ocs' )];
        if (!$wp_filesystem->is_writable(WP_PLUGIN_DIR)) return ['success' => false, 'message' => __( 'Plugin directory is not writable.', 'ocs' )];

        include_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        $plugin_file = $this->find_plugin_file($slug);
        if ($plugin_file && $wp_filesystem->exists(WP_PLUGIN_DIR . '/' . $plugin_file)) return ['success' => true, 'message' => sprintf( __( "Skipped: Plugin '%s' already installed.", 'ocs' ), $slug )];

        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

        $api = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['short_description' => false, 'sections' => false]]);
        if (is_wp_error($api)) {
            // translators: 1: Plugin slug, 2: Error message.
            return ['success' => false, 'message' => sprintf( __( "Plugin '%1\$s': %2\$s", 'ocs' ), $slug, $api->get_error_message() )];
        }

        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);
        if (is_wp_error($result)) {
            // translators: 1: Plugin slug, 2: Error message.
            return ['success' => false, 'message' => sprintf( __( "Install failed for '%1\$s': %2\$s", 'ocs' ), $slug, $result->get_error_message() )];
        }
        
        return ['success' => true, 'message' => sprintf( __( 'Installed plugin: %s', 'ocs' ), $slug )];
    }

    private function activate_plugin(string $slug): array {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $plugin_file = $this->find_plugin_file($slug);
        if (!$plugin_file) return ['success' => false, 'message' => sprintf( __( "Activation failed: Could not find main file for '%s'.", 'ocs' ), $slug )];
        if (is_plugin_active($plugin_file)) return ['success' => true, 'message' => sprintf( __( "Skipped: Plugin '%s' is already active.", 'ocs' ), $slug )];
        
        $result = activate_plugin($plugin_file, '', false, true);
        if (is_wp_error($result)) {
            // translators: 1: Plugin slug, 2: Error message.
            return ['success' => false, 'message' => sprintf( __( "Activation failed for '%1\$s': %2\$s", 'ocs' ), $slug, $result->get_error_message() )];
        }
        
        return ['success' => true, 'message' => sprintf( __( 'Activated plugin: %s', 'ocs' ), $slug )];
    }
    
    private function install_theme(string $slug): array {
        include_once(ABSPATH . 'wp-admin/includes/theme.php');
        if (wp_get_theme($slug)->exists()) return ['success' => true, 'message' => sprintf(__( "Skipped: Theme '%s' already installed.", 'ocs' ), $slug)];
        
        include_once(ABSPATH . 'wp-admin/includes/file.php');
        include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        
        $api = themes_api('theme_information', ['slug' => $slug]);
        if (is_wp_error($api)) {
            // translators: 1: Theme slug, 2: Error message.
            return ['success' => false, 'message' => sprintf(__( "Theme '%1\$s': %2\$s", 'ocs' ), $slug, $api->get_error_message())];
        }
        
        $upgrader = new Theme_Upgrader(new WP_Ajax_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);
        
        if (is_wp_error($result)) {
            // translators: 1: Theme slug, 2: Error message.
            return ['success' => false, 'message' => sprintf(__( "Install failed for theme '%1\$s': %2\$s", 'ocs' ), $slug, $result->get_error_message())];
        }
        
        switch_theme($slug);
        return ['success' => true, 'message' => sprintf(__( 'Installed and activated theme: %s', 'ocs' ), $slug)];
    }

    private function delete_post_by_path(string $path, string $type, string $name): array {
        $post = get_page_by_path($path, OBJECT, $type);
        if ($post) {
            wp_delete_post($post->ID, true);
            return ['success' => true, 'message' => sprintf(__( 'Deleted %s.', 'ocs' ), $name)];
        }
        return ['success' => true, 'message' => sprintf(__( 'Skipped: %s not found.', 'ocs' ), $name)];
    }

    private function delete_plugin(string $plugin_path, string $name): array {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
            delete_plugins([$plugin_path]);
            return ['success' => true, 'message' => sprintf(__( 'Deleted %s plugin.', 'ocs' ), $name)];
        }
        return ['success' => true, 'message' => sprintf(__( 'Skipped: %s plugin not found.', 'ocs' ), $name)];
    }

    private function find_plugin_file(string $slug): ?string {
        $plugin_file_path = WP_PLUGIN_DIR . '/' . $slug;
        if (!is_dir($plugin_file_path)) return null;
        if (file_exists($plugin_file_path . '/' . $slug . '.php')) return $slug . '/' . $slug . '.php';

        $files = scandir($plugin_file_path);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $plugin_data = get_plugin_data($plugin_file_path . '/' . $file);
                if (!empty($plugin_data['Name'])) {
                    return $slug . '/' . $file;
                }
            }
        }
        return null;
    }
}