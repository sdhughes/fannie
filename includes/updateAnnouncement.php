<?php 

	if (isset($_POST['id'])) {

		require_once('../includes/mysqli_connect.php');
		global $db_master;
$message = $_POST['message'];
        $escaped_message = mysqli_real_escape_string($db_master, $message);


        //if ($_POST['enable']) == "true") $enableIt = 1;
        //else $enableIt = 0; 


		$query = "UPDATE fannie.announcements SET " 
                    . "author = \"" . $_POST['author'] 
                    . "\", title = \"" . $_POST['title'] 
                    . "\", message = \"" . $escaped_message 
                    . "\", modified = NOW(), " 
                    . "enabled = \"" . $_POST['enable'] 
                   . "\" WHERE id = " . $_POST['id'] . ";";
//print_r($_POST);
		$result = mysqli_query($db_master, $query) or die("Query Error ($query):  <br/>" . mysqli_error($db_master));
//		$answer = mysqli_fetch_row($result);
		mysqli_close($db_master);
//		echo "the var: " + $enableIt;
        //print_r($_POST);
	} else echo "0";


?>
