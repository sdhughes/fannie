<?php
/*
 * Contains common functions in fannie's GUI
 */

//taken from darian-brown.com
function ping($host,$port=80,$timeout=6) {
        $fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
        if ( ! $fsock ) {
                return FALSE;
        } else {
                return TRUE;
        }
}


/**
 * Creates an HTML Element containing all active employees
 *
 **/
function makeEmployeeSelector() {
//get the connection to db
require_once('/pos/fannie/includes/mysqli_connect.php');
	
	global $db_master;
	
	$empQuery = "select emp_no, FirstName from is4c_op.employees where EmpActive = 1 ORDER BY FirstName ASC";

	$result = mysqli_query($db_master, $empQuery) or die('Query Error: ' . mysqli_error($db_master));

	echo "<select name='emp_selector'>";
	echo "<option value=\"error\">Who?</option>";
	while ($row = mysqli_fetch_row($result)) {


  		echo "<option value = \"$row[0]\">$row[1]</option>";

	}
	echo "</select>";
//    mysqli_close($db_master);
}

/*function makeEmpNoSelector {
//get the connection to db
require_once('/pos/fannie/includes/mysqli_connect.php');
	
	global $db_master;
	
	$empQuery = "select emp_no from is4c_op.employees where EmpActive = 1";

	$result = mysqli_query($db_master, $empQuery) or die('Query Error: ' . mysqli_error($db_master));

	echo "<select name='emp_selector'>";
	echo "<option value=\"error\">Who?</option>";
	while ($row = mysqli_fetch_row($result)) {


  		echo "<option value = \"$row[0]\">$row[0]</option>";

	}
	echo "</select>";
//    mysqli_close($db_master);
}*/


/**
 * Creates a set of HTML checkboxes representing all active Departments
 *
 **/
function dept_picker($div_class_name) {
//get the connection to db
require_once('/pos/fannie/includes/mysqli_connect.php');
	
	$data = 'is4c_op';
	global $db_master;
        
	//mysqli_select_db($db_master,$data) or die("Error selecting database for dept_picker: " . mysqli_error($db_master));
	//get the department names and numbers but not whole-sale, but not the PET, DELI, BREAD/JUICE, FLORAL, or is Tri-MEt
	$deptQ = "SELECT substr(dept_name,1,13) AS name, dept_no FROM is4c_op.departments WHERE dept_no <= 18 AND dept_no NOT IN (13, 15, 16, 17) OR dept_no = 40 ORDER BY dept_name ASC";
        $deptR = mysqli_query($db_master, $deptQ) or die("Query Error in dept_picker: " . mysqli_error($db_master));

	echo "<div id='dept_container'>";

        while (list($name, $no) = mysqli_fetch_row($deptR)) {
		printf("<div class='$div_class_name'><input type='checkbox' name='dept[]' id='input$no' class='deptCheck' value='%u' %s /><label for='input$no'>%s</label></div>", $no, (isset($_POST['dept']) && in_array($no, $_POST['dept']) ? 'checked="checked"' : ''), ucfirst(strtolower($name)));

        }
	echo "</div>";
  //  mysqli_close($db_master);
}



/**
 * Creates a set of HTML checkboxes representing all subdepts under the passed in Department
 *
 **/
function getSubdepts($department) {
//get the connection to db
require_once('../includes/mysqli_connect.php');

	global $db_master;
	
	$query = "SELECT subdept_no,subdept_name FROM is4c_op.subdepts WHERE dept_ID = $department;";

	$result = mysqli_query($db_master,$query) or die("Query Error: " . mysqli_error($db_master));
	
	echo "<div id='$department' class='subdept_container'>";

        while (list($name, $no) = mysqli_fetch_row($deptR)) {
                printf("<div class='subdept_tile'><input type='checkbox' name='subdepts[]' id='input$no' class='subdeptCheck' value='%u' %s /><label for='input$no'>%s</label></div>", $no, (isset($_POST['subdept_name']) && in_array($no, $_POST['dept']) ? 'checked="checked"' : ''), ucfirst(strtolower($name)));

        }
        echo "</div>";

}

function dept_picker_plus($div_class_name) {
//get the connection to db
require_once('/pos/fannie/includes/mysqli_connect.php');
	
	$data = 'is4c_op';
	global $db_master;
        
	//mysqli_select_db($db_master,$data) or die("Error selecting database for dept_picker: " . mysqli_error($db_master));
	//get the department names and numbers but not whole-sale, but not the PET, DELI, BREAD/JUICE, FLORAL, or is Tri-MEt
	$deptQ = "SELECT substr(dept_name,1,13) AS name, dept_no FROM is4c_op.departments WHERE dept_no NOT IN (13, 15, 16, 17) OR dept_no = 40 ORDER BY dept_name ASC";
        $deptR = mysqli_query($db_master, $deptQ) or die("Query Error in dept_picker: " . mysqli_error($db_master));

	echo "<div id='dept_container'>";

        while (list($name, $no) = mysqli_fetch_row($deptR)) {
		printf("<div class='$div_class_name'><input type='checkbox' name='dept[]' id='input$no' class='deptCheck' value='%u' %s /><label for='input$no'>%s</label></div>", $no, (isset($_POST['dept']) && in_array($no, $_POST['dept']) ? 'checked="checked"' : ''), ucfirst(strtolower($name)));

        }
	echo "</div>";
  //  mysqli_close($db_master);
}



//	mysqli_close($db_master);
?>
