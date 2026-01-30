<?php
require_once '../config.php';
check_superadmin_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '请求方法错误']);
    exit;
}

$user_id = intval($_POST['user_id']);
$password = safe_input($_POST['password']);
$type = $_POST['type']; // superadmin 或 brand

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => '密码不能为空']);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    if ($type === 'superadmin') {
        $sql = "UPDATE super_admins SET password = ?, update_time = NOW() WHERE id = ?";
    } else {
        $sql = "UPDATE brand_users SET password = ?, update_time = NOW() WHERE id = ?";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$password_hash, $user_id]);
    
    // 记录操作日志
    $admin_name = $_SESSION['superadmin_username'] ?? '未知';
    log_action('重置密码', "重置用户ID: {$user_id} 的密码 (类型: {$type})");
    
    echo json_encode(['success' => true, 'message' => '密码重置成功']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '重置失败：' . $e->getMessage()]);
}
?>