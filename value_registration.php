<?php
session_start();
require 'funcs.php'; // 共通関数をインクルード

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = db_conn(); // データベース接続を取得
    $user_id = $_SESSION['user_id']; // セッションからユーザーIDを取得

    // POSTデータを取得
    $growth = $_POST['growth'];
    $challenge = $_POST['challenge'];
    $creativity = $_POST['creativity'];
    $love = $_POST['love'];
    $friendship = $_POST['friendship'];
    $family = $_POST['family'];
    $fairness = $_POST['fairness'];
    $social_contribution = $_POST['social_contribution'];
    $diversity = $_POST['diversity'];
    $success = $_POST['success'];
    $professionalism = $_POST['professionalism'];
    $innovation = $_POST['innovation'];
    $faith = $_POST['faith'];
    $peace = $_POST['peace'];
    $gratitude = $_POST['gratitude'];
    $health = $_POST['health'];
    $quality_of_life = $_POST['quality_of_life'];
    $sustainability = $_POST['sustainability'];

    // データベースに挿入
    $sql = "INSERT INTO value_registration (
                user_id, growth, challenge, creativity, love, friendship, family, fairness,
                social_contribution, diversity, success, professionalism, innovation,
                faith, peace, gratitude, health, quality_of_life, sustainability
            ) VALUES (
                :user_id, :growth, :challenge, :creativity, :love, :friendship, :family, :fairness,
                :social_contribution, :diversity, :success, :professionalism, :innovation,
                :faith, :peace, :gratitude, :health, :quality_of_life, :sustainability
            )";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':growth', $growth, PDO::PARAM_INT);
        $stmt->bindValue(':challenge', $challenge, PDO::PARAM_INT);
        $stmt->bindValue(':creativity', $creativity, PDO::PARAM_INT);
        $stmt->bindValue(':love', $love, PDO::PARAM_INT);
        $stmt->bindValue(':friendship', $friendship, PDO::PARAM_INT);
        $stmt->bindValue(':family', $family, PDO::PARAM_INT);
        $stmt->bindValue(':fairness', $fairness, PDO::PARAM_INT);
        $stmt->bindValue(':social_contribution', $social_contribution, PDO::PARAM_INT);
        $stmt->bindValue(':diversity', $diversity, PDO::PARAM_INT);
        $stmt->bindValue(':success', $success, PDO::PARAM_INT);
        $stmt->bindValue(':professionalism', $professionalism, PDO::PARAM_INT);
        $stmt->bindValue(':innovation', $innovation, PDO::PARAM_INT);
        $stmt->bindValue(':faith', $faith, PDO::PARAM_INT);
        $stmt->bindValue(':peace', $peace, PDO::PARAM_INT);
        $stmt->bindValue(':gratitude', $gratitude, PDO::PARAM_INT);
        $stmt->bindValue(':health', $health, PDO::PARAM_INT);
        $stmt->bindValue(':quality_of_life', $quality_of_life, PDO::PARAM_INT);
        $stmt->bindValue(':sustainability', $sustainability, PDO::PARAM_INT);
        $stmt->execute();

        // データが正常に保存されたら次のページにリダイレクト
        redirect('past_involvement.php'); // 適切な次のページにリダイレクト
    } catch (PDOException $e) {
        echo 'データベースエラー: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>価値観登録フォーム</title>
</head>
<body>
    <h2>各価値観に対して、あなたがどれだけ重要と感じるかを選んでください</h2>
    <form action="value_registration.php" method="POST">
        <h3>個人の発展に関する価値観</h3>
        <label>成長:</label><br>
        <label><input type="radio" name="growth" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="growth" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="growth" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="growth" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="growth" value="5"> 5 - 非常に重要である</label><br><br>

        <label>挑戦:</label><br>
        <label><input type="radio" name="challenge" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="challenge" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="challenge" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="challenge" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="challenge" value="5"> 5 - 非常に重要である</label><br><br>

        <label>創造性:</label><br>
        <label><input type="radio" name="creativity" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="creativity" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="creativity" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="creativity" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="creativity" value="5"> 5 - 非常に重要である</label><br><br>

        <h3>人間関係に関する価値観</h3>
        <label>愛:</label><br>
        <label><input type="radio" name="love" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="love" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="love" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="love" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="love" value="5"> 5 - 非常に重要である</label><br><br>

        <label>友情:</label><br>
        <label><input type="radio" name="friendship" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="friendship" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="friendship" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="friendship" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="friendship" value="5"> 5 - 非常に重要である</label><br><br>

        <label>家族:</label><br>
        <label><input type="radio" name="family" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="family" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="family" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="family" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="family" value="5"> 5 - 非常に重要である</label><br><br>

        <h3>社会的な価値観</h3>
        <label>公正:</label><br>
        <label><input type="radio" name="fairness" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="fairness" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="fairness" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="fairness" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="fairness" value="5"> 5 - 非常に重要である</label><br><br>

        <label>社会貢献:</label><br>
        <label><input type="radio" name="social_contribution" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="social_contribution" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="social_contribution" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="social_contribution" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="social_contribution" value="5"> 5 - 非常に重要である</label><br><br>

        <label>多様性:</label><br>
        <label><input type="radio" name="diversity" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="diversity" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="diversity" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="diversity" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="diversity" value="5"> 5 - 非常に重要である</label><br><br>

        <h3>職業・キャリアに関する価値観</h3>
        <label>成功:</label><br>
        <label><input type="radio" name="success" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="success" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="success" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="success" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="success" value="5"> 5 - 非常に重要である</label><br><br>

        <label>プロフェッショナリズム:</label><br>
        <label><input type="radio" name="professionalism" value="1" required> 1 - 全く重要でない</label><br>
        <label><input type="radio" name="professionalism" value="2"> 2 - あまり重要でない</label><br>
        <label><input type="radio" name="professionalism" value="3"> 3 - どちらでもない</label><br>
        <label><input type="radio" name="professionalism" value="4"> 4 - 重要である</label><br>
        <label><input type="radio" name="professionalism" value="5"> 5 - 非常に重要である</label><br><br>

<label>革新:</label><br>
<label><input type="radio" name="innovation" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="innovation" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="innovation" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="innovation" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="innovation" value="5"> 5 - 非常に重要である</label><br><br>

<h3>精神的・宗教的価値観</h3>
<label>信仰:</label><br>
<label><input type="radio" name="faith" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="faith" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="faith" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="faith" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="faith" value="5"> 5 - 非常に重要である</label><br><br>

<label>平和:</label><br>
<label><input type="radio" name="peace" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="peace" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="peace" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="peace" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="peace" value="5"> 5 - 非常に重要である</label><br><br>

<label>感謝:</label><br>
<label><input type="radio" name="gratitude" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="gratitude" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="gratitude" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="gratitude" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="gratitude" value="5"> 5 - 非常に重要である</label><br><br>

<h3>健康と生活に関する価値観</h3>
<label>健康:</label><br>
<label><input type="radio" name="health" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="health" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="health" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="health" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="health" value="5"> 5 - 非常に重要である</label><br><br>

<label>生活の質:</label><br>
<label><input type="radio" name="quality_of_life" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="quality_of_life" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="quality_of_life" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="quality_of_life" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="quality_of_life" value="5"> 5 - 非常に重要である</label><br><br>

<label>持続可能性:</label><br>
<label><input type="radio" name="sustainability" value="1" required> 1 - 全く重要でない</label><br>
<label><input type="radio" name="sustainability" value="2"> 2 - あまり重要でない</label><br>
<label><input type="radio" name="sustainability" value="3"> 3 - どちらでもない</label><br>
<label><input type="radio" name="sustainability" value="4"> 4 - 重要である</label><br>
<label><input type="radio" name="sustainability" value="5"> 5 - 非常に重要である</label><br><br>

<input type="submit" value="次へ">
</form>
</body>
</html>