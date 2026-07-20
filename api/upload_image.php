<?php
// ============================================
// EDUCORE - ImgBB Image Upload API
// ============================================
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

define('IMGBB_API_KEY', 'b0d2b1ef3ff48daeb9f4206be57482bd');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'No image provided']);
    exit();
}

$file     = $_FILES['image'];
$allowed  = ['image/jpeg','image/png','image/gif','image/webp'];
$maxSize  = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF, WEBP allowed']);
    exit();
}
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Image must be under 5MB']);
    exit();
}

// Convert to base64 and upload to ImgBB
$imageData = base64_encode(file_get_contents($file['tmp_name']));

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'image' => $imageData,
        'name'  => pathinfo($file['name'], PATHINFO_FILENAME) . '_' . time(),
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $curlError]);
    exit();
}

$data = json_decode($response, true);

if (!empty($data['success']) && $data['success']) {
    echo json_encode([
        'success'   => true,
        'url'       => $data['data']['url'],
        'thumb_url' => $data['data']['thumb']['url'] ?? $data['data']['url'],
        'display'   => $data['data']['display_url'],
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error'   => $data['error']['message'] ?? 'ImgBB upload failed'
    ]);
}
