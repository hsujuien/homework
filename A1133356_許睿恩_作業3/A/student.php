<html>
<head>
    <title>student</title>
</head>
<body>
    <center>
        <?php
        session_start();
        if(isset($_SESSION["login"])){
            if($_SESSION["login"] == "student"){
                echo "<h1>學生你好</h1>";
            }else{
                echo "<h1>你不是學生</h1>";
                header("Refresh:2;url=login.php");
            }
        }else{
            echo "<h1>你不是學生</h1>";
            header("Refresh:2;url=login.php");
        }
        ?>
    <a href="logout.php">登出</a>
    </center>
</body>
</html>