<?php
/**
 * The template for displaying the recipe builder page.
 *
 * FILE: templates/recipe-builder-page.php
 */

$popular_plugins = [
    'utilities' => [
        'classic-editor' => 'Classic Editor',
        'updraftplus' => 'UpdraftPlus Backup',
        'wordfence' => 'Wordfence Security',
        // 'advanced-custom-fields' => 'Advanced Custom Fields',
        'woocommerce' => 'WooCommerce', 
    ],
    'forms' => [
        'contact-form-7' => 'Contact Form 7',
        'wpforms-lite' => 'WPForms Lite',
        'fluentform' => 'Fluent Forms',
        'formidable' => 'Formidable Forms', 
        // 'ninja-forms' => 'Ninja Forms', 
    ],
    'builders' => [
        'elementor' => 'Elementor',
        'beaver-builder-lite-version' => 'Beaver Builder',
        'ultimate-addons-for-gutenberg' => 'Spectra', 
    ],
    'seo' => [
        'seo-by-rank-math' => 'Rank Math SEO',
        'wordpress-seo' => 'Yoast SEO',
        'all-in-one-seo-pack' => 'All in One SEO',
    ],
    'performance' => [
        'litespeed-cache' => 'LiteSpeed Cache',
        'autoptimize' => 'Autoptimize',
        'w3-total-cache' => 'W3 Total Cache',
        'jetpack' => 'Jetpack',
    ],
    'miscellaneous' => [
        'code-snippets' => 'Code Snippets',
        'really-simple-ssl' => 'Really Simple SSL',
        'fluent-smtp' => 'Fluent SMTP',
		'redirection' => 'Redirection',
    ],
];

$starter_themes = [
    'astra' => 'Astra',
    'hello-elementor' => 'Hello Elementor',
    'kadence' => 'Kadence',
    'blocksy' => 'Blocksy',
    'generatepress' => 'GeneratePress',
    'oceanwp' => 'OceanWP',
    'storefront' => 'Storefront',
];

$recipe_data = get_option('ocs_saved_recipe', []);
?>

<div class="wrap ocs-wrap">
    <h1><span class="dashicons dashicons-controls-play ocs-title-icon"></span> <?php esc_html_e( 'One Click Start', 'ocs' ); ?></h1>
    <p><?php esc_html_e( 'Configure your standard setup tasks below. Save the recipe, then deploy it on this site.', 'ocs' ); ?></p>

    <div id="ocs-progress-modal" style="display:none;">
        <div id="ocs-progress-container">
            <div id="ocs-progress-bar-wrapper">
                <div id="ocs-progress-bar" style="width: 0%;"></div>
            </div>
            <div id="ocs-progress-text">0%</div>
            <h3 class="ocs-modal-title"><?php esc_html_e( 'Deployment in Progress...', 'ocs' ); ?></h3>
            <p class="ocs-modal-subtitle"><?php esc_html_e( 'Please do not close this window until the process is complete.', 'ocs' ); ?></p>
            <pre id="ocs-live-log"></pre>
        </div>
    </div>

    <div id="ocs-ajax-notice" class="notice" style="display:none; padding: 1rem;"></div>

    <form id="ocs-recipe-form" class="ocs-form">
        <div class="ocs-main-content">
            <div class="ocs-recipe-column">
                <!-- Section: Cleanup -->
                <div class="ocs-recipe-section">
                    <h3><span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Initial Cleanup', 'ocs' ); ?></h3>
                    <label class="ocs-checkbox"><input type="checkbox" name="cleanup[]" value="delete_post" <?php checked(in_array('delete_post', $recipe_data['cleanup'] ?? [])); ?>><span><?php esc_html_e( 'Delete "Hello World" post', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="cleanup[]" value="delete_page" <?php checked(in_array('delete_page', $recipe_data['cleanup'] ?? [])); ?>><span><?php esc_html_e( 'Delete "Sample Page"', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="cleanup[]" value="delete_default_comment" <?php checked(in_array('delete_default_comment', $recipe_data['cleanup'] ?? [])); ?>><span><?php esc_html_e( 'Delete default comment', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="cleanup[]" value="delete_hello_dolly" <?php checked(in_array('delete_hello_dolly', $recipe_data['cleanup'] ?? [])); ?>><span><?php esc_html_e( 'Delete Hello Dolly plugin', 'ocs' ); ?></span></label>
                </div>
                
                <!-- Section: Settings -->
                <div class="ocs-recipe-section">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Core Settings', 'ocs' ); ?></h3>
                    <label class="ocs-checkbox"><input type="checkbox" name="settings[]" value="discourage_search" <?php checked(in_array('discourage_search', $recipe_data['settings'] ?? [])); ?>><span><?php esc_html_e( 'Discourage search engines', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="settings[]" value="disable_xml_rpc" <?php checked(in_array('disable_xml_rpc', $recipe_data['settings'] ?? [])); ?>><span><?php esc_html_e( 'Disable XML-RPC', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="content[]" value="create_primary_menu" <?php checked(in_array('create_primary_menu', $recipe_data['content'] ?? [])); ?>><span><?php esc_html_e( 'Create a "Primary" menu', 'ocs' ); ?></span></label>
                </div>

                <!-- Section: Permalinks -->
                <div class="ocs-recipe-section">
                    <h3><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Permalinks', 'ocs' ); ?></h3>
                    <label class="ocs-radio"><input type="radio" name="permalink" value="/%postname%/" <?php checked('/%postname%/' === ($recipe_data['permalink'] ?? '')); ?>><span><?php esc_html_e( 'Post Name', 'ocs' ); ?></span></label>
                    <label class="ocs-radio"><input type="radio" name="permalink" value="/%year%/%monthnum%/%postname%/" <?php checked('/%year%/%monthnum%/%postname%/' === ($recipe_data['permalink'] ?? '')); ?>><span><?php esc_html_e( 'Month and Name', 'ocs' ); ?></span></label>
                </div>

                <!-- Section: Discussion -->
                <div class="ocs-recipe-section">
                    <h3><span class="dashicons dashicons-admin-comments"></span> <?php esc_html_e( 'Discussion Settings', 'ocs' ); ?></h3>
                    <label class="ocs-checkbox"><input type="checkbox" name="settings[]" value="disable_comments" <?php checked( in_array( 'disable_comments', $recipe_data['settings'] ?? [], true ) ); ?>><span><?php esc_html_e( 'Disable comments globally', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="settings[]" value="disable_pingbacks" <?php checked(in_array('disable_pingbacks', $recipe_data['settings'] ?? [])); ?>><span><?php esc_html_e( 'Disable pingbacks & trackbacks', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="settings[]" value="comment_approval" <?php checked(in_array('comment_approval', $recipe_data['settings'] ?? [])); ?>><span><?php esc_html_e( 'Comments must be manually approved', 'ocs' ); ?></span></label>
                    <label class="ocs-checkbox"><input type="checkbox" name="settings[]" value="comment_moderation" <?php checked(in_array('comment_moderation', $recipe_data['settings'] ?? [])); ?>><span><?php esc_html_e( 'Hold comments with 1+ links', 'ocs' ); ?></span></label>
                </div>
            </div>
            <div class="ocs-recipe-column">
                <!-- Section: Plugins -->
                <div class="ocs-recipe-section">
                    <h3><span class="dashicons dashicons-admin-plugins"></span> <?php esc_html_e( 'Install Plugins', 'ocs' ); ?> <span class="ocs-plugin-limit"><?php esc_html_e( 'Max 5', 'one-click-start' ); ?></span></h3>
                    <div class="ocs-select-grid">
    <?php foreach ( $popular_plugins as $group => $plugins ) : ?>
        <div class="ocs-select-group">
            <?php
            // Logic to handle special group names.
            $group_name = 'seo' === $group ? 'SEO' : ucfirst( str_replace( '_', ' ', $group ) );
            ?>
            <h4><?php echo esc_html( $group_name ); ?></h4>
            <?php foreach ( $plugins as $slug => $name ) : ?>
                <label class="ocs-checkbox"><input type="checkbox" name="plugins[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $recipe_data['plugins'] ?? [], true ) ); ?>><span><?php echo esc_html( $name ); ?></span></label>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
                </div>
                
                <!-- Section: Themes -->
<div class="ocs-recipe-section">
    <h3><span class="dashicons dashicons-admin-appearance"></span> <?php esc_html_e( 'Install & Activate Theme', 'ocs' ); ?></h3>
    <div class="ocs-theme-grid">
        <!-- The "None" option -->
        <label class="ocs-radio-image">
            <input type="radio" name="theme" value="" <?php checked( '', $recipe_data['theme'] ?? '' ); ?>>
            <div class="ocs-no-theme"><span><?php esc_html_e('None', 'ocs'); ?></span></div>
        </label>
        <!-- The theme options -->
        <?php foreach ( $starter_themes as $slug => $name ) : ?>
            <label class="ocs-radio-image">
                <input type="radio" name="theme" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, $recipe_data['theme'] ?? '' ); ?>>
                <span><?php echo esc_html( $name ); ?></span>
            </label>
        <?php endforeach; ?>
    </div>
</div>
            </div>
        </div>
        <div class="ocs-sidebar">
            <div class="ocs-actions ocs-recipe-section">
                <h3><?php esc_html_e('Actions', 'ocs'); ?></h3>
                <button type="submit" id="ocs-save-recipe-btn" class="button button-large"><?php esc_html_e( 'Save Recipe', 'ocs' ); ?></button>
                <button type="button" id="ocs-deploy-recipe-btn" class="button button-primary button-large"><?php esc_html_e( 'Deploy This Recipe', 'ocs' ); ?></button>
            </div>
            <div class="ocs-import-export ocs-recipe-section">
                 <h3><?php esc_html_e('Import / Export', 'ocs'); ?></h3>
                 <p><?php esc_html_e('Save your recipe to a file or load one from your computer.', 'ocs'); ?></p>
                 <div class="ocs-import-export-buttons">
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'ocs_export_recipe', 'nonce' => wp_create_nonce('ocs_export_nonce')])); ?>" id="ocs-export-btn" class="button button-small"><span class="dashicons dashicons-download"></span> <?php esc_html_e('Export Recipe', 'ocs'); ?></a>
                    <input type="file" id="ocs-import-file" style="display:none;" accept=".json">
                    <button type="button" id="ocs-import-btn" class="button button-small"><span class="dashicons dashicons-upload"></span> <?php esc_html_e('Import Recipe', 'ocs'); ?></button>
                </div>
            </div>
             <div class="ocs-support ocs-recipe-section">
                <h3><?php esc_html_e('Support This Plugin', 'ocs'); ?></h3>
                <p><?php esc_html_e('If you find this plugin useful, please consider supporting its development.', 'ocs'); ?></p>
                <div class="ocs-support-buttons">
                    <a href="https://wordpress.org/support/plugin/one-click-start/reviews/#new-post" target="_blank" class="button button-small">
                        <span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Leave a Review', 'ocs'); ?>
                    </a>
                    <a href="https://wordpress.org/plugin/one-click-start/" target="_blank" class="button button-small">
                         <span class="dashicons dashicons-money-alt"></span> <?php esc_html_e('Donate', 'ocs'); ?>
                    </a>
                </div>
            </div>
            <div class="ocs-support ocs-recipe-section">
    <h3><?php esc_html_e('Need Help?', 'ocs'); ?></h3>
    <p><?php esc_html_e('If you have an issue or a question, please visit the support forum.', 'ocs'); ?></p>
    <a href="https://wordpress.org/support/plugin/one-click-start/" target="_blank" class="button button-secondary button-large" style="width:100%; text-align:center;">
        <span class="dashicons dashicons-editor-help"></span> <?php esc_html_e('Get Help', 'ocs'); ?>
    </a>
</div>
        </div>
    </form>
</div>