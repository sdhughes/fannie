<?php 
function dept_picker($div_class_name) {
	
	require_once('../includes/mysqli_connect.php');
global $db_master;
mysqli_select_db($db_master,'is4c_op');
	//get the department names and numbers but not whole-sale, but not the PET, DELI, BREAD/JUICE, FLORAL, or is Tri-MEt
	$deptQ = "SELECT substr(dept_name,1,13) AS name, dept_no FROM is4c_op.departments WHERE dept_no <= 18 AND dept_no NOT IN (13, 15, 16, 17) OR dept_no = 40 ORDER BY dept_name ASC";
        $deptR = mysqli_query($db_master, $deptQ) or die("Query Error in dept_picker: " . mysqli_error($db_master));

echo "<div id='dept_container'>";

        while (list($name, $no) = mysqli_fetch_row($deptR)) {
           printf("<div class='$div_class_name'><input type='checkbox' name='dept[]' id='input$no' class='deptCheck' value='%u' %s /><label for='input$no'>%s</label></div>", $no, (isset($_POST['dept']) && in_array($no, $_POST['dept']) ? 'checked="checked"' : ''), ucfirst(strtolower($name)));

        }
echo "</div>";
     //   mysqli_close($db_master);
}

?>
