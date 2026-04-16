<?php

setcookie("uName",'',time()-1);
header("Refresh:2; url=login.php");

?>