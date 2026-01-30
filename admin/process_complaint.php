<?php
require_once '../config.php';
check_admin_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

$complaint_id = intval($_POST['complaint_id']);
$status = safe_input($_POST['status']);
$operator = safe_input($_POST['operator']);
$content = safe_input($_POST['content']);

try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 更新投诉状态
    $update_sql = "UPDATE complaints SET status = ?, update_time = NOW() WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$status, $complaint_id]);
    
    // 添加处理记录
    $log_sql = "INSERT INTO complaint_logs (complaint_id, operator, content, create_time) 
                VALUES (?, ?, ?, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([$complaint_id, $operator, $content]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => '处理成功']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => '处理失败：' . $e->getMessage()]);
}
?>