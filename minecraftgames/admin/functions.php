<?php
/**
 * Butler 游戏管理工具函数库
 */

// 验证用户登录
function validateUser($username, $password) {
    global $users;
    
    if (isset($users[$username])) {
        return password_verify($password, $users[$username]['password']);
    }
    
    return false;
}

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// 生成"记住我"令牌
function generateRememberToken($username) {
    $token = bin2hex(random_bytes(32));
    $expires = time() + (REMEMBER_ME_DAYS * 86400);
    
    // 在数据库中保存令牌（简化版，实际应用中应使用数据库）
    $tokens = getTokensFromFile();
    $tokens[$username] = [
        'token' => $token,
        'expires' => $expires
    ];
    saveTokensToFile($tokens);
    
    return $token;
}

// 验证"记住我"令牌
function validateRememberToken($token) {
    $tokens = getTokensFromFile();
    
    foreach ($tokens as $username => $data) {
        if ($data['token'] === $token && $data['expires'] > time()) {
            return $username;
        }
    }
    
    return false;
}

// 获取令牌数据（简化版，实际应用中应使用数据库）
function getTokensFromFile() {
    $tokenFile = __DIR__ . '/data/tokens.json';
    
    if (!file_exists($tokenFile)) {
        if (!file_exists(__DIR__ . '/data')) {
            mkdir(__DIR__ . '/data', 0755, true);
        }
        file_put_contents($tokenFile, json_encode([]));
        return [];
    }
    
    $data = file_get_contents($tokenFile);
    return json_decode($data, true) ?: [];
}

// 保存令牌数据（简化版，实际应用中应使用数据库）
function saveTokensToFile($tokens) {
    $tokenFile = __DIR__ . '/data/tokens.json';
    
    if (!file_exists(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0755, true);
    }
    
    file_put_contents($tokenFile, json_encode($tokens));
}

// 清除过期的令牌
function cleanupExpiredTokens() {
    $tokens = getTokensFromFile();
    $changed = false;
    
    foreach ($tokens as $username => $data) {
        if ($data['expires'] <= time()) {
            unset($tokens[$username]);
            $changed = true;
        }
    }
    
    if ($changed) {
        saveTokensToFile($tokens);
    }
}

// 生成安全的文件名
function generateSafeFilename($string) {
    // 移除非字母数字字符，转换为小写，替换空格为短横线
    $safe = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower(trim($string)));
    $safe = preg_replace('/-+/', '-', $safe); // 避免多个连续短横线
    $safe = trim($safe, '-'); // 移除首尾短横线
    
    // 如果结果为空，生成随机字符串
    if (empty($safe)) {
        $safe = 'file-' . substr(md5(uniqid()), 0, 8);
    }
    
    return $safe;
}

// 添加游戏
function addGame($title, $description, $url, $imageUrl = '', $isActive = true) {
    try {
        $pdo = getDbConnection();
        
        $sql = "INSERT INTO games (title, description, url, image_url, is_active, date_added) 
                VALUES (:title, :description, :url, :image_url, :is_active, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':image_url', $imageUrl);
        $stmt->bindParam(':is_active', $isActive, PDO::PARAM_BOOL);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('添加游戏失败: ' . $e->getMessage());
        return false;
    }
}

// 获取所有游戏
function getAllGames() {
    try {
        $pdo = getDbConnection();
        
        $sql = "SELECT * FROM games ORDER BY date_added DESC";
        $stmt = $pdo->query($sql);
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('获取游戏列表失败: ' . $e->getMessage());
        return [];
    }
}

// 获取单个游戏
function getGame($id) {
    try {
        $pdo = getDbConnection();
        
        $sql = "SELECT * FROM games WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('获取游戏信息失败: ' . $e->getMessage());
        return null;
    }
}

// 更新游戏信息
function updateGame($id, $title, $description, $url, $imageUrl = null, $isActive = true) {
    try {
        $pdo = getDbConnection();
        
        // 如果未提供新图片，保留原图片
        if ($imageUrl === null) {
            $sql = "UPDATE games SET 
                    title = :title, 
                    description = :description, 
                    url = :url, 
                    is_active = :is_active, 
                    date_updated = NOW() 
                    WHERE id = :id";
        } else {
            $sql = "UPDATE games SET 
                    title = :title, 
                    description = :description, 
                    url = :url, 
                    image_url = :image_url, 
                    is_active = :is_active, 
                    date_updated = NOW() 
                    WHERE id = :id";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':url', $url);
        $stmt->bindParam(':is_active', $isActive, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($imageUrl !== null) {
            $stmt->bindParam(':image_url', $imageUrl);
        }
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('更新游戏信息失败: ' . $e->getMessage());
        return false;
    }
}

// 删除游戏
function deleteGame($id) {
    try {
        $pdo = getDbConnection();
        
        // 先获取游戏信息，以便删除图片
        $game = getGame($id);
        
        if ($game && !empty($game['image_url'])) {
            $imagePath = realpath(__DIR__ . '/../' . $game['image_url']);
            
            // 确保路径在网站目录内（安全检查）
            $siteRoot = realpath(__DIR__ . '/../');
            if (strpos($imagePath, $siteRoot) === 0 && file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // 删除游戏记录
        $sql = "DELETE FROM games WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log('删除游戏失败: ' . $e->getMessage());
        return false;
    }
}

// 批量导入游戏
function bulkImportGames($csvFile, $skipHeader = true) {
    try {
        $handle = fopen($csvFile, 'r');
        
        if (!$handle) {
            return [
                'success' => false,
                'message' => '无法打开CSV文件',
                'imported' => 0
            ];
        }
        
        $importCount = 0;
        $lineNumber = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;
            
            // 跳过第一行（表头）
            if ($skipHeader && $lineNumber === 1) {
                continue;
            }
            
            // 确保至少有标题、描述和URL
            if (count($data) >= 3) {
                $title = trim($data[0]);
                $description = trim($data[1]);
                $url = trim($data[2]);
                $imageUrl = isset($data[3]) ? trim($data[3]) : '';
                
                if (!empty($title) && !empty($url)) {
                    if (addGame($title, $description, $url, $imageUrl)) {
                        $importCount++;
                    }
                }
            }
        }
        
        fclose($handle);
        
        return [
            'success' => true,
            'message' => "成功导入 {$importCount} 个游戏",
            'imported' => $importCount
        ];
    } catch (Exception $e) {
        error_log('批量导入游戏失败: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => '导入过程中发生错误',
            'imported' => 0
        ];
    }
}

// 生成CSRF令牌
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token) && 
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_TIME;
}

// 获取活跃的游戏列表（用于前端展示）
function getActiveGames($limit = 0, $offset = 0) {
    try {
        $pdo = getDbConnection();
        
        $sql = "SELECT * FROM games WHERE is_active = 1 ORDER BY date_added DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT :offset, :limit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->query($sql);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('获取活跃游戏列表失败: ' . $e->getMessage());
        return [];
    }
}

// 为首页生成游戏卡片HTML
function generateGameCardsHtml($games) {
    $html = '';
    
    foreach ($games as $game) {
        $html .= '<div class="game-card">';
        $html .= '    <div class="game-iframe-container">';
        
        if (!empty($game['image_url'])) {
            $html .= '        <div class="minecraft-cover" style="background-image: url(\'' . htmlspecialchars($game['image_url']) . '\');"></div>';
        } else {
            $html .= '        <div class="minecraft-cover"></div>';
        }
        
        $html .= '        <iframe data-src="' . htmlspecialchars($game['url']) . '" class="game-iframe" title="' . htmlspecialchars($game['title']) . '" allowfullscreen></iframe>';
        $html .= '    </div>';
        $html .= '    <div class="game-info">';
        $html .= '        <h3 class="game-title">' . htmlspecialchars($game['title']) . '</h3>';
        $html .= '        <p class="game-description">' . htmlspecialchars($game['description']) . '</p>';
        $html .= '        <a href="' . htmlspecialchars($game['url']) . '" target="_blank" class="play-button">Play Full Screen</a>';
        $html .= '    </div>';
        $html .= '</div>';
    }
    
    return $html;
}

// 更新首页游戏列表
function updateIndexPage($games) {
    $indexFile = __DIR__ . '/../index.html';
    
    if (!file_exists($indexFile)) {
        return false;
    }
    
    $content = file_get_contents($indexFile);
    
    // 查找游戏列表部分
    $pattern = '/<div class="games-grid">(.*?)<\/div>\s*<\/div>\s*<\/section>/s';
    
    if (preg_match($pattern, $content, $matches)) {
        $gamesHtml = '<div class="games-grid">' . "\n";
        $gamesHtml .= generateGameCardsHtml($games);
        $gamesHtml .= '</div>' . "\n" . '</div>' . "\n" . '</section>';
        
        $newContent = preg_replace($pattern, $gamesHtml, $content);
        
        return file_put_contents($indexFile, $newContent) !== false;
    }
    
    return false;
} 