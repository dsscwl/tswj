<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<div class="error">请先登录</div>';
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
    echo '<div class="error">未找到该记录或无权访问</div>';
    exit;
}

// 查询处理记录（先检查表是否存在）
$history_sql = "SHOW TABLES LIKE 'complaint_history'";
$table_exists = $conn->query($history_sql)->num_rows > 0;

$history_result = null;
if ($table_exists) {
    $history_sql = "SELECT * FROM complaint_history WHERE complaint_id = ? ORDER BY created_at DESC";
    $history_stmt = $conn->prepare($history_sql);
    $history_stmt->bind_param("i", $id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
}
?>

<style>
.detail-content {
    padding: 20px;
}
.detail-row {
    display: flex;
    margin-bottom: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    align-items: center;
}
.detail-label {
    width: 120px;
    font-weight: bold;
    color: #555;
    flex-shrink: 0;
}
.detail-value {
    flex: 1;
    color: #333;
}
.content-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    white-space: pre-wrap;
    line-height: 1.6;
    border-left: 4px solid #3498db;
}
.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    display: inline-block;
}
.status-pending { background: #ffeaea; color: #e74c3c; }
.status-processing { background: #fff4e6; color: #f39c12; }
.status-resolved { background: #e6f7ed; color: #27ae60; }
.history-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f0;
}
.history-user {
    font-weight: bold;
    color: #333;
}
.history-time {
    color: #666;
    font-size: 12px;
}
.history-content {
    color: #555;
    line-height: 1.6;
}
.process-form {
    background: white;
    border: 2px solid #e3f2fd;
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
}
.form-group {
    margin-bottom: 15px;
}
.form-label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}
textarea.form-control {
    min-height: 100px;
    resize: vertical;
}
.btn-submit {
    background: linear-gradient(to right, #3498db, #2ecc71);
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    width: 100%;
    margin-top: 10px;
}
.btn-submit:hover {
    opacity: 0.9;
}
</style>

<div class="detail-content">
    <div class="detail-row">
        <div class="detail-label">编号：</div>
        <div class="detail-value">#<?php echo $complaint['id']; ?></div>
    </div>
    
    <div class="detail-row">
        <div class="detail-label">反馈类型：</div>
        <div class="detail-value">
            <?php if ($complaint['type'] == '投诉'): ?>
            <span style="color: #e74c3c; font-weight: bold;">投诉</span>
            <?php else: ?>
            <span style="color: #2ecc71; font-weight: bold;">建议</span>
            <?php endif; ?>
        </div>
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
    
    <h3 style="margin: 25px 0 15px 0; color: #333;">反馈内容：</h3>
    <div class="content-box"><?php echo nl2br(htmlspecialchars($complaint['content'])); ?></div>
    
    <!-- 处理记录 -->
    <?php if ($history_result && $history_result->num_rows > 0): ?>
    <h3 style="margin: 30px 0 15px 0; color: #333; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">处理记录</h3>
    
    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
        <?php while ($history = $history_result->fetch_assoc()): ?>
        <div class="history-item">
            <div class="history-header">
                <span class="history-user"><?php echo htmlspecialchars($history['admin_name']); ?></span>
                <span class="history-time"><?php echo date('Y-m-d H:i', strtotime($history['created_at'])); ?></span>
            </div>
            <div class="history-content">
                <p><strong>操作：</strong> <?php echo htmlspecialchars($history['action']); ?></p>
                <p><strong>备注：</strong> <?php echo nl2br(htmlspecialchars($history['remark'])); ?></p>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    
    <!-- 处理表单 -->
    <div class="process-form">
        <h3 style="margin-bottom: 20px; color: #333;">处理此反馈</h3>
        <form id="processForm" onsubmit="event.preventDefault(); submitProcessForm(<?php echo $complaint['id']; ?>)">
            <div class="form-group">
                <label class="form-label">处理状态</label>
                <select name="status" class="form-control">
                    <option value="待处理" <?php echo $complaint['status'] == '待处理' ? 'selected' : ''; ?>>待处理</option>
                    <option value="处理中" <?php echo $complaint['status'] == '处理中' ? 'selected' : ''; ?>>处理中</option>
                    <option value="已处理" <?php echo $complaint['status'] == '已处理' ? 'selected' : ''; ?>>已处理</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">处理备注</label>
                <textarea name="remark" class="form-control" placeholder="请填写处理情况..." required></textarea>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-check"></i> 提交处理
            </button>
        </form>
    </div>
</div>

<script>
function submitProcessForm(complaintId) {
    const form = document.getElementById('processForm');
    const formData = new FormData(form);
    const status = formData.get('status');
    const remark = formData.get('remark');
    
    if (!remark || remark.trim() === '') {
        alert('请填写处理备注');
        return;
    }
    
    // 调用父窗口的函数
    if (window.parent && window.parent.updateStatus) {
        window.parent.updateStatus(complaintId, status, remark);
    } else {
        // 如果没有父窗口，直接提交
        fetch('ajax_update_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + complaintId + '&status=' + encodeURIComponent(status) + 
                  '&remark=' + encodeURIComponent(remark)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('处理成功！');
                if (window.parent && window.parent.closeModal) {
                    window.parent.closeModal();
                    window.parent.location.reload();
                }
            } else {
                alert('处理失败：' + data.message);
            }
        })
        .catch(error => {
            alert('处理出错：' + error);
        });
    }
}
</script>