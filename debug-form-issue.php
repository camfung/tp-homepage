<?php
/**
 * Debug script to identify form save issues
 */

echo "Traffic Portal Form Debug Report\n";
echo "===============================\n\n";

// Check recent API logs
echo "1. Recent API Responses:\n";
$log_file = __DIR__ . '/api_requests.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -10);

    foreach ($recent_lines as $line) {
        if (!empty(trim($line))) {
            echo "   $line\n";
        }
    }
} else {
    echo "   No log file found\n";
}

echo "\n2. Common Issues Found:\n";

// Analyze the logs for patterns
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);

    $error_502_count = substr_count($log_content, 'Response Code: 502');
    $error_403_count = substr_count($log_content, 'Response Code: 403');
    $success_count = substr_count($log_content, 'Response Code: 200');

    echo "   - 502 Server Errors: $error_502_count\n";
    echo "   - 403 Forbidden Errors: $error_403_count\n";
    echo "   - 200 Success Responses: $success_count\n";

    if ($error_502_count > 0) {
        echo "   - Issue: External API is returning 502 errors with 'cache_content' message\n";
    }

    if ($error_403_count > 0) {
        echo "   - Issue: API authentication may be failing (403 Forbidden)\n";
    }
}

echo "\n3. Potential Solutions:\n";
echo "   A. API Key Issues:\n";
echo "      - Check if API key is valid and has correct permissions\n";
echo "      - Verify API key is being sent in correct header format\n";
echo "\n";
echo "   B. User Token Issues:\n";
echo "      - Check user token generation logic\n";
echo "      - Verify token format matches API expectations\n";
echo "\n";
echo "   C. Frontend Error Handling:\n";
echo "      - Improve error message display for 502/503 errors\n";
echo "      - Add better loading state recovery\n";
echo "      - Show more specific error messages\n";
echo "\n";
echo "   D. API Server Issues:\n";
echo "      - External API may be experiencing cache problems\n";
echo "      - Consider retry logic for temporary failures\n";

echo "\n4. Immediate Actions Needed:\n";
echo "   1. Update frontend error handling for 502/503 errors\n";
echo "   2. Add retry mechanism for temporary API failures\n";
echo "   3. Improve error messages shown to users\n";
echo "   4. Add console logging for debugging\n";

echo "\nDone.\n";
?>