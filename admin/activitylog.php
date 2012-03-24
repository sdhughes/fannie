<?php
    

    $page_title = "Fannie - Admin Module"; //top of window
    $header = "Activity Log Viewer"; //top of center DIV
    include('../includes/header.html');

    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');

echo
<<<EOS
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <!-- a script ref to jquery.js was removed bc it was double included from the header  -->
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd', changeYear: true, changeMonth: true, duration: 0 });
        });

    $(document).ready(function() {
        $('#selectAll').click(function() {
        if ($(this).text() == 'All Departments') {
            $('input.deptCheck').attr('checked', true);
            $(this).text('Clear Selections');
        } else {
            $('input.deptCheck').attr('checked', false);
            $(this).text('All Departments');
        }
        });

        $('.deptCheck').click(function() {
        $('#selectAll').text('Clear Selections');
        });
    });

    </script>
EOS;

echo "<div id='activity_container'>";
 draw_activity_form();

echo "<div id='table_column_div' class='activity_column'>";
//Pull out the vars from the POST or GET arrays
if (isset($_POST['submit'])) {
    foreach ($_POST as $key => $val) ${$key} = $val;
} elseif (isset($_GET['submit']))  {
    foreach ($_GET as $key => $val) ${$key} = $val;
}

if (($_POST['date1'] == NULL) && (isset($_POST['submit']))) {
	echo "<p class='directions'>No Date Selected.</p>";

} elseif ($submit == 'submit') {
//if it's submitted, then do the query work. // or change it to what you want

$date1 = $_POST['date1'];
$regString = implode(", ",$_POST['regs']);

	echo "<h2>Viewing Activity from Reg # $regString on <span class='under'>$date1</span></h2>";


	$where = " date(datetime) = '" . $_POST['date1'] . "' AND LaneNo IN (" . $regString .") ";

    //enter your query here or query generation loop        
    $query = "SELECT al.datetime, al.LaneNo, al.CashierNo, e.FirstName, al.TransNo, a.Description, ROUND((al.Interval/60),2) as duration FROM is4c_log.alog al INNER JOIN is4c_op.employees e INNER JOIN is4c_log.activities a ON al.CashierNo = e.emp_no AND al.Activity = a.Activity WHERE $where ORDER BY al.LaneNo, al.datetime";

//echo $query . "<br />";
    //get the results of the query or spit an error
	mysqli_select_db($db_master, 'is4c_log');
    $result = mysqli_query($db_master, $query) or die ("Error from query: <br /> $query : <br /><br />" . mysqli_error($db_master));

    //you can count the results if you want
    //$row_count = mysqli_num_rows($result);
    
    //iterator

    echo "<div id='activity_table'>";
    echo "<table id='' class='thinborder'>";

    echo "<th>Time</th>";
    echo "<th>Lane #</th>";
    echo "<th>Cashier No</th>";
    echo "<th>First Name</th>";
    echo "<th>Transaction No</th>";
    echo "<th>Activity</th>";
    echo "<th>Interval (min)</th>";

    while ($row = mysqli_fetch_row($result)) {
        echo "<tr>";
        echo "<td>";
        echo substr($row[0],10);
        echo "</td>";
        echo "<td>";
        echo $row[1];
        echo "</td>";
        echo "<td>";
        echo $row[2];
        echo "</td>";
        echo "<td>";
        echo $row[3];
        echo "</td>";
        echo "<td>";
        echo $row[4];
        echo "</td>";
        echo "<td>";
        echo $row[5];
        echo "</td>";
        echo "<td>";
        echo $row[6];
        echo "</td>";
        echo "</tr>";
    
    
      
    }
    echo "</table>";
    
    echo "</div>"; 

} else { //what if it's not submitted?

	echo "<div class='directions'>Please select a date and click submit to see Register Activity for that day.</div>";
}
	echo "</div>";
	echo "</div>";
function draw_activity_form() {
?>
<div id='activity_form' class='activity_column' >
	<form method='post' action='<?php echo $_SERVER['PHP_SELF']; ?>'>
	    Select Date: <input type='text' name='date1' value='' class='datepick' /><br />
	    Reg 1 <input type='checkbox' name='regs[]' value='1' class='' checked=checked /></label>
	    Reg 2 <input type='checkbox' name='regs[]' value='2' class='' checked=checked /></label>
	    Reg 3 <input type='checkbox' name='regs[]' value='3' class='' checked=checked /></label>

	    <br /><input type='submit' name='submit' value='submit'>
	    <input type='submit' name='submit' value='reset'>
	    
	</form>
</div>

<?php
}
    include('../includes/footer.html');
?>
