<?php
/**
 * Handles all AJAX requests for the plugin.
 *
 * @since      1.0.0
 * @package    One_Click_Start
 * @author     haas_ib
 *
 * FILE: includes/class-ocs-ajax.php
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    die;
}

class OCS_Ajax {

    public function save_recipe(): void {
        check_ajax_referer('ocs_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __( 'Permission denied.', 'ocs' )]);
        }

        $form_data_raw = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : '';
        if (empty($form_data_raw)) {
            wp_send_json_error(['message' => __( 'Missing form data.', 'ocs' )]);
        }
        parse_str($form_data_raw, $form_data);

        $sanitized_data = [
            'cleanup'   => isset($form_data['cleanup']) ? array_map('sanitize_text_field', $form_data['cleanup']) : [],
            'permalink' => isset($form_data['permalink']) ? sanitize_text_field($form_data['permalink']) : '',
            'settings'  => isset($form_data['settings']) ? array_map('sanitize_text_field', $form_data['settings']) : [],
            'content'   => isset($form_data['content']) ? array_map('sanitize_text_field', $form_data['content']) : [],
            'plugins'   => isset($form_data['plugins']) ? array_map('sanitize_text_field', $form_data['plugins']) : [],
            'theme'     => isset($form_data['theme']) ? sanitize_text_field($form_data['theme']) : '',
        ];

        update_option('ocs_saved_recipe', $sanitized_data);
        wp_send_json_success(['message' => __( 'Recipe saved successfully!', 'ocs' )]);
    }

    public function execute_task(): void {
        check_ajax_referer('ocs_ajax_nonce', 'nonce');

        $task_details = isset($_POST['task_details']) ? wp_unslash($_POST['task_details']) : null;
        if (empty($task_details) || !is_array($task_details) || empty($task_details['task'])) {
            wp_send_json_error(['message' => __( 'Invalid task specified.', 'ocs' )]);
        }

        $capability_map = [
            'install_plugin' => 'install_plugins',
            'activate_plugin' => 'activate_plugins',
            'install_theme' => 'install_themes',
        ];
        $required_cap = $capability_map[$task_details['task']] ?? 'manage_options';

        if (!current_user_can($required_cap)) {
            wp_send_json_error(['message' => sprintf(__( "Permission denied. Requires '%s' capability.", 'ocs' ), $required_cap)]);
        }

        if (!class_exists('OCS_Recipe_Handler')) {
            wp_send_json_error(['message' => __( 'Recipe handler class missing.', 'ocs' )]);
        }

        $handler = new OCS_Recipe_Handler();
        $result = $handler->execute_single_task($task_details['task'], $task_details['value']);

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
    
    public function import_recipe(): void {
        check_ajax_referer('ocs_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __( 'Permission denied.', 'one-click-start' )]);
        }
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __( 'File upload error.', 'one-click-start' )]);
        }

        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($file_content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            // Sanitize all imported data before saving
            $sanitized_data = [
                'cleanup'   => isset($data['cleanup']) ? array_map('sanitize_text_field', $data['cleanup']) : [],
                'permalink' => isset($data['permalink']) ? sanitize_text_field($data['permalink']) : '',
                'settings'  => isset($data['settings']) ? array_map('sanitize_text_field', $data['settings']) : [],
                'content'   => isset($data['content']) ? array_map('sanitize_text_field', $data['content']) : [],
                'plugins'   => isset($data['plugins']) ? array_map('sanitize_text_field', $data['plugins']) : [],
                'theme'     => isset($data['theme']) ? sanitize_text_field($data['theme']) : '',
            ];
            update_option('ocs_saved_recipe', $sanitized_data);
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}