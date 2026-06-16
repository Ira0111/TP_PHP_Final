<?php
header('Content-Type: application/json');

$text = $_POST['text'] ?? '';

if (!$text) {
    echo json_encode(['error' => 'No text']);
    exit;
}

$api_key = "eef096d0-09de-4e73-86ef-7e683206fcb0:fx";

$payload = http_build_query([
    'text' => $text,
    'target_lang' => 'FR'
]);

$ch = curl_init("https://api-free.deepl.com/v2/translate");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: DeepL-Auth-Key $api_key",
    "Content-Type: application/x-www-form-urlencoded"
]);

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);

if (!$response) {
    echo json_encode(['error' => 'curl error', 'details' => curl_error($ch)]);
    exit;
}

curl_close($ch);

echo $response;
