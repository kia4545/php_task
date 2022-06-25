<?php

//データベース接続情報
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'board');

//タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

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
    //メッセージのデータを取得する
    $sql = "SELECT * FROM message ORDER BY post_date DESC";
    $message_array = $pdo->query($sql);
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

        .info p {
            display: inline-block;
            line-height: 1.6em;
            font-size: 86%;
        }

        article p {
            color: #555;
            font-size: 86%;
            line-height: 1.6em;
        }

        .page-numbers {
            text-align: center;
            list-style-position: inside;
            list-style-type: none;
            padding: 0;
        }

        .page-numbers li {
            display: inline-block;
        }

        .page-numbers a {
            display: inline-block;
            padding: .5rem;
            margin: 0 .2rem .2rem 0;
            background: #fff;
            border: 1px solid #ccc;
        }

        .page-numbers span {
            display: inline-block;
            padding: .5rem;
            margin: .2rem;
            border: 1px solid #ccc;
        }

        a:link {
            text-decoration: none
        }

        a.page_number:visited {
            color: black;
            text-decoration: none
        }

        .page_number {
            width: 30px;
            margin: 0 10px;
            padding: 5px;
            text-align: center;
            background: #b8b8b8;
            color: black;
        }

        .now_page_number {
            width: 30px;
            margin: 0 10px;
            padding: 5px;
            text-align: center;
            background: #f0f0f0;
            color: black;
            font-weight: bold;
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
        <?php if (!empty($message_array)) : ?>
            <?php foreach ($message_array as $value) : ?>
                <article>
                    <div class="info">
                        <h2><label for="view_id">投稿ID:<?php echo nl2br(htmlspecialchars($value['view_id'], ENT_QUOTES, 'UTF-8')); ?></label></h2>
                        <h2><label for="view_name">投稿者:<?php echo nl2br(htmlspecialchars($value['view_name'], ENT_QUOTES, 'UTF-8')); ?></h2>
                        <time><?php echo date('Y年m月d日 H:i', strtotime($value['post_date'])); ?></time>
                        <p><a href="edit.php?view_id=<?php echo $value['view_id']; ?>">編集</a> <a href="delete.php?view_id=<?php echo $value['view_id']; ?>">削除</a></p>
                    </div>
                    <p><label for="view_title">タイトル:<?php echo nl2br(htmlspecialchars($value['view_title'], ENT_QUOTES, 'UTF-8')); ?></label></p>
                    <p><label for="message">本文:<?php echo nl2br(htmlspecialchars($value['message'], ENT_QUOTES, 'UTF-8')); ?></label></p>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</body>

</html>
