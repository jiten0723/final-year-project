<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(600);

require_once __DIR__ . '/config/db.php';

define('IMGBB_API_KEY', 'b0d2b1ef3ff48daeb9f4206be57482bd');

// Direct ImgBB-hosted or Wikipedia images — fast, no redirects
$categoryImages = [
    'Programming'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c3/Python-logo-notext.svg/240px-Python-logo-notext.svg.png',
    'Design'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/Adobe_Photoshop_CC_icon.svg/240px-Adobe_Photoshop_CC_icon.svg.png',
    'Business'     => 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Business_people.jpg/320px-Business_people.jpg',
    'Music'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/8/8a/Banana_and_guitar.jpg/320px-Banana_and_guitar.jpg',
    'Photography'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a7/Camponotus_flavomarginatus_ant.jpg/320px-Camponotus_flavomarginatus_ant.jpg',
    'Marketing'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Marketing_mix.png/320px-Marketing_mix.png',
    'Data Science' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0a/Python_and_R_logos.png/320px-Python_and_R_logos.png',
    'Personal Dev' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1e/Meditating_in_Madison_Square_Park.jpg/320px-Meditating_in_Madison_Square_Park.jpg',
];

function downloadAndUpload($cat, $imageUrl) {
    echo "[$cat] Downloading... ";

    $ch = curl_init($imageUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $imageData = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr   = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200 || !$imageData) {
        echo "FAILED (HTTP $httpCode, $curlErr)\n";
        return null;
    }
    echo round(strlen($imageData)/1024) . "KB OK. Uploading to ImgBB... ";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['image' => base64_encode($imageData)],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) { echo "FAILED ($curlErr)\n"; return null; }

    $data = json_decode($response, true);
    if (!empty($data['success'])) {
        $url = $data['data']['url'];
        echo "✅\n   → $url\n";
        return $url;
    }
    echo "FAILED: " . ($data['error']['message'] ?? substr($response, 0, 100)) . "\n";
    return null;
}

$db      = getDB();
$results = [];

echo "=== EDUCORE Thumbnail Seeder ===\n\n";

foreach ($categoryImages as $cat => $url) {
    $imgUrl = downloadAndUpload($cat, $url);
    if ($imgUrl) $results[$cat] = $imgUrl;
    sleep(1);
}

echo "\n=== Updating Database ===\n";
$updated = 0;
foreach ($results as $catName => $thumbUrl) {
    $stmt = $db->prepare("
        UPDATE courses co
        INNER JOIN categories cat ON cat.id = co.category_id
        SET co.thumbnail = ?
        WHERE cat.name = ?
        AND (co.thumbnail IS NULL OR co.thumbnail = '')
    ");
    $stmt->execute([$thumbUrl, $catName]);
    $rows = $stmt->rowCount();
    echo "  [$catName] $rows courses updated\n";
    $updated += $rows;
}

echo "\n✅ Complete! $updated courses updated.\n";
echo "\nNow delete: http://localhost/edu-core/seed_thumbnails.php\n";
