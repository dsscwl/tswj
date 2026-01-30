<?php
require_once '../../config.php';
check_admin_login();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => '缺少参数']);
    exit;
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM stores WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if ($store) {
    echo json_encode($store);
} else {
    echo json_encode(['error' => '门店不存在']);
}
?>