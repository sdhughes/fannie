<?php

require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

?>
<SCRIPT TYPE="text/javascript">
<!--
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
//-->
</SCRIPT>
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

if (isset($_POST['submitted'])) {
    foreach ($_POST AS $key => $value) {
	$$key = $value;
    }

    $today = date("F d, Y");

    $_SESSION['deptArray'] = 0;

    if($_POST['allDepts'] == 1) {
        $_SESSION['deptArray'] = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,40";
        $arrayName = "ALL DEPARTMENTS";
    } else {

    }

    if (is_array($_POST['dept'])) {
        $_SESSION['deptArray'] = implode(",",$_POST['dept']);
        $arrayName = $_SESSION['deptArray'];
    }

// Following lines creates a header for the report, listing sort option chosen, report date, date and department range.

    echo "Report sorted by $sort on<br />
        $today<br />
        From $date1 to $date2<br />
        Department range: $arrayName<br /><br />";

    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

    $date2a = $date2 . " 23:59:59";
    $date1a = $date1 . " 00:00:00";
    //decide what the sort index is and translate from lay person to mySQL table label

    $_SESSION['sort'] = $_POST['sort'];
    $sort = $_SESSION['sort'];

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

    if (isset($inUse)) {
        $inUseA = "AND p.inUse = 1";
    } else {
        $inUseA = "AND p.inUse IN(0,1)";
    }


    if (isset($salesTotal)) {
        $query1 = "SELECT * FROM (";
        for ($i = $year1; $i <= $year2; $i++) {
            $query1 .= "SELECT d.dept_name,ROUND(SUM(t.total),2) AS total
                FROM is4c_op.departments AS d, is4c_log.trans_$i AS t
                WHERE d.dept_no = t.department
                    AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                    AND t.department IN(" . $_SESSION['deptArray'] . ")
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999
                GROUP BY t.department";

            if ($i == $year2) {
                if (substr($date2a, 0, 10) == date('Y-m-d')) {
                    $query1 .= " UNION SELECT d.dept_name,ROUND(SUM(t.total),2) AS total
                        FROM is4c_op.departments AS d, is4c_log.dtransactions AS t
                        WHERE d.dept_no = t.department
                            AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.department IN(" . $_SESSION['deptArray'] . ")
                            AND t.trans_status <> 'X'
                            AND t.emp_no <> 9999
                        GROUP BY t.department";
                }

                $query1 .= ") AS yearSpan";

            } else $query1 .= " UNION ";

        }

        $result1 = mysqli_query($db_slave, $query1);
        //echo $query1;
        echo "<table>\n"; //create table
        echo "<tr><td>";
        echo "<b>Department</b></td><td>";
        echo "<b>Total Sales</b></td></tr>";

        if (!$result1) {
            $message  = 'Invalid query: ' . mysqli_error($db_slave) . "\n";
            $message .= 'Whole query: ' . $query1;
            die($message);
        }

        while ($myrow = mysqli_fetch_row($result1)) { //create array from query

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
                    AND t.department IN(".$_SESSION['deptArray'].")
                    AND d.dept_no = t.department
                GROUP BY t.department";

            if ($i == $year2) {
                if (substr($date2a, 0, 10) == date('Y-m-d')) {
                    $query2 .= " UNION SELECT d.dept_name AS Department,ROUND(SUM(t.total),2) AS open_dept, d.dept_no AS Dept_No
                        FROM is4c_op.departments AS d,is4c_log.dtransactions AS t
                        WHERE t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.trans_status <> 'X'
                            AND t.trans_type = 'D'
                            AND t.emp_no <> 9999
                            AND t.department IN(".$_SESSION['deptArray'].")
                            AND d.dept_no = t.department
                        GROUP BY t.department";
                }

                $query2 .= ") AS yearSpan";

            } else $query2 .= " UNION ";
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
                        AND t.department IN (".$_SESSION['deptArray'].")
                        AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                        AND t.emp_no <> 9999
                        AND t.trans_status <> 'X'
                        AND t.upc NOT LIKE '%DP%'
                        $inUseA
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
                            AND t.department IN (".$_SESSION['deptArray'].")
                            AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            AND t.upc NOT LIKE '%DP%'
                            $inUseA
                        GROUP BY CONCAT(t.upc, '-',t.unitprice)";

                    if ($i == $year2) {
                        if (substr($date2a, 0, 10) == date('Y-m-d')) {
                            $query3 .= "UNION SELECT DISTINCT
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
                                    AND t.department IN (".$_SESSION['deptArray'].")
                                    AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                                    AND t.emp_no <> 9999
                                    AND t.trans_status <> 'X'
                                    AND t.upc NOT LIKE '%DP%'
                                    $inUseA
                                GROUP BY CONCAT(t.upc, '-',t.unitprice)";
                        }
                        $query3 .= ") AS yearSpan GROUP BY CONCAT(PLU, Price) ORDER BY $order";
                    } else $query3 .= " UNION ";
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
                        AND t.department IN(".$_SESSION['deptArray'].")
                        AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                        AND t.emp_no <> 9999
                        AND t.trans_status <> 'X'
                        AND t.upc NOT LIKE '%DP%'
                        $inUseA
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
                            AND t.department IN(".$_SESSION['deptArray'].")
                            AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            AND t.upc NOT LIKE '%DP%'
                            $inUseA
                        GROUP BY CONCAT(t.upc, '-',t.unitprice)";

                    if ($i == $year2) {
                        if (substr($date2a, 0, 10) == date('Y-m-d')) {
                            $query3 .= " UNION SELECT DISTINCT
                                p.upc AS PLU,
                                p.description AS Description,
                                ROUND(p.normal_price,2) AS 'Current Price',
                                ROUND(t.unitPrice,2) AS Price,
                                ROUND(SUM(t.quantity),2) AS Qty,
                                ROUND(SUM(t.total),2) AS Total,
                                p.scale as Scale
                            FROM is4c_log.trans_$i t, is4c_op.products p
                            WHERE t.upc = p.upc
                                AND t.department IN(".$_SESSION['deptArray'].")
                                AND t.datetime >= '$date1a' AND t.datetime <= '$date2a'
                                AND t.emp_no <> 9999
                                AND t.trans_status <> 'X'
                                AND t.upc NOT LIKE '%DP%'
                                $inUseA
                            GROUP BY CONCAT(t.upc, '-',t.unitprice)";
                        }

                        $query3 .= ") AS yearSpan GROUP BY CONCAT(PLU, Price) ORDER BY $order";
                    } else $query3 .= " UNION ";
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
            printf("<tr><td><a href=\"http://192.168.1.102/item/itemMaint.php?submitted=search&upc=%s\">" . $myrow[0] . "</a></td><td>%s</th><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",$myrow[0], $myrow[1],$myrow[2],$myrow[3],$myrow[4],$myrow[5],number_format($myrow[6],2),$myrow[7], $myrow[8]);
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

    $page_title = 'Fannie - Reports Module';
    $header = 'Department Movement Report';
    include ('../includes/header.html');
?>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/demo.css" />
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/jquery.datePicker-2.1.2.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datePicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true})
            .dpSetOffset(0, 125);
        });
    </script>
    <form method="post" action="deptSales.php" target="_blank">
        <div id="box">
            <table border="0" cellspacing="3" cellpadding="5">
                <tr>
                    <th colspan="2" align="center"> <p><b>Select dept.*</b></p></th>
                </tr>
                <tr>
                    <td>
                        <font size="-1"><p>
                        <input type="checkbox" value=1 name="allDepts"><b>All Departments</b><br>
                        <input type="checkbox" name="dept[]" value="1">Grocery<br>
                        <input type="checkbox" name="dept[]" value="2">Bulk<br>
                        <input type="checkbox" name="dept[]" value="3">Perishable<br>
                        <input type="checkbox" name="dept[]" value="4">Dairy<br>
                        <input type="checkbox" name="dept[]" value="7">Cheese<br>
                        <input type="checkbox" name="dept[]" value="15">Deli<br>
                        <input type="checkbox" name="dept[]" value="16">Bread & Juice<br>
                        <input type="checkbox" name="dept[]" value="14">Beer<br>
                        <input type="checkbox" name="dept[]" value="5">Wine<br>
                        </p></font>
                    </td>
                    <td>
                        <font size="-1"><p>
                        <input type="checkbox" name="dept[]" value="8">Produce<br>
                        <input type="checkbox" name="dept[]" value="6">Frozen<br>
                        <input type="checkbox" name="dept[]" value="12">NF-Supplements<br>
                        <input type="checkbox" name="dept[]" value="11">NF-Personal Care<br>
                        <input type="checkbox" name="dept[]" value="10">NF-General<br>
                        <input type="checkbox" name="dept[]" value="9">Bulk Herbs<br>
                        <input type="checkbox" name="dept[]" value="13">NF-Pet<br>
                        <input type="checkbox" name="dept[]" value="17">Floral<br>
                        <input type="checkbox" name="dept[]" value="40">Tri-Met<br />
                        <input type="checkbox" name="dept[]" value="18">Marketing
                        </p></font>
                    </td>
                </tr>
            </table>
        </div>
        <div id="box">
            <table border="0" cellspacing="3" cellpadding="3">
                <tr>
                    <td align="right">
                        <p><b>Date Start</b> </p>
                        <p><b>End</b></p>
                    </td>
                    <td>
                        <p><input type="text" size="10" autocomplete="off" name="date1" class="datepick">&nbsp;&nbsp;*</p>
                        <p><input type="text" size="10" autocomplete="off" name="date2" class="datepick">&nbsp;&nbsp;*</p>
                    </td>
                    <td colspan=2>
                        <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                    </td>
                </tr>
            </table>
        </div>
        <div id="box">
            <table border="0" cellspacing="3" cellpadding="3">
                <tr>
                    <td align="right"><p><b>Sales totals</b></p></td>
                    <td><input type="checkbox" value="1" name="salesTotal" CHECKED></td>
                    <td align="right"><p><b>Include department details</b></p></td>
                    <td><input type="checkbox" value="1" name="deptDetails" CHECKED /></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td align="right"><p><b>Open ring totals</b></p></td>
                    <td><input type="checkbox" value="1" name="openRing" CHECKED></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td align="right"><p><b>PLU report</b></p></td>
                    <td><input type="checkbox" value="1" name="pluReport" CHECKED></td>
                    <td colspan="2" align="center">
                        <p>Group PLUs by:
                            <select name="sort" size="1">
                                <option>PLU</option>
                                <option>Qty</option>
                                <option>Sales</option>
                                <option>Department</option>
                                <option>Subdepartment</option>
                            </select></p>
                    </td>
                </tr>
                <tr>
                    <td align="right"><p><b>In use</b></p></td>
                    <td><input type="checkbox" value="1" name="inUse" CHECKED></td>
                    <td><p>Filter out items that are NOT currently in use</p></td>
                </tr>
                <tr>
                    <td colspan="3" align="center"><p>* -- indicates required field</p></td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                    <td> <input type=submit name=submit value="Submit"> </td>
                    <td> <input type=reset name=reset value="Start Over"> </td>
                    <input type="hidden" name="submitted" value="TRUE">
                </tr>
            </table>
        </div>
    </form>
<?php
  include('../includes/footer.html');
}

?>
