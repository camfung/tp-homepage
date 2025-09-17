
<?php
function logMessage($message) {
    $logFile = __DIR__ . '/api_requests.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

function validateItem($tpkey, $domain, $apiKey) {
    $url = "https://dev.trfc.link/items/validate?tpkey=" . urlencode($tpkey) . "&domain=" . urlencode($domain);
    logMessage("Sending GET request to: $url");

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-api-key: $apiKey"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        logMessage("cURL Error: $error");
        throw new Exception('Request Error: ' . $error);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    logMessage("Response Code: $httpCode | Response Body: $response");

    return [
        'status' => $httpCode,
        'body' => $response
    ];
}

function createMaskedRecord($apiKey, $uid, $tpTkn, $tpKey, $domain, $destination, $status = "active") {
    $url = "https://dev.trfc.link/items";
    $payload = json_encode([
        "uid" => $uid,
        "tpTkn" => $tpTkn,
        "tpKey" => $tpKey,
        "domain" => $domain,
        "destination" => $destination,
        "status" => $status
    ]);

    logMessage("Sending POST request to: $url | Payload: $payload");

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "x-api-key: $apiKey"
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        logMessage("cURL Error: $error");
        throw new Exception('Request Error: ' . $error);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    logMessage("Response Code: $httpCode | Response Body: $response");

    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Example usage:
$user_token = "MkmFJGQJlCyAuFWkkIiG";
$api_key = "q9D7lp99A818aVMcVM9vU1QoY7KM0SZa5lyw8M0d";
$result = validateItem("abc123", "trafficportal.dev", $api_key);
print_r($result);
$result = createMaskedRecord($api_key, 125, $user_token, "abc123", "dev.trfc.link", "https://example.com/landing");
print_r($result);
?>
