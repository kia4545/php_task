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
    //書き込み日時を取得
    $current_date = date("Y-m-d H:i:s");

    //トランザクション開始
    $pdo->beginTransaction();

    try {
        //SQL作成
        $stmt = $pdo->prepare("DELETE FROM message WHERE view_id = :view_id");

        //値をセットする
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
        $success_message = '削除が完了しました。';
        header("Location: ./index.php");
        exit;
    } else {
        $error_message[] = '削除に失敗しました';
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
    <title>研修用掲示板 投稿の削除</title>
    <style>
        /*------------------------------
    Reset Style

------------------------------*/
        html,
        body,
        div,
        span,
        object,
        iframe,
        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p,
        blockquote,
        pre,
        abbr,
        address,
        cite,
        code,
        del,
        dfn,
        em,
        img,
        ins,
        kbd,
        q,
        samp,
        small,
        strong,
        sub,
        sup,
        var,
        b,
        i,
        dl,
        dt,
        dd,
        ol,
        ul,
        li,
        fieldset,
        form,
        label,
        legend,
        table,
        caption,
        tbody,
        tfoot,
        thead,
        tr,
        th,
        td,
        article,
        aside,
        canvas,
        details,
        figcaption,
        figure,
        footer,
        header,
        hgroup,
        menu,
        nav,
        section,
        summary,
        time,
        mark,
        audio,
        video {
            margin: 0;
            padding: 0;
            border: 0;
            outline: 0;
            font-size: 100%;
            vertical-align: baseline;
            background: transparent;
        }

        body {
            line-height: 1;
        }

        article,
        aside,
        details,
        figcaption,
        figure,
        footer,
        header,
        hgroup,
        menu,
        nav,
        section {
            display: block;
        }

        nav ul {
            list-style: none;
        }

        blockquote,
        q {
            quotes: none;
        }

        blockquote:before,
        blockquote:after,
        q:before,
        q:after {
            content: '';
            content: none;
        }

        a {
            margin: 0;
            padding: 0;
            font-size: 100%;
            vertical-align: baseline;
            background: transparent;
        }

        /* change colours to suit your needs */
        ins {
            background-color: #ff9;
            color: #000;
            text-decoration: none;
        }

        /* change colours to suit your needs */
        mark {
            background-color: #ff9;
            color: #000;
            font-style: italic;
            font-weight: bold;
        }

        del {
            text-decoration: line-through;
        }

        abbr[title],
        dfn[title] {
            border-bottom: 1px dotted;
            cursor: help;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
        }

        hr {
            display: block;
            height: 1px;
            border: 0;
            border-top: 1px solid #cccccc;
            margin: 1em 0;
            padding: 0;
        }

        input,
        select {
            vertical-align: middle;
        }

        /*------------------------------
Common Style
------------------------------*/
        body {
            padding: 50px;
            font-size: 100%;
            font-family: 'ヒラギノ角ゴ Pro W3', 'Hiragino Kaku Gothic Pro', 'メイリオ', Meiryo, 'ＭＳ Ｐゴシック', sans-serif;
            color: #222;
            background: #f7f7f7;
        }

        a {
            color: #007edf;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        h1 {
            margin-bottom: 30px;
            font-size: 100%;
            color: #222;
            text-align: center;
        }

        /*-----------------------------------
入力エリア
-----------------------------------*/
        label {
            display: block;
            margin-bottom: 7px;
            font-size: 86%;
        }

        input[type="text"],
        textarea {
            margin-bottom: 20px;
            padding: 10px;
            font-size: 86%;
            border: 1px solid #ddd;
            border-radius: 3px;
            background: #fff;
        }

        input[type="text"] {
            width: 200px;
        }

        textarea {
            width: 50%;
            max-width: 50%;
            height: 70px;
        }

        input[type="submit"] {
            appearance: none;
            -webkit-appearance: none;
            padding: 10px 20px;
            color: #fff;
            font-size: 86%;
            line-height: 1.0em;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #37a1e5;
        }

        input[type=submit]:hover,
        button:hover {
            background-color: #2392d8;
        }

        hr {
            margin: 20px 0;
            padding: 0;
        }

        .success_message {
            margin-bottom: 20px;
            padding: 10px;
            color: #48b400;
            border-radius: 10px;
            border: 1px solid #4dc100;
        }

        .error_message {
            margin-bottom: 20px;
            padding: 10px;
            color: #ef072d;
            list-style-type: none;
            border-radius: 10px;
            border: 1px solid #ff5f79;
        }

        .success_message,
        .error_message li {
            font-size: 86%;
            line-height: 1.6em;
        }

        .btn_cancel {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 20px;
            color: #555;
            font-size: 86%;
            border-radius: 5px;
            border: 1px solid #999;
        }

        .btn_cancel:hover {
            color: #999;
            border-color: #999;
            text-decoration: none;
        }

        .text-confirm {
            margin-bottom: 20px;
            font-size: 86%;
            line-height: 1.6em;
        }


        /*-----------------------------------
掲示板エリア
-----------------------------------*/
        article {
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            background: #fff;
        }

        article.reply {
            position: relative;
            margin-top: 15px;
            margin-left: 30px;
        }

        article.reply::before {
            position: absolute;
            top: -10px;
            left: 20px;
            display: block;
            content: "";
            border-top: none;
            border-left: 7px solid #f7f7f7;
            border-right: 7px solid #f7f7f7;
            border-bottom: 10px solid #fff;
        }

        .info {
            margin-bottom: 10px;
        }

        .info h2 {
            display: inline-block;
            margin-right: 10px;
            color: #222;
            line-height: 1.6em;
            font-size: 86%;
        }

        .info time {
            color: #999;
            line-height: 1.6em;
            font-size: 72%;
        }

        article p {
            color: #555;
            font-size: 86%;
            line-height: 1.6em;
        }

        @media only screen and (max-width: 1000px) {
            body {
                padding: 30px 5%;
            }

            input[type="text"] {
                width: 100%;
            }

            textarea {
                width: 100%;
                max-width: 100%;
                height: 70px;
            }
        }
    </style>
</head>

<body>
    <h1>研修用掲示板 投稿の削除</h1>

    <?php if (!empty($error_message)) : ?>
        <ul class="error_message">
            <?php foreach ($error_message as $value) : ?>
                <li><?php echo $value; ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <p class="text-confirm">以下の投稿を削除します。<br>よろしければ「削除」ボタンを押してください。</p>
    <form method="post">
        <div>
            <label for="view_id">投稿ID</label>
            <input id="view_id" type="text" name="view_id" value="<?php if (!empty($message_date['view_id'])) {
                                                                        echo $message_date['view_id'];
                                                                    } elseif (!empty($view_id)) {
                                                                        echo htmlspecialchars($view_id, ENT_QUOTES, 'UTF-8');
                                                                    } ?> " disabled>
        </div>
        <div>
            <label for="view_name">投稿者名</label>
            <input id="view_name" type="text" name="view_name" value="<?php if (!empty($message_date['view_name'])) {
                                                                            echo $message_date['view_name'];
                                                                        } elseif (!empty($view_name)) {
                                                                            echo htmlspecialchars($view_name, ENT_QUOTES, 'UTF-8');
                                                                        } ?>" disabled>
        </div>
        <div>
            <label for="view_title">タイトル</label>
            <input id="view_title" type="text" name="view_title" value="<?php if (!empty($message_date['view_title'])) {
                                                                            echo $message_date['view_title'];
                                                                        } elseif (!empty($view_title)) {
                                                                            echo htmlspecialchars($view_title, ENT_QUOTES, 'UTF-8');
                                                                        } ?>" disabled>
        </div>
        <div>
            <label for="message">本文</label>
            <textarea id="message" name="message" disabled><?php if (!empty($message_date['message'])) {
                                                                echo $message_date['message'];
                                                            } elseif (!empty($message)) {
                                                                echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
                                                            } ?></textarea>
        </div>
        <a class="btn_cancel" href="index.php">キャンセル</a>
        <input type="submit" name="btn_submit" value="削除">
        <input type="hidden" name="view_id" value="<?php if (!empty($message_date['view_id'])) {
                                                        echo $message_date['view_id'];
                                                    } elseif (!empty($_POST['view_id'])) {
                                                        echo htmlspecialchars($_POST['view_id'], ENT_QUOTES, 'UTF-8');
                                                    } ?>">
    </form>
</body>

</html>
