<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = intval($_GET['id']);
$admin_id = $_SESSION['admin_id'];
$role = $_SESSION['role'];
$store_id = $_SESSION['store_id'];

// 查询投诉详情
$sql = "SELECT c.*, s.store_name, cat.category_name 
        FROM complaints c 
        LEFT JOIN stores s ON c.store_id = s.id 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        WHERE c.id = ?";
        
if ($role == 'store_admin') {
    $sql .= " AND c.store_id = $store_id";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$complaint = $result->fetch_assoc();

if (!$complaint) {
    die("未找到该记录或无权访问");
}

// 更新状态处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $remark = $_POST['remark'];
    
    $update_sql = "UPDATE complaints SET status = ?, admin_remark = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssi", $status, $remark, $id);
    
    if ($stmt->execute()) {
        $success = "处理记录已更新";
        $complaint['status'] = $status;
        $complaint['admin_remark'] = $remark;
    } else {
        $error = "更新失败";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投诉详情</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .detail-label {
            width: 120px;
            font-weight: bold;
            color: #555;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .content-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        .form-group {
            margin: 20px 0;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 5px;
        }
        
        textarea.form-control {
            height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-back {
            background: #7f8c8d;
            color: white;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-pending { background: #ffeaea; color: #e74c3c; }
        .status-processing { background: #fff4e6; color: #f39c12; }
        .status-resolved { background: #e6f7ed; color: #27ae60; }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>投诉建议详情</h1>
        
        <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="detail-row">
            <div class="detail-label">编号：</div>
            <div class="detail-value">#<?php echo $complaint['id']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">反馈类型：</div>
            <div class="detail-value"><?php echo $complaint['type']; ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">所属门店：</div>
            <div class="detail-value"><?php echo htmlspecialchars($complaint['store_name']); ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">问题类别：</div>
            <div class="detail-value"><?php echo htmlspecialchars($complaint['category_name']); ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">当前状态：</div>
            <div class="detail-value">
                <span class="status-badge status-<?php echo str_replace('处理', '', $complaint['status']); ?>">
                    <?php echo $complaint['status']; ?>
                </span>
            </div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">提交时间：</div>
            <div class="detail-value"><?php echo date('Y-m-d H:i:s', strtotime($complaint['created_at'])); ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">联系人：</div>
            <div class="detail-value"><?php echo htmlspecialchars($complaint['customer_name']); ?></div>
        </div>
        
        <div class="detail-row">
            <div class="detail-label">联系电话：</div>
            <div class="detail-value"><?php echo htmlspecialchars($complaint['phone']); ?></div>
        </div>
        
        <h3>反馈内容：</h3>
        <div class="content-box"><?php echo nl2br(htmlspecialchars($complaint['content'])); ?></div>
        
        <?php if (!empty($complaint['admin_remark'])): ?>
        <h3>处理记录：</h3>
        <div class="content-box"><?php echo nl2br(htmlspecialchars($complaint['admin_remark'])); ?></div>
        <?php endif; ?>
        
        <h3>更新处理状态</h3>
        <form method="POST">
            <div class="form-group">
                <label>处理状态：</label>
                <select name="status" class="form-control" required>
                    <option value="待处理" <?php echo $complaint['status'] == '待处理' ? 'selected' : ''; ?>>待处理</option>
                    <option value="处理中" <?php echo $complaint['status'] == '处理中' ? 'selected' : ''; ?>>处理中</option>
                    <option value="已处理" <?php echo $complaint['status'] == '已处理' ? 'selected' : ''; ?>>已处理</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>处理备注：</label>
                <textarea name="remark" class="form-control" 
                          placeholder="请填写处理情况..."><?php echo htmlspecialchars($complaint['admin_remark'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">更新处理记录</button>
            <button type="button" class="btn btn-back" onclick="history.back()">返回列表</button>
        </form>
    </div>
</body>
</html>