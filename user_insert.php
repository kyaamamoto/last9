<?php
require_once 'admin_session_config.php';
require_once 'funcs.php';

// CSRFトークンの検証
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['registration_error'] = '不正なリクエストです。もう一度お試しください。';
    redirect('cmslogin.php');
}

// POSTデータを取得し、サニタイズ
$name = sanitize($_POST["name"] ?? '');
$lid = sanitize($_POST["lid"] ?? '');
$lpw = $_POST["lpw"] ?? ''; // パスワードはサニタイズしない
$kanri_flg = filter_input(INPUT_POST, "kanri_flg", FILTER_SANITIZE_NUMBER_INT);

// 入力値の検証
if (empty($name) || empty($lid) || empty($lpw)) {
    $_SESSION['registration_error'] = "全ての項目を入力してください。";
    redirect('cmslogin.php');
}

try {
    $pdo = db_conn();
    
    // ユーザーIDの重複チェック
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_table WHERE lid = :lid");
    $stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['registration_error'] = "このログインIDは既に使用されています。";
        redirect('cmslogin.php');
    }

    // データ登録SQL作成
    $sql = "INSERT INTO user_table (name, lid, lpw, kanri_flg, life_flg) VALUES (:name, :lid, :lpw, :kanri_flg, 0)";
    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
    $stmt->bindValue(':lpw', password_hash($lpw, PASSWORD_DEFAULT), PDO::PARAM_STR);
    $stmt->bindValue(':kanri_flg', $kanri_flg, PDO::PARAM_INT);

    $status = $stmt->execute();

    if ($status === false) {
        throw new PDOException($stmt->errorInfo()[2]);
    } else {
        $_SESSION['registration_success'] = true;
        // 新しいCSRFトークンを生成（オプション）
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        redirect('login.php');
    }
} catch (PDOException $e) {
    error_log("登録エラー: " . $e->getMessage());
    $_SESSION['registration_error'] = "登録中にエラーが発生しました。管理者にお問い合わせください。";
    redirect('cmslogin.php');
}
?>