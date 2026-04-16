<html>
<head>
    <title>admin</title>
</head>
<body>
    <center>
        <?php
        session_start();
        if(isset($_SESSION["login"])){
            if($_SESSION["login"] == "admin"){
                echo "<h1>管理員您好</h1>";
            }else{
                echo "<h1>你不是管理員</h1>";
                header("Refresh:2;url=login.php");
            }
        }else{
            echo "<h1>你不是管理員</h1>";
            header("Refresh:2;url=login.php");
        }
        ?>
    <a href="logout.php">登出</a>
    </center>
</body>
</html>