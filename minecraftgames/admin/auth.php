<?php
/**
 * Butler 游戏管理工具认证文件
 */

// 初始化会话（如果尚未初始化）
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 处理注销请求
if (isset($_GET['logout'])) {
    // 清除会话
    $_SESSION = [];
    
    // 如果使用了会话cookie，清除它
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // 销毁会话
    session_destroy();
    
    // 清除记住我cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    
    // 重定向到登录页面
    header('Location: login.php');
    exit;
}

// 如果用户未登录且存在记住我cookie，尝试自动登录
if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
    $username = validateRememberToken($_COOKIE['remember_token']);
    
    if ($username) {
        // 自动登录成功
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        
        // 更新令牌
        $token = generateRememberToken($username);
        setcookie('remember_token', $token, time() + (REMEMBER_ME_DAYS * 86400), '/', '', false, true);
    } else {
        // 令牌无效，清除cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// 清理过期令牌 