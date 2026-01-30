<?php
require_once '../../config.php';
check_admin_login();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => '缺少参数']);
    exit;
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM categories WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if ($category) {
    echo json_encode($category);
} else {
    echo json_encode(['error' => '分类不存在']);
}
?>