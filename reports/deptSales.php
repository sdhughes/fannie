<?php

require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

if (isset($_POST['submitted'])) {
    
    $today = date("F d, Y");

    $date1 = (isset($_POST['date1']) ? $_POST['date1'] : NULL);
    $date2 = (isset($_POST['date2']) ? $_POST['date2'] : NULL);

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
            .tablesorter({widthFixed: true, debug: false, widgets:['zebra']})
            .tablesorterPager({container: $("#pager")});
        $(".tablesorter tr").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});
    });
</script>

</head>
<body>
<?php
    // Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
    //decide what the sort index is and translate from lay person to mySQL table label
    $sort = $_POST['sort'];
    
    echo "Report sorted by $sort on<br />
        $today<br />
        From $date1 to $date2<br />
        Department range: $deptArray<br /><br />";
    

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


    if (isset($_POST['salesTotal'])) {
        $salesQ = "SELECT dept, SUM(total) FROM (";
        for ($i = $year1; $i <= $year2; $i++) {
            $salesQ .= "SELECT d.dept_name AS dept, ROUND(SUM(t.total),2) AS total
                FROM is4c_op.departments AS d, is4c_log.trans_$i AS t
                WHERE d.dept_no = t.department
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.department IN ($deptArray)
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999
                GROUP BY dept";

            if ($i == $year2) {
                if ($date2 == date('Y-m-d')) {
                    $salesQ .= " UNION ALL SELECT d.dept_name AS dept, ROUND(SUM(t.total),2) AS total
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
        echo "<table>\n"; //create table
        echo "<tr><td>";
        echo "<b>Department</b></td><td>";
        echo "<b>Total Sales</b></td></tr>";

        if (!$salesR) {
            $message  = 'Invalid query: ' . mysqli_error($db_slave) . "\n";
            $message .= 'Whole query: ' . $salesQ;
            die($message);
        }

        while ($myrow = mysqli_fetch_row($salesR)) { //create array from query
	    printf("<tr><td>%s</td><td>%s</td></tr>\n",$myrow[0], $myrow[1]);
	    //convert row information to strings, enter in table cells
	}

	echo "</table>\n";//end table

    }

    if (isset($openRing)) {
        //$query2 - Total open dept. ring
        $query2 = "SELECT * FROM (";
        for ($i = $year1; $i <= $year2; $i++) {
            $query2 .= "SELECT d.dept_name AS Department,ROUND(SUM(t.total),2) AS open_dept, d.dept_no AS Dept_No
                FROM is4c_op.departments AS d,is4c_log.trans_$i AS t
                WHERE t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                    AND t.trans_status <> 'X'
                    AND t.trans_type = 'D'
                    AND t.emp_no <> 9999
                    AND t.department IN ($deptArray)
                    AND d.dept_no = t.department
                GROUP BY t.department";

            if ($i == $year2) {
                if (substr($date2a, 0, 10) == date('Y-m-d')) {
                    $query2 .= " UNION ALL SELECT d.dept_name AS Department,ROUND(SUM(t.total),2) AS open_dept, d.dept_no AS Dept_No
                        FROM is4c_op.departments AS d,is4c_log.dtransactions AS t
                        WHERE t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.trans_status <> 'X'
                            AND t.trans_type = 'D'
                            AND t.emp_no <> 9999
                            AND t.department IN ($deptArray)
                            AND d.dept_no = t.department
                        GROUP BY t.department";
                }

                $query2 .= ") AS yearSpan";

            } else $query2 .= " UNION ALL ";
        }



        $result2 = mysqli_query($db_slave, $query2);
                //echo $query;
        echo "<table>\n"; //create table
        echo "<tr><td>";
        echo "<b>Department</b></td><td>";
        echo "<b>Open Ring</b></td></tr>";


        if (!$result2) {
            $message  = 'Invalid query: ' . mysqli_error($db_slave) . "\n";
            $message .= 'Whole query: ' . $query2;
            die($message);
        }


        while ($myrow = mysqli_fetch_row($result2)) { //create array from query
            $dept = $myrow[2];
            echo "<tr>
                <td>{$myrow[0]}</td>
                <td>{$myrow[1]}&nbsp;
                    <a href=" . '"openRingDetail.php?date1=' . $date1 . '&date2=' . $date2 . '&dept=' . $dept . '" onClick="return popup(this, \'openRingDetail\')">(Detail)</a></td>
                </tr>' . "\n";
            //convert row information to strings, enter in table cells

        }
        echo "</table><br />\n";//end table
        // end of $query2


    }

    if (isset($pluReport)) {
	// $query3 - Sales per PLU
/*	SELECT DISTINCT p.upc AS PLU, p.description AS Description, ROUND(p.normal_price,2) AS 'Current Price', ROUND(t.unitPrice,2) AS Price, p.department AS Dept, p.subdept AS Subdept, SUM(t.quantity) AS Qty, ROUND(SUM(t.total),2) AS Total, p.scale as Scale FROM is4c_log.dtransactions t, is4c_op.products p WHERE t.upc = p.upc AND t.department IN(8) AND t.datetime >= '2007-08-06 00:00:00' AND t.datetime <= '2007-08-13 23:59:59' AND t.emp_no <> 9999 AND t.trans_status <> 'X' AND t.upc NOT LIKE '%DP%' AND p.inUse = 1 GROUP BY CONCAT(t.upc, '-',t.unitprice) ORDER BY t.upc */
	if (isset($deptDetails)) {
            if ($year1 == $year2 && substr($date2a, 0, 10) != date('Y-m-d')) {

                $query3 = "SELECT DISTINCT
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
                        AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                        AND t.emp_no <> 9999
                        AND t.trans_status <> 'X'
                        AND t.upc NOT LIKE '%DP%'
                        $inUse
                    GROUP BY CONCAT(t.upc, '-',t.unitprice)
                    ORDER BY $order";

            } else {

                $query3 = "SELECT * FROM (";
                for ($i = $year1; $i <= $year2; $i++) {
                    $query3 .= "SELECT DISTINCT
                            p.upc AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS 'Current Price',
                            ROUND(t.unitPrice,2) AS Price,
                            d.dept_name AS Dept,
                            s.subdept_name AS Subdept,
                            SUM(t.quantity) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale
                            FROM is4c_log.trans_$i t, is4c_op.products p, is4c_op.subdepts s, is4c_op.departments d
                        WHERE t.upc = p.upc AND s.subdept_no = p.subdept AND t.department = d.dept_no
                            AND t.department IN ($deptArray)
                            AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            AND t.upc NOT LIKE '%DP%'
                            $inUse
                        GROUP BY CONCAT(t.upc, '-',t.unitprice)";

                    if ($i == $year2) {
                        if (substr($date2a, 0, 10) == date('Y-m-d')) {
                            $query3 .= "UNION ALL SELECT DISTINCT
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
                                    AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                                    AND t.emp_no <> 9999
                                    AND t.trans_status <> 'X'
                                    AND t.upc NOT LIKE '%DP%'
                                    $inUse
                                GROUP BY CONCAT(t.upc, '-',t.unitprice)";
                        }
                        $query3 .= ") AS yearSpan GROUP BY CONCAT(PLU, Price) ORDER BY $order";
                    } else $query3 .= " UNION ALL ";
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

	} elseif (!isset($deptDetails)) {

            if ($year1 == $year2 && substr($date2a, 0, 10) != date('Y-m-d')) {

                $query3 = "SELECT DISTINCT
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
                        AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                        AND t.emp_no <> 9999
                        AND t.trans_status <> 'X'
                        AND t.upc NOT LIKE '%DP%'
                        $inUse
                    GROUP BY CONCAT(t.upc, '-',t.unitprice)";

            } else {
                $query3 = "SELECT * FROM (";
                for ($i = $year1; $i <= $year2; $i++) {
                    $query3 .= "SELECT DISTINCT
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
                            AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            AND t.upc NOT LIKE '%DP%'
                            $inUse
                        GROUP BY CONCAT(t.upc, '-',t.unitprice)";

                    if ($i == $year2) {
                        if (substr($date2a, 0, 10) == date('Y-m-d')) {
                            $query3 .= " UNION ALL SELECT DISTINCT
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
                                AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                                AND t.emp_no <> 9999
                                AND t.trans_status <> 'X'
                                AND t.upc NOT LIKE '%DP%'
                                $inUse
                            GROUP BY CONCAT(t.upc, '-',t.unitprice)";
                        }

                        $query3 .= ") AS yearSpan GROUP BY CONCAT(PLU, Price) ORDER BY $order";
                    } else $query3 .= " UNION ALL ";
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

        $result3 = mysqli_query($db_slave, $query3);

	echo '<table border="1" cellpadding="3" cellspacing="3" class="tablesorter">';
	echo $table_header;
        echo $table_footer;

	if (!$result3) {
            $message  = 'Invalid query: ' . mysqli_error($db_slave) . "\n";
            $message .= 'Whole query: ' . $query3;
            die($message);
	}


	while ($myrow = mysqli_fetch_row($result3)) { //create array from query
            if ($myrow[$scaleRow] == 0) {$myrow[$scaleRow] = 'No';} elseif ($myrow[$scaleRow] == 1) {$myrow[$scaleRow] = 'Yes';}
            printf('<tr><td><a href="/item/itemMaint.php?submitted=search&upc=%s\">%s</a></td><td>%s</th><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
		   $myrow[0], $myrow[0], $myrow[1], $myrow[2], $myrow[3], $myrow[4], $myrow[5], number_format($myrow[6],2), $myrow[7], $myrow[8]);
            //convert row information to strings, enter in table cells
	}

	echo "</tbody></table><br /><br />\n";//end table
        echo '<div id="pager" class="pager">
                <form>
                    <img src="../includes/javascript/tablesorter/addons/pager/icons/first.png" class="first"/>
                    <img src="../includes/javascript/tablesorter/addons/pager/icons/prev.png" class="prev"/>
                    <input type="text" class="pagedisplay"/>
                    <img src="../includes/javascript/tablesorter/addons/pager/icons/next.png" class="next"/>
                    <img src="../includes/javascript/tablesorter/addons/pager/icons/last.png" class="last"/>
                    <select class="pagesize">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="25" selected="selected">25</option>
                            <option value="30">30</option>
                            <option value="40">40</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="250">250</option>
                            <option value="500">500</option>
                            <option value="1000">1000</option>
                    </select>
                </form>
            </div>';


    }

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
            <form method="post" action="%s">
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
		    <td colspan="2" align="left"><input type="checkbox" name="salesTotal" checked="CHECKED" /><strong>Sales totals</strong></td>
		    <td colspan="2" align="left"><input type="checkbox" name="deptDetails" checked="CHECKED" /><strong>Include department details</strong></td>
		</tr>
                <tr>
		    <td colspan="2" align="left"><input type="checkbox" name="openRing" checked="CHECKED" /><strong>Open ring totals</strong></td>
		    <td colspan="2" align="center"><strong>Sort by: &nbsp;</strong>
                            <select name="sort">
                                <option name="PLU" selected="selected">PLU</option>
                                <option name="Qty">Qty</option>
                                <option name="Sales">Sales</option>
                                <option name="Department">Department</option>
                                <option name="Subdepartment">Subdepartment</option>
                            </select>
		    </td>
		</tr>
                <tr>
                    <td colspan="2" align="left"><input type="checkbox" name="inUse" checked="CHECKED" /><strong>Filter not "in use"</strong></td>
		    <td colspan="2" align="left"><input type="checkbox" name="shrinkReport" checked="CHECKED" /><strong>Include shrink summary</strong></td>
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
