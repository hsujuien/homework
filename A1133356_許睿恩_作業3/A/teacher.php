<html>
<head>
    <title>student</title>
</head>
<body>
    <center>
        <?php
        session_start();
        if(isset($_SESSION["login"])){
            if($_SESSION["login"] == "teacher"){
                echo "<h1>老師您好</h1>";
            }else{
                echo "<h1>你不是老師</h1>";
                header("Refresh:2;url=login.php");
            }
        }else{
            echo "<h1>你不是老師</h1>";
            header("Refresh:2;url=login.php");
        }
        ?>
    <a href="logout.php">登出</a>
    </center>
</body>
</html>