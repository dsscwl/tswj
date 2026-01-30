<?php
echo "=== 数据库连接测试 ===<br><br>";

// 数据库信息
$host = 'localhost';
$dbname = 'tswj_dmykj_cn';
$user = 'tswj_dmykj_cn';
$pass = 'tZr3Z3ZKKRyRp38G';

// 尝试连接
$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    echo "❌ <strong>连接失败！</strong><br>";
    echo "错误：" . mysqli_connect_error() . "<br>";
    echo "<br><br>";
    echo "💡 <strong>可能的原因：</strong><br>";
    echo "1. 数据库名不对<br>";
    echo "2. 用户名/密码错误<br>";
    echo "3. 数据库不存在<br>";
} else {
    echo "✅ <strong>连接成功！</strong><br><br>";
    
    // 检查时间
    $result = mysqli_query($conn, "SELECT NOW() as time");
    $row = mysqli_fetch_assoc($result);
    echo "数据库时间：" . $row['time'] . "<br>";
    
    // 检查表
    $result = mysqli_query($conn, "SELECT * FROM feedback_channels LIMIT 3");
    echo "feedback_channels表记录数：" . mysqli_num_rows($result) . "<br>";
    
    mysqli_close($conn);
}

echo "<br>=== 测试完成 ===";
echo "<br><br><strong>⚠️ 测试完成后请立即删除此文件！</strong>";
?>