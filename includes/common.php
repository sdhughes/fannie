<?php
/*
 * Contains common functions in fannie's GUI
 */
require_once('/pos/fannie/includes/mysqli_connect.php');

//taken from darian-brown.com
function ping($host,$port=80,$timeout=6) {
        $fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
        if ( ! $fsock ) {
                return FALSE;
        } else {
                return TRUE;
        }
}

//lists all the subdepts of desired dept @input (department id)
function listSubdepts($dept,$actionFile = '') {

    if (empty($actionFile)) $actionFile = $_SERVER['PHP_SELF'];
	
	global $db_master;

mysqli_select_db($db_master, 'is4c_op') or die("DB Select Error" . mysqli_error($db_master));
    //enter your query here or query generation loop        
    $subdept_query = "SELECT s.subdept_no, s.subdept_name, d.dept_name FROM subdepts s INNER JOIN departments d ON s.dept_ID = d.dept_no WHERE dept_ID = $dept ORDER BY dept_ID";

    //get the results of the query or spit an error 
    $result = mysqli_query($db_master, $subdept_query) or die ("Error from query: <br /> $subdept_query : <br />" . mysqli_error($db_master));

    //you can count the results if you want
    //$row_count = mysqli_num_rows($result);
    
    //iterator


        echo "<div class='sd_col' >";
        echo "<form method='POST' action='" . $actionFile . "'>";
        echo "<table id=''>";
                echo "<tr>";
                        echo "<th>ID</th>";
                        echo "<th>Name</th>";
                        echo "<th>Dept</th>";
                echo "</tr>";
        while ($row = mysqli_fetch_row($result)) {
                echo "<tr class='subdept_row'>";
                        echo "<td class='subdept_id'>";
                        echo "<label>$row[0]</label>";
                        echo "</td>";

                        echo "<td class='subdept_name'>";
                        echo "<label>$row[1]</label>";
                        echo "</td>";

                        echo "<td class='dept_ID'>";
                        echo $row[2];
                        echo "</td>";
                        
                        echo "<td><input class='edit_subdept_button' name='submit' type='submit' value='edit' ></td>";
                echo "</tr>";
        }


    echo "<tr>";
    echo "<td class='subdept_id'>";
        //print input box for new dept #
                echo "<input id='subdept_id' name='subdept_id' type='text' value='' />";
    echo "</td>";


    echo "<td>";
                //print input box for new dept name
                echo "<input id='subdept_name' name='subdept_name' type='text' value='' />";
    echo "</td>";


    echo "<td>";
                //print dept selector, drop down
              //  dept_selector('dept',$dept);
                echo "<input id='dept' name='dept' type='hidden' value='$dept' />";
    echo "</td>";
    echo "<td>";

                echo "<input id='add_button' name='submit' type='submit' value='add' />";
            echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";
    echo "</div>";
}

/**
 * Creates an HTML Element containing all active employees
 *
 **/
function emp_selector() {
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
 * Creates a HTML SELECT tag with all active Departments as options
 *
 **/
function dept_selector($id_name = "dept_container",$selected_dept) {
//get the connection to db
        require_once('/pos/fannie/includes/mysqli_connect.php');
	
	    $data = 'is4c_op';
	    global $db_master;
        
	    //mysqli_select_db($db_master,$data) or die("Error selecting database for dept_picker: " . mysqli_error($db_master));
	    //get the department names and numbers but not whole-sale, but not the PET, DELI, BREAD/JUICE, FLORAL, or is Tri-MEt
	    $deptQ = "SELECT substr(dept_name,1,13) AS name, dept_no FROM is4c_op.departments WHERE dept_no <= 18 AND dept_no NOT IN (13, 15, 16, 17) OR dept_no = 40 ORDER BY dept_name ASC";
        $deptR = mysqli_query($db_master, $deptQ) or die("Query Error in dept_picker: " . mysqli_error($db_master));

        $selected = ' selected = "selected" ';    

        echo "<select name='$id_name'>";
        echo "    <option value='0'>---</option>";
        while (list($name, $no) = mysqli_fetch_row($deptR)) {
            
		    printf("<option class='' value='%u' %s> %s</option>", $no,(($no==$selected_dept)?$selected:"") ,  ucfirst(strtolower($name)));

        }
        echo "</select>";
  //  mysqli_close($db_master);
}


/**
 * Creates a set of HTML checkboxes representing all subdepts under the passed in Department
 *
 **/
function subdept_picker($department) {
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

if (($_POST['reason']) || ($_GET['reason'] )) {

    if (isset($_GET['reason'])) {
        foreach ($_GET as $key => $val) {
            ${$key} = $val; 
        }
    }elseif (isset($_POST['reason'])) {
            foreach ($_POST as $key => $val) { 
                ${$key} = $val;
            }
    }
    switch ($reason) {
        case 'listSubdepts':
                listSubdepts($dept);
            break;
        case 'emp_selector':
                emp_selector();
            break;
        case 'dept_selector':
                dept_selector($div_class_name);
            break;
        case 'dept_picker':
                dept_picker($div_class_name);
            break;
        case 'dept_picker_plus':
                dept_picker_plus($div_class_namve);
            break;
        case 'subdept_picker':
                subdept_picker($dept);
        default:
            break;
    }
}
//	mysqli_close($db_master);
?>
