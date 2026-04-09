<?php


$fID="a1133356";
$fPWD="a1133356";

if(isset($_POST["uID"])&&isset($_POST["uPWD"])){
    $uID=$_POST["uID"];
    $uPWD=$_POST["uPWD"];

    if($fID == $uID && $fPWD == $uPWD){
        header("Location: form.php");
    }else{
        echo "登入失敗";
        header("Refresh:2;url=login.php");
    }
}

?>