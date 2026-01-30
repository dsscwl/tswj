<?php
/**
 * 获取系统配置
 */
function get_config($key, $default = '') {
    global $pdo;
    
    $sql = "SELECT config_value FROM system_config WHERE config_key = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['config_value'] : $default;
}

/**
 * 获取所有菜单配置
 */
function get_menu_configs() {
    global $pdo;
    
    $sql = "SELECT config_key, config_value FROM system_config WHERE config_group = 'menu'";
    $stmt = $pdo->query($sql);
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return $configs;
}
?>