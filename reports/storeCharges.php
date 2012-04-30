<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$header = "Store Charges Report";
$page_title = "Fannie - Reports Module";
require_once ('../includes/header.html');

//echo '<link href="../style.css" rel="stylesheet" type="text/css">
//  <script src="../src/CalendarControl.js" language="javascript"></script>';

?>
 <link rel="STYLESHEET" type="text/css" href="../includes/style.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
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
<?php

if (isset($_POST['submitted'])) {
    
    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db ($db_slave, 'is4c_log');
    
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    
    $query = "SELECT DATE(datetime), emp_no, upc, description, total, itemqtty FROM transarchive
        WHERE trans_subtype = 'MI'
        " . //AND department <> 0
        "AND trans_status <> 'X'
        AND emp_no <> 9999
        AND card_no = 9999
        AND date(datetime) BETWEEN '$date1' AND '$date2'
        ORDER BY datetime";
   /* $query = "SELECT DATE(datetime), emp_no, upc, description, ROUND(unitprice, 2), itemqtty FROM transarchive
        WHERE card_no = 9999
        AND trans_type IN ('I', 'D')
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999
        AND date(datetime) BETWEEN '$date1' AND '$date2'
        ORDER BY datetime";*/
    $result = mysqli_query($db_slave, $query);
    $chargeTotal = 0;

echo $query . "<br />";
echo "<div align='center'>";
        echo "<h3>Charges between $date1 and $date2:</h3>";
    if ($result) {
        echo '<table border="1" cellspacing="3" cellpadding="3">
            <tr><th>Date</th><th>Emp. #</th><th>UPC</th><th>Description</th><th>Price</th><th>Quantity</th><th>Total</th></tr>';
        while (list($date, $emp_no, $upc, $description, $price, $qty) = mysqli_fetch_row($result)) {
            echo "<tr><td>$date</td><td>$emp_no</td><td>$upc</td><td>$description</td><td>" . sprintf("$%.2f", $price) . "</td><td>$qty</td><td>" . sprintf("$%.2f", $price * $qty) . "</td></tr>";
            
            $chargeTotal += $price * $qty;

        }
        echo '</table>';
        echo "<h3>Total Value Charged between $date1 and $date2: " . sprintf("$%.2f", $chargeTotal) . "</h3>";
    } else {
        echo "<p>Error...</p><p>Query: $query</p>" .  mysqli_error($db_slave);
    }
    echo "</div>";
    mysqli_close($db_slave);

} else {
    echo '<form action="storeCharges.php" method="POST">
            <div id="box">
            <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                        <td align="right">
                                <p><b>Date Start</b> </p>
                        <p><b>End</b></p>
                        </td>
                        <td>			
                                <p><input type=text size=10 name=date1 class="datepick"></p>
                                <p><input type=text size=10 name=date2 class="datepick"></p>
                        </td>
                        <td colspan=2>
                                <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                        </td>
                    </tr>
                    <tr> 
                        <td>&nbsp;</td>
                        <td> <input type=submit name=submit value="Submit"> </td>
                        <td> <input type=reset name=reset value="Start Over"> </td>
                        <input type="hidden" name="submitted" value="TRUE">
                    </tr>
            </table>
            </div>
            </form>';
        
}

require_once ('../includes/footer.html');
?>
