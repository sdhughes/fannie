<?php

require_once ('../includes/mysqli_connect.php');
require_once ('../includes/dept_picker_generator.php');
mysqli_select_db($db_slave, 'is4c_log');



if (isset($_POST['submitted'])) {
    ?>
<html>
<head>
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/themes/blue/style.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/addons/pager/jquery.tablesorter.pager.css" />
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
<script type="text/javascript" src="../includes/javascript/jquery.tablesorter.pager.js"></script>
<script type="text/javascript" src="../includes/javascript/jquery.metadata.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $(".tablesorter").tablesorter({widthFixed: true, debug: false, widgets:['zebra']});
        $(".tablesorter tr").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});
    });
</script>
</head>
<body>
<?php
    $today = date("F d, Y");

    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];

    $reasons = (isset($_POST['reasons']) ? true : false);

    if (isset($_POST['dept']) && is_array($_POST['dept'])) {
        $deptArray = implode(", ", $_POST['dept']);
    } elseif (!isset($_POST['dept']) || !is_array($_POST['dept'])) {
	drawForm('<h2><font color="red">You must select a department.</font></h2>', $_POST);
	exit();
    }

    if (empty($date1) || empty($date2) || !checkdate(substr($date1, 5, 2), substr($date1, 8, 2), substr($date1, 0, 4)) || !checkdate(substr($date1, 5, 2), substr($date1, 8, 2), substr($date1, 0, 4))) {
	drawForm('<h2><font color="red">You must enter a valid date.</font></h2>', $_POST);
	exit();
    }

    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);
//2010-12-27 sdh - added emp_no and First name to query and shrink report

    $shrinkQ = sprintf("SELECT CASE WHEN s.UPC < 1000 THEN SUBSTR(s.UPC, 11, 3) WHEN s.UPC < 10000 THEN SUBSTR(s.UPC, 10, 4) ELSE s.UPC END AS UPC,
		       p.description, s.price, SUM(s.quantity), s.emp_no, e.FirstName, %s
	FROM is4c_log.shrinkLog AS s
	    INNER JOIN is4c_op.products AS p ON s.upc = p.upc
	    INNER JOIN is4c_log.shrinkReasons AS sr ON s.reason = sr.shrinkID
            INNER JOIN is4c_op.employees AS e ON e.emp_no = s.emp_no
	WHERE s.department IN (%s)
	    AND DATE(datetime) BETWEEN '%s' AND '%s'
	    AND s.emp_no <> 9999
	GROUP BY s.UPC %s", ($reasons ? 'sr.shrinkReason AS reason' : 'NULL'), $deptArray, $date1, $date2, ($reasons ? ', reason' : NULL));

/*
//this query has 1 less INNER JOIN with the employee tables

$shrinkQ = sprintf("SELECT CASE WHEN s.UPC < 1000 THEN SUBSTR(s.UPC, 11, 3) WHEN s.UPC < 10000 THEN SUBSTR(s.UPC, 10, 4) ELSE s.UPC END AS UPC,
		       p.description, s.price, SUM(s.quantity), s.emp_no, %s
	FROM is4c_log.shrinkLog AS s
	    INNER JOIN is4c_op.products AS p ON s.upc = p.upc
	    INNER JOIN is4c_log.shrinkReasons AS sr ON s.reason = sr.shrinkID
	WHERE s.department IN (%s)
	    AND DATE(datetime) BETWEEN '%s' AND '%s'
	    AND s.emp_no <> 9999
	GROUP BY s.UPC %s", ($reasons ? 'sr.shrinkReason AS reason' : 'NULL'), $deptArray, $date1, $date2, ($reasons ? ', reason' : NULL));
*/	

    $shrinkR = mysqli_query($db_slave, $shrinkQ);

    if (!$shrinkR) {
	drawForm(sprintf('Error: %s, Query: %s', mysqli_error($db_slave), $shrinkQ), $_POST);
	exit();
    } elseif (mysqli_num_rows($shrinkR) == 0) {
	drawForm('<h2>Your report generated no results. Please try again.</h2>', $_POST);
	exit();
    } else {
	echo "Report run on: $today<br />
	    From $date1 to $date2<br />
	    Department range: $deptArray<br /><br />";

	printf('<table class="tablesorter" cellspacing="3" border="1">
	       <thead>
		    <tr>
			<th>UPC</th>
			<th>Description</th>
			<th>Price</th>
			<th>Total Value</th>
			<th>Total Quantity</th>
			<th>Who Shrank?</th>
			%s
		    </tr>
		</thead><tbody>', ($reasons ? '<th>Shrink Reason</th>' : NULL));
	$shrinkTotal = 0;
	$shrinkCount = 0;
	
	while (list($upc, $description, $price, $quantity,$emp_no, $who, $reason) = mysqli_fetch_row($shrinkR)) {
	//while (list($upc, $description, $price, $quantity,$emp_no, $reason) = mysqli_fetch_row($shrinkR)) {
	    printf('<tr><td>%s</td><td>%s</td><td>$%s</td><td>$%s</td><td>%s</td><td>%s</td>%s</tr>',
		   $upc, $description, number_format($price, 2), number_format($price * $quantity, 2), $quantity,$who, ($reasons ? '<td>' . $reason . '</td>' : NULL));
	    $shrinkTotal += ($price * $quantity);
	    $shrinkCount += $quantity;
	}
	printf('<thead><tr><th colspan="3">Total Shrink</th><th>$%s</th><th>%s items</th></tr></thead></tbody></table>', $shrinkTotal, $shrinkCount);
    }
    echo '</body></html>';

} else { // Show the form.
    drawForm();
}

function drawForm($msg = NULL, $_POST = NULL) {
    global $db_slave;
    $page_title = 'Fannie - Reports Module';
    $header = 'Department Shrink Report';
    include ('../includes/header.html');
    echo
    <<<EOS
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd', changeYear: true,changeMonth:true, duration: 0 });
        });

	$(document).ready(function() {
	    $('#selectAll').click(function() {
		if ($(this).text() == 'All Departments') {
		    $('.deptCheck').attr('checked', true);
		    $(this).text('Clear Selections');
		} else {
		    $('.deptCheck').attr('checked', false);
		    $(this).text('All Departments');
		}
	    });

	    $('.deptCheck').click(function() {
		$('#selectAll').text('Clear Selections');
	    });
	});

    </script>
EOS;
    printf('<div align="center" id="box">
	   %s
	    <h3><strong>Select Department</strong></h3>
	    <button name="selectAll" id="selectAll" type="button">All Departments</button>
            <form method="post" action="shrinkReport.php" target="_blank">
	    <div align="left">',$msg);
dept_picker('dept_tile');
printf ('</div>  
        </div>
        <div id="box">
            <table border="0" cellspacing="3" cellpadding="3">
                <tr>
                    <td align="right">
                        <p><b>Date Start</b> </p>
                        <p><b>End</b></p>
                    </td>
                    <td>
                        <p><input type="text" size="10" autocomplete="off" name="date1" value="%s" class="datepick">&nbsp;&nbsp;*</p>
                        <p><input type="text" size="10" autocomplete="off" name="date2" value="%s" class="datepick">&nbsp;&nbsp;*</p>
                    </td>
                    <td colspan=2>
                        <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                    </td>
		</tr>
		<tr>
		    <td colspan="2" align="center">
			<p><input type="checkbox" name="reasons" />Separate By Shrink Reason?</p>
		    </td>
		</tr>
		<tr align="center">
		    <td><input type="submit" name="submit" value="Submit"></td>
                    <input type="hidden" name="submitted" value="TRUE">
                </tr>
            </table>
        </div>
    </form>', (isset($_POST['date1']) ? $_POST['date1'] : ''), (isset($_POST['date2']) ? $_POST['date2'] : ''));
  include('../includes/footer.html');
}

?>
