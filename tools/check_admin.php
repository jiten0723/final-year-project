<?php
require __DIR__ . '/../config/db.php';
try {
    $db = getDB();
    $emails = ['admin@educore.com','teacher@educore.com','student@educore.com'];
    $in  = str_repeat('?,', count($emails) - 1) . '?';
    $stmt = $db->prepare("SELECT email,role,is_active,is_verified,password FROM users WHERE email IN ($in)");
    $stmt->execute($emails);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/check_admin_out.txt', json_encode($rows, JSON_PRETTY_PRINT));
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/check_admin_out.txt', "ERROR: " . $e->getMessage());
}
