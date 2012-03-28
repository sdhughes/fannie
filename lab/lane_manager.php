<?php
    
    $page_title = "Fannie - Admin Module"; //top of window
    $header = "Lane Management"; //top of center DIV
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

//Pull out the vars from the POST or GET arrays
if (isset($_POST['submit'])) {
    foreach ($_POST as $key => $val) ${$key} = $val;

} elseif (isset($_GET['submit']))  {
    foreach ($_GET as $key => $val) ${$key} = $val;
}

//if it's submitted, then do the query work. // or change it to what you want
//if ($submit == 'submit') {

    //enter your query here or query generation loop        
    $query = "SELECT * FROM fannie.lanes AS l ORDER BY lane_no, location";

    //get the results of the query or spit an error 
    $result = mysqli_query($db_master, $query) or die ("Error from query: <br /> $query : <br /><br />" . mysqli_error($db_master));

    //you can count the results if you want
    //$row_count = mysqli_num_rows($result);
    
    //iterator

   echo "<div id='lane_manager' class='centered'>"; 
    echo "<table id='lane_management_table' class='thinborder'>";
    echo "<tr><th>Lane #</th><th>Location</th><th>IP Address</th><th>Ping?</th></tr>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<tr>";

        echo "<td>";
        echo $row[0];
        echo "</td>";

        echo "<td>";
        echo $row[1];
        echo "</td>";

        echo "<td>";
	echo "<input type='text' name='IP_addys[]' value='";
        $ip =  $row[2];
	echo long2ip($ip);
	echo "' / >";
	echo "</td>";
	echo "<td>";
	if (ping(long2ip($ip))) {
		echo "&nbsp;O&nbsp;";
	} else {
		echo "&nbsp;X&nbsp;";
	}

        echo "</td>";
        echo "</tr>";
    
    
      
    }
    echo "</table>";
	echo "</div>";
    
    
/*
} else { //what if it's not submitted?

}
?>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" >
    <input type='text' name='name' value=''>
    <input type='text' name='date1' value='' class='datepick' >
    <input type='text' name='date2' value='' class='datepick' >
    <input type='submit' name='submit' value='submit'>
    <input type='submit' name='submit' value='submit'>
    
</form>


<?php
*/
    include('../includes/footer.html');
?>
