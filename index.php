<?php
require_once 'config.php';

// 获取品牌参数
$brand_code = isset($_GET['brand']) ? safe_input($_GET['brand']) : '';

if (!empty($brand_code)) {
    // 验证品牌是否存在且启用
    $sql = "SELECT * FROM brands WHERE brand_code = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$brand_code]);
    $brand = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($brand) {
        $brand_id = $brand['id'];
        $brand_name = $brand['brand_name'];
        $_SESSION['brand_id'] = $brand_id;
    }
}

// 获取门店列表（如果指定品牌，只显示该品牌的门店）
if (isset($brand_id) && $brand_id > 0) {
    $stores = $pdo->prepare("SELECT id, name FROM stores WHERE brand_id = ? ORDER BY name");
    $stores->execute([$brand_id]);
} else {
    $stores = $pdo->query("SELECT id, name FROM stores ORDER BY name")->fetchAll();
}

$stores = $stores->fetchAll();

// 获取分类列表
if (isset($brand_id) && $brand_id > 0) {
    $categories = $pdo->prepare("SELECT id, name FROM categories WHERE brand_id = ? ORDER BY name");
    $categories->execute([$brand_id]);
} else {
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
}

$categories = $categories->fetchAll();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = safe_input($_POST['type']);
    $store_id = intval($_POST['store_id']);
    $category_id = intval($_POST['category_id']);
    $name = safe_input($_POST['name']);
    $phone = safe_input($_POST['phone']);
    $email = safe_input($_POST['email']);
    $title = safe_input($_POST['title']);
    $content = safe_input($_POST['content']);
    $brand_id = isset($_POST['brand_id']) ? intval($_POST['brand_id']) : 0;
    
    // 图片上传处理
    $images = [];
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = time() . '_' . uniqid() . '_' . $_FILES['images']['name'][$key];
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $images[] = $file_name;
                }
            }
        }
    }
    
    $images_str = implode(',', $images);
    
    // 插入数据
    $sql = "INSERT INTO complaints (brand_id, type, store_id, category_id, name, phone, email, title, content, images, status, create_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$brand_id, $type, $store_id, $category_id, $name, $phone, $email, $title, $content, $images_str]);
    
    $success_message = '提交成功！感谢您的反馈。';
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($brand_name) ? $brand_name : ''; ?> - 投诉建议反馈</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header text-center">
                        <h4><?php echo isset($brand_name) ? $brand_name : ''; ?> 投诉建议反馈表</h4>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                    <div class="alert alert-success text-center">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if (isset($brand_id)): ?>
                            <input type="hidden" name="brand_id" value="<?php echo $brand_id; ?>">
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">反馈类型 *</label>
                                    <select name="type" class="form-select" required>
                                        <option value="complaint">投诉</option>
                                        <option value="suggestion">建议</option>
                                        <option value="praise">表扬</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">选择门店</label>
                                    <select name="store_id" class="form-select">
                                        <option value="0">请选择门店</option>
                                        <?php foreach ($stores as $store): ?>
                                        <option value="<?php echo $store['id']; ?>"><?php echo $store['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">分类</label>
                                    <select name="category_id" class="form-select">
                                        <option value="0">请选择分类</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">您的姓名 *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">联系电话 *</label>
                                    <input type="tel" name="phone" class="form-control" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">电子邮箱</label>
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">标题 *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">详细内容 *</label>
                                <textarea name="content" class="form-control" rows="5" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">上传图片（最多3张）</label>
                                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                                <small class="text-muted">支持 JPG, PNG 格式，每张图片不超过2MB</small>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">提交反馈</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>