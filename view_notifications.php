<?php
require_once 'admin_session_config.php';
require_once 'funcs.php';

// 管理者認証チェック
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    die('管理者認証に失敗しました。');
}

// データベース接続
$pdo = db_conn();
if (!$pdo) {
    die('データベース接続に失敗しました。');
}

// お知らせを取得
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.name AS sender_name, GROUP_CONCAT(h.name SEPARATOR ', ') AS recipients
        FROM notifications n
        JOIN user_table u ON n.sender_id = u.id
        JOIN notification_recipients nr ON nr.notification_id = n.id
        JOIN holder_table h ON nr.user_id = h.id
        GROUP BY n.id
        ORDER BY n.created_at DESC
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データ取得エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>過去のお知らせ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <style>
        .navbar-custom {
            background-color: #0c344e;
        }
        .navbar-custom .nav-link, .navbar-custom .navbar-brand {
            color: white;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <a class="navbar-brand" href="#">
        <img src="./img/ZOUUU.png" alt="ZOUUU Logo" class="d-inline-block align-top" height="30">
        ZOUUU Platform
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="nav-link">ようこそ <?php echo htmlspecialchars($_SESSION['name']); ?> さん</span>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">ログアウト</a>
            </li>
        </ul>
    </div>
</nav>

<nav aria-label="breadcrumb" class="mt-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="admin_dashboard.php">ホーム</a></li>
    <li class="breadcrumb-item active" aria-current="page">過去のお知らせ</li>
  </ol>
</nav>

<div class="container mt-5">
    <h2>過去のお知らせ</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>送信者</th>
                <th>受信者</th>
                <th>メッセージ</th>
                <th>送信日時</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?= htmlspecialchars($notification['sender_name']) ?></td>
                    <td><?= htmlspecialchars($notification['recipients']) ?></td>
                    <td><?= htmlspecialchars($notification['message']) ?></td>
                    <td><?= htmlspecialchars($notification['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="text-center">
        <a href="cms.php" class="btn btn-secondary">戻る</a>
    </div>
</div>

<footer class="footer bg-light text-center py-3 mt-4">
    <div class="container">
        <span class="text-muted">Copyright &copy; 2024 <a href="#">ZOUUU</a>. All rights reserved.</span>
    </div>
</footer>

<!-- jQueryとBootstrapのライブラリを読み込む -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>