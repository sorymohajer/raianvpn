<?php
// api.php - نقطة نهاية التطبيق ولوحة التحكم
header('Content-Type: application/json');

$data_file = 'servers_data.json';

// تحميل البيانات الافتراضية إذا لم يكن الملف موجوداً
if (!file_exists($data_file)) {
    $initial_data = [
        "api_enabled" => true,
        "show_openvpn" => true,
        "show_wireguard" => true,
        "timer_enabled" => false,
        "servers" => [],
        "wireguard_servers" => []
    ];
    file_put_contents($data_file, json_encode($initial_data, JSON_PRETTY_PRINT));
}

$current_data = json_decode(file_get_contents($data_file), true);

// إذا كان الطلب قادم من التطبيق (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['admin'])) {
    echo json_encode([
        "success" => true,
        "message" => "Servers fetched successfully",
        "data" => $current_data['servers']
    ]);
    exit;
}

// طلب سيرفرات WireGuard
if (isset($_GET['wireguard'])) {
    echo json_encode([
        "success" => true,
        "data" => array_values($current_data['wireguard_servers'])
    ]);
    exit;
}
?>
