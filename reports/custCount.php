<?php
$page_title = "Fannie - Reporting";
$header = "Customer Count";
include('../includes/header.html');
?>    
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
<!--    <script type="text/javascript" src="../includes/javascript/jquery.js"></script> -->
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, changeMonth:true, changeYear: true, dateFormat: 'yy-mm-dd'});
        });
    </script>
<?php


//print_r($_POST);    

    
if ( $_POST['submit'] == 'Submit' ) {
 
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];

    foreach ($_POST as $key => $value) ${$key} = $value;

    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

/*
 $CountQ = "SELECT COUNT(upc) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND upc = 'DISCOUNT'
        AND emp_no <> 9999
        AND trans_status <> 'X'
        AND trans_subtype <> 'LN'";
*/
 

    $CountQ = "SELECT upc FROM (";



    for ($i = $year1; $i <= $year2; $i++) {

        $CountQ .= "SELECT COUNT(upc) as upc FROM is4c_log.trans_$i WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN'";


        if ($i == $year2) {
        //add the current years data        
            if ($date2 == date('Y-m-d')) {
        

        	$CountQ .= "UNION ALL SELECT COUNT(upc) as upc FROM is4c_log.dtransactions WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN'";

            }
            $CountQ .= ") AS yearSpan";

        } else {
            $CountQ .= " UNION ALL "; 
        }
    }
  //  echo "<br />The Queries<br /><br />" . $CountQ . "<br /><br />" . $salesQ;
    require_once("../includes/mysqli_connect.php");
    $CountC = mysqli_select_db($db_master,'is4c_log') or die("<p>Could not select db: " . mysqli_errno($db_master) . "</p>");
    $CountR = mysqli_query($db_master,$CountQ) or die("<p>Count not complete query: " . mysqli_errno($db_master). "</p>");
    $count = mysqli_fetch_row($CountR);

    echo "<h2> Customer Count is: " . $count[0] . "</h2>";
	echo "<p>Re-choose dates:</p>";
} 
?>
    <form action='custCount.php' method='POST' >
        <p>Please select the dates between which we'll count the customers. (NOTE: Cannot count today's #s)</p>
        <input type="text" name="date1" class="datepick" />
        <input type="text" name="date2" class="datepick" />
        <input type="submit" name="submit" value="Submit" />
    </form>
<?php
include('../includes/footer.html');
?>
