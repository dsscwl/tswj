<?php
// 生成密码哈希
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "原始密码: $password\n";
echo "哈希密码: $hashed_password\n";

// 示例输出，复制这个哈希值替换上面的INSERT语句
$sample_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
echo "示例哈希: $sample_hash\n";
?>