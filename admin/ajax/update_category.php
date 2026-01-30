<?php
require_once '../../config.php';
check_admin_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

$id = intval($_POST['id']);
$field = safe_input($_POST['field']);
$value = safe_input($_POST['value']);

// 允许编辑的字段
$allowed_fields = ['name', 'description', 'sort_order'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => '不允许编辑的字段']);
    exit;
}

try {
    $sql = "UPDATE categories SET $field = ?, update_time = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$value, $id]);
    
    echo json_encode(['success' => true, 'message' => '更新成功']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '更新失败：' . $e->getMessage()]);
}
?>