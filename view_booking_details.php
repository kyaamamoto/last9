<?php
session_start();
require_once 'session_config.php';
require_once 'security_headers.php';
require_once 'funcs.php';

// ログインチェック
loginCheck();

// ユーザー情報の取得
$user = getUserInfo($_SESSION['user_id']);

// データベース接続
$pdo = db_conn();

// 予約IDを取得
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id === 0) {
    // エラーメッセージを設定し、マイページにリダイレクト
    $_SESSION['error_message'] = '予約IDが指定されていません。';
    header('Location: mypage.php');
    exit();
}

// 予約詳細を取得
try {
    $stmt = $pdo->prepare("
        SELECT br.id, br.status, br.created_at, br.updated_at, f.name AS frontier_name, f.category, f.image_url,
               br.user_message, br.admin_reply
        FROM booking_requests br
        JOIN gs_chiiki_frontier f ON br.frontier_id = f.id
        WHERE br.id = :booking_id AND br.user_id = :user_id
    ");
    $stmt->execute([':booking_id' => $booking_id, ':user_id' => $user['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        // エラーメッセージを設定し、マイページにリダイレクト
        $_SESSION['error_message'] = '指定された予約が見つかりません。';
        header('Location: mypage.php');
        exit();
    }

    // 体験希望日時を取得（承認状態を含む）
    $stmt_slots = $pdo->prepare("
        SELECT date, start_time, end_time, is_confirmed
        FROM booking_request_slots
        WHERE booking_request_id = :booking_id
        ORDER BY date, start_time
    ");
    $stmt_slots->execute([':booking_id' => $booking_id]);
    $booking_slots = $stmt_slots->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('データベースエラー: ' . $e->getMessage());
    $_SESSION['error_message'] = 'システムエラーが発生しました。管理者にお問い合わせください。';
    header('Location: mypage.php');
    exit();
}

// 予約全体のステータスを決定する関数
function determineOverallStatus($booking_slots) {
    $confirmed_count = 0;
    $rejected_count = 0;
    $pending_count = 0;
    $total_count = count($booking_slots);

    foreach ($booking_slots as $slot) {
        if ($slot['is_confirmed'] == 1) {
            $confirmed_count++;
        } elseif ($slot['is_confirmed'] == -1) {
            $rejected_count++;
        } else {
            $pending_count++;
        }
    }

    if ($pending_count > 0) {
        return 'pending';
    } elseif ($confirmed_count > 0) {
        return 'confirmed';
    } elseif ($rejected_count == $total_count) {
        return 'rejected';
    } else {
        return 'unknown';
    }
}

// 予約全体のステータスを決定
$overall_status = determineOverallStatus($booking_slots);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>予約詳細 - ZOUUU</title>
    <link rel="icon" type="image/png" href="./img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <style>
        /* ここにスタイルを記述 */
        body {
            font-family: 'Noto Sans JP', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #fff;
            color: #0c344e;
            padding: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        header .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        header nav ul {
            list-style-type: none;
            margin: 0;
            padding: 0;
            display: flex;
        }
        header nav ul li {
            margin-left: 20px;
        }
        header nav ul li a {
            color: #0c344e;
            text-decoration: none;
            font-size: 1rem;
            padding: 5px 10px;
            transition: background-color 0.3s;
        }
        header nav ul li a:hover {
            background-color: #f0f0f0;
            border-radius: 5px;
        }
        main {
            flex: 1;
            padding: 20px 0;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        h1, h2, h3, h4 {
            color: #0c344e;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #0c344e;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, opacity 0.3s;
            font-size: 1rem;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0a2a3f;
            opacity: 0.9;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-cancel {
            background-color: #dc3545;
        }
        .btn-cancel:hover {
            background-color: #c82333;
        }
        .status {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .status.pending {
            background-color: #ffc107;
            color: #000;
        }
        .status.confirmed {
            background-color: #28a745;
            color: #fff;
        }
        .status.rejected {
            background-color: #dc3545;
            color: #fff;
        }
        .center-btns {
            text-align: center;
            margin-top: 20px;
        }
        .center-btns .btn {
            margin: 0 5px;
        }
        footer {
            background-color: #fff;
            color: #0c344e;
            padding: 20px 0;
            margin-top: auto;
            border-top: 1px solid #e0e0e0;
        }
        footer .container {
            text-align: center;
        }
        .footer-logo {
            font-size: 1.5em;
            font-weight: bold;
        }
        .slot-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        .slot-pending {
            background-color: #ffc107;
            color: #000;
        }
        .slot-confirmed {
            background-color: #28a745;
            color: #fff;
        }
        .slot-rejected {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">ZOUUU</div>
            <nav>
                <ul>
                    <li><a href="chiiki_kasseika.php"><b>フロンティア一覧</b></a></li>
                    <li><a href="mypage.php"><b>マイページ</b></a></li>
                    <li><a href="logoutmypage.php"><b>ログアウト</b></a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <section class="card">
                <h2>予約詳細</h2>
                <div class="status <?= h($overall_status) ?>">
                    ステータス: 
                    <?php
                    switch($overall_status) {
                        case 'confirmed':
                            echo '確定';
                            break;
                        case 'rejected':
                            echo 'NG';
                            break;
                        case 'pending':
                            echo '承認待ち';
                            break;
                        default:
                            echo '不明';
                            break;
                    }
                    ?>
                </div>
                <p><b>フロンティア名:</b> <?= h($booking['frontier_name']) ?></p>
                <p><b>カテゴリー:</b> <?= h($booking['category']) ?></p>
                <p><b>申込日時:</b> <?= h($booking['created_at']) ?></p>
                
                <p><b>体験希望日時:</b></p>
                <ul>
                <?php foreach ($booking_slots as $slot): ?>
                    <li>
                        <?= h($slot['date'] . ' ' . $slot['start_time'] . ' - ' . $slot['end_time']) ?>
                        <?php
                        if ($slot['is_confirmed'] == 1) {
                            echo '<span class="slot-status slot-confirmed">確定</span>';
                        } elseif ($slot['is_confirmed'] == -1) {
                            echo '<span class="slot-status slot-rejected">NG</span>';
                        } else {
                            echo '<span class="slot-status slot-pending">承認待ち</span>';
                        }
                        ?>
                    </li>
                <?php endforeach; ?>
                </ul>

                <?php if (!empty($booking['user_message'])): ?>
                    <div class="user-message">
                        <h4>あなたのメッセージ:</h4>
                        <p><?= nl2br(h($booking['user_message'])) ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($booking['admin_reply'])): ?>
                    <div class="admin-reply">
                        <h4>管理者からの返信:</h4>
                        <p><?= nl2br(h($booking['admin_reply'])) ?></p>
                    </div>
                <?php endif; ?>

                <p><b>最終更新日時:</b> <?= h($booking['updated_at']) ?></p>
                <img src="<?= h($booking['image_url']) ?>" alt="<?= h($booking['frontier_name']) ?>" style="width:100%; max-width: 400px; border-radius: 8px; margin-top: 20px;">
                
                <div class="center-btns">
                    <a href="mypage.php" class="btn btn-secondary">マイページに戻る</a>
                    <?php if ($overall_status === 'pending'): ?>
                        <button class="btn btn-cancel" id="cancel-booking">予約をキャンセル</button>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="footer-logo">ZOUUU</p>
            <small>&copy; 2024 ZOUUU. All rights reserved.</small>
        </div>
    </footer>

    <script>
        document.getElementById('cancel-booking')?.addEventListener('click', function() {
            if (confirm('予約をキャンセルしてもよろしいですか？')) {
                const bookingId = <?= json_encode($booking['id']) ?>;

                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ booking_id: bookingId }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('予約がキャンセルされました。');
                        // ページをリロード
                        window.location.reload();
                    } else {
                        alert('予約のキャンセルに失敗しました: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('通信エラーが発生しました。');
                });
            }
        });
    </script>
</body>
</html>