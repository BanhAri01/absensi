<?php
$instance_id = "instance113723"; // Ganti dengan instance ID-mu
$token = "4sd8ktsua5evscgx"; // Ganti dengan token API-mu
$nomor = "628785987616"; // Ganti dengan nomor tujuan
$pesan = "Tes kirim pesan dari PHP ke UltraMsg API!";

$url = "https://api.ultramsg.com/$instance_id/messages/chat";
$data = [
    "token" => $token,
    "to" => $nomor,
    "body" => $pesan
];

$options = [
    "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded",
        "method" => "POST",
        "content" => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo $result;
?>
