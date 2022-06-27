<?php

//データベース設定を読み込み
require_once 'config.php';

//変数の初期化
$current_date = null;
$message = array();
$message_array = array();
$success_message = null;
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

session_start();

//データベース接続
try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    );

    $pdo = new PDO('mysql:charset=UTF8;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
} catch (PDOException $e) {
    //接続エラーのとき、エラー内容を取得する
    $error_message[] = $e->getMessage();
}


if (!empty($_POST['btn_submit'])) {

    //空白除去
    $view_id = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_id']);
    $view_name = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_name']);
    $view_title = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_title']);
    $message = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);


    //投稿IDバリエーション
    if (empty($view_id)) {
        $error_message[] = '投稿IDを入力して下さい';
    } else {
        //セクションに投稿ID名を保存
        $_SESSION['view_id'] = $view_id;
    }

    //投稿者バリエーション
    if (empty($view_name)) {
        $error_message[] = '投稿者名を入力して下さい';
    }

    //タイトルバリエーション
    if (empty($view_title)) {
        $error_message[] = 'タイトルを入力して下さい';
    }

    //本文バリエーション
    if (empty($message)) {
        $error_message[] = '本文を入力して下さい';
    }

    if (empty($error_message)) {

        //書き込み日時を取得
        $current_date = date("Y-m-d H:i:s");

        //トランザクション開始
        $pdo->beginTransaction();

        try {
            //SQL作成
            $stmt = $pdo->prepare("INSERT INTO message (view_id, view_name, view_title, message, post_date) VALUES ( :view_id, :view_name, :view_title, :message, :current_date)");

            //値をセットする
            $stmt->bindParam(':view_id', $view_id, PDO::PARAM_STR);
            $stmt->bindParam(':view_name', $view_name, PDO::PARAM_STR);
            $stmt->bindParam(':view_title', $view_title, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':current_date', $current_date, PDO::PARAM_STR);

            //SQLクエリの実行
            $res = $stmt->execute();

            //コミット
            $res = $pdo->commit();
        } catch (Exception $e) {
            //エラー発生時はロールバック
            $pdo->rollBack();
        }

        if ($res) {
            $success_message = '投稿が完了しました。';
        } else {
            $error_message[] = '投稿に失敗しました';
        }

        //プリペアードステートメントを削除
        $stmt = null;
    }
}

if (empty($error_message)) {
    //データベース接続
    $pdo = new PDO('mysql:charset=UTF8;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);

    //GETでページ数取得
    if (isset($_GET['page'])) {
        $page = (int)$_GET['page'];
    } else {
        $page = 1;
    }

    if ($page > 1) {
        $start = ($page * 20) - 20;
    } else {
        $start = 0;
    }


    //データ取得
    $messages = $pdo->prepare("SELECT * FROM message ORDER BY post_date DESC LIMIT {$start}, 20");
    $messages->execute();
    $messages = $messages->fetchAll(PDO::FETCH_ASSOC);

    //messagesテーブルのデータ件数を取得する
    $page_num = $pdo->prepare("SELECT COUNT(*) view_id FROM message");
    $page_num->execute();
    $page_num = $page_num->fetchColumn();

    //ページネーションの数を取得する
    $pagination = ceil($page_num / 20);
}

//データベース接続を閉じる
$stmt = null;
$pdo = null;

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>研修用掲示板</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <h1>研修用掲示板</h1>
    <?php if (!empty($success_message)) : ?>
        <p class="success_message"><?php echo $success_message; ?></p>
    <?php endif; ?>
    <?php if (!empty($error_message)) : ?>
        <ul class="error_message">
            <?php foreach ($error_message as $value) : ?>
                <li><?php echo $value; ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <form method="post">
        <div>
            <label for="view_id">投稿ID</label>
            <input id="view_id" type="text" name="view_id" value="<?php if (!empty($_SESSION['view_id'])) {
                                                                        echo htmlspecialchars($_SESSION['view_id'], ENT_QUOTES, 'UTF-8');
                                                                    } ?>">
        </div>
        <div>
            <label for="view_name">投稿者名</label>
            <input id="view_name" type="text" name="view_name" value="">
        </div>
        <div>
            <label for="view_title">タイトル</label>
            <input id="view_title" type="text" name="view_title" value="">
        </div>
        <div>
            <label for="message">本文</label>
            <textarea id="message" name="message"></textarea>
        </div>
        <input type="submit" name="btn_submit" value="投稿する">
    </form>
    <hr>
    <section>
        <?php foreach ($messages as $message) : ?>
            <article>
                <div class="info">
                    <h2><label for="view_id">投稿ID:<?php echo nl2br(htmlspecialchars($message['view_id'], ENT_QUOTES, 'UTF-8')); ?></label></h2>
                    <h2><label for="view_name">投稿者:<?php echo nl2br(htmlspecialchars($message['view_name'], ENT_QUOTES, 'UTF-8')); ?></h2>
                    <time><?php echo date('Y年m月d日 H:i', strtotime($message['post_date'])); ?></time>
                    <p><a href="edit.php?view_id=<?php echo $message['view_id']; ?>">編集</a> <a href="delete.php?view_id=<?php echo $message['view_id']; ?>">削除</a></p>
                </div>
                <p><label for="view_title">タイトル:<?php echo nl2br(htmlspecialchars($message['view_title'], ENT_QUOTES, 'UTF-8')); ?></label></p>
                <p><label for="message">本文:<?php echo nl2br(htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8')); ?></label></p>
            </article>
        <?php endforeach; ?>
        <?php for ($x = 1; $x <= $pagination; $x++) { ?>
            <a href="?page=<?php echo $x ?>"><?php echo $x; ?></a>
        <?php } ?>
    </section>
</body>

</html>
