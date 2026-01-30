<?php
require_once 'config.php';

// 获取POST数据
$type = $_POST['type'] ?? '';
$store_id = intval($_POST['store_id'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$customer_name = trim($_POST['customer_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// 验证数据
if (empty($type) || $store_id <= 0 || $category_id <= 0 || empty($content) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => '请填写完整信息']);
    exit;
}

// 验证手机号格式
if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => '请输入正确的手机号码']);
    exit;
}

// 插入数据库
$sql = "INSERT INTO complaints (type, store_id, category_id, content, customer_name, phone, status) 
        VALUES (?, ?, ?, ?, ?, ?, '待处理')";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("siisss", $type, $store_id, $category_id, $content, $customer_name, $phone);

if ($stmt->execute()) {
    // 发送成功响应
    echo json_encode(['success' => true, 'message' => '提交成功']);
    
    // 这里可以添加短信通知（可选）
    // sendSMS($phone, "感谢您的反馈，我们已收到您的{$type}，会尽快处理。");
} else {
    echo json_encode(['success' => false, 'message' => '提交失败，请稍后重试']);
}

$stmt->close();
$conn->close();

// 短信发送函数（需要配置短信服务商）
function sendSMS($phone, $message) {
    // 示例：使用阿里云短信服务
    // 你需要先申请短信服务
    return true;
}
?>