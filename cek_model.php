<?php
// Masukkan API Key kamu
$api_key = 'AIzaSyCvr22goQ_Yhh8CmA2etwzjUZ0feGCrvt8'; 

// URL untuk meminta daftar model
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

echo "<h3>Daftar Model AI yang Aktif di API Key Kamu:</h3>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
?>