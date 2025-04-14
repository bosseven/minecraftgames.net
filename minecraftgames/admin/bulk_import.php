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

$message = '';
$result = null;

// 处理CSV文件上传
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查是否有文件上传
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $csvFile = $_FILES['csv_file']['tmp_name'];
        $skipHeader = isset($_POST['skip_header']) && $_POST['skip_header'] === '1';
        
        // 验证文件是否为CSV
        $fileType = mime_content_type($csvFile);
        if ($fileType === 'text/csv' || $fileType === 'text/plain' || $fileType === 'application/vnd.ms-excel') {
            // 执行导入
            $result = bulkImportGames($csvFile, $skipHeader);
            
            if ($result['success']) {
                $message = '<div class="alert alert-success">' . $result['message'] . '</div>';
            } else {
                $message = '<div class="alert alert-danger">' . $result['message'] . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">请上传有效的CSV文件。</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">文件上传失败，请重试。</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量导入 - Butler 游戏管理工具</title>
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
                        <i class="fas fa-file-import me-2"></i>批量导入游戏
                    </div>
                    <div class="card-body">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="index.php">游戏管理</a></li>
                                <li class="breadcrumb-item active">批量导入</li>
                            </ol>
                        </nav>
                        
                        <?php if ($result && $result['success']): ?>
                            <div class="alert alert-success">
                                <h4><i class="fas fa-check-circle me-2"></i>导入成功</h4>
                                <p>成功导入 <?php echo $result['imported']; ?> 个游戏。</p>
                                <a href="index.php" class="btn btn-primary">返回游戏列表</a>
                                <button class="btn btn-outline-primary ms-2" onclick="window.location.reload()">继续导入</button>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <h5>CSV文件格式要求</h5>
                                <p>请准备包含以下列的CSV文件：</p>
                                <ol>
                                    <li><strong>游戏标题</strong> - 必填</li>
                                    <li><strong>游戏描述</strong> - 必填</li>
                                    <li><strong>游戏URL</strong> - 必填</li>
                                    <li><strong>图片URL</strong> - 可选</li>
                                </ol>
                                <p>示例：</p>
                                <pre class="bg-light p-2">游戏标题,游戏描述,游戏URL,图片URL
我的世界经典版,体验原版我的世界经典版本,https://classic.minecraft.net,
Townscaper,建造美丽的城镇岛屿,https://oskarstalberg.com/Townscaper,
Krunker.io,一款快节奏的FPS游戏,https://krunker.io,</pre>
                            </div>
                            
                            <form action="bulk_import.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">CSV文件 <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="skip_header" name="skip_header" value="1" checked>
                                    <label class="form-check-label" for="skip_header">
                                        跳过首行（表头）
                                    </label>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-minecraft">
                                        <i class="fas fa-upload me-1"></i>上传并导入
                                    </button>
                                    <a href="index.php" class="btn btn-outline-secondary ms-2">
                                        <i class="fas fa-arrow-left me-1"></i>返回
                                    </a>
                                </div>
                            </form>
                            
                            <div class="mt-4">
                                <h5>下载模板</h5>
                                <p>您可以下载CSV模板文件，填写内容后再上传。</p>
                                <a href="templates/games_template.csv" class="btn btn-outline-secondary">
                                    <i class="fas fa-download me-1"></i>下载CSV模板
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 