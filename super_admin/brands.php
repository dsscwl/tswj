<?php
// super_admin/brands.php - 品牌管理（上级后台）
session_start();
require_once '../config.php';

// 检查是否为超级管理员
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] != 'super_admin') {
    header('Location: ../admin/login.php');
    exit;
}

// 处理品牌操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add_brand') {
        $brand_name = trim($_POST['brand_name']);
        $brand_domain = trim($_POST['brand_domain']);
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (!empty($brand_name) && !empty($brand_domain)) {
            // 创建品牌管理员账号
            $admin_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $brand_name));
            $admin_password = password_hash('123456', PASSWORD_DEFAULT);
            
            $conn->begin_transaction();
            
            try {
                // 插入品牌
                $brand_sql = "INSERT INTO brands (brand_name, brand_domain, contact_person, phone, email) 
                             VALUES (?, ?, ?, ?, ?)";
                $brand_stmt = $conn->prepare($brand_sql);
                $brand_stmt->bind_param("sssss", $brand_name, $brand_domain, $contact_person, $phone, $email);
                $brand_stmt->execute();
                $brand_id = $conn->insert_id;
                
                // 创建品牌管理员
                $admin_sql = "INSERT INTO admin_users (username, password, role, brand_id) 
                             VALUES (?, ?, 'brand_admin', ?)";
                $admin_stmt = $conn->prepare($admin_sql);
                $admin_stmt->bind_param("ssi", $admin_username, $admin_password, $brand_id);
                $admin_stmt->execute();
                
                $conn->commit();
                $message = "品牌创建成功！管理员账号：{$admin_username}，密码：123456";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "创建失败: " . $e->getMessage();
            }
        }
    } elseif ($action == 'update_brand') {
        $id = intval($_POST['brand_id']);
        $brand_name = trim($_POST['brand_name']);
        $brand_domain = trim($_POST['brand_domain']);
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $status = intval($_POST['status']);
        
        $sql = "UPDATE brands SET brand_name = ?, brand_domain = ?, contact_person = ?, 
                phone = ?, email = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", $brand_name, $brand_domain, $contact_person, $phone, $email, $status, $id);
        $stmt->execute();
        $message = "品牌更新成功！";
    }
}

// 获取品牌列表
$result = $conn->query("SELECT b.*, 
                       (SELECT COUNT(*) FROM stores WHERE brand_id = b.id) as store_count,
                       (SELECT COUNT(*) FROM admin_users WHERE brand_id = b.id) as admin_count
                       FROM brands b ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>品牌管理 - 超级后台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial; background: #f5f5f5; margin: 0; }
        .super-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .super-header { background: linear-gradient(135deg, #9b59b6 0%, #34495e 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 20px; }
        .brand-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .brand-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .brand-card:hover { transform: translateY(-5px); }
        .brand-header { background: linear-gradient(135deg, #3498db 0%, #2ecc71 100%); color: white; padding: 20px; }
        .brand-body { padding: 20px; }
        .brand-stats { display: flex; justify-content: space-around; margin: 15px 0; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; }
        .stat-label { font-size: 12px; color: #666; }
        .brand-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 600px; max-width: 90%; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 5px; color: #555; font-weight: 500; }
        .form-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .nav-tabs { display: flex; background: white; border-radius: 10px; overflow: hidden; margin-bottom: 20px; }
        .tab { flex: 1; padding: 15px; text-align: center; cursor: pointer; border-right: 1px solid #eee; }
        .tab.active { background: #3498db; color: white; }
        .tab:last-child { border-right: none; }
    </style>
</head>
<body>
    <div class="super-container">
        <div class="super-header">
            <h1><i class="fas fa-crown"></i> 多品牌管理系统</h1>
            <p>管理所有品牌客户的系统实例</p>
        </div>
        
        <div class="nav-tabs">
            <div class="tab active" onclick="showTab('brands')">品牌管理</div>
            <div class="tab" onclick="showTab('modules')">模块管理</div>
            <div class="tab" onclick="showTab('system')">系统监控</div>
        </div>
        
        <?php if (isset($message)): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div id="brandsTab">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>品牌列表</h2>
                <button class="btn btn-success" onclick="showAddBrandModal()">
                    <i class="fas fa-plus"></i> 添加新品牌
                </button>
            </div>
            
            <div class="brand-grid">
                <?php while ($brand = $result->fetch_assoc()): ?>
                <div class="brand-card">
                    <div class="brand-header">
                        <h3><?php echo htmlspecialchars($brand['brand_name']); ?></h3>
                        <p><?php echo htmlspecialchars($brand['brand_domain']); ?></p>
                    </div>
                    <div class="brand-body">
                        <div class="brand-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $brand['store_count']; ?></div>
                                <div class="stat-label">门店</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $brand['admin_count']; ?></div>
                                <div class="stat-label">管理员</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php echo $brand['status'] == 1 ? '正常' : '禁用'; ?>
                                </div>
                                <div class="stat-label">状态</div>
                            </div>
                        </div>
                        
                        <div style="color: #666; font-size: 13px; margin: 10px 0;">
                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($brand['contact_person']); ?></p>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($brand['phone']); ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($brand['email']); ?></p>
                        </div>
                        
                        <div class="brand-actions">
                            <button class="btn btn-primary" onclick="editBrand(<?php echo $brand['id']; ?>)">
                                <i class="fas fa-edit"></i> 编辑
                            </button>
                            <a href="../admin/login.php?brand=<?php echo $brand['id']; ?>" 
                               class="btn btn-success" target="_blank">
                                <i class="fas fa-sign-in-alt"></i> 登录后台
                            </a>
                            <button class="btn btn-danger" onclick="deleteBrand(<?php echo $brand['id']; ?>)">
                                <i class="fas fa-trash"></i> 删除
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    
    <!-- 添加品牌模态框 -->
    <div id="addBrandModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-plus-circle"></i> 添加新品牌</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_brand">
                
                <div class="form-group">
                    <label class="form-label">品牌名称 *</label>
                    <input type="text" name="brand_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">品牌域名 *</label>
                    <input type="text" name="brand_domain" class="form-input" placeholder="例如：brand1.com" required>
                    <small style="color: #666;">用于生成独立访问地址</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">联系人</label>
                    <input type="text" name="contact_person" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">联系电话</label>
                    <input type="text" name="phone" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">邮箱</label>
                    <input type="email" name="email" class="form-input">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">创建品牌</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">取消</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        // 切换标签逻辑
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // 显示对应内容
        document.querySelectorAll('[id$="Tab"]').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(tabName + 'Tab').style.display = 'block';
    }
    
    function showAddBrandModal() {
        document.getElementById('addBrandModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('addBrandModal').style.display = 'none';
    }
    
    function editBrand(id) {
        // 这里应该加载品牌数据并显示编辑模态框
        alert('编辑功能开发中...');
    }
    
    function deleteBrand(id) {
        if (confirm('确定要删除这个品牌吗？所有相关数据也会被删除！')) {
            fetch('ajax_delete_brand.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('删除失败：' + data.message);
                    }
                });
        }
    }
    
    // 点击模态框外部关闭
    window.onclick = function(event) {
        const modal = document.getElementById('addBrandModal');
        if (event.target == modal) {
            closeModal();
        }
    };
    </script>
</body>
</html>