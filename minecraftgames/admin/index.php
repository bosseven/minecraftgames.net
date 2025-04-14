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

// 处理游戏添加
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_game') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $url = trim($_POST['url']);
    $imageUrl = '';
    
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
        }
    }
    
    if (!empty($title) && !empty($description) && !empty($url)) {
        if (addGame($title, $description, $url, $imageUrl)) {
            $message = '<div class="alert alert-success">游戏添加成功！</div>';
        } else {
            $message = '<div class="alert alert-danger">游戏添加失败，请重试！</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">请填写所有必填字段！</div>';
    }
}

// 处理游戏删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_game') {
    $gameId = (int)$_POST['game_id'];
    if (deleteGame($gameId)) {
        $message = '<div class="alert alert-success">游戏已成功删除！</div>';
    } else {
        $message = '<div class="alert alert-danger">游戏删除失败，请重试！</div>';
    }
}

// 获取所有游戏
$games = getAllGames();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Butler - 游戏管理工具</title>
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
        .table-games {
            margin-top: 20px;
        }
        .nav-link.active {
            background-color: #5d9c41 !important;
            color: white !important;
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
                            <a class="nav-link active" href="index.php">游戏管理</a>
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
            <div class="col-md-12 mb-4">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="games-tab" data-bs-toggle="tab" data-bs-target="#games" type="button" role="tab">游戏列表</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">添加游戏</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bulk-tab" data-bs-toggle="tab" data-bs-target="#bulk" type="button" role="tab">批量导入</button>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active p-3 bg-white border border-top-0" id="games" role="tabpanel">
                        <?php if (empty($games)): ?>
                            <div class="alert alert-info">
                                暂无游戏记录，请添加游戏。
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-games">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>标题</th>
                                            <th>URL</th>
                                            <th>添加日期</th>
                                            <th>状态</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($game['id']); ?></td>
                                                <td><?php echo htmlspecialchars($game['title']); ?></td>
                                                <td>
                                                    <a href="<?php echo htmlspecialchars($game['url']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars(substr($game['url'], 0, 40)) . (strlen($game['url']) > 40 ? '...' : ''); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($game['date_added']); ?></td>
                                                <td>
                                                    <?php if ($game['is_active']): ?>
                                                        <span class="badge bg-success">可用</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">禁用</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="edit.php?id=<?php echo $game['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger delete-game" data-id="<?php echo $game['id']; ?>" data-title="<?php echo htmlspecialchars($game['title']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="tab-pane fade p-3 bg-white border border-top-0" id="add" role="tabpanel">
                        <form action="index.php" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_game">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">游戏标题 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">游戏描述 <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="url" class="form-label">游戏URL <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">游戏截图</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <div class="form-text">推荐尺寸：600x400像素，最大文件大小：2MB</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">
                                    立即激活
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-minecraft">
                                <i class="fas fa-plus me-1"></i>添加游戏
                            </button>
                        </form>
                    </div>
                    <div class="tab-pane fade p-3 bg-white border border-top-0" id="bulk" role="tabpanel">
                        <form action="bulk_import.php" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="csv_file" class="form-label">CSV文件 <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                <div class="form-text">上传CSV文件进行批量导入，格式：标题,描述,URL,图片URL(可选)</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="skip_header" name="skip_header" value="1" checked>
                                <label class="form-check-label" for="skip_header">
                                    跳过首行（表头）
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-minecraft">
                                <i class="fas fa-file-import me-1"></i>导入游戏
                            </button>
                        </form>
                        
                        <div class="mt-4">
                            <h5>模板下载</h5>
                            <a href="templates/games_template.csv" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>下载CSV模板
                            </a>
                        </div>
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
                    你确定要删除游戏 "<span id="gameTitle"></span>" 吗？此操作不可撤销。
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <form action="index.php" method="post" id="deleteForm">
                        <input type="hidden" name="action" value="delete_game">
                        <input type="hidden" name="game_id" id="gameId" value="">
                        <button type="submit" class="btn btn-danger">删除</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 处理删除按钮点击
            const deleteButtons = document.querySelectorAll('.delete-game');
            const gameTitle = document.getElementById('gameTitle');
            const gameId = document.getElementById('gameId');
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    
                    gameId.value = id;
                    gameTitle.textContent = title;
                    
                    deleteModal.show();
                });
            });
        });
    </script>
</body>
</html> 