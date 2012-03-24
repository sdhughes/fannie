<?php
    
    $page_title = "Fannie - Report Module"; //top of window
    $header = "Shifts Clocked By Date"; //top of center DIV
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
echo "<div id='acg_center'>";
//Pull out the vars from the POST or GET arrays
if (isset($_POST['submit'])) {
    foreach ($_POST as $key => $val) ${$key} = $val;

} elseif (isset($_GET['submit']))  {
    foreach ($_GET as $key => $val) ${$key} = $val;
}

//if it's submitted, then do the query work. // or change it to what you want
if ($submit == 'submit') {

    //enter your query here or query generation loop        
    $query = "SELECT t.date, e.firstName, t.emp_no, TIMEDIFF(t.time_out, t.time_in), TIME(t.time_in), TIME(t.time_out), s.ShiftName FROM is4c_log.timesheet t inner join is4c_op.employees e inner join is4c_log.shifts s on t.emp_no = e.emp_no AND t.area = s.ShiftId WHERE area != 0 AND date = '$date1' ORDER BY t.date,t.emp_no ASC";

    //get the results of the query or spit an error 
    $result = mysqli_query($db_master, $query) or die ("Error from query: <br /> $query : <br /><br />" . mysqli_error($db_master));

    //you can count the results if you want
    //$row_count = mysqli_num_rows($result);
    
    //iterator

    $maxhours = 17;
    $totalMinutes = $maxhours * 60;
    $storeOpen = 7;
    
    echo "<table id='shiftTable' class='' >";
        echo "<tr>";
 //       echo "<th>date</th>";
        echo "<th>first name</th>";
//        echo "<th>emp no</th>";
        echo "<th>hours</th>";
        echo "<th>in</th>";
        echo "<th>out</th>";
//        echo "<th>area</th>";

        for ($j = 0; $j < $maxhours; $j++) { $hours = $j + $storeOpen; echo "<td colspan=12>$hours</td>";} 
        
        echo "</tr>";
    while ($row = mysqli_fetch_row($result)) {
        $startHour = substr($row[4],0,2 );
        $endHour  = substr($row[5],0,2 );

        $startMin = substr($row[4],3,2);
        $endMin = substr($row[5],3,2);


        echo "<tr class='";
	
	switch ($row[6]) {
		case 'Committee':
			echo "";
			break;
		case 'Management':
			echo "";
			break;
		case 'Technology':
			echo "";
			break;
		case 'Maintenance':
			echo "";
			break;
		case 'Bookkeeper':
			echo "";
			break;
		case 'Front End':
			echo "";
			break;
		case 'Produce':
			echo "";
			break;
		case 'Grocery':
			echo "";
			break;
		case 'Perishables':
			echo "";
			break;
		case 'Wellness':
			echo "";
			break;
		case 'Bulk':
			echo "";
			break;
		case 'Spirits':
			echo "";
			break;
		case 'Owner Services':
			echo "";
			break;
		case 'Board Scribe':
			echo "";
			break;
		case 'Marketing':
			echo "";
			break;
		default: 
			echo " ";

	}

	echo "'>";
 //       echo "<td>" . $row[0] . "</td>";
        echo "<td>" . $row[1] . "</td>";
 //       echo "<td>" . $row[2] . "</td>";
        echo "<td>" . substr($row[3],0,5) . "</td>";
        echo "<td>" . substr($row[4],0,5) . "</td>";
        echo "<td>" . substr($row[5],0,5) . "</td>";
//        echo "<td>" . $row[6] . "</td>";
    


        for ($i = 0;$i < $maxhours; $i++) {
            $currHour = $i + $storeOpen;

            for ($k = 0; $k < 12; $k++ ) {
                $currMin = $k * 5;
/*
                echo "<td";
                //color the background
                if (($startHour < $currHour) && ($currhour < $endHour)) {
                    
                    if ($startMin < $currMin) && ($currMin < $endMin)) echo " class='red'>&nbsp;</td> ";
                    else echo "";
                }
                else echo ">$startMin $currMin</td>";
*/
                if ($currHour < $startHour) echo "<td class='shiftCell'></td>";
                elseif (($currHour >= $startHour) && ($currHour < $endHour )) {
                    if (($currHour == $startHour) && ($currMin < $startMin)) echo "<td class='shiftCell'></td>";
                    else echo "<td class='shiftCell red'></td>";
                }
                elseif (($currHour ==  $endHour) && ($currMin < $endMin)) echo "<td class='shiftCell red' ></td>";
                else echo "<td class='shiftCell'></td>";   
                //elseif (($currHour > $startHour) && ($currHour < $endHour)) echo "<td class='red' >&nbsp;</td>";
                //elseif (($currHour > $endHour) && ($currMin < $endMin)) echo "<td class='red' >&nbsp;</td>";



            }
        }
        echo "</tr>";
    
    
      
    }
    echo "</table>";
    
   

} else { //what if it's not submitted?

}
?>
<p>
Choose date to see who worked that day.
</p>
<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
<!--    <input type='text' name='name' value=''>
-->    <input type='text' name='date1' value='' class='datepick' >
<!--    <input type='text' name='date2' value='' class='datepick' >
    <input type='submit' name='submit' value='submit'>
-->    <input type='submit' name='submit' value='submit'>
    
</form>


<?php
echo '</div>';
    include('../includes/footer.html');
?>
