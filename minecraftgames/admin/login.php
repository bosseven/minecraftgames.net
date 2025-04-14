<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// 如果用户已登录，重定向到管理面板
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        // 验证用户凭据
        if (validateUser($username, $password)) {
            // 登录成功，设置会话变量
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            
            // 如果勾选了"记住我"，设置cookie
            if (isset($_POST['remember_me']) && $_POST['remember_me'] === '1') {
                $token = generateRememberToken($username);
                setcookie('remember_token', $token, time() + 2592000, '/', '', false, true); // 30天
            }
            
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - Butler游戏管理工具</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 15px;
        }
        .login-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .login-header {
            background-color: #5d9c41;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .login-footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .btn-minecraft {
            background-color: #5d9c41;
            border-color: #3a7127;
            color: white;
        }
        .btn-minecraft:hover {
            background-color: #3a7127;
            color: white;
        }
        .brand-logo {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .form-control:focus {
            border-color: #5d9c41;
            box-shadow: 0 0 0 0.25rem rgba(93, 156, 65, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-logo">
                    <i class="fas fa-gamepad me-2"></i>Butler
                </div>
                <h2>游戏管理工具</h2>
            </div>
            
            <div class="login-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="请输入用户名" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">密码</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="请输入密码" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" value="1">
                        <label class="form-check-label" for="remember_me">记住我</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-minecraft btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>登录
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="login-footer">
                <p class="mb-0">MinecraftGames.net &copy; 2024</p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 