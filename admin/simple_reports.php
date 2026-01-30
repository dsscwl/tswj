<?php
session_start();
require_once '../config.php';

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 只允许超级管理员查看报表
if ($_SESSION['role'] != 'super_admin') {
    header('Location: index.php');
    exit;
}

// 获取基本统计数据
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = '待处理' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = '处理中' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status = '已处理' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN type = '投诉' THEN 1 ELSE 0 END) as complaints,
    SUM(CASE WHEN type = '建议' THEN 1 ELSE 0 END) as suggestions
    FROM complaints";

$stats_result = $conn->query($sql);
$stats = $stats_result->fetch_assoc();

// 获取最近投诉
$recent_sql = "SELECT c.*, s.store_name, cat.category_name 
              FROM complaints c 
              LEFT JOIN stores s ON c.store_id = s.id 
              LEFT JOIN categories cat ON c.category_id = cat.id 
              ORDER BY c.created_at DESC LIMIT 10";
$recent_result = $conn->query($recent_sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>简易报表</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .stat-box { background: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 36px; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #666; }
        .table { background: white; border-radius: 10px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        .back-link { color: #3498db; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>数据报表</h1>
            <a href="index.php" class="back-link">← 返回</a>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">总反馈数</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['complaints'] ?? 0; ?></div>
                <div class="stat-label">投诉数量</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['suggestions'] ?? 0; ?></div>
                <div class="stat-label">建议数量</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">待处理</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['processing'] ?? 0; ?></div>
                <div class="stat-label">处理中</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="stat-label">已处理</div>
            </div>
        </div>
        
        <div class="table">
            <h2>最近10条反馈</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>类型</th>
                        <th>门店</th>
                        <th>类别</th>
                        <th>状态</th>
                        <th>提交时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recent_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['type']; ?></td>
                        <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td><?php echo $row['status']; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>