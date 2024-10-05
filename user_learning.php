<?php
session_start();
require_once 'session_config.php';
require_once 'security_headers.php';
require_once 'funcs.php';

// ログイン状態の確認
if (!isset($_SESSION['user_id'])) {
    header("Location: login_holder.php");
    exit();
}

$pdo = db_conn();

// フロンティアIDの取得
$frontier_id = filter_input(INPUT_GET, 'frontier_id', FILTER_SANITIZE_NUMBER_INT);

if (!$frontier_id) {
    header("Location: chiiki_kasseika.php");
    exit();
}

// フロンティア情報の取得
$stmt = $pdo->prepare("SELECT * FROM gs_chiiki_frontier WHERE id = :id");
$stmt->bindValue(':id', $frontier_id, PDO::PARAM_INT);
$status = $stmt->execute();

if ($status == false) {
    $error = $stmt->errorInfo();
    exit("ErrorQuery:" . $error[2]);
} else {
    $frontier = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 学習コンテンツ情報の取得
$stmt = $pdo->prepare("SELECT * FROM learning_contents WHERE gs_chiiki_frontier_id = :frontier_id");
$stmt->bindValue(':frontier_id', $frontier_id, PDO::PARAM_INT);
$status = $stmt->execute();

if ($status == false) {
    $error = $stmt->errorInfo();
    exit("ErrorQuery:" . $error[2]);
} else {
    $learning_content = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ユーザー情報の取得
$user = getUserInfo($_SESSION['user_id']);

// YouTube動画IDを取得
$youtube_id = $learning_content['youtube_video_id'];

// YouTube oEmbed APIを使用して動画情報を取得
$oembed_url = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v={$youtube_id}&format=json";
$oembed_data = @file_get_contents($oembed_url);
$video_info = $oembed_data ? json_decode($oembed_data, true) : null;

// 動画が利用可能かどうかをチェック
$video_available = $video_info !== null;

// ユーザーの学習状況を取得
$stmt = $pdo->prepare("SELECT * FROM user_frontier_progress WHERE user_id = :user_id AND frontier_id = :frontier_id");
$stmt->execute([':user_id' => $_SESSION['user_id'], ':frontier_id' => $frontier_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

// ボタンの状態を決定
$learning_button_text = "学習する";
$learning_button_action = "start";
if ($progress) {
    if ($progress['status'] == 'in_progress') {
        $learning_button_text = "学習を再開する";
        $learning_button_action = "resume";
    } elseif ($progress['status'] == 'completed') {
        $learning_button_text = "復習する";
        $learning_button_action = "review";
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($frontier['name']) ?> 学習ページ - ZOUUU</title>
    <link rel="icon" type="image/png" href="./img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/education_card.css">
    <script src="./js/jquery-3.6.0.min.js"></script>
    <style>
            body {
                font-family: 'Noto Sans JP', sans-serif;
                background-color: #f8f9fa;
                margin: 0;
                padding-bottom: 80px; /* フッターの高さに応じて調整 */
                min-height: 100vh;
                position: relative;
            }
            .content-section {
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                padding: 20px;
                margin-bottom: 20px;
            }
            .video-container {
                position: relative;
                padding-bottom: 56.25%;
                height: 0;
                overflow: hidden;
                margin-bottom: 20px;
            }
            .video-container iframe {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
            .btn {
                padding: 8px 16px;
                text-align: center;
                border-radius: 5px;
                display: inline-block;
                transition: background-color 0.3s ease;
                text-decoration: none;
                font-size: 14px;
                margin: 0 10px;
                border: none;
                cursor: pointer;
            }
            .btn-primary {
                background-color: #28a745;
                color: #fff;
            }
            .btn-success {
                background-color: #007bff;
                color: #fff;
            }
            .btn-secondary {
                background-color: #6c757d;
                color: #fff;
            }
            .btn:hover {
                opacity: 0.8;
                text-decoration: none;
                color: #fff;
            }
            h1, h2, h3, h4 {
                color: #0c344e;
            }
            .tag {
                display: inline-block;
                background-color: #f1f3f5;
                padding: 3px 8px;
                border-radius: 4px;
                margin-right: 5px;
                margin-bottom: 5px;
                font-size: 0.9em;
            }
            .video-unavailable {
                background-color: #f8d7da;
                color: #721c24;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .button-container {
                text-align: center;
                margin-top: 30px;
                margin-bottom: 60px;
            }
            
            .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 30px;
    border: none;
    width: 90%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.modal-content h2 {
    margin-top: 0;
    color: #0c344e;
}

#reportUrl {
    width: 100%;
    padding: 12px;
    margin: 15px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

#submitReport, #closeModal {
    padding: 10px 20px;
    margin-right: 10px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

#submitReport {
    background-color: #28a745;
    color: white;
}

#closeModal {
    background-color: #6c757d;
    color: white;
}

#submitReport:hover, #closeModal:hover {
    opacity: 0.8;
}

            footer {
                background-color: #fff;
                color: #0c344e;
                padding: 20px 0;
                width: 100%;
                position: absolute;
                bottom: 0;
                left: 0;
            }

            footer .container {
                width: 80%;
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
                text-align: center; /* コンテンツを中央揃えに */
            }

            footer p {
                margin: 0;
            }

            .footer-logo {
                font-size: 1.5em;
                font-weight: bold;
            }
</style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">ZOUUU</div>
                <div class="welcome">
                    <h1>ようこそ、<?= h($user['name']) ?>さん</h1>
                </div>
                <ul>
                    <li><a href="education.php">コース一覧</a></li>
                    <li><a href="mypage.php">マイページ</a></li>
                    <li><a href="logoutmypage.php">ログアウト</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="chiiki_kasseika.php">地域活性化</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= h($frontier['name']) ?></li>
            </ol>
        </nav>

        <h2 class="mb-4"><?= h($frontier['name']) ?> - 学習ページ</h2>

        <div class="content-section">
            <h3><?= h($learning_content['title']) ?></h3>
            <p>難易度: <?= h($learning_content['difficulty']) ?></p>
            <p>推定学習時間: <?= h($learning_content['estimated_time']) ?>分</p>
            
            <?php if ($video_available): ?>
                <div class="video-container">
                    <iframe width="560" height="315" src="https://www.youtube.com/embed/<?= h($youtube_id) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div class="video-unavailable">
                    <p><strong>申し訳ありません。この動画は現在利用できません。</strong></p>
                    <p>代わりに、以下のリンクから動画を直接YouTubeで視聴することができます：</p>
                    <a href="https://www.youtube.com/watch?v=<?= h($youtube_id) ?>" target="_blank" rel="noopener noreferrer">YouTubeで視聴する</a>
                </div>
            <?php endif; ?>

            <h4>学習目標</h4>
            <p><?= nl2br(h($learning_content['learning_objective'])) ?></p>
            <h4>タグ</h4>
            <div>
                <?php
                $tags = explode(',', $frontier['tags']);
                foreach ($tags as $tag) {
                    echo '<span class="tag">' . h(trim($tag)) . '</span>';
                }
                ?>
            </div>
        </div>

        <div class="content-section">
            <h4>探究テーマ</h4>
            <p><?= nl2br(h($learning_content['inquiry_theme'])) ?></p>
            <h4>探究プロセス</h4>
            <ol>
                <?php foreach (explode("\n", $learning_content['inquiry_process']) as $step): ?>
                    <li><?= h($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div class="content-section">
            <h4>期待されるアプローチ</h4>
            <p><?= nl2br(h($learning_content['expected_approach'])) ?></p>
            <h4>評価基準</h4>
            <ul>
                <?php foreach (explode("\n", $learning_content['evaluation_criteria']) as $criteria): ?>
                    <li><?= h($criteria) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if (!empty($learning_content['resources'])): ?>
        <div class="content-section">
            <h4>参考リソース</h4>
            <ul>
                <?php foreach (explode("\n", $learning_content['resources']) as $resource): ?>
                    <li><?= h($resource) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($learning_content['tasks'])): ?>
        <div class="content-section">
            <h4>タスク</h4>
            <ol>
                <?php foreach (explode("\n", $learning_content['tasks']) as $task): ?>
                    <li><?= h($task) ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

        <div class="button-container">
            <a href="chiiki_kasseika.php" class="btn btn-secondary">戻る</a>
            <button id="startLearningBtn" class="btn btn-primary"><?= $learning_button_text ?></button>
            <button id="submitReportBtn" class="btn btn-success">レポート提出</button>
        </div>

        <div id="reportModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h2>レポート提出</h2>
        <input type="url" id="reportUrl" placeholder="Google ドキュメントのURLを入力">
        <div class="button-group">
            <button id="submitReport" class="btn btn-primary">提出</button>
            <button id="closeModal" class="btn btn-secondary">閉じる</button>
        </div>
    </div>
</div>

    <footer>
        <div class="container">
            <p class="footer-logo">ZOUUU</p>
            <small>&copy; 2024 ZOUUU. All rights reserved.</small>
        </div>
    </footer>

    <script>
      $(document).ready(function() {
        console.log('Document ready');
    var learningStatus = '<?= $learning_button_action ?>';
        console.log('Initial learning status:', learningStatus);

    function updateButton(status) {
        if (status === 'in_progress') {
            $('#startLearningBtn').text('一時停止').data('status', 'in_progress');
        } else if (status === 'paused') {
            $('#startLearningBtn').text('学習を再開する').data('status', 'paused');
        } else if (status === 'start' || status === 'review') {
            $('#startLearningBtn').text(status === 'review' ? '復習する' : '学習する').data('status', status);
        }
    }

    // 初期状態を反映
    updateButton(learningStatus);

    // 学習開始、一時停止、再開、復習ボタンクリックイベント
    $('#startLearningBtn').click(function() {
        console.log('Start learning button clicked');
        var currentStatus = $(this).data('status');
        console.log('Start learning button clicked');
        var action;
        var nextStatus;
        var popupMessage;

        if (currentStatus === 'start' || currentStatus === 'review') {
            action = 'start_learning';
            nextStatus = 'in_progress';
            popupMessage = currentStatus === 'review' ? '復習を開始します。' : '学習を開始します。';
        } else if (currentStatus === 'in_progress') {
            action = 'pause_learning';
            nextStatus = 'paused';
            popupMessage = '学習を一時停止しました。';
        } else if (currentStatus === 'paused') {
            action = 'resume_learning';
            nextStatus = 'in_progress';
            popupMessage = '学習を再開します。';
        }

        $.ajax({
            url: 'update_learning_status.php',
            method: 'POST',
            data: {
                action: action,
                frontier_id: <?= $frontier_id ?>
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    updateButton(nextStatus);
                } else {
                    alert('エラーが発生しました: ' + response.message);
                }
            },
            error: function() {
                alert('サーバーとの通信に失敗しました。');
            }
        });
    });

    // レポート提出ボタンクリックイベント
    $('#submitReportBtn').click(function() {
        $('#reportModal').show();
    });

    // モーダルを閉じる
    $('#closeModal').click(function() {
        $('#reportModal').hide();
    });

    // レポート提出処理
    $('#submitReport').click(function() {
        var reportUrl = $('#reportUrl').val();
        $.ajax({
            url: 'update_learning_status.php',
            method: 'POST',
            data: {
                action: 'submit_report',
                frontier_id: <?= $frontier_id ?>,
                report_url: reportUrl
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#reportModal').hide();
                    $('#startLearningBtn').text('復習する');
                    $('#submitReportBtn').prop('disabled', true);
                } else {
                    alert('エラーが発生しました: ' + response.message);
                }
            },
            error: function() {
                alert('サーバーとの通信に失敗しました。');
            }
        });
    });
});
    </script>
</body>
</html>