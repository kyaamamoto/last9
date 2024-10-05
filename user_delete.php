<?php
require_once 'admin_session_config.php';
require_once 'funcs.php';

// エラー表示を有効にする
ini_set('display_errors', 1);
error_reporting(E_ALL);

// デバッグログファイル
$logFile = 'debug_log.txt';

function debugLog($message) {
    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

debugLog("Script started");

// 管理者認証チェック
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    debugLog("Authentication failed");
    header("Location: login.php");
    exit();
}

debugLog("Authentication passed");

// CSRFトークンの検証
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    debugLog("CSRF token validation failed");
    $_SESSION['error_message'] = '不正なリクエストです。';
    header("Location: user_table.php");
    exit();
}

debugLog("CSRF token validated");

// データベース接続
try {
    $pdo = db_conn();
    debugLog("Database connection successful");
} catch (PDOException $e) {
    debugLog("Database connection failed: " . $e->getMessage());
    $_SESSION['error_message'] = 'データベース接続エラー: ' . $e->getMessage();
    header("Location: user_table.php");
    exit();
}

// IDの取得と検証
$id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;

if ($id === false || $id === null) {
    debugLog("Invalid ID");
    $_SESSION['error_message'] = '無効なIDです。';
    header("Location: user_table.php");
    exit();
}

debugLog("Valid ID: " . $id);

// トランザクション開始
$pdo->beginTransaction();

try {
    // ユーザーの削除
    $stmt = $pdo->prepare("DELETE FROM user_table WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $result = $stmt->execute();

    debugLog("Delete query executed. Result: " . ($result ? 'true' : 'false'));

    // 削除されたレコードの数を確認
    if ($stmt->rowCount() === 0) {
        throw new Exception('ユーザーが見つかりません。');
    }

    debugLog("User deleted successfully");

    // トランザクションをコミット
    $pdo->commit();

    $_SESSION['success_message'] = 'ユーザーが正常に削除されました。';
    debugLog("Transaction committed");
} catch (Exception $e) {
    // エラーが発生した場合、ロールバック
    $pdo->rollBack();
    debugLog("Error occurred: " . $e->getMessage());
    $_SESSION['error_message'] = 'ユーザーの削除中にエラーが発生しました: ' . $e->getMessage();
}

debugLog("Redirecting to user_table.php");
// user_table.phpにリダイレクト
header("Location: user_table.php");
exit();
?>