<html>

<head>
    <title>報名結果</title>
</head>

<body bgcolor="#f0f8ff">

    <?php

    echo "<h1>報名結果：</h1>" . "<br>";
    echo "學員姓名：" . $_POST["nName"] . "<br>";
    echo "查詢密碼：" . $_POST["nPass"] . "<br>";
        
    $gender = ($_POST["mGender"] == "m") ? "男" : "女";
    echo "性別：" . $gender . "<br>";
        
    echo "出生日期：" . $_POST["mDate"] . "<br>";
    echo "聯絡電話：" . $_POST["mNumber"] . "<br>";
    echo "電子信箱：" . $_POST["mEmail"] . "<br>";
        
    echo "守備位置：";
        if (!empty($_POST["mPosition"])) {
            echo implode("、", $_POST["mPosition"]);
        }
    echo "<br>";
        
    echo "報名場次：" . $_POST["nCity"] . "<br>";
    echo "報到時間：" . $_POST["mTime"] . "<br>";
    echo "球衣顏色：" . $_POST["mColor"] . "<br>";
    echo "體能狀況自評：" . $_POST["mRange"] . "<br>";
    echo "備註事項：" . nl2br($_POST["comment"]) . "<br>";


    ?>


</body>

</html>