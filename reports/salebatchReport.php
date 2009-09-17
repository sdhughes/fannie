<?php
$header = "Sale Batch Movement Report";
$page_title = "Fannie - Reports Module";
require_once ('../includes/header.html');
require_once ('../includes/mysqli_connect.php');

echo '<link href="../style.css" rel="stylesheet" type="text/css">
  <script src="../src/CalendarControl.js" language="javascript"></script>';

if (isset($_POST['submitted'])) {
    mysqli_select_db ($db_slave, 'is4c_op');
    $ID = $_POST['batch'];
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    
    $rangeQ = "SELECT b.upc, t.description, ROUND(SUM(t.itemqtty), 2), DATEDIFF('$date2', '$date1') AS diff
        FROM batchList AS b INNER JOIN is4c_log.transarchive AS t ON (b.upc = t.upc)
        WHERE b.batchID = $ID
            AND t.trans_type = 'I'
            AND t.trans_status <> 'X'
            AND t.emp_no <> 9999
            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
        GROUP BY t.upc
        ORDER BY t.datetime";
    $rangeR = mysqli_query($db_slave, $rangeQ);
    
    if (mysqli_num_rows($rangeR) != 0) {
        if ($rangeR) {
            $range = array();
            while (list($upc, $description, $qty, $diff) = mysqli_fetch_array($rangeR)) {
                $rangeDiff = $diff;
                $range[$upc] = array("UPC" => $upc, "Description" => $description, "Quantity" => $qty);
            }
        } else {
            echo "<p>Error...</p><p>Query: $rangeQ</p>" .  mysqli_error($db_slave);
        }
    } else {
        echo "<p>Error: there were no results...maybe try a different date range?</p>";
        include_once ('../includes/footer.html');
        exit();
    }
    
    $batchQ = "SELECT bl.upc, t.description, ROUND(SUM(t.itemqtty), 2),
            DATEDIFF(CASE WHEN (b.endDate > curdate()) THEN (curdate()) ELSE (b.endDate) END, b.startDate) AS diff
        FROM batchList AS bl INNER JOIN is4c_log.transarchive AS t ON (bl.upc = t.upc)
            INNER JOIN batches AS b ON (b.batchID = bl.batchID)
        WHERE bl.batchID = $ID
            AND t.trans_type = 'I'
            AND t.trans_status <> 'X'
            AND t.emp_no <> 9999
            AND DATE(t.datetime) BETWEEN b.startDate AND CASE WHEN (b.endDate > curdate()) THEN (curdate()) ELSE (b.endDate) END
        GROUP BY t.upc
        ORDER BY t.datetime";
    $batchR = mysqli_query($db_slave, $batchQ);
    
    if (mysqli_num_rows($rangeR) != 0) {
        if ($batchR) {
            $batch = array();
            while (list($upc, $description, $qty, $diff) = mysqli_fetch_array($batchR)) {
                $batchDiff = $diff;
                $batch[$upc] = array("UPC" => $upc, "Description" => $description, "Quantity" => $qty);
            }
        } else {
            echo "<p>Error...</p><p>Query: $batchQ</p>" .  mysqli_error($db_slave);
        }
    } else {
        echo "<p>Error: there were no results...maybe try a different date range?</p>";
        include_once ('../includes/footer.html');
        exit();
    }
    
    /*
    foreach ($batch AS $key => $value) {
        echo "<p>$key</p>";
        foreach ($batch[$key] AS $k => $v) {
            echo "<p>$k - $v</p>";
        }
    }
    */
    
    if ($rangeR && $batchR) {
        echo "<p>Report for from $date1 to $date2 on sale batch #$ID:</p><p>(Averages are on a per day sales)</p>" . 
            '<table border="1" cellspacing="3" cellpadding="3">
            <tr><th>UPC</th><th>Description</th><th>Average Quantity Sold Before Batch</th><th>Average Quantity Sold During Batch</th></tr>';
        foreach ($batch AS $key => $value) {
            echo "<tr><td>$key</td><td>{$batch[$key]['Description']}</td><td>" . number_format($range[$key]['Quantity'] / $rangeDiff, 2) . "</td><td>" . number_format($batch[$key]['Quantity'] / $batchDiff, 2) . "</td></tr>";
        }
        echo '</table>';
    } else {
        echo "<p>Error...</p>";
    }

} else {
    mysqli_select_db ($db_slave, 'is4c_op');
    
    $query = "SELECT batchName, DATE(startDate), endDate, batchID
        FROM batches
        ORDER BY batchID DESC";
    $result = mysqli_query ($db_slave, $query);
    
    echo '<form action="salebatchReport.php" method="POST">
            <p>Compare sales of a sales batch to previous sales of the same items.</p>
            <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                        <td align="left"><b>Sales Batch</b></td>
                    </tr>
                    <tr>
                        <td align="center"><select name="batch">';
    if ($result) {
        while (list($batchName, $start, $end, $ID) = mysqli_fetch_row($result)) {
            echo "<option value=\"$ID\">$batchName ($start -> $end)</option>";
        }
    } else {
        echo "<p>Error...</p><p>Query: $query</p>" .  mysqli_error($db_slave);
    }
    echo '</select>
                    </tr>
                </table>
                <br /><p>What previous range to you want to compare it with?</p>
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
                        <td><input type=submit name=submit value="Submit"></td>
                        <input type="hidden" name="submitted" value="TRUE">
                    </tr>
            </table>
            </form>';
        
}

mysqli_close($db_slave);
require_once ('../includes/footer.html');
?>