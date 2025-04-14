<?php
/**
 * Butler 游戏管理工具配置文件
 */

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'minecraftgames');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 用户配置（管理员账户信息）
// 注意：在生产环境中，应使用更安全的密码存储方式
$users = [
    'admin' => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'role' => 'admin'
    ]
];

// 应用配置
define('SITE_NAME', 'MinecraftGames');
define('BASE_URL', 'https://minecraftgames.net');
define('ADMIN_EMAIL', 'admin@minecraftgames.net');

// 安全配置
define('CSRF_TOKEN_TIME', 3600); // 1小时
define('REMEMBER_ME_DAYS', 30); // 30天
define('SESSION_LIFETIME', 7200); // 2小时

// 游戏配置
define('MAX_UPLOAD_SIZE', 2097152); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('GAMES_PER_PAGE', 20); // 每页显示的游戏数

// 数据库连接
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // 记录错误但不显示敏感信息
            error_log('数据库连接失败: ' . $e->getMessage());
            die('数据库连接失败，请联系管理员。');
        }
    }
    
    return $pdo;
}

// 导入SQL架构文件（首次安装时使用）
function importSchema() {
    try {
        $pdo = getDbConnection();
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log('导入数据库架构失败: ' . $e->getMessage());
        return false;
    }
}

// 检查是否需要初始化数据库
function checkDatabaseSetup() {
    try {
        $pdo = getDbConnection();
        $statement = $pdo->query("SHOW TABLES LIKE 'games'");
        
        if ($statement->rowCount() === 0) {
            // 如果没有games表，执行初始化
            return importSchema();
        }
        
        return true; // 数据库已设置
    } catch (PDOException $e) {
        error_log('检查数据库设置失败: ' . $e->getMessage());
        return false;
    }
}

// 初始化会话设置
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME, '/', '', isset($_SERVER['HTTPS']), true);

// 检查数据库设置（自动运行）
if (!defined('SKIP_DB_CHECK')) {
    checkDatabaseSetup();
} 