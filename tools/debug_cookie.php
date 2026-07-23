<?php
// Debug script - check cookie vs trusted_devices
// Visit: http://localhost/edu-core/tools/debug_cookie.php
// DELETE this file after debugging
require_once __DIR__ . '/../config/db.php';

$db = getDB();

echo "<pre style='font-family:monospace;padding:20px;background:#111;color:#0f0;'>";
echo "=== COOKIE DEBUG ===\n\n";

$cookieVal = $_COOKIE['educore_trusted'] ?? '';
echo "Raw cookie value: " . ($cookieVal ?: '(NONE)') . "\n\n";

if ($cookieVal && strpos($cookieVal, ':') !== false) {
    [$cookieUid, $cookieToken] = explode(':', $cookieVal, 2);
    echo "Parsed UID:   " . $cookieUid . "\n";
    echo "Token length: " . strlen($cookieToken) . " chars\n";
    echo "Token value:  " . $cookieToken . "\n\n";

    $stmt = $db->prepare("SELECT * FROM trusted_devices WHERE user_id = ? AND token = ?");
    $stmt->execute([$cookieUid, $cookieToken]);
    $row = $stmt->fetch();

    if ($row) {
        echo "DB match FOUND:\n";
        echo "  expires_at: " . $row['expires_at'] . "\n";
        echo "  NOW():      " . date('Y-m-d H:i:s') . "\n";
        echo "  Expired?    " . ($row['expires_at'] < date('Y-m-d H:i:s') ? 'YES - EXPIRED!' : 'No, still valid') . "\n";
    } else {
        echo "DB match: NOT FOUND for this token + user_id combo\n";
        // Check if any token exists for this user
        $stmt2 = $db->prepare("SELECT id, LEFT(token,16) as tok, expires_at FROM trusted_devices WHERE user_id = ?");
        $stmt2->execute([$cookieUid]);
        $rows = $stmt2->fetchAll();
        echo "\nAll tokens in DB for user $cookieUid:\n";
        foreach ($rows as $r) {
            echo "  id={$r['id']} tok={$r['tok']}... expires={$r['expires_at']}\n";
        }
    }
} else {
    echo "No valid cookie found.\n";
    echo "All cookies on this request:\n";
    print_r($_COOKIE);
}

echo "\n\n=== ALL TRUSTED DEVICES IN DB ===\n";
$all = $db->query("SELECT id, user_id, LEFT(token,16) as token_preview, expires_at, ip_address FROM trusted_devices ORDER BY created_at DESC LIMIT 10")->fetchAll();
foreach ($all as $r) {
    echo "id={$r['id']} user={$r['user_id']} tok={$r['token_preview']}... expires={$r['expires_at']} ip={$r['ip_address']}\n";
}

echo "</pre>";
?>
