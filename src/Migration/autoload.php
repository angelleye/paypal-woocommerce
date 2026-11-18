<?php
/**
 * Autoloader for the Migration namespace.
 * 
 * This file can be included to register the autoloader for all Migration classes.
 */

spl_autoload_register(function (string $class): void {
    // Only handle AngellEYE\PayPal\Migration namespace
    $prefix = 'AngellEYE\\PayPal\\Migration\\';
    
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    
    // Remove namespace prefix
    $relative_class = substr($class, strlen($prefix));
    
    // Convert namespace separators to directory separators
    $file = str_replace('\\', '/', $relative_class) . '.php';
    
    // Base directory for migration classes
    $base_dir = __DIR__ . '/';
    
    // Map class types to directories
    $file_mappings = [
        'Admin/' => $base_dir . 'Admin/',
        'Contracts/' => $base_dir . 'Contracts/',
        'Controller/' => $base_dir,
        'DTOs/' => $base_dir . 'DTOs/',
        'Enums/' => $base_dir . 'Enums/',
        'Queue/' => $base_dir . 'Queue/',
        'Reporting/' => $base_dir . 'Reporting/',
        'Services/' => $base_dir . 'Services/',
        'State/' => $base_dir . 'State/',
    ];
    
    foreach ($file_mappings as $prefix_dir => $dir) {
        if (str_starts_with($file, $prefix_dir)) {
            $file_path = $dir . substr($file, strlen($prefix_dir));
            
            if (file_exists($file_path)) {
                require_once $file_path;
                return;
            }
        }
    }
    
    // Check for Migration_Controller in root
    if ($relative_class === 'Migration_Controller') {
        $controller_path = $base_dir . 'Migration_Controller.php';
        if (file_exists($controller_path)) {
            require_once $controller_path;
            return;
        }
    }
});

/**
 * Initialize migration admin functionality.
 * 
 * Call this function during plugin initialization to set up admin pages.
 */
function angelleye_ppcp_migration_init_admin(): void {
    if (!is_admin()) {
        return;
    }
    
    \AngellEYE\PayPal\Migration\Admin\Migration_Admin_Page::init();
}

/**
 * Initialize migration system.
 * 
 * Call this function during plugin initialization.
 */
function angelleye_ppcp_migration_init(): void {
    // Initialize admin functionality if in admin
    add_action('init', 'angelleye_ppcp_migration_init_admin', 10);
    
    // Register Action Scheduler hooks
    add_action('angelleye_ppcp_migration_process_batch', 'angelleye_ppcp_migration_process_batch', 10, 3);
}

/**
 * Process batch callback for Action Scheduler.
 *
 * @param string $from_payment_method Source payment method.
 * @param string $to_payment_method Target payment method.
 * @param int $batch_size Batch size.
 * @return void
 */
function angelleye_ppcp_migration_process_batch(
    string $from_payment_method,
    string $to_payment_method,
    int $batch_size
): void {
    $controller = \AngellEYE\PayPal\Migration\Migration_Controller::instance();
    $controller->process_batch($from_payment_method, $to_payment_method, $batch_size);
}
