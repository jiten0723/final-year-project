<?php
// ============================================
// EDUCORE - Database Migration Script
// Run once: http://localhost/edu-core/migrate.php
// ============================================
require_once __DIR__ . '/config/db.php';

$db = getDB();
$results = [];

try {
    // 1. Add otp_codes table
    $db->exec("
        CREATE TABLE IF NOT EXISTS otp_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(10) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $results[] = ['status' => 'ok', 'msg' => 'Table otp_codes created (or already exists)'];
} catch (Exception $e) {
    $results[] = ['status' => 'error', 'msg' => 'otp_codes: ' . $e->getMessage()];
}

try {
    // 2. Add is_verified column to users (if not exists)
    $db->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 1");
    $results[] = ['status' => 'ok', 'msg' => 'Column is_verified added to users'];
} catch (Exception $e) {
    // Column might already exist
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $results[] = ['status' => 'ok', 'msg' => 'Column is_verified already exists in users'];
    } else {
        $results[] = ['status' => 'error', 'msg' => 'is_verified column: ' . $e->getMessage()];
    }
}

try {
    // 3. Set all existing users as verified
    $affected = $db->exec("UPDATE users SET is_verified=1 WHERE is_verified IS NULL OR is_verified=0");
    $results[] = ['status' => 'ok', 'msg' => "Set is_verified=1 for $affected existing users"];
} catch (Exception $e) {
    $results[] = ['status' => 'error', 'msg' => 'Update users: ' . $e->getMessage()];
}

try {
    // 5. Add signature_image column to users (teacher signature for certificates)
    $db->exec("ALTER TABLE users ADD COLUMN signature_image VARCHAR(255) DEFAULT NULL");
    $results[] = ['status' => 'ok', 'msg' => 'Column signature_image added to users'];
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $results[] = ['status' => 'ok', 'msg' => 'Column signature_image already exists in users'];
    } else {
        $results[] = ['status' => 'error', 'msg' => 'signature_image column: ' . $e->getMessage()];
    }
}

try {
    // 4. Add trusted_devices table (cookie-based OTP skip)
    $db->exec("
        CREATE TABLE IF NOT EXISTS trusted_devices (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL,
            token       VARCHAR(64) NOT NULL UNIQUE,
            user_agent  VARCHAR(255) DEFAULT NULL,
            ip_address  VARCHAR(45) DEFAULT NULL,
            expires_at  DATETIME NOT NULL,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    $results[] = ['status' => 'ok', 'msg' => 'Table trusted_devices created (or already exists)'];
} catch (Exception $e) {
    $results[] = ['status' => 'error', 'msg' => 'trusted_devices: ' . $e->getMessage()];
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EDUCORE Migration</title>
    <style>
        body { font-family: monospace; background: #0a1628; color: #e2e8f0; padding: 40px; max-width: 700px; margin: 0 auto; }
        h1 { color: #22c55e; font-size: 22px; margin-bottom: 24px; }
        .item { padding: 12px 16px; border-radius: 8px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; }
        .ok { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
        .error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
        .icon { font-size: 18px; flex-shrink: 0; }
        .done { margin-top: 28px; padding: 16px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.3); border-radius: 8px; }
        a { color: #60a5fa; }
    </style>
</head>
<body>
    <h1>🛠 EDUCORE Database Migration</h1>
    <?php foreach ($results as $r): ?>
        <div class="item <?php echo $r['status']; ?>">
            <span class="icon"><?php echo $r['status'] === 'ok' ? '✅' : '❌'; ?></span>
            <span><?php echo htmlspecialchars($r['msg']); ?></span>
        </div>
    <?php endforeach; ?>
    <div class="done">
        <strong>✅ Migration complete!</strong><br><br>
        You can now delete this file and go to:
        <a href="<?php echo BASE_URL; ?>/index.php"><?php echo BASE_URL; ?>/index.php</a>
    </div>
</body>
</html>
