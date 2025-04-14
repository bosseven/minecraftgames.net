<?php
session_start();
require_once 'auth.php';
require_once 'config.php';
require_once 'functions.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 检查用户角色
global $users;
$isAdmin = isset($users[$_SESSION['username']]) && $users[$_SESSION['username']]['role'] === 'admin';

$message = '';

// 处理设置更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    if (!$isAdmin) {
        $message = '<div class="alert alert-danger">只有管理员才能更改设置。</div>';
    } else {
        // 更新网站设置
        $siteName = trim($_POST['site_name']);
        $siteDescription = trim($_POST['site_description']);
        $gamesPerPage = (int)$_POST['games_per_page'];
        $autoUpdateIndex = isset($_POST['auto_update_index']) ? 1 : 0;
        
        try {
            $pdo = getDbConnection();
            
            // 更新设置
            $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = $pdo->prepare($updateSql);
            
            $settings = [
                ['site_name', $siteName],
                ['site_description', $siteDescription],
                ['games_per_page', $gamesPerPage],
                ['auto_update_index', $autoUpdateIndex]
            ];
            
            foreach ($settings as $setting) {
                $stmt->execute([$setting[1], $setting[0]]);
            }
            
            $message = '<div class="alert alert-success">设置已成功更新！</div>';
        } catch (PDOException $e) {
            error_log('更新设置失败: ' . $e->getMessage());
            $message = '<div class="alert alert-danger">更新设置失败，请重试。</div>';
        }
    }
}

// 处理数据库导出
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export_database') {
    if (!$isAdmin) {
        $message = '<div class="alert alert-danger">只有管理员才能导出数据库。</div>';
    } else {
        try {
            $pdo = getDbConnection();
            
            // 获取所有游戏数据
            $games = getAllGames();
            
            // 创建CSV内容
            $csvContent = "id,title,description,url,image_url,is_active,date_added,date_updated\n";
            
            foreach ($games as $game) {
                $csvContent .= '"' . $game['id'] . '",';
                $csvContent .= '"' . str_replace('"', '""', $game['title']) . '",';
                $csvContent .= '"' . str_replace('"', '""', $game['description']) . '",';
                $csvContent .= '"' . str_replace('"', '""', $game['url']) . '",';
                $csvContent .= '"' . str_replace('"', '""', $game['image_url']) . '",';
                $csvContent .= '"' . $game['is_active'] . '",';
                $csvContent .= '"' . $game['date_added'] . '",';
                $csvContent .= '"' . ($game['date_updated'] ?? '') . '"' . "\n";
            }
            
            // 设置导出文件路径
            $exportDir = __DIR__ . '/exports';
            if (!file_exists($exportDir)) {
                mkdir($exportDir, 0755, true);
            }
            
            $filename = 'games_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = $exportDir . '/' . $filename;
            
            // 保存CSV文件
            file_put_contents($filepath, $csvContent);
            
            $message = '<div class="alert alert-success">数据库已成功导出！<a href="exports/' . $filename . '" class="btn btn-sm btn-primary ms-2">下载CSV</a></div>';
        } catch (Exception $e) {
            error_log('导出数据库失败: ' . $e->getMessage());
            $message = '<div class="alert alert-danger">导出数据库失败，请重试。</div>';
        }
    }
}

// 处理重建首页
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rebuild_index') {
    try {
        // 获取活跃游戏
        $games = getActiveGames();
        
        // 更新首页
        if (updateIndexPage($games)) {
            $message = '<div class="alert alert-success">首页已成功重建！</div>';
        } else {
            $message = '<div class="alert alert-danger">重建首页失败，请检查文件权限。</div>';
        }
    } catch (Exception $e) {
        error_log('重建首页失败: ' . $e->getMessage());
        $message = '<div class="alert alert-danger">重建首页失败，请重试。</div>';
    }
}

// 获取当前设置
try {
    $pdo = getDbConnection();
    
    $settingsSql = "SELECT setting_key, setting_value FROM settings";
    $stmt = $pdo->query($settingsSql);
    
    $settingsData = [];
    while ($row = $stmt->fetch()) {
        $settingsData[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log('获取设置失败: ' . $e->getMessage());
    $settingsData = [
        'site_name' => 'MinecraftGames',
        'site_description' => 'Minecraft-inspired online games',
        'games_per_page' => 20,
        'auto_update_index' => 1
    ];
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>设置 - Butler 游戏管理工具</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 20px;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
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
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #5d9c41;
            color: white;
            font-weight: bold;
        }
        .settings-section {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-gamepad me-2"></i>Butler 游戏管理系统
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">游戏管理</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="settings.php">设置</a>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <span class="navbar-text me-3">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if ($isAdmin): ?>
                                <span class="badge bg-success">管理员</span>
                            <?php endif; ?>
                        </span>
                        <a href="logout.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i>退出
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <?php if (!empty($message)): ?>
            <?php echo $message; ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cog me-2"></i>系统设置
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">基本设置</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button" role="tab">工具</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab">关于</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content p-3 border border-top-0" id="settingsTabContent">
                            <!-- 基本设置选项卡 -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel">
                                <form action="settings.php" method="post">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="settings-section">
                                        <h5 class="mb-3">网站信息</h5>
                                        
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">网站名称</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settingsData['site_name'] ?? 'MinecraftGames'); ?>" <?php echo $isAdmin ? '' : 'readonly'; ?>>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="site_description" class="form-label">网站描述</label>
                                            <textarea class="form-control" id="site_description" name="site_description" rows="2" <?php echo $isAdmin ? '' : 'readonly'; ?>><?php echo htmlspecialchars($settingsData['site_description'] ?? 'Minecraft-inspired online games'); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="settings-section">
                                        <h5 class="mb-3">显示设置</h5>
                                        
                                        <div class="mb-3">
                                            <label for="games_per_page" class="form-label">每页显示游戏数量</label>
                                            <input type="number" class="form-control" id="games_per_page" name="games_per_page" value="<?php echo (int)($settingsData['games_per_page'] ?? 20); ?>" min="1" max="100" <?php echo $isAdmin ? '' : 'readonly'; ?>>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="auto_update_index" name="auto_update_index" value="1" <?php echo ($settingsData['auto_update_index'] ?? 1) ? 'checked' : ''; ?> <?php echo $isAdmin ? '' : 'disabled'; ?>>
                                            <label class="form-check-label" for="auto_update_index">
                                                自动更新首页内容（添加/编辑游戏时）
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <?php if ($isAdmin): ?>
                                        <button type="submit" class="btn btn-minecraft">
                                            <i class="fas fa-save me-1"></i>保存设置
                                        </button>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-lock me-1"></i>只有管理员才能更改设置
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                            
                            <!-- 工具选项卡 -->
                            <div class="tab-pane fade" id="tools" role="tabpanel">
                                <h5 class="mb-3">维护工具</h5>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light text-dark">
                                                <i class="fas fa-sync me-1"></i>重建首页
                                            </div>
                                            <div class="card-body">
                                                <p>重新生成首页游戏列表，使其与数据库中的活跃游戏保持同步。</p>
                                                <form action="settings.php" method="post">
                                                    <input type="hidden" name="action" value="rebuild_index">
                                                    <button type="submit" class="btn btn-primary">重建首页</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-light text-dark">
                                                <i class="fas fa-download me-1"></i>导出数据库
                                            </div>
                                            <div class="card-body">
                                                <p>将游戏数据导出为CSV文件，方便备份和迁移。</p>
                                                <?php if ($isAdmin): ?>
                                                    <form action="settings.php" method="post">
                                                        <input type="hidden" name="action" value="export_database">
                                                        <button type="submit" class="btn btn-primary">导出CSV</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-primary" disabled>导出CSV</button>
                                                    <small class="text-muted d-block mt-2">需要管理员权限</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h5 class="mb-3">缓存管理</h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>此功能将在未来版本中提供。
                                </div>
                            </div>
                            
                            <!-- 关于选项卡 -->
                            <div class="tab-pane fade" id="about" role="tabpanel">
                                <div class="text-center mb-4">
                                    <h2><i class="fas fa-gamepad me-2"></i>Butler</h2>
                                    <p class="lead">MinecraftGames 游戏管理工具</p>
                                    <div class="badge bg-primary mb-2">版本 1.0.0</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 offset-md-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">关于 Butler</h5>
                                                <p>Butler 是专为 MinecraftGames.net 设计的游戏管理工具，提供简单直观的界面，帮助您管理网站上的 Minecraft 风格游戏。</p>
                                                <p>主要功能：</p>
                                                <ul>
                                                    <li>游戏添加与管理</li>
                                                    <li>批量导入游戏</li>
                                                    <li>首页内容自动更新</li>
                                                    <li>数据备份与恢复</li>
                                                </ul>
                                                <p class="mb-0">如有问题或建议，请联系管理员。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>