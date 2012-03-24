<?php
$page_title = 'Fannie - Executive Summary by Date Range';
$header = 'Executive Summary';
include('../includes/header.html');
echo "<html><head><title>Numbers Relevant to NCGA</title>";
?>    
    <link rel="STYLESHEET" type="text/css" href="../includes/style.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, changeMonth:true, changeYear: true, dateFormat: 'yy-mm-dd'});
        });
    </script>
	</head>
	<body>
<?php


//print_r($_POST);    

    
if ( $_POST['submit'] == 'Submit' ) {

    //copy the variables out of the POST array
    foreach ($_POST as $key => $value) ${$key} = $value;

    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

    echo "<p>Numbers have been compiled from between $date1 and $date2.</p>";


 $CountQ = "SELECT COUNT(upc) FROM is4c_log.$transtable
        WHERE DATE(datetime) = '$date'
        AND upc = 'DISCOUNT'
        AND emp_no <> 9999
        AND trans_status <> 'X'
        AND trans_subtype <> 'LN'";

 

    $CountQ = "SELECT upc FROM (";
    $CouponQ = "SELECT ROUND(SUM(total),2) AS Coupons FROM (";
    $TotalDiscountQ = "SELECT SUM(total) AS totaldisc FROM (";
    $GrossQ = "SELECT ROUND(SUM(total),2) AS 'Gross Sales' FROM (";
    $NetQ = "SELECT SUM(total) FROM (";

    for ($i = $year1; $i <= $year2; $i++) {
	//all Jason's wants
        $TotalDiscountQ .= "SELECT SUM(total) AS total
               FROM is4c_log.trans_$i
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND trans_status <> 'X'
               AND emp_no <> 9999";

        $CouponQ .= "SELECT SUM(total) AS total
               FROM is4c_log.trans_$i
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND trans_subtype = 'IC'
               AND trans_status <> 'X'
               AND emp_no <> 9999";

        $GrossQ .= "SELECT SUM(total) AS 'total'
               FROM is4c_log.trans_$i
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND department <> 0
               AND trans_status <> 'X'
               AND trans_subtype <> 'MC'
               AND emp_no <> 9999";

        $NetQ .= "SELECT SUM(total) AS 'total'
               FROM is4c_log.trans_$i
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND trans_type IN ('I', 'D')
               AND trans_status <> 'X'
               AND trans_subtype <> 'MC'
               AND emp_no <> 9999
               AND upc <> 'MADISCOUNT'";


        $CountQ .= "SELECT COUNT(upc) as upc FROM is4c_log.trans_$i WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN'";

        if ($i == $year2) {
        //add the current years data        
            if ($date2 == date('Y-m-d')) {
		$TotalDiscountQ .= " UNION ALL SELECT SUM(total) AS total
               FROM is4c_log.dtransactions
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND trans_status <> 'X'
               AND emp_no <> 9999";

	        $CouponQ .= " UNION ALL SELECT SUM(total) AS total
               FROM is4c_log.dtransactions
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND trans_subtype = 'IC'
               AND trans_status <> 'X'
               AND emp_no <> 9999";

        	$GrossQ .= " UNION ALL SELECT SUM(total) AS 'total' FROM is4c_log.dtransactions WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND department <> 0 AND trans_status <> 'X' AND trans_subtype <> 'MC' AND emp_no <> 9999";

        	$NetQ .= "UNION ALL SELECT SUM(total) 
               FROM is4c_log.dtransactions
               WHERE DATE(datetime) BETWEEN '$date1' AND '$date2'
               AND trans_type IN ('I', 'D')
               AND trans_status <> 'X'
               AND trans_subtype <> 'MC'
               AND emp_no <> 9999
               AND upc <> 'MADISCOUNT'";



        	$CountQ .= " UNION ALL SELECT COUNT(upc) as upc FROM is4c_log.dtransactions WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN'";

            }
            $CountQ .= ") AS yearSpan";
	    $TotalDiscountQ .= ") AS yearSpan";
	    $CouponQ .= ") AS yearSpan";
	    $GrossQ .=") AS yearSpan";
	    $NetQ .= ") AS yearSpan";

        } else {
            $CountQ .= " UNION ALL "; 
            $TotalDiscountQ .= " UNION ALL ";
	    $CouponQ .= " UNION ALL ";
	    $GrossQ .= " UNION ALL ";
	    $NetQ .= " UNION ALL ";
        }
    }
/*	
    echo "<br />The Queries<br /><br />" . $CountQ 
	. "<br /><br />" . $GrossQ
	. "<br /><br />" . $NetQ
	. "<br /><br />" . $CouponQ;
*/
    //Now that we've got all the queries rounded up, let's use them!

    require_once("../includes/mysqli_connect.php");
    //set  up the connection and select the database
    mysqli_select_db($db_master,'is4c_log') or die("<p>Could not select db: " . mysqli_errno($db_master) . "</p>");

    $CountR = mysqli_query($db_master,$CountQ) or die("<p>Could not complete query: " . mysqli_errno($db_master). "</p>");
    $GrossR = mysqli_query($db_master,$GrossQ) or die("<p>Could not complete Gross Sales query: " . mysqli_errno($db_master). "</p>");
    $TotalDiscountR = mysqli_query($db_master,$TotalDiscountQ) or die("<p>Count not complete Total Disc query: " . mysqli_errno($db_master). "</p>");
    $CouponR = mysqli_query($db_master,$CouponQ) or die("<p>Count not complete Coupon query: " . mysqli_errno($db_master). "</p>");
    $NetR = mysqli_query($db_master,$NetQ) or die("<p>Count not complete Net Sales query: " . mysqli_errno($db_master). "</p>");

    $count = mysqli_fetch_row($CountR);
    list($gross) = mysqli_fetch_row($GrossR);
    list($totalDiscount) = mysqli_fetch_row($TotalDiscountR);
    list($coupon) = mysqli_fetch_row($CouponR);
    list($net) = mysqli_fetch_row($NetR);

    $net += $coupon;
    echo "<table id='ncga_data'><tr><td> Customer Count: </td><td>" . $count[0] . "</td></tr>";
    echo "<tr><td> Gross Total: </td><td>$" . number_format($gross,2) . "</td></tr>";
    echo "<tr><td> Total Discount: </td><td>$" . number_format($totalDiscount,2) . "</td></tr>";
    echo "<tr><td> Coupon Total: </td><td>$". number_format($coupon,2) . "</td></tr>";
    echo "<tr><td> Net Total: </td><td>$". number_format($net,2) . "</td></tr></table>";
/*	
	while ($row = mysqli_fetch_array($CountR, MYSQLI_ASSOC) { 
		echo "<p>upc is ". $row['upc'] . " </p>";
	}
*/
	echo "<p>Re-choose dates:</p>";
} 
?>
    <form action='ncgaNumbers.php' method='POST' >
        <p>Please select the dates between which to grab important info:</p>
        <input type="text" name="date1" class="datepick" />
        <input type="text" name="date2" class="datepick" />
        <input type="submit" name="submit" value="Submit" />
    </form>
<?php
include('../includes/footer.html');
//</body>
//</html>

