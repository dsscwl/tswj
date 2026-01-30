<?php
session_start();
require_once '../config.php';

// 检查登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户信息
$admin_id = $_SESSION['admin_id'];
$role = $_SESSION['role'];
$store_id = $_SESSION['store_id'];

// 构建查询条件
$where = "WHERE 1=1";
if ($role == 'store_admin' && $store_id) {
    $where .= " AND c.store_id = $store_id";
}

// 获取统计数据
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = '待处理' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = '处理中' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status = '已处理' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN type = '投诉' THEN 1 ELSE 0 END) as complaints,
    SUM(CASE WHEN type = '建议' THEN 1 ELSE 0 END) as suggestions
    FROM complaints c $where";

$stats = $conn->query($stats_sql)->fetch_assoc();

// 获取投诉列表
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$list_sql = "SELECT c.*, s.store_name, cat.category_name 
            FROM complaints c 
            LEFT JOIN stores s ON c.store_id = s.id 
            LEFT JOIN categories cat ON c.category_id = cat.id 
            $where 
            ORDER BY c.created_at DESC 
            LIMIT $limit OFFSET $offset";

$list_result = $conn->query($list_sql);

// 获取总记录数用于分页
$total_sql = "SELECT COUNT(*) as total FROM complaints c $where";
$total_row = $conn->query($total_sql)->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投诉管理后台</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            font-size: 20px;
            font-weight: bold;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            display: flex;
            min-height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }
        
        .sidebar ul {
            list-style: none;
        }
        
        .sidebar li a {
            display: block;
            padding: 15px 30px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar li a:hover,
        .sidebar li a.active {
            background: #f8f9fa;
            border-left-color: #667eea;
            color: #667eea;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card.pending .stat-number { color: #e74c3c; }
        .stat-card.processing .stat-number { color: #f39c12; }
        .stat-card.resolved .stat-number { color: #27ae60; }
        
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending {
            background: #ffeaea;
            color: #e74c3c;
        }
        
        .status-processing {
            background: #fff4e6;
            color: #f39c12;
        }
        
        .status-resolved {
            background: #e6f7ed;
            color: #27ae60;
        }
        
        .btn-action {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
        }
        
        .btn-edit {
            background: #2ecc71;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .page-link {
            padding: 8px 15px;
            background: white;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .page-link:hover,
        .page-link.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">📊 投诉管理系统</div>
        <div class="user-info">
            <span>欢迎，<?php echo $_SESSION['username']; ?></span>
            <button class="logout-btn" onclick="location.href='logout.php'">退出登录</button>
        </div>
    </div>
    
    <div class="container">
       <div class="sidebar">
    <ul>
        <li><a href="index.php" class="active">📋 投诉列表</a></li>
        <li><a href="stores.php">🏪 门店管理</a></li>
        <li><a href="categories.php">📂 分类管理</a></li>
        <?php if ($role == 'super_admin'): ?>
        <li><a href="tags.php">🏷️ 问题标签</a></li>        <!-- 新增 -->
        <li><a href="channels.php">📡 反馈渠道</a></li>      <!-- 新增 -->
        <li><a href="admins.php">👥 管理员管理</a></li>
        <li><a href="reports.php">📈 数据报表</a></li>
        <li><a href="settings.php">⚙️ 系统设置</a></li>     <!-- 新增 -->
        <?php endif; ?>
    </ul>
</div>
        
        <div class="main-content">
            <!-- 统计卡片 -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h3>总反馈数</h3>
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                </div>
                <div class="stat-card pending">
                    <h3>待处理</h3>
                    <div class="stat-number"><?php echo $stats['pending']; ?></div>
                </div>
                <div class="stat-card processing">
                    <h3>处理中</h3>
                    <div class="stat-number"><?php echo $stats['processing']; ?></div>
                </div>
                <div class="stat-card resolved">
                    <h3>已处理</h3>
                    <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                </div>
                <div class="stat-card">
                    <h3>投诉/建议</h3>
                    <div class="stat-number"><?php echo $stats['complaints']; ?>/<?php echo $stats['suggestions']; ?></div>
                </div>
            </div>
            
            <!-- 投诉列表 -->
            <div class="table-container">
                <h2 style="margin-bottom: 20px;">投诉建议列表</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>类型</th>
                            <th>门店</th>
                            <th>类别</th>
                            <th>内容</th>
                            <th>联系人</th>
                            <th>电话</th>
                            <th>状态</th>
                            <th>提交时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $list_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['type']; ?></td>
                            <td><?php echo htmlspecialchars($row['store_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td style="max-width: 200px;">
                                <?php echo mb_substr($row['content'], 0, 30, 'UTF-8'); ?>...
                            </td>
                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo str_replace('处理', '', $row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                <button class="btn-action btn-view" onclick="viewDetail(<?php echo $row['id']; ?>)">查看</button>
                                <button class="btn-action btn-edit" onclick="editStatus(<?php echo $row['id']; ?>)">处理</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function viewDetail(id) {
        window.open('detail.php?id=' + id, '_blank');
    }
    
    function editStatus(id) {
        const status = prompt('请输入处理状态（待处理、处理中、已处理）：');
        if (status) {
            fetch('update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id + '&status=' + status
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('状态更新成功');
                    location.reload();
                } else {
                    alert('更新失败：' + data.message);
                }
            });
        }
    }
    </script>
</body>
</html>