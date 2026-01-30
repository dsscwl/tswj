<?php
require_once '../config.php';
check_admin_login();

if (!isset($_GET['id'])) {
    die('<div class="alert alert-danger">参数错误</div>');
}

$id = intval($_GET['id']);

// 获取投诉详情
$sql = "SELECT c.*, s.name as store_name, cat.name as category_name 
        FROM complaints c 
        LEFT JOIN stores s ON c.store_id = s.id 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        WHERE c.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$complaint) {
    die('<div class="alert alert-danger">数据不存在</div>');
}

// 获取处理记录
$logs_sql = "SELECT * FROM complaint_logs WHERE complaint_id = ? ORDER BY create_time ASC";
$logs_stmt = $pdo->prepare($logs_sql);
$logs_stmt->execute([$id]);
$logs = $logs_stmt->fetchAll();

// 状态映射
$status_map = [
    'pending' => '待处理',
    'processing' => '处理中',
    'resolved' => '已处理'
];

$type_map = [
    'complaint' => '投诉',
    'suggestion' => '建议',
    'praise' => '表扬'
];
?>

<div class="row">
    <div class="col-md-6">
        <h5>基本信息</h5>
        <table class="table table-sm">
            <tr>
                <th width="100">ID：</th>
                <td><?php echo $complaint['id']; ?></td>
            </tr>
            <tr>
                <th>类型：</th>
                <td><?php echo $type_map[$complaint['type']] ?? $complaint['type']; ?></td>
            </tr>
            <tr>
                <th>门店：</th>
                <td><?php echo $complaint['store_name'] ?? '未指定'; ?></td>
            </tr>
            <tr>
                <th>类别：</th>
                <td><?php echo $complaint['category_name'] ?? '未分类'; ?></td>
            </tr>
            <tr>
                <th>状态：</th>
                <td>
                    <span class="badge bg-<?php 
                        switch($complaint['status']) {
                            case 'pending': echo 'warning'; break;
                            case 'processing': echo 'info'; break;
                            case 'resolved': echo 'success'; break;
                            default: echo 'secondary';
                        }
                    ?>">
                        <?php echo $status_map[$complaint['status']] ?? $complaint['status']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>提交时间：</th>
                <td><?php echo date('Y-m-d H:i:s', strtotime($complaint['create_time'])); ?></td>
            </tr>
            <tr>
                <th>姓名：</th>
                <td><?php echo htmlspecialchars($complaint['name']); ?></td>
            </tr>
            <tr>
                <th>电话：</th>
                <td><?php echo htmlspecialchars($complaint['phone']); ?></td>
            </tr>
            <?php if (!empty($complaint['email'])): ?>
            <tr>
                <th>邮箱：</th>
                <td><?php echo htmlspecialchars($complaint['email']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="col-md-6">
        <h5>详细内容</h5>
        <div class="card">
            <div class="card-body">
                <h6><?php echo htmlspecialchars($complaint['title']); ?></h6>
                <hr>
                <p><?php echo nl2br(htmlspecialchars($complaint['content'])); ?></p>
                
                <?php if (!empty($complaint['images'])): ?>
                <hr>
                <h6>相关图片：</h6>
                <div class="row">
                    <?php 
                    $images = explode(',', $complaint['images']);
                    foreach ($images as $image):
                        if (!empty($image)):
                    ?>
                    <div class="col-4 mb-2">
                        <a href="<?php echo '../uploads/' . $image; ?>" target="_blank">
                            <img src="<?php echo '../uploads/' . $image; ?>" class="img-thumbnail" style="max-height: 100px;">
                        </a>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 处理记录 -->
<div class="row mt-4">
    <div class="col-12">
        <h5>处理记录</h5>
        <div class="card">
            <div class="card-body">
                <?php if (empty($logs)): ?>
                <div class="text-muted">暂无处理记录</div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($logs as $log): ?>
                    <div class="timeline-item mb-3">
                        <div class="timeline-header">
                            <strong><?php echo htmlspecialchars($log['operator']); ?></strong>
                            <span class="text-muted float-end">
                                <?php echo date('Y-m-d H:i', strtotime($log['create_time'])); ?>
                            </span>
                        </div>
                        <div class="timeline-body">
                            <?php echo nl2br(htmlspecialchars($log['content'])); ?>
                            <?php if (!empty($log['attachment'])): ?>
                            <div class="mt-2">
                                <a href="<?php echo '../uploads/logs/' . $log['attachment']; ?>" target="_blank">
                                    查看附件
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 处理表单 -->
<div class="row mt-4">
    <div class="col-12">
        <h5>更新处理状态</h5>
        <form id="processForm" onsubmit="return processComplaint(<?php echo $complaint['id']; ?>)">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">状态</label>
                    <select name="status" class="form-select" required>
                        <option value="processing" <?php echo $complaint['status'] == 'processing' ? 'selected' : ''; ?>>处理中</option>
                        <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>已处理</option>
                    </select>
                </div>
                <div class="col-md-9 mb-3">
                    <label class="form-label">处理人</label>
                    <input type="text" name="operator" class="form-control" value="<?php echo $_SESSION['admin_name'] ?? ''; ?>" required>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">处理说明</label>
                    <textarea name="content" class="form-control" rows="3" required placeholder="请输入处理说明..."></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">提交处理</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function processComplaint(id) {
    const form = document.getElementById('processForm');
    const formData = new FormData(form);
    formData.append('complaint_id', id);
    
    fetch('process_complaint.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('处理成功！');
            window.location.reload();
        } else {
            alert('处理失败：' + data.message);
        }
    })
    .catch(error => {
        alert('请求失败');
    });
    
    return false;
}
</script>