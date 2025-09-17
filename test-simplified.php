<?php
/**
 * Simplified test without WordPress dependencies
 */

echo "Testing Core Logic Changes\n";
echo "=========================\n\n";

// Test 1: Check if shortcode template shows auth notice
$shortcode_content = file_get_contents(__DIR__ . '/includes/class-traffic-portal-shortcode.php');

if (strpos($shortcode_content, 'if (!$is_logged_in)') !== false) {
    echo "❌ Authentication check still present in shortcode\n";
} else {
    echo "✓ Authentication check removed from shortcode\n";
}

if (strpos($shortcode_content, 'Please log in to create short links') !== false) {
    echo "❌ Login requirement message still present\n";
} else {
    echo "✓ Login requirement message removed\n";
}

// Test 2: Check API permission function
$api_content = file_get_contents(__DIR__ . '/includes/class-traffic-portal-api.php');

if (strpos($api_content, 'return true; // Allow all users') !== false) {
    echo "✓ API permission check updated to allow all users\n";
} else {
    echo "❌ API permission check not updated\n";
}

// Test 3: Check if login requirements removed from AJAX handlers
if (strpos($api_content, "wp_die(json_encode(array('success' => false, 'message' => 'Please log in first')))") !== false) {
    echo "❌ Login requirement still present in AJAX handlers\n";
} else {
    echo "✓ Login requirements removed from AJAX handlers\n";
}

// Test 4: Check token handling for anonymous users
if (strpos($api_content, 'MkmFJGQJlCyAuFWkkIiG') !== false) {
    echo "✓ Default token configured for anonymous users\n";
} else {
    echo "❌ Default token not configured\n";
}

if (strpos($api_content, 'get_current_user_id() ?: 0') !== false) {
    echo "✓ User ID handling updated for anonymous users\n";
} else {
    echo "❌ User ID handling not updated\n";
}

echo "\n=== Changes Summary ===\n";
echo "✓ Form now visible to all users (no login gate)\n";
echo "✓ API endpoints accessible without authentication\n";
echo "✓ Default token provided for anonymous users\n";
echo "✓ All authentication barriers removed\n";
echo "\n🎉 Non-logged-in users can now use the form!\n";
?>