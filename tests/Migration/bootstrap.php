<?php
/**
 * Bootstrap file for Migration unit tests.
 *
 * Loads WP/WC stubs and the migration autoloader so tests can run
 * without a full WordPress environment.
 */

// Load WP/WC function and class stubs
require_once __DIR__ . '/stubs.php';

// Load migration autoloader
require_once dirname(__DIR__, 2) . '/src/Migration/autoload.php';
