<html>
    <head>
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/themes/blue/style.css" />
        <style rel="STYLESHEET" type="text/css">
            tr.alt td {
                background: #ecf6fc !important;
            }
            tr.over td {
                background: #bcd4ec !important;
            }
        </style>
        <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
        <script type="text/javascript" src="../includes/javascript/jquery.tablesorter.js"></script>
        <script type="text/javascript" src="../includes/javascript/jquery.metadata.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                $(".tablesorter")
                    .tablesorter({widthFixed: true, debug: false, widgets:['zebra']});
                $(".tablesorter tr").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});
            });
        </script>
    </head>
    <body>
    <?php
        
        require_once ('../includes/mysqli_connect.php');
        mysqli_select_db($db_slave, 'is4c_op');
        $months = array('01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June',
                        '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',);
        $year = date('Y');
        ?>
        <center><h3>Hours By Month</h3></center>
        <table class="tablesorter" border="1">
            <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
        <?php
        
        foreach ($months AS $month => $monthname) {
            echo "<th>$monthname</th>";
            $totalHours[$month] = 0;
        }
        echo '<th>Total Hours Worked</th>
            </tr></thead><tbody>';
        
        foreach ($months AS $month => $monthname) {
            $query = "SELECT w.id, c.firstname, c.lastname, SUM(hours)
                FROM wmHoursLog AS w
                    INNER JOIN custdata AS c
                    ON (w.id = c.id)
                WHERE w.datetime LIKE '$year-$month-%'
                GROUP BY w.id";
            $result = mysqli_query($db_slave, $query);
            while (list($id, $first, $last, $sum) = mysqli_fetch_array($result)) {
                $WM[$id]['first'] = $first;
                $WM[$id]['last'] = $last;
                $WM[$id][$month] = $sum;
                if (!isset($WM[$id]['total'])) $WM[$id]['total'] = 0.00;
                $WM[$id]['total'] += $sum;
                $totalHours[$month] += $sum;
            }
        }
        
        foreach ($WM AS $id => $ID) {
            foreach ($months AS $month => $monthname) {
                if (!isset($ID[$month])) {
                    $ID[$month]='0';
                }
            }
            
            printf('<tr>
                <td align="center">%s</td><td align="center">%s</td>
                <td align="center">%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>
                <td align="center">%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td>
                <td align="center">%s</td>
                </tr>', $ID['first'], $ID['last'],
                    number_format($ID['01'], 2), number_format($ID['02'], 2), number_format($ID['03'], 2),
                    number_format($ID['04'], 2), number_format($ID['05'], 2), number_format($ID['06'], 2),
                    number_format($ID['07'], 2), number_format($ID['08'], 2), number_format($ID['09'], 2),
                    number_format($ID['10'], 2), number_format($ID['11'], 2), number_format($ID['12'], 2),
                    number_format($ID['total'], 2)
                );
        }
        echo '</tbody>
            <tfoot>
                <tr style="font-weight:bold;"><td align="center" colspan="2">Totals</td>';
        $totalHours['total'] = 0;
        foreach ($months AS $month => $monthname) {
            printf('<td align="center">%s</td>', number_format($totalHours[$month], 2));
            $totalHours['total'] += $totalHours[$month];
        }
        echo '<td align="center">' . number_format($totalHours['total'], 2) . '</td></tr><tr style="font-weight:bold;"><td align="center" colspan="2">Total Discount Used</td>';
	
	$totalSpent = 0.00;
	
	foreach ($months AS $month => $monthname) {
	    $totalSpendingQ = "SELECT -1 * SUM(total) FROM is4c_log.trans_$year
		WHERE MONTH(datetime) = '$month'
		    AND upc = 'DISCOUNT'
		    AND emp_no <> 9999
		    AND trans_status <> 'X'
		    AND staff IN (3, 6)";
	    
	    $totalSpendingR = mysqli_query($db_slave, $totalSpendingQ);
	    
	    if ($totalSpendingR) {
		list($total) = mysqli_fetch_row($totalSpendingR);
		printf('<td align="center">$%s</td>', (is_null($total) ? number_format(0.00, 2) : number_format($total,2)));
		$totalSpent += $total;
	    } else {
		printf("Query: %s<br />MySQL Error: %s", $totalSpendingQ, mysqli_error($db_slave));
	    }
	}
	
	echo '<td align="center">$' . number_format($totalSpent, 2) . "</td>" . 
	    '</tr></tfoot>
            </table>';
          
    ?>
    </body>
</html>