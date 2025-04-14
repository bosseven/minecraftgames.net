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

// 确保提供了游戏ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$gameId = (int)$_GET['id'];
$game = getGame($gameId);

// 确保游戏存在
if (!$game) {
    header('Location: index.php');
    exit;
}

$message = '';

// 处理游戏更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_game') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url = trim($_POST['url']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imageUrl = null; // 默认不更新图片
    
    // 处理图片上传
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/games/';
        
        // 确保上传目录存在
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = basename($_FILES['image']['name']);
        $fileExt = pathinfo($filename, PATHINFO_EXTENSION);
        $safeFilename = generateSafeFilename($title) . '.' . $fileExt;
        $uploadFile = $uploadDir . $safeFilename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $imageUrl = 'images/games/' . $safeFilename;
            
            // 如果有旧图片，删除它
            if (!empty($game['image_url'])) {
                $oldImagePath = realpath(__DIR__ . '/../' . $game['image_url']);
                
                // 确保路径在网站目录内（安全检查）
                $siteRoot = realpath(__DIR__ . '/../');
                if (strpos($oldImagePath, $siteRoot) === 0 && file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }
    }
    
    if (!empty($title) && !empty($url)) {
        if (updateGame($gameId, $title, $description, $url, $imageUrl, $isActive)) {
            $message = '<div class="alert alert-success">游戏信息已成功更新！</div>';
            
            // 重新获取游戏信息
            $game = getGame($gameId);
        } else {
            $message = '<div class="alert alert-danger">游戏信息更新失败，请重试！</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">请填写所有必填字段！</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑游戏 - Butler 游戏管理工具</title>
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
        .game-preview {
            max-width: 300px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .game-preview img {
            max-width: 100%;
            height: auto;
            border-radius: 3px;
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
                            <a class="nav-link" href="settings.php">设置</a>
                        </li>
                    </ul>
                    <div class="d-flex">
                        <span class="navbar-text me-3">
                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['username']); ?>
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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-edit me-2"></i>编辑游戏
                    </div>
                    <div class="card-body">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">游戏管理</a></li>
                                <li class="breadcrumb-item active">编辑游戏</li>
                            </ol>
                        </nav>
                        
                        <form action="edit.php?id=<?php echo $gameId; ?>" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_game">
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">游戏标题 <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($game['title']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">游戏描述 <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($game['description']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="url" class="form-label">游戏URL <span class="text-danger">*</span></label>
                                        <input type="url" class="form-control" id="url" name="url" value="<?php echo htmlspecialchars($game['url']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">游戏截图</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <div class="form-text">上传新图片将替换现有图片。推荐尺寸：600x400像素，最大文件大小：2MB</div>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo $game['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            激活游戏（在前台显示）
                                        </label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">创建日期</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($game['date_added']); ?>" readonly>
                                    </div>
                                    
                                    <?php if (!empty($game['date_updated'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">上次更新</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($game['date_updated']); ?>" readonly>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="game-preview mb-3">
                                        <h5>预览</h5>
                                        <?php if (!empty($game['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars('../' . $game['image_url']); ?>" alt="<?php echo htmlspecialchars($game['title']); ?>" class="img-fluid mb-2">
                                        <?php else: ?>
                                            <div class="alert alert-secondary">无图片预览</div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-2">
                                            <a href="<?php echo htmlspecialchars($game['url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>访问游戏
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-minecraft">
                                    <i class="fas fa-save me-1"></i>保存更改
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-times me-1"></i>取消
                                </a>
                                <button type="button" class="btn btn-danger float-end" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i>删除游戏
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 删除确认模态框 -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">确认删除</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    你确定要删除游戏 "<?php echo htmlspecialchars($game['title']); ?>" 吗？此操作不可撤销。
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <form action="index.php" method="post">
                        <input type="hidden" name="action" value="delete_game">
                        <input type="hidden" name="game_id" value="<?php echo $gameId; ?>">
                        <button type="submit" class="btn btn-danger">删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 