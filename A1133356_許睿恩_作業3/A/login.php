<center>
    <?php
        if(isset($_COOKIE["uName"])){
            echo $_COOKIE["uName"]." 歡迎回來<br/>";
            echo "<a href='delcookie.php'>刪除 COOKIE</a>";
        }
    ?>
</center>

<html>
<head>
    <title>登入系統</title>
</head>
<body>
<center>
    <h1>登入系統</h1>

    <form action="logincheck.php" method="POST">
        帳號:<input type="text" name="uName"><br>
        密碼:<input type="password" name="uPWD"><br>
        <input type="submit" value="登入">
    </form>
</center>
</body>
</html>