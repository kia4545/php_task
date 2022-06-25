<?php

//データベース接続情報
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'board');

//タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 変数の初期化
$view_id = null;
$view_name = null;
$view_title = null;
$message = array();
$current_date = null;
$message_data = null;
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

//投稿データを取得
if (!empty($_GET['view_id']) && empty($_POST['view_id'])) {
    try {
        //SQL文作成
        $stmt = $pdo->prepare("SELECT * FROM message WHERE view_id = :view_id");
        //値をセット
        $stmt->bindValue(':view_id', $_GET['view_id'], PDO::PARAM_INT);
        //SQL文実行
        $stmt->execute();
        //表示するデータを取得する
        $message_date = $stmt->fetch();

        //もし投稿データが取得できない場合はindexページに戻る
        if (empty($message_date)) {
            header("Location: ./index.php");
        }
    } catch (Exception $e) {
        //エラー発生時はロールバック
        $pdo->rollBack();
    }
} elseif (!empty($_POST['view_id'])) {
    //空白除去
    $view_id = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_id']);
    $view_name = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_name']);
    $view_title = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_title']);
    $message = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);

    //投稿IDバリエーション
    if (empty($view_id) && preg_match("/^[0-9]+$/", $text)) {
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
            $stmt = $pdo->prepare("UPDATE message SET view_id = :view_id, view_name = :view_name, view_title = :view_title, message = :message, post_date = :current_date WHERE view_id = :view_id");

            //値をセットする
            $stmt->bindParam(':view_id', $view_name, PDO::PARAM_STR);
            $stmt->bindParam(':view_name', $view_name, PDO::PARAM_STR);
            $stmt->bindParam(':view_title', $view_title, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':current_date', $current_date, PDO::PARAM_STR);
            $stmt->bindValue(':view_id', $_POST['view_id'], PDO::PARAM_STR);

            //SQLクエリの実行
            $res = $stmt->execute();

            //コミット
            $res = $pdo->commit();
        } catch (Exception $e) {
            //エラー発生時はロールバック
            $pdo->rollBack();
        }

        if ($res) {
            header("Location: ./index.php");
            exit;
        } else {
            $error_message[] = '更新に失敗しました';
        }

        //プリペアードステートメントを削除
        $stmt = null;
    }
}

//データベース接続を閉じる
$stmt = null;
$pdo = null;

?>


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <title>研修用掲示板 投稿の更新</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <h1>研修用掲示板 投稿の更新</h1>

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
            <input id="view_id" type="text" name="view_id" value=" <?php if (!empty($message_date['view_id'])) {
                                                                        echo $message_date['view_id'];
                                                                    } elseif (!empty($view_id)) {
                                                                        echo htmlspecialchars($view_id, ENT_QUOTES, 'UTF-8');
                                                                    } ?>" disabled>
        </div>
        <div>
            <label for="view_name">投稿者名</label>
            <input id="view_name" type="text" name="view_name" value="<?php if (!empty($message_date['view_name'])) {
                                                                            echo $message_date['view_name'];
                                                                        } elseif (!empty($view_name)) {
                                                                            echo htmlspecialchars($view_name, ENT_QUOTES, 'UTF-8');
                                                                        } ?>">
        </div>
        <div>
            <label for="view_title">タイトル</label>
            <input id="view_title" type="text" name="view_title" value="<?php if (!empty($message_date['view_title'])) {
                                                                            echo $message_date['view_title'];
                                                                        } elseif (!empty($view_title)) {
                                                                            echo htmlspecialchars($view_title, ENT_QUOTES, 'UTF-8');
                                                                        } ?>">
        </div>
        <div>
            <label for="message">本文</label>
            <textarea id="message" name="message"><?php if (!empty($message_date['message'])) {
                                                        echo $message_date['message'];
                                                    } elseif (!empty($message)) {
                                                        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                                                    } ?></textarea>
        </div>
        <a class="btn_cancel" href="index.php">キャンセル</a>
        <input type="submit" name="btn_submit" value="更新">
        <input type="hidden" name="view_id" value="<?php if (!empty($message_date['view_id'])) {
                                                        echo $message_date['view_id'];
                                                    } elseif (!empty($_POST['view_id'])) {
                                                        echo htmlspecialchars($_POST['view_id'], ENT_QUOTES, 'UTF-8');
                                                    } ?>">
    </form>
</body>

</html>
