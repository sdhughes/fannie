<?php
require_once('../includes/mysqli_connect.php');


	global $db_master;

	function getSubdepts($department) {

		global $db_master;
	
		$output = '';
	
		if ($department) {

			$query = "SELECT subdept_no,subdept_name FROM is4c_op.subdepts WHERE dept_ID = $department;";

			$result = mysqli_query($db_master,$query) or die("Query Error: " . mysqli_error($db_master));

			//$output = "<select>";
			$output = "<div class='subdept_tile' id='". $_POST['deptName']."_tile'><div class='subdept_header'>" . $_POST['deptName_raw'] . "</div>";
			while ($row = mysqli_fetch_row($result)) {
				$output .= "<div><input type='checkbox' name='subdept_array[]' value='$row[0]' id='subdept$row[0]' class='subdept_dropdown' /><label for='subdept$row[0]'>$row[1]</label></div>";
			}

			//$output .= "</select>";
			$output .= "</div>";

		} else {
	
		$output = "No Dept Selected";
		}
			echo $output;
	
	}
	
	if (isset($_POST['department'])) {
//print_r($_POST[]);	
//		getSubdepts(1);
		getSubdepts($_POST['department']);

	} else echo "ERROR ERROR CYLONS DETECTED";


?>
