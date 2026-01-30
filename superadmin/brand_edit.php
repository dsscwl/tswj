<?php
require_once 'config.php';
check_superadmin_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $id > 0;

// 获取品牌信息（编辑时）
if ($is_edit) {
    $sql = "SELECT * FROM brands WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$brand) {
        $_SESSION['error_message'] = '品牌不存在';
        header('Location: brands.php');
        exit();
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand_name = safe_input($_POST['brand_name']);
    $brand_code = safe_input($_POST['brand_code']);
    $contact_person = safe_input($_POST['contact_person']);
    $contact_phone = safe_input($_POST['contact_phone']);
    $contact_email = safe_input($_POST['contact_email']);
    $status = $_POST['status'];
    $max_stores = intval($_POST['max_stores']);
    $max_users = intval($_POST['max_users']);
    $expire_date = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;
    
    // 验证品牌代码唯一性
    if ($is_edit) {
        $check_sql = "SELECT COUNT(*) FROM brands WHERE brand_code = ? AND id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$brand_code, $id]);
    } else {
        $check_sql = "SELECT COUNT(*) FROM brands WHERE brand_code = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$brand_code]);
    }
    
    if ($check_stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = '品牌代码已存在，请更换其他代码';
        header('Location: ' . ($is_edit ? "brand_edit.php?id=$id" : 'brand_edit.php'));
        exit();
    }
    
    if ($is_edit) {
        // 更新品牌
        $sql = "UPDATE brands SET 
                brand_name = ?, brand_code = ?, contact_person = ?, contact_phone = ?, 
                contact_email = ?, status = ?, max_stores = ?, max_users = ?, expire_date = ?,
                update_time = NOW() 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $brand_name, $brand_code, $contact_person, $contact_phone, 
            $contact_email, $status, $max_stores, $max_users, $expire_date, $id
        ]);
        
        log_action('更新品牌', "更新品牌: {$brand_name} (ID: {$id})");
        $_SESSION['success_message'] = '品牌更新成功';
    } else {
        // 添加品牌
        $sql = "INSERT INTO brands (brand_name, brand_code, contact_person, contact_phone, 
                contact_email, status, max_stores, max_users, expire_date, create_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $brand_name, $brand_code, $contact_person, $contact_phone, 
            $contact_email, $status, $max_stores, $max_users, $expire_date
        ]);
        
        $new_id = $pdo->lastInsertId();
        log_action('添加品牌', "添加品牌: {$brand_name} (ID: {$new_id})");
        $_SESSION['success_message'] = '品牌添加成功';
    }
    
    header('Location: brands.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? '编辑品牌' : '添加品牌'; ?> - 超级管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <nav class="navbar navbar-light navbar-custom">
            <div class="container-fluid">
                <span class="navbar-brand"><?php echo $is_edit ? '编辑品牌' : '添加品牌'; ?></span>
                <a href="brands.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> 返回列表
                </a>
            </div>
        </nav>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header card-header-custom">
                        <h5 class="mb-0"><?php echo $is_edit ? '编辑品牌信息' : '创建新品牌'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">品牌名称 *</label>
                                    <input type="text" name="brand_name" class="form-control" required
                                           value="<?php echo $brand['brand_name'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">品牌代码 *</label>
                                    <div class="input-group">
                                        <input type="text" name="brand_code" class="form-control" required
                                               value="<?php echo $brand['brand_code'] ?? generate_brand_code($brand['brand_name'] ?? ''); ?>">
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                            <i class="bi bi-shuffle"></i> 生成
                                        </button>
                                    </div>
                                    <small class="text-muted">用于生成专属链接，只能包含字母和数字</small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">联系人 *</label>
                                    <input type="text" name="contact_person" class="form-control" required
                                           value="<?php echo $brand['contact_person'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">联系电话 *</label>
                                    <input type="tel" name="contact_phone" class="form-control" required
                                           value="<?php echo $brand['contact_phone'] ?? ''; ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">联系邮箱</label>
                                    <input type="email" name="contact_email" class="form-control"
                                           value="<?php echo $brand['contact_email'] ?? ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">最大门店数</label>
                                    <input type="number" name="max_stores" class="form-control" min="1" max="999"
                                           value="<?php echo $brand['max_stores'] ?? 10; ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">最大用户数</label>
                                    <input type="number" name="max_users" class="form-control" min="1" max="999"
                                           value="<?php echo $brand['max_users'] ?? 5; ?>">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">状态</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo ($brand['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>启用</option>
                                        <option value="inactive" <?php echo ($brand['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>停用</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">过期时间</label>
                                    <input type="date" name="expire_date" class="form-control"
                                           value="<?php echo $brand['expire_date'] ?? ''; ?>">
                                    <small class="text-muted">留空表示永不过期</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">专属访问链接</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" readonly
                                           id="brandLink" 
                                           value="<?php echo $site_url; ?>/?brand=<?php echo $brand['brand_code'] ?? ''; ?>">
                                    <button type="button" class="btn btn-outline-primary" onclick="copyLink()">
                                        <i class="bi bi-clipboard"></i> 复制
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save"></i> <?php echo $is_edit ? '保存更改' : '创建品牌'; ?>
                                </button>
                                <a href="brands.php" class="btn btn-secondary btn-lg">取消</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function generateCode() {
            var name = document.querySelector('[name="brand_name"]').value;
            if (!name) {
                alert('请先输入品牌名称');
                return;
            }
            
            // 简单生成代码：移除特殊字符，转换为小写
            var code = name.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
            if (code.length > 20) code = code.substring(0, 20);
            
            // 添加时间戳防止重复
            var timestamp = new Date().getTime().toString().slice(-4);
            code = code + timestamp;
            
            document.querySelector('[name="brand_code"]').value = code;
            updateLink();
        }
        
        function updateLink() {
            var code = document.querySelector('[name="brand_code"]').value;
            if (code) {
                document.getElementById('brandLink').value = '<?php echo $site_url; ?>/?brand=' + code;
            }
        }
        
        function copyLink() {
            var linkInput = document.getElementById('brandLink');
            linkInput.select();
            document.execCommand('copy');
            alert('链接已复制到剪贴板');
        }
        
        // 监听品牌代码变化
        document.querySelector('[name="brand_code"]').addEventListener('input', updateLink);
        document.querySelector('[name="brand_name"]').addEventListener('input', function() {
            if (!document.querySelector('[name="brand_code"]').value) {
                updateLink();
            }
        });
        
        // 页面加载时更新链接
        window.addEventListener('load', updateLink);
    </script>
</body>
</html>