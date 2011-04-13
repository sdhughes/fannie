<?php 
function dept_picker($div_class_name) {
	//echo '<table>';


 	$db = mysqli_connect('localhost','cron','cr0n','is4c_op') or die ("error: " . mysql_errno());
        
	//get the department names and numbers but not whole-sale, but not the PET, DELI, BREAD/JUICE, FLORAL, or IS a Tri-MEt
	$deptQ = "SELECT substr(dept_name,1,13) AS name, dept_no FROM is4c_op.departments WHERE dept_no <= 18 AND dept_no NOT IN (13, 15, 16, 17) OR dept_no = 40 ORDER BY dept_name ASC";
        $deptR = mysqli_query($db, $deptQ);

        //$count = 0;

        while (list($name, $no) = mysqli_fetch_row($deptR)) {
         // if ($count % $number_of_columns == 0) echo '<tr>';
          // $count++;
           printf("
			<div class='$div_class_name'>
				<input type=\"checkbox\" name=\"dept[]\" class=\"deptCheck\" value=\"%u\" %s />%s
			</div>
		", $no, (isset($_POST['dept']) && in_array($no, $_POST['dept']) ? 'checked="checked"' : ''), ucfirst(strtolower($name)));

          // if ($count % $number_of_columns == 0) echo '</tr>';
        }

        mysqli_close($db);
       // echo '</table>';
}

?>
