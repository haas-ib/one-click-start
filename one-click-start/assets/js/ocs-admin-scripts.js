/**
 * JavaScript for the admin interface.
 *
 * FILE: assets/js/ocs-admin-scripts.js
 */
 (function($) {
    'use strict';

    let taskQueue = [];
    let totalTasks = 0;
    const i18n = one_click_start_ajax_object.i18n;

    $(function() {
        // Handle theme selection visibility
        $('.ocs-radio-image input').on('change', function() {
            $('.ocs-radio-image').removeClass('ocs-radio-image-selected');
            if ($(this).is(':checked')) {
                $(this).closest('.ocs-radio-image').addClass('ocs-radio-image-selected');
            }
        });
        $('.ocs-radio-image input:checked').trigger('change');

        // Handle Save Recipe
        $('#ocs-recipe-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $notice = $('#ocs-ajax-notice');
            const $button = $('#ocs-save-recipe-btn');

            $.ajax({
                url: one_click_start_ajax_object.ajax_url,
                type: 'POST',
                data: { action: 'one_click_start_save_recipe', nonce: one_click_start_ajax_object.nonce, form_data: $form.serialize() },
                beforeSend: function() {
                    $button.prop('disabled', true);
                    $notice.text(i18n.saving).removeClass('notice-error notice-success').addClass('notice-info').show();
                },
                success: function(response) {
                    const message = response.success ? response.data.message : i18n.error_prefix + response.data.message;
                    const noticeClass = response.success ? 'notice-success' : 'notice-error';
                    $notice.text(message).removeClass('notice-info notice-error notice-success').addClass(noticeClass);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    setTimeout(() => $notice.fadeOut(), 4000);
                }
            });
        });
        
        // This JS-based export handles exporting the CURRENT state of the form, which differs from the PHP which exports the SAVED state.
        // We are leaving this as is to preserve existing functionality.
        $('#ocs-export-btn').on('click', function(e) {
            // Check if the click is from the link itself and not a programmatic trigger
            if (e.originalEvent) {
                e.preventDefault(); 
    
                const $form = $('#ocs-recipe-form');
                let recipeData = {
                    'cleanup': [],
                    'settings': [],
                    'content': [],
                    'plugins': [],
                    'permalink': $form.find('input[name="permalink"]:checked').val() || '',
                    'theme': $form.find('input[name="theme"]:checked').val() || ''
                };
    
                // Manually loop through checkboxes to build clean arrays.
                $form.find('input[name="cleanup[]"]:checked').each(function() {
                    recipeData.cleanup.push($(this).val());
                });
                $form.find('input[name="settings[]"]:checked').each(function() {
                    recipeData.settings.push($(this).val());
                });
                $form.find('input[name="content[]"]:checked').each(function() {
                    recipeData.content.push($(this).val());
                });
                $form.find('input[name="plugins[]"]:checked').each(function() {
                    recipeData.plugins.push($(this).val());
                });
    
                const jsonString = JSON.stringify(recipeData, null, 2);
                const blob = new Blob([jsonString], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
    
                const a = document.createElement('a');
                a.href = url;
                a.download = 'one-click-start-recipe.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        });

        // Handle Deploy Recipe
        $('#ocs-deploy-recipe-btn').on('click', function() {
            if ( $('input[name="plugins[]"]:checked').length > 5 ) {
                alert(i18n.plugin_limit_exceeded);
                return;
            }
            if (!confirm(i18n.confirm_deploy)) return;
            
            buildTaskQueue();
            
            if (taskQueue.length > 0) {
                $('#ocs-progress-modal').fadeIn();
                $('#ocs-live-log').empty();
                $('.ocs-actions .button').prop('disabled', true);
                processNextTask();
            } else {
                alert(i18n.no_tasks);
            }
        });
        
        // Handle Import
        $('#ocs-import-btn').on('click', function() {
            $('#ocs-import-file').click();
        });
        
        $('#ocs-import-file').on('change', function(e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'one_click_start_import_recipe');
            formData.append('nonce', one_click_start_ajax_object.nonce);
            formData.append('import_file', file);

            $.ajax({
                url: one_click_start_ajax_object.ajax_url,
                type: 'POST',
                data: formData,
                processData: false, 
                contentType: false, 
                success: function(response) {
                    if (response.success) {
                        alert(i18n.import_success);
                        location.reload();
                    } else {
                        alert(i18n.import_error);
                    }
                },
                error: function() {
                    alert(i18n.import_error);
                }
            });
        });

        function buildTaskQueue() {
            taskQueue = [];
            const $form = $('#ocs-recipe-form');

            $form.find('input[name="cleanup[]"]:checked').each(function() { taskQueue.push({ task: 'cleanup', value: $(this).val() }); });
            $form.find('input[name="settings[]"]:checked').each(function() { taskQueue.push({ task: 'setting', value: $(this).val() }); });
            $form.find('input[name="content[]"]:checked').each(function() { taskQueue.push({ task: 'content', value: $(this).val() }); });
            
            const permalink = $form.find('input[name="permalink"]:checked').val();
            if (permalink) { taskQueue.push({ task: 'permalink', value: permalink }); }
            
            const theme = $form.find('input[name="theme"]:checked').val();
            if (theme) { taskQueue.push({ task: 'install_theme', value: theme }); }

            $form.find('input[name="plugins[]"]:checked').each(function() {
                taskQueue.push({ task: 'install_plugin', value: $(this).val() });
                taskQueue.push({ task: 'activate_plugin', value: $(this).val() });
            });
            
            totalTasks = taskQueue.length;
        }

        function processNextTask() {
            if (taskQueue.length === 0) {
                const successIcon = '<span class="dashicons dashicons-yes-alt"></span>';
                updateLog(successIcon + ' ' + i18n.all_tasks_complete);
                $('.ocs-modal-title').html(successIcon + ' ' + i18n.all_tasks_complete);
                $('.ocs-modal-subtitle').html(
                    $('<a>').attr({href: '#', id: 'ocs-close-modal'}).text(i18n.you_may_close)
                );
                return;
            }

            const task = taskQueue.shift();
            
            $.ajax({
                url: one_click_start_ajax_object.ajax_url,
                type: 'POST',
                data: { action: 'one_click_start_execute_task', nonce: one_click_start_ajax_object.nonce, task_details: task },
                success: function(response) {
                    const icon = response.success ? '<span class="dashicons dashicons-yes-alt"></span>' : '<span class="dashicons dashicons-dismiss"></span>';
                    updateLog(icon + ' ' + response.data.message);
                    updateProgress();
                    
                    if (response.success) {
                        processNextTask();
                    } else {
                        const errorIcon = '<span class="dashicons dashicons-warning"></span>';
                        updateLog(errorIcon + ' ' + i18n.deployment_halted_error);
                        $('.ocs-modal-title').html(errorIcon + ' ' + i18n.deployment_halted_error);
                        $('.ocs-modal-subtitle').html(
                            $('<a>').attr({href: '#', id: 'ocs-close-modal'}).text(i18n.you_may_close)
                        );
                    }
                },
                error: function(xhr) {
                    const errorIcon = '<span class="dashicons dashicons-warning"></span>';
                    let errorText = xhr.responseText || (xhr.status + ' ' + xhr.statusText);
                    updateLog(errorIcon + ' ' + i18n.ajax_error_prefix + errorText);
                    updateLog(errorIcon + ' ' + i18n.server_error);
                    $('.ocs-modal-title').html(errorIcon + ' ' + i18n.server_error);
                     $('.ocs-modal-subtitle').html(
                        $('<a>').attr({href: '#', id: 'ocs-close-modal'}).text(i18n.you_may_close)
                    );
                }
            });
        }
        
        $(document).on('click', '#ocs-close-modal', function(e) {
            e.preventDefault();
            $('#ocs-progress-modal').fadeOut();
            location.reload();
        });

        function updateProgress() {
            const tasksCompleted = totalTasks - taskQueue.length;
            const percentage = totalTasks > 0 ? (tasksCompleted / totalTasks) * 100 : 0;
            $('#ocs-progress-bar').css('width', percentage + '%');
            $('#ocs-progress-text').text(Math.round(percentage) + '%');
        }

        function updateLog(message) {
            const $log = $('#ocs-live-log');
            $log.append('<div>' + message + '</div>').scrollTop($log[0].scrollHeight);
        }
    });
})(jQuery);