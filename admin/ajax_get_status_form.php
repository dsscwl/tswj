<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    echo '<div class="error">请先登录</div>';
    exit;
}

$id = intval($_GET['id']);
?>
<div style="padding: 20px;">
    <h3 style="margin-bottom: 20px; color: #333;">处理投诉建议</h3>
    
    <form id="processForm" onsubmit="event.preventDefault(); window.parent.submitStatusForm(<?php echo $id; ?>)">
        <div style="margin-bottom: 15px;">
            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 500;">处理状态</label>
            <select name="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px;">
                <option value="待处理">待处理</option>
                <option value="处理中">处理中</option>
                <option value="已处理">已处理</option>
            </select>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 5px; color: #555; font-weight: 500;">处理备注 *</label>
            <textarea name="remark" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; min-height: 120px;" 
                      placeholder="请详细填写处理情况..." required></textarea>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" style="flex: 1; background: linear-gradient(to right, #3498db, #2ecc71); color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold;">
                <i class="fas fa-check"></i> 确认处理
            </button>
            <button type="button" onclick="window.parent.closeStatusModal()" style="background: #95a5a6; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                取消
            </button>
        </div>
    </form>
</div>