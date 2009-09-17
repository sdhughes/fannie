<?php
$header = "Store Charges Report";
$page_title = "Fannie - Reports Module";
require_once ('../includes/header.html');

echo '<link href="../style.css" rel="stylesheet" type="text/css">
  <script src="../src/CalendarControl.js" language="javascript"></script>';

if (isset($_POST['submitted'])) {
    
    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db ($db_slave, 'is4c_log');
    
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    
    $query = "SELECT DATE(datetime), emp_no, upc, description, ROUND(unitprice, 2), itemqtty FROM transarchive
        WHERE card_no = 9999
        AND trans_type IN ('I', 'D')
        AND department <> 0
        AND trans_status <> 'X'
        AND emp_no <> 9999
        AND date(datetime) BETWEEN '$date1' AND '$date2'
        ORDER BY datetime";
    $result = mysqli_query($db_slave, $query);
    
    if ($result) {
        echo '<table border="1" cellspacing="3" cellpadding="3">
            <tr><th>Date</th><th>Emp. #</th><th>UPC</th><th>Description</th><th>Price</th><th>Quantity</th></tr>';
        while (list($date, $emp_no, $upc, $description, $price, $qty) = mysqli_fetch_row($result)) {
            echo "<tr><td>$date</td><td>$emp_no</td><td>$upc</td><td>$description</td><td>$price</td><td>$qty</td></tr>";
        }
        echo '</table>';
    } else {
        echo "<p>Error...</p><p>Query: $query</p>" .  mysqli_error($db_slave);
    }
    
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
                                <p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                                <p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
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