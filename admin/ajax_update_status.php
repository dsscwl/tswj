<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

$id = intval($_POST['id']);
$status = trim($_POST['status']);
$remark = trim($_POST['remark'] ?? '');
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['username'];

// 验证状态
$valid_statuses = ['待处理', '处理中', '已处理'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => '无效的状态']);
    exit;
}

// 开始事务
$conn->begin_transaction();

try {
    // 1. 更新投诉状态
    $update_sql = "UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    
    // 2. 记录处理历史
    if (!empty($remark)) {
        // 检查处理记录表是否存在，不存在则创建
        $check_table_sql = "SHOW TABLES LIKE 'complaint_history'";
        $result = $conn->query($check_table_sql);
        
        if ($result->num_rows == 0) {
            // 创建处理记录表
            $create_table_sql = "CREATE TABLE IF NOT EXISTS complaint_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                complaint_id INT NOT NULL,
                admin_id INT NOT NULL,
                admin_name VARCHAR(100),
                action VARCHAR(100),
                remark TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_complaint_id (complaint_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $conn->query($create_table_sql);
        }
        
        // 插入处理记录
        $action = "更新状态为: {$status}";
        $history_sql = "INSERT INTO complaint_history (complaint_id, admin_id, admin_name, action, remark) 
                       VALUES (?, ?, ?, ?, ?)";
        $history_stmt = $conn->prepare($history_sql);
        $history_stmt->bind_param("iisss", $id, $admin_id, $admin_name, $action, $remark);
        $history_stmt->execute();
    }
    
    // 提交事务
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => '处理成功']);
    
} catch (Exception $e) {
    // 回滚事务
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '处理失败: ' . $e->getMessage()]);
}
?>