<?php
$header = 'Daily Item Sales';
$page_title = 'Fannie - Daily Item Sales';
include('../includes/header.html');

echo "<html><head><title>Numbers Relevant to NCGA</title>";
 
    //get the db connection
    require_once("../includes/mysqli_connect.php");
//	echo "<html><head><title>Daily Item Sales</title>";
?>
    <link rel="STYLESHEET" type="text/css" href="../includes/style.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script language='javascript' type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/myquery.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/flot/jquery.flot.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script language='javascript' type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){            
		$('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, changeMonth:true, changeYear: true, dateFormat: 'yy-mm-dd'});
        });
    </script>

        </head>
        <body>
<?php
//DEBUGGING
//echo "printing POST: ";
//print_r($_POST);    
//echo "<br/><br />";

foreach ($_POST as $key => $value) ${$key} = $value;

//ENTER THE GAUNTLET (check to see how page was submitted)
if (isset($_POST['submit']) && $_POST['submit'] == 'Submit' && $_POST ['upc'] != NULL && $_POST['date1'] != NULL && $_POST['date2'] != NULL ) {

    //create the variable from the POST array

    //get the start and ending years for accessing the correct archives
    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

    //check which form the description came in
    if (is_numeric($upc)) $where_upc = "upc LIKE '%$upc'";
    else $where_upc = " description LIKE '%$upc%'";

    //put the query together from the archives and current tables
    $DailySalesQ = "SELECT UNIX_TIMESTAMP(datetime) as Date, upc AS UPC, description AS Description,SUM(quantity) AS Quantity, SUM(total) as Sales FROM (";

    //loop from date1's year til date2's year
    for ($i = $year1; $i <= $year2; $i++) {

    	$DailySalesQ .= " SELECT datetime, upc, description, quantity, total FROM is4c_log.trans_$i WHERE $where_upc and DATE(datetime) BETWEEN '$date1' AND '$date2' ";

	if ($i == $year2)  {
		//we've caught up, get the current years data from the right DB
		if (substr($date2,-19,10) == date('Y-m-d')) {
    			
			$DailySalesQ .= " UNION ALL SELECT datetime, upc, description, quantity, total FROM dtransactions WHERE $where_upc and DATE(datetime) BETWEEN '$date1' AND '$date2'";

		}

		//if its the last year, then close it, name it and group it.
		$DailySalesQ .= " ) AS yearSpan GROUP BY DATE(datetime)";
	    } else {
		//if its not teh last year, then you ahve to UNION in the other years
		$DailySalesQ .= " UNION ALL";
    	}
    }
    //DEBUG 
//echo "<br /> Query is : $DailySalesQ <br />";

    //select the database, the query it
    mysqli_select_db($db_master,'is4c_log') or die("Could not select database: " . mysqli_errno($db_master));
    $DailySalesR = mysqli_query($db_master, $DailySalesQ) or die("Could not complete query: " . mysqli_error($db_master));

    //DEBUG    echo "<br />Row Count is: " . mysqli_num_rows($DailySalesR);

    $firstTime = 1;

    //print the table headers
    echo "<div style='vertical-align: top;' ><div style='display:inline-block;vertical-align: top;'><table style='margin: 1px;' border ><th>Date</th><th>Amount</th><th>Sales</th>";
    while ($row = mysqli_fetch_row($DailySalesR)) {

    	if ($firstTime == 1) {
		echo "<p>You searched for " . $row[2] . " between $date1 and $date2.</p>";
		$firstTime = 0;
	}

	//loop through and print out the table
	echo "<tr><td>$row[0]</td><td>$row[3]</td><td class='right'>$$row[4]</td></tr>";
//	$myjson = json_encode($row);
//	echo "<input type='hidden' name='myjson' value='$myjson' /> ";
    }
    echo "</table></div>";

    //plot the data
    echo "<div id='placeholder' style=\"vertical-align:top; width: 300px; height: 200px; display: inline-block; resize: both;\"></div></div>";

echo "<div style='diplay:inline-block'><input type='button' name='graph' id='graph' value='Graph' /></div>";
} elseif ( isset($_POST['submit']) && $_POST['submit'] == 'Submit' && $_POST['upc'] != NULL ) {
	echo "<p>Tsk tsk, you didn't enter the dates or something.</p>";
} elseif ( isset($_POST['submit']) && $_POST['submit'] == 'Submit' ) {
	echo "<p>Tsk tsk, you didn't enter a upc to search for.</p>";
} else {

	$date1 = '';
	$date2 = '';
/*
echo '
<script type="text/javascript" >

	$(function() {
	

		var placeholder = $('#placeholder');
		var data = '';
	
		$("#graph").click(function(){
			
	
			$.plot(placeholder,data);
		});
	
	});	

</script>
';
*/


}

echo '    <form action="dailyItemSales.php" method="POST" >
	<input type="input" id="upc" name="upc" value="' . $upc  . '" /> Please enter a UPC to search for<br />
        <input type="text" name="date1" class="datepick" value="' . $date1 . '" /> Start Date <br />
        <input type="text" name="date2" class="datepick" value="' . $date2 . '" /> End Date<br />
        <input type="submit" name="submit" value="Submit" />
	<input type="reset" name="reset" value="Reset" />
    </form>';

//</body>
//</html>
include('../includes/footer.html');

?>
