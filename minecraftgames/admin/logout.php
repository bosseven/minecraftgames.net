<?php
/**
 * Butler 游戏管理工具注销处理
 */

// 重定向到带有注销参数的auth.php
header('Location: auth.php?logout=1');
exit; 