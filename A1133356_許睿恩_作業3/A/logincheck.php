<?php

session_start();

$sID = "a1133356";
$sPWD = "a1133356";

$tID = "teacher";
$tPWD = "imteacher";

$aID = "admin";
$aPWD = "imadmin";

$uID = $_POST["uName"];
$uPWD = $_POST["uPWD"];

$date = strtotime("+3 days", time());


if($sID == $uID && $sPWD == $uPWD){
    $_SESSION["login"] = "student";
    setcookie("uName",$uID,$date);
    header("Location:student.php");
}else if($tID == $uID && $tPWD == $uPWD){
    $_SESSION["login"] = "teacher";
    setcookie("uName",$uID,$date);
    header("Location:teacher.php");
}else if($aID == $uID && $aPWD == $uPWD){
    $_SESSION["login"] = "admin";
    setcookie("uName",$uID,$date);
    header("Location:admin.php");
}else{
    echo "登入失敗";
    header("Refresh:2;url=login.php");
}

?>