<?php

require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

if (isset($_POST['submitted'])) {
    
    $today = date("F d, Y");

    $date1 = (isset($_POST['date1']) ? $_POST['date1'] : NULL);
    $date2 = (isset($_POST['date2']) ? $_POST['date2'] : NULL);

    if (isset($_POST['dept']) && is_array($_POST['dept'])) {
	asort($_POST['dept']);
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

?>
<script type="text/javascript">
function popup(mylink, windowname) {
    if (! window.focus)return true;
    var href;
    if (typeof(mylink) == 'string')
        href=mylink;
    else
        href=mylink.href;
    window.open(href, windowname, 'width=500,height=300,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes');
    return false;
}
</script>
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
    // Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
    //decide what the sort index is and translate from lay person to mySQL table label
    $sort = $_POST['sort'];
    
    $report = NULL;
    
    $report .= sprintf("Report sorted by %s on<br />
        %s<br />
        From %s to %s<br />
        Department range: %s<br /><br />", $sort, $today, $date1, $date2, $deptArray);
    

    if ($sort == 'Department') {
        $order = "Dept";
    } elseif($sort == 'PLU') {
        $order = "PLU";
    } elseif($sort == 'Qty') {
        $order = 'Qty';
    } elseif($sort == 'Sales') {
        $order = 'Total';
    } elseif($sort == 'Subdepartment') {
        $order = 'Subdept';
    }

    if (isset($_POST['inUse'])) {
        $inUse = "AND p.inUse = 1";
    } else {
        $inUse = "AND p.inUse IN (0,1)";
    }

    // Gross sales report by department...
    $salesQ = "SELECT dept, dept_no, SUM(total) FROM (";
    for ($i = $year1; $i <= $year2; $i++) {
	$salesQ .= "SELECT d.dept_name AS dept, d.dept_no AS dept_no, ROUND(SUM(t.total),2) AS total
	    FROM is4c_op.departments AS d, is4c_log.trans_$i AS t
	    WHERE d.dept_no = t.department
		AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
		AND t.department IN ($deptArray)
		AND t.trans_status <> 'X'
		AND t.emp_no <> 9999
	    GROUP BY dept";

	if ($i == $year2) {
	    if ($date2 == date('Y-m-d')) {
		$salesQ .= " UNION ALL SELECT d.dept_name AS dept, d.dept_no AS dept_no, ROUND(SUM(t.total),2) AS total
		    FROM is4c_op.departments AS d, is4c_log.dtransactions AS t
		    WHERE d.dept_no = t.department
			AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
			AND t.department IN ($deptArray)
			AND t.trans_status <> 'X'
			AND t.emp_no <> 9999
		    GROUP BY dept";
	    }

	    $salesQ .= ") AS yearSpan GROUP BY dept";

	} else $salesQ .= " UNION ALL ";

    }
    
    $salesR = mysqli_query($db_slave, $salesQ);
    
    // If the query fails, draw the form, print an error.
    if (!$salesR) {
	$message  = sprintf('Invalid query: %s' . "\n" . 'Whole query: %s', mysqli_error($db_slave), $salesQ);
	drawForm($message, $_POST);
	exit();
    }
    
    $reportData = array();
    
    // Load the data into a large array for later.
    while (list($dept, $dept_no, $total) = mysqli_fetch_row($salesR)) { //create array from query
	$reportData[$dept_no]['sales'] = $total;
	$reportData[$dept_no]['name'] = $dept;
    }
    
    // Now get open ring summaries for all selected departments...
    $openQ = "SELECT dept, total, dept_no FROM (";
    for ($i = $year1; $i <= $year2; $i++) {
	$openQ .= "SELECT d.dept_name AS dept, ROUND(SUM(t.total),2) AS total, d.dept_no AS dept_no
	    FROM is4c_op.departments AS d,is4c_log.trans_$i AS t
	    WHERE DATE(t.datetime) BETWEEN '$date1' AND '$date2'
		AND t.trans_status <> 'X'
		AND t.trans_type = 'D'
		AND t.emp_no <> 9999
		AND t.department IN ($deptArray)
		AND d.dept_no = t.department
	    GROUP BY dept";

	if ($i == $year2) {
	    if ($date2 == date('Y-m-d')) {
		$openQ .= " UNION ALL SELECT d.dept_name AS dept, ROUND(SUM(t.total),2) AS total, d.dept_no AS dept_no
		    FROM is4c_op.departments AS d,is4c_log.dtransactions AS t
		    WHERE DATE(t.datetime) BETWEEN '$date1' AND '$date2'
			AND t.trans_status <> 'X'
			AND t.trans_type = 'D'
			AND t.emp_no <> 9999
			AND t.department IN ($deptArray)
			AND d.dept_no = t.department
		    GROUP BY dept";
	    }

	    $openQ .= ") AS yearSpan";

	} else $openQ .= " UNION ALL ";
    }



    $openR = mysqli_query($db_slave, $openQ);
    
    // If the query failed, reload the form with an error.
    if (!$openR) {
	$message  = sprintf('Invalid query: %s' . "\n" . 'Whole query: %s', mysqli_error($db_slave), $openQ);
	drawForm($message, $_POST);
	exit();
    }
    
    // Load the data into an array.
    while (list($dept, $total, $dept_no) = mysqli_fetch_row($openR)) { //create array from query
	$reportData[$dept_no]['open'] = $total;
	$reportData[$dept_no]['name'] = $dept;
    }
    
    // Get reported shrink totals for the selected departments.
    $shrinkQ = "SELECT d.dept_name AS dept, ROUND(SUM(s.quantity * s.price), 2) AS total, d.dept_no AS dept_no
	FROM is4c_op.departments AS d, is4c_log.shrinkLog AS s
	WHERE DATE(s.datetime) BETWEEN '$date1' AND '$date2'
	    AND s.emp_no <> 9999
	    AND s.department IN ($deptArray)
	    AND d.dept_no = s.department
	GROUP BY dept";

    $shrinkR = mysqli_query($db_slave, $shrinkQ);
    
    // If the query fails, reload the form and show an error.
    if (!$shrinkR) {
	$message  = sprintf('Invalid query: %s' . "\n" . 'Whole query: %s', mysqli_error($db_slave), $shrinkQ);
	drawForm($message, $_POST);
	exit();
    }
    
    // Load the shrink data into an array.
    while (list($dept, $total, $dept_no) = mysqli_fetch_row($shrinkR)) { //create array from query
	$reportData[$dept_no]['shrink'] = $total;
	$reportData[$dept_no]['name'] = $dept;

    }
    
    $report .= sprintf('<table cellspacing="2" cellpadding="3"><tr><th>Department Name</th><th>Gross Sales</th><th>Open Rings</th><th>Shrink</th></tr>');
    
    foreach ($reportData AS $dept_no => $data) {
	$report .= sprintf('<tr>
				<td>%s</td>
				<td align="center">$%s</td>
				<td align="center">$%s&nbsp;<a href="openRingDetail.php?date1=%s&date2=%s&dept=%u" onClick="return popup(this, \'openRingDetail\')">(Detail)</a></td>
				<td align="center">$%s</td>
			    </tr>' . "\n",
			   $data['name'],
			   number_format(isset($data['sales']) ? $data['sales'] : 0.00, 2),
			   number_format(isset($data['open']) ? $data['open'] : 0.00, 2),
			   $date1, $date2, $dept_no,
			   number_format(isset($data['shrink']) ? $data['shrink'] : 0.00, 2));
    }
    
/*	SELECT DISTINCT p.upc AS PLU, p.description AS Description, ROUND(p.normal_price,2) AS 'Current Price', ROUND(t.unitPrice,2) AS Price, p.department AS Dept, p.subdept AS Subdept, SUM(t.quantity) AS Qty, ROUND(SUM(t.total),2) AS Total, p.scale as Scale FROM is4c_log.dtransactions t, is4c_op.products p WHERE t.upc = p.upc AND t.department IN(8) AND t.datetime >= '2007-08-06 00:00:00' AND t.datetime <= '2007-08-13 23:59:59' AND t.emp_no <> 9999 AND t.trans_status <> 'X' AND t.upc NOT LIKE '%DP%' AND p.inUse = 1 GROUP BY CONCAT(t.upc, '-',t.unitprice) ORDER BY t.upc */
    if (isset($_POST['deptDetails'])) {
	if ($year1 == $year2 && $date2 != date('Y-m-d')) {
	    $detailedQ = "SELECT DISTINCT
		    p.upc AS PLU,
		    p.description AS Description,
		    ROUND(p.normal_price,2) AS 'Current Price',
		    ROUND(t.unitPrice,2) AS Price,
		    d.dept_name AS Dept,
		    s.subdept_name AS Subdept,
		    SUM(t.quantity) AS Qty,
		    ROUND(SUM(t.total),2) AS Total,
		    p.scale as Scale
		    FROM is4c_log.trans_$year1 t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
		WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
		    AND t.department IN ($deptArray)
		    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
		    AND t.emp_no <> 9999
		    AND t.trans_status <> 'X'
		    AND t.upc NOT LIKE '%DP%'
		    $inUse
		GROUP BY CONCAT(t.upc, '-',t.unitprice)
		ORDER BY $order";
	} else {
	    $detailedQ = "SELECT PLU, Description, CurrPrice, Price, Dept, Subdept, SUM(Qty), SUM(Total), Scale FROM (";
	    for ($i = $year1; $i <= $year2; $i++) {
		$detailedQ .= "SELECT DISTINCT
			p.upc AS PLU,
			p.description AS Description,
			ROUND(p.normal_price,2) AS CurrPrice,
			ROUND(t.unitPrice,2) AS Price,
			d.dept_name AS Dept,
			s.subdept_name AS Subdept,
			SUM(t.quantity) AS Qty,
			ROUND(SUM(t.total),2) AS Total,
			p.scale as Scale
			FROM is4c_log.trans_$i t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
		    WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
			AND t.department IN ($deptArray)
			AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
			AND t.emp_no <> 9999
			AND t.trans_status <> 'X'
			AND t.upc NOT LIKE '%DP%'
			$inUse
		    GROUP BY CONCAT(t.upc, '-',t.unitprice)";

		if ($i == $year2) {
		    if ($date2 == date('Y-m-d')) {
			$detailedQ .= "UNION ALL SELECT DISTINCT
				p.upc AS PLU,
				p.description AS Description,
				ROUND(p.normal_price,2) AS 'Current Price',
				ROUND(t.unitPrice,2) AS Price,
				d.dept_name AS Dept,
				s.subdept_name AS Subdept,
				SUM(t.quantity) AS Qty,
				ROUND(SUM(t.total),2) AS Total,
				p.scale as Scale
				FROM is4c_log.dtransactions t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
			    WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
				AND t.department IN ($deptArray)
				AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
				AND t.emp_no <> 9999
				AND t.trans_status <> 'X'
				AND t.upc NOT LIKE '%DP%'
				$inUse
			    GROUP BY CONCAT(t.upc, '-',t.unitprice)";
		    }
		    
		    $detailedQ .= ") AS yearSpan GROUP BY CONCAT(PLU, Price) ORDER BY $order";
		} else $detailedQ .= " UNION ALL ";
	    }
	}

	$scaleRow = 8;
	$table_header = '<thead>
		<tr>
		    <th>UPC</th>
		    <th>Description</th>
		    <th>Current Price</th>
		    <th>Price Sold At</th>
		    <th>Department</th>
		    <th>Subdepartment</th>
		    <th>Qty</th>
		    <th>Sales</th>
		    <th>Scale</th>
		</tr>
	    </thead>';
	$table_footer = '<tfoot>
		<tr>
		    <th>UPC</th>
		    <th>Description</th>
		    <th>Current Price</th>
		    <th>Price Sold At</th>
		    <th>Department</th>
		    <th>Subdepartment</th>
		    <th>Qty</th>
		    <th>Sales</th>
		    <th>Scale</th>
		</tr>
	    </tfoot><tbody>';

    } elseif (!isset($_POST['deptDetails'])) {

	if ($year1 == $year2 && substr($date2a, 0, 10) != date('Y-m-d')) {

	    $detailedQ = "SELECT DISTINCT
		    p.upc AS PLU,
		    p.description AS Description,
		    ROUND(p.normal_price,2) AS 'Current Price',
		    ROUND(t.unitPrice,2) AS Price,
		    ROUND(SUM(t.quantity),2) AS Qty,
		    ROUND(SUM(t.total),2) AS Total,
		    p.scale as Scale
		FROM is4c_log.trans_$year1 t, is4c_op.products p
		WHERE t.upc = p.upc
		    AND t.department IN ($deptArray)
		    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
		    AND t.emp_no <> 9999
		    AND t.trans_status <> 'X'
		    AND t.upc NOT LIKE '%DP%'
		    $inUse
		GROUP BY CONCAT(t.upc, '-',t.unitprice)";

	} else {
	    $detailedQ = "SELECT PLU, Description, CurrPrice, Price, SUM(Qty), SUM(Total), Scale FROM (";
	    for ($i = $year1; $i <= $year2; $i++) {
		$detailedQ .= "SELECT DISTINCT
			p.upc AS PLU,
			p.description AS Description,
			ROUND(p.normal_price,2) AS CurrPrice,
			ROUND(t.unitPrice,2) AS Price,
			ROUND(SUM(t.quantity),2) AS Qty,
			ROUND(SUM(t.total),2) AS Total,
			p.scale as Scale
		    FROM is4c_log.trans_$i t, is4c_op.products p
		    WHERE t.upc = p.upc
			AND t.department IN ($deptArray)
			AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
			AND t.emp_no <> 9999
			AND t.trans_status <> 'X'
			AND t.upc NOT LIKE '%DP%'
			$inUse
		    GROUP BY CONCAT(t.upc, '-',t.unitprice)";

		if ($i == $year2) {
		    if ($date2 == date('Y-m-d')) {
			$detailedQ .= " UNION ALL SELECT DISTINCT
			    p.upc AS PLU,
			    p.description AS Description,
			    ROUND(p.normal_price,2) AS 'Current Price',
			    ROUND(t.unitPrice,2) AS Price,
			    ROUND(SUM(t.quantity),2) AS Qty,
			    ROUND(SUM(t.total),2) AS Total,
			    p.scale as Scale
			FROM is4c_log.trans_$i t, is4c_op.products p
			WHERE t.upc = p.upc
			    AND t.department IN ($deptArray)
			    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
			    AND t.emp_no <> 9999
			    AND t.trans_status <> 'X'
			    AND t.upc NOT LIKE '%DP%'
			    $inUse
			GROUP BY CONCAT(t.upc, '-',t.unitprice)";
		    }

		    $detailedQ .= ") AS yearSpan GROUP BY CONCAT(PLU, Price) ORDER BY $order";
		} else $detailedQ .= " UNION ALL ";
	    }
	}

	$scaleRow = 6;
	$table_header = '<thead>
		<tr>
		    <th>UPC</th>
		    <th>Description</th>
		    <th>Current Price</th>
		    <th>Price Sold At</th>
		    <th>Qty</th>
		    <th>Sales</th>
		    <th>Scale</th>
		</tr>
	    </thead>';
	$table_footer = '<tfoot><tr>
		    <th>UPC</th>
		    <th>Description</th>
		    <th>Current Price</th>
		    <th>Price Sold At</th>
		    <th>Qty</th>
		    <th>Sales</th>
		    <th>Scale</th>
		</tr>
	    </tfoot><tbody>';
    }

    $detailedR = mysqli_query($db_slave, $detailedQ);

    $detailTable = '<table border="1" cellpadding="3" cellspacing="3" class="tablesorter">';
    $detailTable .= $table_header;

    if (!$detailedR) {
	$message = sprintf('Invalid query: %s' . "\nWhole query: %s", mysqli_error($db_slave), $detailedQ);
	drawForm($message, $_POST);
	exit();
    }

    while ($myrow = mysqli_fetch_row($detailedR)) { //create array from query
	if ($myrow[$scaleRow] == 0) {$myrow[$scaleRow] = 'No';} elseif ($myrow[$scaleRow] == 1) {$myrow[$scaleRow] = 'Yes';}
	$detailTable .= sprintf('<tr><td><a href="/item/itemMaint.php?submitted=search&upc=%s">%s</a></td><td>%s</th><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
	       $myrow[0], $myrow[0], $myrow[1], $myrow[2], $myrow[3], $myrow[4], $myrow[5], number_format($myrow[6],2), $myrow[7], $myrow[8]);
    }
    
    $detailTable .= $table_footer;

    $detailTable .= "</tbody></table><br /><br />\n";
    
    $report .= $detailTable;
    
    echo $report;

} else { // Show the form.
    drawForm();
}

function drawForm($msg = NULL, $_POST = NULL) {
    global $db_slave;
    $page_title = 'Fannie - Reports Module';
    $header = 'Department Movement Report';
    include ('../includes/header.html');
    echo
    <<<EOS
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
            <form method="post" target="_blank" action="%s">
	    <div align="center">
		<table border="0" cellspacing="3" cellpadding="5">', $msg, $_SERVER['PHP_SELF']);
    $deptQ = "SELECT dept_name, dept_no FROM is4c_op.departments WHERE dept_no <= 18 AND dept_no NOT IN (13, 15, 16, 17) OR dept_no = 40 ORDER BY dept_name ASC";
    $deptR = mysqli_query($db_slave, $deptQ);

    $count = 0;

    while (list($name, $no) = mysqli_fetch_row($deptR)) {
	if ($count % 3 == 0) echo '<tr>';
	$count++;
	printf('<td><input type="checkbox" name="dept[]" class="deptCheck" value="%u" %s />%s</td>', $no, (isset($_POST['dept']) && in_array($no, $_POST['dept']) ? 'checked="checked"' : ''), ucfirst(strtolower($name)));

	if ($count % 3 == 0) echo '</tr>';
    }

    printf('</table>
	    </div>
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
		    <td colspan="2" align="left"><input type="checkbox" name="inUse" checked="CHECKED" /><strong>Filter not "in use"</strong></td>
		    <td colspan="2" align="left"><input type="checkbox" name="deptDetails" checked="CHECKED" /><strong>Include department details</strong></td>
		</tr>
                <tr>
		    <td colspan="4" align="center"><strong>Sort by: &nbsp;</strong>
                            <select name="sort">
                                <option name="PLU" selected="selected">PLU</option>
                                <option name="Qty">Qty</option>
                                <option name="Sales">Sales</option>
                                <option name="Department">Department</option>
                                <option name="Subdepartment">Subdepartment</option>
                            </select>
		    </td>
		</tr>
		<tr align="center">
		    <td colspan="4" align="center"><input type="submit" name="submit" value="Submit"></td>
                    <input type="hidden" name="submitted" value="TRUE">
                </tr>
            </table>
        </div>
    </form>', (isset($_POST['date1']) ? $_POST['date1'] : ''), (isset($_POST['date2']) ? $_POST['date2'] : ''));
  include('../includes/footer.html');
}

?>
