<?php
/**
 * Test script for the updated validation functionality
 * Tests the core functions without WordPress dependencies
 */

echo "Testing Traffic Portal API Validation Functions\n";
echo "==============================================\n\n";

// Test the functions from tmp.php directly first
echo "Test 1: Testing original functions from tmp.php\n";
require_once __DIR__ . '/tmp.php';

try {
    $tpkey = "abc123";
    $domain = "trafficportal.dev";
    $api_key = "q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d"; // From tmp.php

    echo "Testing validateItem function...\n";
    $result = validateItem($tpkey, $domain, $api_key);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

} catch (Exception $e) {
    echo "Error testing original functions: " . $e->getMessage() . "\n\n";
}

// Test if we can at least load the API class and check syntax
echo "Test 2: Testing API class loading and basic methods\n";

// Mock WordPress constants and functions that the class needs
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('TPLS_API_BASE_URL')) {
    define('TPLS_API_BASE_URL', 'https://dev.trfc.link');
}

if (!defined('TPLS_API_KEY')) {
    define('TPLS_API_KEY', 'q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d');
}

if (!defined('TPLS_VERSION')) {
    define('TPLS_VERSION', '1.0.0');
}

// Mock WordPress functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

try {
    require_once __DIR__ . '/includes/class-traffic-portal-api.php';
    echo "API class loaded successfully!\n";

    // Test key validation method
    $api = new ReflectionClass('Traffic_Portal_API');
    $validate_method = $api->getMethod('validate_key_format');
    $validate_method->setAccessible(true);

    $instance = $api->newInstance();
    $is_valid = $validate_method->invoke($instance, 'abc123');
    echo "Key format validation for 'abc123': " . ($is_valid ? "VALID" : "INVALID") . "\n";

    $is_valid = $validate_method->invoke($instance, 'invalid key!');
    echo "Key format validation for 'invalid key!': " . ($is_valid ? "VALID" : "INVALID") . "\n\n";

} catch (Exception $e) {
    echo "Error loading API class: " . $e->getMessage() . "\n\n";
} catch (Error $e) {
    echo "Fatal error: " . $e->getMessage() . "\n\n";
}

echo "Test 3: Check logging functionality\n";
$log_file = __DIR__ . '/api_requests.log';
if (file_exists($log_file)) {
    echo "Log file exists: YES\n";
    $log_size = filesize($log_file);
    echo "Log file size: $log_size bytes\n";

    if ($log_size > 0) {
        echo "Recent log entries:\n";
        $log_content = file_get_contents($log_file);
        $lines = explode("\n", $log_content);
        $recent_lines = array_slice($lines, -10); // Last 10 lines
        foreach ($recent_lines as $line) {
            if (!empty(trim($line))) {
                echo "  $line\n";
            }
        }
    }
} else {
    echo "Log file exists: NO\n";
}

echo "\nTesting complete.\n";
?>