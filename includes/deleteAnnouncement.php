<?php 

	if (isset($_POST['id'])) {
//echo $_POST['id'];
		require_once('../includes/mysqli_connect.php');
		global $db_slave;
		$query = "DELETE FROM fannie.announcements WHERE id = " . $_POST['id'] . ";";
		$result = mysqli_query($db_slave, $query) or die("Query Error ($query):  <br/>" . mysqli_error($db_slave));
//		$answer = mysqli_fetch_row($result);
		//echo $answer;
		mysqli_close($db_slave);
	} else echo "0";


?>
