<?php
declare(strict_types=1);

namespace AngellEYE\PayPal\Migration\Admin;

use AngellEYE\PayPal\Migration\Migration_Controller;
use AngellEYE\PayPal\Migration\Enums\Migration_Status;

/**
 * Admin page for managing subscription migrations.
 * 
 * @package AngellEYE\PayPal\Migration\Admin
 * @since 1.0.0
 */
class Migration_Admin_Page {
    
    private const PAGE_SLUG = 'angelleye-ppcp-migration';
    private const CAPABILITY = 'manage_woocommerce';
    
    /**
     * Initialize admin hooks.
     */
    public static function init(): void {
        add_action('admin_menu', [self::class, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('wp_ajax_angelleye_ppcp_migration_stats', [self::class, 'ajax_get_stats']);
        add_action('wp_ajax_angelleye_ppcp_migration_start', [self::class, 'ajax_start_migration']);
        add_action('wp_ajax_angelleye_ppcp_migration_stop', [self::class, 'ajax_stop_migration']);
        add_action('wp_ajax_angelleye_ppcp_migration_retry', [self::class, 'ajax_retry_failed']);
        add_action('wp_ajax_angelleye_ppcp_migration_reset', [self::class, 'ajax_reset_all']);
        add_action('wp_ajax_angelleye_ppcp_migration_export', [self::class, 'ajax_export_failed']);
        add_action('admin_notices', [self::class, 'maybe_show_notices']);
    }
    
    /**
     * Add submenu page under WooCommerce.
     */
    public static function add_menu_page(): void {
        add_submenu_page(
            'woocommerce',
            __('PayPal Migration', 'paypal-for-woocommerce'),
            __('PayPal Migration', 'paypal-for-woocommerce'),
            self::CAPABILITY,
            self::PAGE_SLUG,
            [self::class, 'render_page']
        );
    }
    
    /**
     * Enqueue admin assets.
     */
    public static function enqueue_assets(string $hook): void {
        if ($hook !== 'woocommerce_page_' . self::PAGE_SLUG) {
            return;
        }
        
        $asset_path = plugin_dir_path(PAYPAL_FOR_WOOCOMMERCE_PLUGIN_FILE) . 'assets/';
        $asset_url = plugins_url('assets/', PAYPAL_FOR_WOOCOMMERCE_PLUGIN_FILE);
        
        // CSS
        wp_enqueue_style(
            'angelleye-ppcp-migration-admin',
            $asset_url . 'css/migration-admin.css',
            [],
            '1.0.0'
        );
        
        // JS
        wp_enqueue_script(
            'angelleye-ppcp-migration-admin',
            $asset_url . 'js/migration-admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('angelleye-ppcp-migration-admin', 'angelleyeMigration', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('angelleye_ppcp_migration_nonce'),
            'strings' => [
                'confirmStart' => __('Are you sure you want to start the migration? This process cannot be undone.', 'paypal-for-woocommerce'),
                'confirmStop' => __('Are you sure you want to stop the migration?', 'paypal-for-woocommerce'),
                'confirmReset' => __('WARNING: This will reset ALL migration data. Are you sure?', 'paypal-for-woocommerce'),
                'starting' => __('Starting migration...', 'paypal-for-woocommerce'),
                'stopping' => __('Stopping migration...', 'paypal-for-woocommerce'),
                'retrying' => __('Retrying failed subscriptions...', 'paypal-for-woocommerce'),
                'error' => __('An error occurred. Please try again.', 'paypal-for-woocommerce'),
                'success' => __('Operation completed successfully.', 'paypal-for-woocommerce'),
            ],
        ]);
    }
    
    /**
     * Render the admin page.
     */
    public static function render_page(): void {
        if (!current_user_can(self::CAPABILITY)) {
            wp_die(__('You do not have permission to access this page.', 'paypal-for-woocommerce'));
        }
        
        $controller = Migration_Controller::instance();
        $stats = $controller->get_stats('paypal_express');
        $is_running = !$controller->can_start_migration();
        
        ?>
        <div class="wrap angelleye-ppcp-migration-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php self::render_status_banner($is_running, $stats); ?>
            
            <div class="angelleye-migration-grid">
                <div class="angelleye-migration-main">
                    <?php self::render_progress_section($stats); ?>
                    <?php self::render_failed_subscriptions_table(); ?>
                </div>
                
                <div class="angelleye-migration-sidebar">
                    <?php self::render_actions_panel($is_running, $stats); ?>
                    <?php self::render_help_panel($stats); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render status banner.
     */
    private static function render_status_banner(bool $is_running, array $stats): void {
        $total = $stats['total'] ?? 0;
        $completed = $stats['completed'] ?? 0;
        $failed = $stats['failed'] ?? 0;
        $pending = $total - $completed - $failed;
        
        if ($is_running) {
            $class = 'notice-info';
            $message = sprintf(
                /* translators: %d: number of pending subscriptions */
                __('Migration is currently running. %d subscriptions remaining.', 'paypal-for-woocommerce'),
                $pending
            );
        } elseif ($completed > 0 || $failed > 0) {
            $class = $failed > 0 ? 'notice-warning' : 'notice-success';
            $message = sprintf(
                /* translators: %1$d: completed count, %2$d: failed count */
                __('Migration completed. %1$d successful, %2$d failed.', 'paypal-for-woocommerce'),
                $completed,
                $failed
            );
        } else {
            $class = 'notice-info';
            $message = __('Ready to start migration.', 'paypal-for-woocommerce');
        }
        ?>
        <div class="notice <?php echo esc_attr($class); ?> angelleye-migration-status-banner">
            <p>
                <span class="dashicons dashicons-<?php echo $is_running ? 'update spin' : ($failed > 0 ? 'warning' : 'yes'); ?>"></span>
                <?php echo esc_html($message); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render progress section.
     */
    private static function render_progress_section(array $stats): void {
        $total = $stats['total'] ?? 0;
        $completed = $stats['completed'] ?? 0;
        $by_status = $stats['by_status'] ?? [];
        $failed = ($by_status[Migration_Status::FAILED_NO_TOKEN->value] ?? 0) + 
                  ($by_status[Migration_Status::FAILED_API_ERROR->value] ?? 0) + 
                  ($by_status[Migration_Status::FAILED_DATA_ERROR->value] ?? 0);
        $skipped = ($by_status[Migration_Status::SKIPPED_EXCLUDED->value] ?? 0) + 
                   ($by_status[Migration_Status::SKIPPED_MANUAL->value] ?? 0);
        $not_started = $by_status['not_started'] ?? 0;
        $in_progress = $by_status[Migration_Status::IN_PROGRESS->value] ?? 0;
        
        $progress_percent = $total > 0 ? round((($completed + $failed + $skipped) / $total) * 100, 1) : 0;
        ?>
        <div class="angelleye-migration-card">
            <h2><?php _e('Migration Progress', 'paypal-for-woocommerce'); ?></h2>
            
            <div class="angelleye-progress-bar">
                <div class="angelleye-progress-bar-inner" style="width: <?php echo esc_attr($progress_percent); ?>%"></div>
                <span class="angelleye-progress-text"><?php echo esc_html($progress_percent); ?>%</span>
            </div>
            
            <div class="angelleye-stats-grid">
                <div class="angelleye-stat-box stat-total">
                    <span class="stat-number"><?php echo number_format($total); ?></span>
                    <span class="stat-label"><?php _e('Total', 'paypal-for-woocommerce'); ?></span>
                </div>
                <div class="angelleye-stat-box stat-completed">
                    <span class="stat-number"><?php echo number_format($completed); ?></span>
                    <span class="stat-label"><?php _e('Completed', 'paypal-for-woocommerce'); ?></span>
                </div>
                <div class="angelleye-stat-box stat-failed">
                    <span class="stat-number"><?php echo number_format($failed); ?></span>
                    <span class="stat-label"><?php _e('Failed', 'paypal-for-woocommerce'); ?></span>
                </div>
                <div class="angelleye-stat-box stat-pending">
                    <span class="stat-number"><?php echo number_format($not_started + $in_progress); ?></span>
                    <span class="stat-label"><?php _e('Pending', 'paypal-for-woocommerce'); ?></span>
                </div>
                <div class="angelleye-stat-box stat-skipped">
                    <span class="stat-number"><?php echo number_format($skipped); ?></span>
                    <span class="stat-label"><?php _e('Skipped', 'paypal-for-woocommerce'); ?></span>
                </div>
            </div>
            
            <div class="angelleye-progress-details">
                <h3><?php _e('Detailed Status', 'paypal-for-woocommerce'); ?></h3>
                <table class="widefat">
                    <tbody>
                        <?php foreach (Migration_Status::cases() as $status): ?>
                            <?php $count = $by_status[$status->value] ?? 0; ?>
                            <?php if ($count > 0): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?php echo esc_attr($status->css_class()); ?>">
                                            <?php echo esc_html($status->label()); ?>
                                        </span>
                                    </td>
                                    <td class="column-count"><?php echo number_format($count); ?></td>
                                    <td>
                                        <?php if ($status->is_failure()): ?>
                                            <button type="button" 
                                                    class="button button-small retry-error-type" 
                                                    data-error-code="<?php echo esc_attr(str_replace('failed_', '', $status->value)); ?>">
                                                <?php _e('Retry', 'paypal-for-woocommerce'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($not_started > 0): ?>
                            <tr>
                                <td>
                                    <span class="status-badge status-not-started">
                                        <?php _e('Not Started', 'paypal-for-woocommerce'); ?>
                                    </span>
                                </td>
                                <td class="column-count"><?php echo number_format($not_started); ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render failed subscriptions table.
     */
    private static function render_failed_subscriptions_table(): void {
        $controller = Migration_Controller::instance();
        $failed = $controller->get_failed_subscriptions('paypal_express');
        
        if (empty($failed)) {
            return;
        }
        ?>
        <div class="angelleye-migration-card">
            <div class="card-header">
                <h2><?php _e('Failed Subscriptions', 'paypal-for-woocommerce'); ?></h2>
                <div class="card-actions">
                    <button type="button" class="button" id="export-failed-btn">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'paypal-for-woocommerce'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="retry-all-failed-btn">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Retry All', 'paypal-for-woocommerce'); ?>
                    </button>
                </div>
            </div>
            
            <div class="failed-subscriptions-filter">
                <select id="filter-error-code">
                    <option value=""><?php _e('All Error Types', 'paypal-for-woocommerce'); ?></option>
                    <option value="no_token"><?php _e('No Payment Token', 'paypal-for-woocommerce'); ?></option>
                    <option value="api_error"><?php _e('API Error', 'paypal-for-woocommerce'); ?></option>
                    <option value="data_error"><?php _e('Data Error', 'paypal-for-woocommerce'); ?></option>
                </select>
                <button type="button" class="button" id="apply-filter"><?php _e('Filter', 'paypal-for-woocommerce'); ?></button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="column-id"><?php _e('ID', 'paypal-for-woocommerce'); ?></th>
                        <th class="column-customer"><?php _e('Customer', 'paypal-for-woocommerce'); ?></th>
                        <th class="column-status"><?php _e('Error Type', 'paypal-for-woocommerce'); ?></th>
                        <th class="column-message"><?php _e('Error Message', 'paypal-for-woocommerce'); ?></th>
                        <th class="column-date"><?php _e('Failed At', 'paypal-for-woocommerce'); ?></th>
                        <th class="column-actions"><?php _e('Actions', 'paypal-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($failed, 0, 50) as $item): ?>
                        <tr data-subscription-id="<?php echo esc_attr($item['subscription_id']); ?>" data-error-code="<?php echo esc_attr($item['error_code'] ?? ''); ?>">
                            <td class="column-id">
                                <a href="<?php echo esc_url(get_edit_post_link($item['subscription_id'])); ?>" target="_blank">
                                    #<?php echo esc_html($item['subscription_id']); ?>
                                    <span class="dashicons dashicons-external"></span>
                                </a>
                            </td>
                            <td class="column-customer">
                                <?php
                                $subscription = wcs_get_subscription($item['subscription_id']);
                                if ($subscription) {
                                    $customer_id = $subscription->get_customer_id();
                                    $customer = new \WC_Customer($customer_id);
                                    echo esc_html($customer->get_display_name() . ' (' . $customer->get_email() . ')');
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="column-status">
                                <span class="status-badge status-failed">
                                    <?php echo esc_html($item['error_code'] ?? 'unknown'); ?>
                                </span>
                            </td>
                            <td class="column-message">
                                <?php echo esc_html($item['error_message'] ?? '—'); ?>
                            </td>
                            <td class="column-date">
                                <?php 
                                $completed_at = get_post_meta($item['subscription_id'], '_angelleye_ppcp_migration_completed_at', true);
                                echo $completed_at ? esc_html(human_time_diff($completed_at, time()) . ' ago') : '—';
                                ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button button-small retry-subscription" data-id="<?php echo esc_attr($item['subscription_id']); ?>">
                                    <?php _e('Retry', 'paypal-for-woocommerce'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($failed) > 50): ?>
                <p class="description">
                    <?php 
                    printf(
                        /* translators: %d: number of hidden items */
                        __('Showing 50 of %d failed subscriptions. Use Export to see all.', 'paypal-for-woocommerce'),
                        count($failed)
                    ); 
                    ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render actions panel.
     */
    private static function render_actions_panel(bool $is_running, array $stats): void {
        ?>
        <div class="angelleye-migration-card">
            <h2><?php _e('Actions', 'paypal-for-woocommerce'); ?></h2>
            
            <div class="angelleye-migration-actions">
                <?php if (!$is_running): ?>
                    <button type="button" class="button button-primary button-hero" id="start-migration-btn">
                        <span class="dashicons dashicons-migrate"></span>
                        <?php _e('Start Migration', 'paypal-for-woocommerce'); ?>
                    </button>
                <?php else: ?>
                    <button type="button" class="button button-secondary button-hero" id="stop-migration-btn">
                        <span class="dashicons dashicons-no"></span>
                        <?php _e('Stop Migration', 'paypal-for-woocommerce'); ?>
                    </button>
                <?php endif; ?>
                
                <hr>
                
                <button type="button" class="button" id="refresh-stats-btn">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh Stats', 'paypal-for-woocommerce'); ?>
                </button>
                
                <button type="button" class="button" id="reset-migration-btn">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Reset All Data', 'paypal-for-woocommerce'); ?>
                </button>
            </div>
            
            <div class="migration-settings">
                <h3><?php _e('Settings', 'paypal-for-woocommerce'); ?></h3>
                <label>
                    <?php _e('Batch Size:', 'paypal-for-woocommerce'); ?>
                    <select id="batch-size">
                        <option value="25">25</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                </label>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render help panel.
     */
    private static function render_help_panel(array $stats): void {
        $by_status = $stats['by_status'] ?? [];
        $failed_no_token = $by_status[Migration_Status::FAILED_NO_TOKEN->value] ?? 0;
        ?>
        <div class="angelleye-migration-card">
            <h2><?php _e('Help & Information', 'paypal-for-woocommerce'); ?></h2>
            
            <div class="help-section">
                <h3><?php _e('Error Types', 'paypal-for-woocommerce'); ?></h3>
                <dl>
                    <dt><?php _e('No Payment Token', 'paypal-for-woocommerce'); ?></dt>
                    <dd>
                        <?php _e('Subscription is missing a valid PayPal payment token. These cannot be auto-recovered.', 'paypal-for-woocommerce'); ?>
                        <?php if ($failed_no_token > 0): ?>
                            <br><strong><?php printf(__('%d subscriptions affected.', 'paypal-for-woocommerce'), $failed_no_token); ?></strong>
                        <?php endif; ?>
                    </dd>
                    
                    <dt><?php _e('API Error', 'paypal-for-woocommerce'); ?></dt>
                    <dd>
                        <?php _e('PayPal API returned an error. These can usually be retried.', 'paypal-for-woocommerce'); ?>
                    </dd>
                    
                    <dt><?php _e('Data Error', 'paypal-for-woocommerce'); ?></dt>
                    <dd>
                        <?php _e('Subscription data is corrupt or incomplete.', 'paypal-for-woocommerce'); ?>
                    </dd>
                </dl>
            </div>
            
            <?php 
            $failed_no_token = $by_status[Migration_Status::FAILED_NO_TOKEN->value] ?? 0;
            if ($failed_no_token > 0): 
            ?>
                <div class="help-section help-warning">
                    <h3><?php _e('Missing Tokens Recovery', 'paypal-for-woocommerce'); ?></h3>
                    <p>
                        <?php _e('Subscriptions with missing tokens cannot be automatically migrated. Options:', 'paypal-for-woocommerce'); ?>
                    </p>
                    <ol>
                        <li><?php _e('Restore database backup from before token loss (if available)', 'paypal-for-woocommerce'); ?></li>
                        <li><?php _e('Ask customers to update payment method via "My Account"', 'paypal-for-woocommerce'); ?></li>
                        <li><?php _e('Cancel and have customers re-subscribe', 'paypal-for-woocommerce'); ?></li>
                        <li><?php _e('Contact PayPal support for reference transactions', 'paypal-for-woocommerce'); ?></li>
                    </ol>
                </div>
            <?php endif; ?>
            
            <div class="help-section">
                <h3><?php _e('Shortcuts', 'paypal-for-woocommerce'); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=shop_subscription')); ?>" target="_blank">
                        <?php _e('View All Subscriptions', 'paypal-for-woocommerce'); ?>
                    </a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=wc-status&tab=action-scheduler&s=angelleye_ppcp_migration')); ?>" target="_blank">
                        <?php _e('View Scheduled Actions', 'paypal-for-woocommerce'); ?>
                    </a></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get current stats.
     */
    public static function ajax_get_stats(): void {
        check_ajax_referer('angelleye_ppcp_migration_nonce', 'nonce');

        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'paypal-for-woocommerce')]);
        }

        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'paypal_express';
        $controller = Migration_Controller::instance();

        wp_send_json_success([
            'stats' => $controller->get_stats($payment_method),
            'is_running' => !$controller->can_start_migration($payment_method),
        ]);
    }
    
    /**
     * AJAX: Start migration.
     */
    public static function ajax_start_migration(): void {
        check_ajax_referer('angelleye_ppcp_migration_nonce', 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'paypal-for-woocommerce')]);
        }
        
        $controller = Migration_Controller::instance();
        
        if (!$controller->can_start_migration()) {
            wp_send_json_error(['message' => __('Migration is already running.', 'paypal-for-woocommerce')]);
        }
        
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        $batch_size = max(10, min(500, $batch_size));
        
        $result = $controller->start_migration('paypal_express', 'angelleye_ppcp', $batch_size);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Migration started successfully.', 'paypal-for-woocommerce'),
                'stats' => $controller->get_stats('paypal_express'),
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to start migration.', 'paypal-for-woocommerce')]);
        }
    }
    
    /**
     * AJAX: Stop migration.
     */
    public static function ajax_stop_migration(): void {
        check_ajax_referer('angelleye_ppcp_migration_nonce', 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'paypal-for-woocommerce')]);
        }
        
        $controller = Migration_Controller::instance();
        $controller->stop_migration();
        
        wp_send_json_success([
            'message' => __('Migration stopped.', 'paypal-for-woocommerce'),
            'stats' => $controller->get_stats('paypal_express'),
        ]);
    }
    
    /**
     * AJAX: Retry failed subscriptions.
     */
    public static function ajax_retry_failed(): void {
        check_ajax_referer('angelleye_ppcp_migration_nonce', 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'paypal-for-woocommerce')]);
        }
        
        $controller = Migration_Controller::instance();
        
        // Retry specific error type or all failed
        $error_code = isset($_POST['error_code']) ? sanitize_text_field($_POST['error_code']) : null;
        $subscription_id = isset($_POST['subscription_id']) ? intval($_POST['subscription_id']) : null;
        
        if ($subscription_id) {
            // Retry single subscription
            $result = $controller->retry_subscription($subscription_id);
            if ($result && $result->is_success()) {
                wp_send_json_success([
                    'message' => __('Subscription retried successfully.', 'paypal-for-woocommerce'),
                    'stats' => $controller->get_stats('paypal_express'),
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result ? $result->error_message : __('Retry failed.', 'paypal-for-woocommerce'),
                ]);
            }
        } else {
            // Retry by error code or all
            $retried = $controller->retry_failed($error_code, 'paypal_express', 'angelleye_ppcp');
            wp_send_json_success([
                'message' => sprintf(
                    /* translators: %d: number of subscriptions */
                    __('%d subscriptions queued for retry.', 'paypal-for-woocommerce'),
                    $retried
                ),
                'stats' => $controller->get_stats('paypal_express'),
                'retried_count' => $retried,
            ]);
        }
    }
    
    /**
     * AJAX: Reset all migration data.
     */
    public static function ajax_reset_all(): void {
        check_ajax_referer('angelleye_ppcp_migration_nonce', 'nonce');

        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'paypal-for-woocommerce')]);
        }

        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'paypal_express';
        $controller = Migration_Controller::instance();
        $controller->reset_all($payment_method);

        wp_send_json_success([
            'message' => __('All migration data has been reset.', 'paypal-for-woocommerce'),
            'stats' => $controller->get_stats($payment_method),
        ]);
    }
    
    /**
     * AJAX: Export failed subscriptions as CSV.
     */
    public static function ajax_export_failed(): void {
        check_ajax_referer('angelleye_ppcp_migration_nonce', 'nonce');
        
        if (!current_user_can(self::CAPABILITY)) {
            wp_send_json_error(['message' => __('Permission denied.', 'paypal-for-woocommerce')]);
        }
        
        $controller = Migration_Controller::instance();
        $error_code = isset($_POST['error_code']) ? sanitize_text_field($_POST['error_code']) : null;
        
        $failed = $controller->get_failed_subscriptions('paypal_express', $error_code ? [$error_code] : null, 1000);
        
        $csv_data = [];
        $csv_data[] = ['Subscription ID', 'Customer ID', 'Customer Email', 'Customer Name', 'Error Code', 'Error Message', 'Failed At'];
        
        foreach ($failed as $item) {
            $subscription = wcs_get_subscription($item['subscription_id']);
            $customer_id = $subscription ? $subscription->get_customer_id() : 0;
            $customer = $customer_id ? new \WC_Customer($customer_id) : null;
            $completed_at = get_post_meta($item['subscription_id'], '_angelleye_ppcp_migration_completed_at', true);
            
            $csv_data[] = [
                $item['subscription_id'],
                $customer_id,
                $customer ? $customer->get_email() : 'N/A',
                $customer ? $customer->get_display_name() : 'N/A',
                $item['error_code'] ?? 'unknown',
                $item['error_message'] ?? '',
                $completed_at ? date('Y-m-d H:i:s', $completed_at) : 'N/A',
            ];
        }
        
        $filename = 'failed-subscriptions-' . date('Y-m-d-His') . '.csv';
        
        wp_send_json_success([
            'filename' => $filename,
            'data' => $csv_data,
            'count' => count($failed),
        ]);
    }
    
    /**
     * Show admin notices on other pages.
     */
    public static function maybe_show_notices(): void {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'woocommerce_page_' . self::PAGE_SLUG) {
            return;
        }
        
        // Any additional notices can be added here
    }
}
