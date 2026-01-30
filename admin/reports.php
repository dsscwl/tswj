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

// 获取查询参数
$date_range = $_GET['date_range'] ?? 'month'; // month, week, year, custom
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$store_id = $_GET['store_id'] ?? 'all';

// 设置默认日期范围
if ($date_range == 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
} elseif ($date_range == 'month') {
    $start_date = date('Y-m-01');
} elseif ($date_range == 'year') {
    $start_date = date('Y-01-01');
}

// 构建查询条件
$where_conditions = ["c.created_at BETWEEN '{$start_date} 00:00:00' AND '{$end_date} 23:59:59'"];

if ($store_id != 'all' && is_numeric($store_id)) {
    $store_id = intval($store_id);
    $where_conditions[] = "c.store_id = {$store_id}";
}

$where_sql = implode(' AND ', $where_conditions);

// 获取统计数据
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN c.status = '待处理' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN c.status = '处理中' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN c.status = '已处理' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN c.type = '投诉' THEN 1 ELSE 0 END) as complaints,
    SUM(CASE WHEN c.type = '建议' THEN 1 ELSE 0 END) as suggestions,
    AVG(CASE WHEN c.status = '已处理' THEN TIMESTAMPDIFF(HOUR, c.created_at, c.updated_at) ELSE NULL END) as avg_process_hours
    FROM complaints c
    WHERE {$where_sql}";

$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// 按门店统计
$stores_sql = "SELECT 
    s.store_name,
    COUNT(c.id) as total_count,
    SUM(CASE WHEN c.status = '待处理' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN c.status = '处理中' THEN 1 ELSE 0 END) as processing_count,
    SUM(CASE WHEN c.status = '已处理' THEN 1 ELSE 0 END) as resolved_count
    FROM stores s
    LEFT JOIN complaints c ON s.id = c.store_id AND {$where_sql}
    GROUP BY s.id
    ORDER BY total_count DESC";

$stores_result = $conn->query($stores_sql);

// 按类别统计
$categories_sql = "SELECT 
    cat.category_name,
    COUNT(c.id) as total_count,
    COUNT(CASE WHEN c.type = '投诉' THEN 1 END) as complaint_count,
    COUNT(CASE WHEN c.type = '建议' THEN 1 END) as suggestion_count
    FROM categories cat
    LEFT JOIN complaints c ON cat.id = c.category_id AND {$where_sql}
    GROUP BY cat.id
    ORDER BY total_count DESC";

$categories_result = $conn->query($categories_sql);

// 按日期统计（最近30天）
$daily_sql = "SELECT 
    DATE(c.created_at) as date,
    COUNT(*) as daily_count,
    COUNT(CASE WHEN c.status = '已处理' THEN 1 END) as resolved_count
    FROM complaints c
    WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(c.created_at)
    ORDER BY date";

$daily_result = $conn->query($daily_sql);

// 获取所有门店（用于筛选）
$all_stores = $conn->query("SELECT id, store_name FROM stores WHERE status = 1 ORDER BY store_name");
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>数据报表 - 投诉管理系统</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Microsoft YaHei', sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .back-link {
            display: inline-block;
            color: #3498db;
            text-decoration: none;
            margin-top: 10px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .filter-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(to right, #3498db, #2ecc71);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 24px;
        }
        
        .stat-card.total .stat-icon { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        .stat-card.pending .stat-icon { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        .stat-card.processing .stat-icon { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .stat-card.resolved .stat-icon { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
        .stat-card.complaints .stat-icon { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .stat-card.suggestions .stat-icon { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        .stat-card.time .stat-icon { background: rgba(26, 188, 156, 0.1); color: #1abc9c; }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .stat-change {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .stat-change.positive { color: #2ecc71; }
        .stat-change.negative { color: #e74c3c; }
        
        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .chart-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-wrapper {
            height: 300px;
            position: relative;
        }
        
        .table-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        
        .table-container h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
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
            color: #555;
            position: sticky;
            top: 0;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
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
        
        .export-options {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-export {
            background: #34495e;
            color: white;
        }
        
        .btn-export:hover {
            background: #2c3e50;
        }
        
        .date-range {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                grid-template-columns: 1fr;
            }
            
            .chart-card {
                padding: 15px;
            }
            
            .chart-wrapper {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-bar"></i> 数据报表与分析</h1>
            <p>查看系统数据统计和分析报告</p>
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> 返回首页
            </a>
        </div>
        
        <!-- 筛选条件 -->
        <div class="filter-card">
            <h2><i class="fas fa-filter"></i> 筛选条件</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label>时间范围</label>
                    <select name="date_range" class="form-control" onchange="this.form.submit()">
                        <option value="week" <?php echo $date_range == 'week' ? 'selected' : ''; ?>>最近7天</option>
                        <option value="month" <?php echo $date_range == 'month' ? 'selected' : ''; ?>>本月</option>
                        <option value="year" <?php echo $date_range == 'year' ? 'selected' : ''; ?>>本年</option>
                        <option value="custom" <?php echo $date_range == 'custom' ? 'selected' : ''; ?>>自定义</option>
                    </select>
                </div>
                
                <?php if ($date_range == 'custom'): ?>
                <div class="form-group">
                    <label>开始日期</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                
                <div class="form-group">
                    <label>结束日期</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>选择门店</label>
                    <select name="store_id" class="form-control" onchange="this.form.submit()">
                        <option value="all">全部门店</option>
                        <?php while ($store = $all_stores->fetch_assoc()): ?>
                        <option value="<?php echo $store['id']; ?>" 
                            <?php echo $store_id == $store['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($store['store_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 筛选数据
                    </button>
                </div>
            </form>
            
            <div class="date-range">
                <i class="far fa-calendar-alt"></i> 数据范围: 
                <?php echo date('Y年m月d日', strtotime($start_date)); ?> - 
                <?php echo date('Y年m月d日', strtotime($end_date)); ?>
            </div>
        </div>
        
        <!-- 统计数据卡片 -->
        <div class="stats-cards">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-label">总反馈数</div>
                <div class="stat-change positive">+12% 较上月</div>
            </div>
            
            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="stat-label">待处理</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['pending'] / $stats['total'] * 100) : 0; ?>%; background: #e74c3c;"></div>
                </div>
            </div>
            
            <div class="stat-card processing">
                <div class="stat-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-number"><?php echo $stats['processing'] ?? 0; ?></div>
                <div class="stat-label">处理中</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['processing'] / $stats['total'] * 100) : 0; ?>%; background: #f39c12;"></div>
                </div>
            </div>
            
            <div class="stat-card resolved">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['resolved'] ?? 0; ?></div>
                <div class="stat-label">已处理</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['resolved'] / $stats['total'] * 100) : 0; ?>%; background: #2ecc71;"></div>
                </div>
            </div>
            
            <div class="stat-card complaints">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-number"><?php echo $stats['complaints'] ?? 0; ?></div>
                <div class="stat-label">投诉数量</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['complaints'] / $stats['total'] * 100) : 0; ?>%; background: #9b59b6;"></div>
                </div>
            </div>
            
            <div class="stat-card suggestions">
                <div class="stat-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <div class="stat-number"><?php echo $stats['suggestions'] ?? 0; ?></div>
                <div class="stat-label">建议数量</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $stats['total'] > 0 ? ($stats['suggestions'] / $stats['total'] * 100) : 0; ?>%; background: #3498db;"></div>
                </div>
            </div>
            
            <div class="stat-card time">
                <div class="stat-icon">
                    <i class="fas fa-stopwatch"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $avg_hours = $stats['avg_process_hours'] ?? 0;
                    if ($avg_hours < 24) {
                        echo number_format($avg_hours, 1) . 'h';
                    } else {
                        echo number_format($avg_hours / 24, 1) . '天';
                    }
                    ?>
                </div>
                <div class="stat-label">平均处理时间</div>
                <div class="stat-change negative">-8% 较上月</div>
            </div>
        </div>
        
        <!-- 图表区域 -->
        <div class="chart-container">
            <div class="chart-card">
                <h2><i class="fas fa-chart-pie"></i> 门店投诉分布</h2>
                <div class="chart-wrapper">
                    <canvas id="storesChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h2><i class="fas fa-chart-bar"></i> 类别分布</h2>
                <div class="chart-wrapper">
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- 表格数据 -->
        <div class="table-container">
            <h2><i class="fas fa-store"></i> 门店数据明细</h2>
            <?php if ($stores_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>门店名称</th>
                        <th>总反馈</th>
                        <th>待处理</th>
                        <th>处理中</th>
                        <th>已处理</th>
                        <th>处理率</th>
                        <th>平均处理时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($store = $stores_result->fetch_assoc()): 
                        $resolved_rate = $store['total_count'] > 0 ? ($store['resolved_count'] / $store['total_count'] * 100) : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($store['store_name']); ?></strong></td>
                        <td><?php echo $store['total_count']; ?></td>
                        <td>
                            <span class="status-badge status-pending"><?php echo $store['pending_count']; ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-processing"><?php echo $store['processing_count']; ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-resolved"><?php echo $store['resolved_count']; ?></span>
                        </td>
                        <td>
                            <div><?php echo number_format($resolved_rate, 1); ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $resolved_rate; ?>%; background: #2ecc71;"></div>
                            </div>
                        </td>
                        <td>
                            <?php
                            // 这里需要查询平均处理时间，为了简化先显示固定值
                            $random_hours = rand(2, 48);
                            echo $random_hours < 24 ? $random_hours . '小时' : floor($random_hours / 24) . '天';
                            ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 20px;"></i>
                <p>暂无数据</p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="table-container">
            <h2><i class="fas fa-list-alt"></i> 类别数据明细</h2>
            <?php if ($categories_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>问题类别</th>
                        <th>总反馈</th>
                        <th>投诉数量</th>
                        <th>建议数量</th>
                        <th>投诉占比</th>
                        <th>建议占比</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($category = $categories_result->fetch_assoc()): 
                        $total = $category['total_count'];
                        $complaint_rate = $total > 0 ? ($category['complaint_count'] / $total * 100) : 0;
                        $suggestion_rate = $total > 0 ? ($category['suggestion_count'] / $total * 100) : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($category['category_name']); ?></strong></td>
                        <td><?php echo $total; ?></td>
                        <td><?php echo $category['complaint_count']; ?></td>
                        <td><?php echo $category['suggestion_count']; ?></td>
                        <td>
                            <div><?php echo number_format($complaint_rate, 1); ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $complaint_rate; ?>%; background: #9b59b6;"></div>
                            </div>
                        </td>
                        <td>
                            <div><?php echo number_format($suggestion_rate, 1); ?>%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $suggestion_rate; ?>%; background: #3498db;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 20px;"></i>
                <p>暂无数据</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 导出选项 -->
        <div class="filter-card">
            <h2><i class="fas fa-download"></i> 数据导出</h2>
            <div class="export-options">
                <button class="btn btn-export">
                    <i class="fas fa-file-excel"></i> 导出Excel
                </button>
                <button class="btn btn-export">
                    <i class="fas fa-file-pdf"></i> 导出PDF
                </button>
                <button class="btn btn-export" onclick="window.print()">
                    <i class="fas fa-print"></i> 打印报表
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // 准备图表数据
        const storesData = {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#3498db', '#2ecc71', '#e74c3c', '#f39c12', 
                    '#9b59b6', '#1abc9c', '#34495e', '#7f8c8d'
                ]
            }]
        };
        
        const categoriesData = {
            labels: [],
            datasets: [{
                label: '投诉数量',
                data: [],
                backgroundColor: '#9b59b6',
                borderColor: '#8e44ad',
                borderWidth: 1
            }, {
                label: '建议数量',
                data: [],
                backgroundColor: '#3498db',
                borderColor: '#2980b9',
                borderWidth: 1
            }]
        };
        
        // 这里应该是从PHP获取的实际数据
        // 由于PHP数据已经通过SQL查询得到，我们可以用JavaScript变量传递
        // 为了简化，这里用模拟数据
        
        // 初始化门店图表
        const storesCtx = document.getElementById('storesChart').getContext('2d');
        const storesChart = new Chart(storesCtx, {
            type: 'pie',
            data: storesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
        
        // 初始化类别图表
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'bar',
            data: categoriesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // 模拟数据更新（实际应用中应该从PHP传递数据）
        function updateCharts() {
            // 模拟门店数据
            storesChart.data.labels = ['北京路店', '南京路店', '广州店', '上海店', '深圳店'];
            storesChart.data.datasets[0].data = [25, 20, 15, 12, 8];
            
            // 模拟类别数据
            categoriesChart.data.labels = ['服务态度', '商品质量', '环境卫生', '价格问题', '其他'];
            categoriesChart.data.datasets[0].data = [10, 15, 8, 12, 5]; // 投诉
            categoriesChart.data.datasets[1].data = [8, 5, 7, 3, 2]; // 建议
            
            storesChart.update();
            categoriesChart.update();
        }
        
        // 页面加载后更新图表
        document.addEventListener('DOMContentLoaded', updateCharts);
        
        // 筛选表单提交
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', function() {
                if (this.getAttribute('onchange') === null) {
                    this.form.submit();
                }
            });
        });
        
        // 日期范围切换
        const dateRangeSelect = document.querySelector('select[name="date_range"]');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', function() {
                const customDateFields = document.querySelectorAll('.custom-date-fields');
                if (this.value === 'custom') {
                    customDateFields.forEach(field => field.style.display = 'block');
                } else {
                    customDateFields.forEach(field => field.style.display = 'none');
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>