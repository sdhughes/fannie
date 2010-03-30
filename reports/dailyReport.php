<?php
ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

if (isset($_POST['submitted'])) {
    require_once('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, 'is4c_log');
    
    // Date validation...
    if (isset($_POST['date'])) {
        if (checkdate(
                      date('m', strtotime($_POST['date'])),
                      date('d', strtotime($_POST['date'])),
                      date('Y', strtotime($_POST['date']))
                      )
            )
            $date = escape_data($_POST['date']);
        else {
            drawForm('<font color="red"><p>Please pick a valid date</p></font>');
            exit();
        }
    }
    
    // Set up some variables...
    $day = date('d', strtotime($date));
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    
    $numDays = cal_days_in_month(0, $month, $year);
    $percentMonth = $day / $numDays;
    
    // Make the budget query...
    $budgetQ = sprintf("SELECT dept_name, amount, dept_nos, deptID
        FROM is4c_op.budgetNames AS bn
            INNER JOIN is4c_op.budgetDetails AS bd ON bn.id=bd.deptID
        WHERE period='%u-%u-01'", $year, $month);
    $budgetR = mysqli_query($db_slave, $budgetQ);
    
    if (!$budgetR) {
        // If the query fails, kill the script...
        $msg = sprintf('Query: %s, Error: %s', $budgetQ, mysqli_error($db_slave));
        drawForm($msg);
        exit();
    }
    
    while (list($name, $budget, $deptArray, $deptID) = mysqli_fetch_row($budgetR)) {
        // Divide monthly budgets into # of days in month...
        // Get sales totals for each department, compare to budgeted amount
        $deptBudget[$deptID]['budget'] = $budget;
        $deptBudget[$deptID]['name'] = $name;
        
        
        if ($date == date('Y-m-d')) {
            // If it's today, we need to inner join dtransactions...
            $deptQ = "SELECT SUM(total) FROM (";
            $deptQ .= sprintf("SELECT total
                             FROM trans_%u
                             WHERE department IN (%s)
                             AND trans_status <> 'X'
                             AND trans_subtype <> 'MC'
                             AND emp_no <> 9999
                             AND DATE(datetime) BETWEEN '%u-%u-01' AND '%s'",
                             $year, $deptArray, $year, $month, $date);
            
            $deptQ .= " UNION ALL ";
            
            $deptQ .= sprintf("SELECT total
                             FROM dtransactions
                             WHERE department IN (%s)
                             AND trans_status <> 'X'
                             AND trans_subtype <> 'MC'
                             AND emp_no <> 9999
                             AND DATE(datetime) BETWEEN '%u-%u-01' AND '%s'",
                             $deptArray, $year, $month, $date);
            
            $deptQ .= ") AS yearSpan";
            
        } else {
            $deptQ = sprintf("SELECT SUM(total) AS total
                             FROM trans_%u
                             WHERE department IN (%s)
                             AND trans_status <> 'X'
                             AND trans_subtype <> 'MC'
                             AND emp_no <> 9999
                             AND DATE(datetime) BETWEEN '%u-%u-01' AND '%s'",
                             $year, $deptArray, $year, $month, $date);
        }
        
        $deptR = mysqli_query($db_slave, $deptQ);
        
        if (!$deptR) {
            // If the query fails, kill the script...
            $msg = sprintf('Query: %s, Error: %s', $deptR, mysqli_error($db_slave));
            drawForm($msg);
            exit();
        }
        
        list($total) = mysqli_fetch_row($deptR);
        $deptBudget[$deptID]['sales'] = $total;
        
    }
    
        
    printf('<table cellspacing="2" cellpadding="2" border=".5">');
    printf('<tr><th colspan="4"><div style="text-align:center;"><h3>Department Sales vs Budget<br /> for %s as of %s (%s%% of month)</h3></div></tr>', date('F', strtotime($date)), $date, number_format($percentMonth * 100, 0));
    printf('<tr>
                <th>%s</th>
                <th>%s</th>
                <th>%s</th>
                <th>%s</th>
            </tr>',
            'Department', 'Sales', 'Budget', '% of Budget');
    
    $store['budget'] = 0;
    $store['sales'] = 0;
    
    foreach ($deptBudget AS $deptNo => $info) {
        printf('<tr><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s%%</td></tr>',
               $info['name'],
               number_format($info['sales'], 2),
               number_format($info['budget'], 2),
               number_format($info['sales']/$info['budget'] * 100, 2));
        
        $store['budget'] += $info['budget'];
        $store['sales'] += $info['sales'];
    }
    
    printf('<tr><th align="left">%s</th><th>%s</th><th>%s</th><th>%s%%</th></tr>',
               'Store',
               number_format($store['sales'], 2),
               number_format($store['budget'], 2),
               number_format($store['sales']/$store['budget'] * 100, 2));
    
    echo '</table>';
        
} else {
    drawForm();
}

// Draw form with datepicker...
function drawForm($msg = NULL, $_POST = NULL) {
        $page_title = 'Fannie - Reports Module';
        $header = 'Daily Report';
        include('../includes/header.html');
        ?>
	<link href="../style.css" rel="stylesheet" type="text/css">
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
	<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
	<script type="text/javascript" src="../includes/javascript/jquery.js"></script>
	<script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
	<script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
	<script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
	<script type="text/javascript">
		Date.format = 'yyyy-mm-dd';
		$(function(){
		    $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd'});
		    $('.datepick').focus();
		});
        </script>
        </head>
        <body>

        <form action="<?php echo $_SERVER['PHP_SELF'];?>" name="datelist" method="post" target="_blank">
		<?php echo $msg; ?>
            <p>Pick a date to run that day's daily report</p>
            <input type="text" size="10" name="date" class="datepick" autocomplete="off" />
            <br />

            <input type="hidden" name="submitted" value="TRUE" />
            <br />
            <input name="Submit" type="submit" value="submit" />
        </form>
        <?php

        include('../includes/footer.html');
}

?>