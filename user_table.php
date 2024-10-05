<?php
require_once 'admin_session_config.php';
require_once 'funcs.php';

// 管理者認証チェック
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header("Location: login.php");
    exit();
}

// CSRFトークンの生成（まだ存在しない場合）
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// データベース接続
$pdo = db_conn();

// データ取得SQL作成
$stmt = $pdo->prepare("SELECT id, name, lid, kanri_flg, life_flg FROM user_table ORDER BY id ASC");
$status = $stmt->execute();

// テーブルの開始タグとヘッダー行
$view = "<table class='table table-striped table-bordered'>";
$view .= "<thead>
            <tr class='thead-custom'>
                <th>ID</th>
                <th>名前</th>
                <th>ログインID</th>
                <th>管理者権限</th>
                <th>アカウント状態</th>
                <th>操作</th>
            </tr></thead><tbody>";

// データ表示
if ($status == false) {
    $error = $stmt->errorInfo();
    exit("ErrorQuery:" . $error[2]);
} else {
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $view .= "<tr>";
        $view .= "<td>" . h($result['id']) . "</td>";
        $view .= "<td>" . h($result['name']) . "</td>";
        $view .= "<td>" . h($result['lid']) . "</td>";
        $view .= "<td>" . ($result['kanri_flg'] == 1 ? '管理者' : '一般ユーザー') . "</td>";
        $view .= "<td>" . ($result['life_flg'] == 0 ? '有効' : '無効') . "</td>";

        // 管理者であれば編集と削除ボタンを追加
        if ($_SESSION['kanri_flg'] == 1) {
            $view .= "<td class='text-center'>
                        <a href='update2.php?id=" . h($result['id']) . "' class='btn btn-primary btn-sm mr-2'>編集</a>
                        <button class='btn btn-danger btn-sm' onclick='deleteUser(" . h($result['id']) . ")'>削除</button>
                      </td>";
        } else {
            $view .= "<td>-</td>";
        }

        $view .= "</tr>";
    }
    $view .= "</tbody></table>";
}

// CSRFトークンの取得
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ユーザー情報一覧 - ZOUUU Platform</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .navbar-custom {
            background-color: #0c344e;
        }
        .navbar-custom .nav-link, .navbar-custom .navbar-brand {
            color: white;
        }
        .thead-custom {
            background-color: #0c344e;
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
                    <span class="nav-link">ようこそ <?php echo h($_SESSION['name']); ?> さん</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cms.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">ログアウト</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- パンくずリスト -->
    <nav aria-label="breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="cms.php">ホーム</a></li>
            <li class="breadcrumb-item active" aria-current="page">ユーザー情報一覧</li>
        </ol>
    </nav>

    <div class="container mt-5">
        <h1 class="mb-4">ユーザー情報一覧</h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php echo $view; ?>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-3">
            <a href="cms.php" class="btn btn-secondary mr-2">戻る</a>
        </div>
    </div>

    <footer class="footer bg-light text-center py-3 mt-4">
        <div class="container">
            <span class="text-muted">Copyright &copy; 2024 <a href="#">ZOUUU</a>. All rights reserved.</span>
        </div>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteUser(id) {
        if (confirm('このユーザーを削除してもよろしいですか？')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'user_delete.php';
            
            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);
            
            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            tokenInput.value = '<?php echo $csrf_token; ?>';
            form.appendChild(tokenInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>