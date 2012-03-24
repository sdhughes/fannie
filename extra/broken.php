<?php 

require_once('/pos/fannie/includes/mysqli_connect.php');

global $db_master;

mysqli_select_db($db_master, 'is4c_log') or die("dead");
?>
