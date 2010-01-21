<?php

if (isset($_POST['submitted'])) { // If the form has been submitted, print the report.

    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db ($db_slave, 'is4c_log');

    $subdepartment = $_POST['subdepartment'];
    $query = "SELECT s.subdept_name, d.dept_name, d.dept_no
        FROM is4c_op.subdepts AS s INNER JOIN is4c_op.departments AS d ON (s.dept_ID = d.dept_no)
        WHERE s.subdept_no = $subdepartment";
    $result = mysqli_query($db_slave, $query);
    list($subdept, $dept, $dept_no) = mysqli_fetch_row($result);

    $sort = $_POST['sort'];
    $today = date("l F jS, Y");

    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];

    $year1 = substr($date1, 0, 4);
    $year2 = substr($date2, 0, 4);

    if ($sort == 'PLU') {
	$order = "PLU";
    } elseif($sort == 'Quantity') {
	$order = 'Qty DESC, PLU';
    } elseif($sort == 'Sales') {
    	$order = 'Total DESC, PLU';
    }

    ?>  <html>
        <head>
        <title>Sub-department Movement Report</title>
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/addons/pager/jquery.tablesorter.pager.css" />
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/themes/blue/style.css" />
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datePicker.css" />
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
        <script type="text/javascript" src="../includes/javascript/jquery.datePicker.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
                $(".tablesorter")
                    .tablesorter({widthFixed: true, debug: false, widgets:['zebra']})
                    .tablesorterPager({container: $("#pager")});
                $(".tablesorter tr").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});
                Date.format = 'yyyy-mm-dd';
                $('.date-pick').datePicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true});
            });
        </script>
        </head>
    <?php
    //Following lines creates a header for the report, listing sort option chosen, report date, date and department range.
    echo "Report run: $today<br />";
    echo "From $date1 to $date2<br />";
    echo "Sorted by $sort for<br />";
    echo "Sub-department #$subdepartment: $subdept<br />";
    echo "Department #$dept_no: " . ucfirst(strtolower($dept)) . "<br />";

    if ($_POST['inUse'] == 1) {
        echo 'Items not in use filtered out.<br />';
        $inUse = 'AND p.inUse = 1';
    } else {
        $inUse = NULL;
    }

    echo '<br />';

    if ($year1 != $year2) {

        $grossQ = "SELECT SUM(Total) FROM (";

        for ($i = $year1; $i <= $year2; $i++) {

            $grossQ .= "SELECT ROUND(SUM(t.total), 2) AS Total
                FROM is4c_log.trans_$i AS t, is4c_op.products AS p
                WHERE p.subdept = $subdepartment AND t.UPC = p.UPC
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999";

            if ($i == $year2) {

                if (substr($date2, 0, 10) == date('Y-m-d')) {
                    $grossQ .= " UNION ALL SELECT ROUND(SUM(t.total), 2) AS Total
                FROM is4c_log.dtransactions AS t, is4c_op.products AS p
                WHERE p.subdept = $subdepartment AND t.UPC = p.UPC
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999";
                }

                $grossQ .= ") AS yearSpan";

            } else $grossQ .= " UNION ALL ";

        }

    } else {
        if (substr($date2, 0, 10) == date('Y-m-d')) {

            $grossQ = "SELECT SUM(Total) FROM (";

            $grossQ .= "SELECT ROUND(SUM(t.total), 2) AS Total
                FROM is4c_log.trans_$year1 AS t, is4c_op.products AS p
                WHERE p.subdept = $subdepartment AND t.UPC = p.UPC
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999";

            $grossQ .= " UNION ALL SELECT ROUND(SUM(t.total), 2) AS Total
                FROM is4c_log.dtransactions AS t, is4c_op.products AS p
                WHERE p.subdept = $subdepartment AND t.UPC = p.UPC
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999";

	    $grossQ .= ") AS yearSpan";

        } else {

            $grossQ = "SELECT ROUND(SUM(t.total), 2) AS Total
                FROM is4c_log.trans_$year1 AS t, is4c_op.products AS p
                WHERE p.subdept = $subdepartment AND t.UPC = p.UPC
                    AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                    AND t.trans_status <> 'X'
                    AND t.emp_no <> 9999";

        }
    }

    $grossR = mysqli_query($db_slave, $grossQ);
    if ($grossR)
	list($gross) = mysqli_fetch_row($grossR);
    else
	printf("mySQL Error: %s\nQuery: %s", mysqli_error($db_slave), $grossQ);

    echo "<p>Sub-department gross sales: $gross</p><br />";

    // Print the row headers.
    echo '<table border="1" class="tablesorter">
            <thead>
                <tr>
                    <th>UPC</th>
                    <th>Description</th>
                    <th>Current Price</th>
                    <th>Quantity</th>
                    <th>Total Value Sold</th>
                    <th>Scale (Weighed)</th>
                    <th><b>In Use</b></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>UPC</th>
                    <th>Description</th>
                    <th>Current Price</th>
                    <th>Quantity</th>
                    <th>Total Value Sold</th>
                    <th>Scale (Weighed)</th>
                    <th><b>In Use</b></th>
                </tr>
            </tfoot><tbody>';
    echo "\n";

    // Make the query.
    if ($year1 == $year2) { // Simple.
        if (substr($date2, 0, 10) == date('Y-m-d')) {
            $query = "SELECT PLU, Description, `Current Price`, SUM(Qty) AS Qty, SUM(Total) AS total, Scale, inUse FROM (";
            $query .= "SELECT
                            p.UPC AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS 'Current Price',
                            SUM(t.ItemQtty) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale,
                            p.inUse as inUse
                            FROM is4c_op.products AS p RIGHT JOIN is4c_log.trans_$year1 AS t ON (p.UPC = t.UPC)
                            WHERE p.subdept = $subdepartment
                            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            $inUse
                            GROUP BY p.upc";
            $query .= " UNION ALL SELECT p.UPC AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS 'Current Price',
                            SUM(t.ItemQtty) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale,
                            p.inUse as inUse
                            FROM is4c_op.products AS p RIGHT JOIN is4c_log.dtransactions AS t ON (p.UPC = t.UPC)
                            WHERE p.subdept = $subdepartment
                            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            $inUse
                            GROUP BY p.upc";
            $query .= ") AS yearSpan GROUP BY PLU ORDER BY $order";
        } else {
            $query = "SELECT
                            p.UPC AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS 'Current Price',
                            SUM(t.ItemQtty) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale,
                            p.inUse as inUse
                            FROM is4c_op.products AS p RIGHT JOIN is4c_log.trans_$year1 AS t ON (p.UPC = t.UPC)
                            WHERE p.subdept = $subdepartment
                            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            $inUse
                            GROUP BY p.upc
                            ORDER BY $order";

        }
    } else {
        $query = "SELECT PLU, Description, `Current Price`, SUM(Qty) AS Qty, SUM(Total) AS total, Scale, inUse FROM (";
        for ($i = $year1; $i <= $year2; $i++) {
            $query .= "SELECT
                            p.UPC AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS 'Current Price',
                            SUM(t.ItemQtty) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale,
                            p.inUse as inUse
                            FROM is4c_op.products AS p RIGHT JOIN is4c_log.trans_$i AS t ON (p.UPC = t.UPC)
                            WHERE p.subdept = $subdepartment
                            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            $inUse
                            GROUP BY p.upc";

            if ($i == $year2) {

                if (substr($date2, 0, 10) == date('Y-m-d')) {

                    $query .= " UNION ALL SELECT p.UPC AS PLU,
                            p.description AS Description,
                            ROUND(p.normal_price,2) AS 'Current Price',
                            SUM(t.ItemQtty) AS Qty,
                            ROUND(SUM(t.total),2) AS Total,
                            p.scale as Scale,
                            p.inUse as inUse
                            FROM is4c_op.products AS p RIGHT JOIN is4c_log.dtransactions AS t ON (p.UPC = t.UPC)
                            WHERE p.subdept = $subdepartment
                            AND DATE(t.datetime) BETWEEN '$date1' AND '$date2'
                            AND t.emp_no <> 9999
                            AND t.trans_status <> 'X'
                            $inUse
                            GROUP BY p.upc";
                }

                $query .= ") AS yearSpan GROUP BY PLU ORDER BY $order";

            } else $query .= " UNION ALL ";
        }

    }

    $result = mysqli_query($db_slave, $query);

    while ($row = mysqli_fetch_array($result)) {
        printf('<tr>
                <td align="center">%s</td>
                <td align="center">%s</td>
                <td align="center">$%s</td>
                <td align="center">%s</td>
                <td align="center">$%s</td>
                <td align="center">%s</td>
                <td align="center">%s</td>
                </tr>',
                '<a href="../item/itemMaint.php?submitted=search&upc=' . $row[0] . '">' . $row[0] . '</a></td>',
                $row[1],
                number_format($row[2], 2),
                round($row[3], 2),
                round($row[4], 2),
                $row[5] == 1 ? 'Yes' : 'No',
                $row[6] == 1 ? 'Yes' : 'No');
    }

    echo "</tbody></table>";

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

} else { // Draw the form.
    $page_title = 'Fannie - Reports Module';
    $header = 'Sub-department Movement Report';
    include ('../includes/header.html');
    //echo '<script src="../src/CalendarControl.js" language="javascript"></script>';
    /**
    **	BEGIN CHAINEDSELECTOR CLASS
    **/
    require_once("../src/chainedSelectors_modified.php");
    require_once('../includes/mysqli_connect.php');

    if (!(mysqli_select_db($db_slave, "is4c_op"))) {
        print("Unable to use the database!<br>\n");
        exit();
    }
    $selectorNames = array(
                           CS_FORM=>"pickSubDepartment",
                           CS_FIRST_SELECTOR=>"department",
                           CS_SECOND_SELECTOR=>"subdepartment");

    $Query = "SELECT d.dept_no AS dept_no,d.dept_name AS dept_name,s.subdept_no AS subdept_no,s.subdept_name AS subdept_name
              FROM is4c_op.departments AS d INNER JOIN is4c_op.subdepts AS s ON (d.dept_no = s.dept_ID)
              ORDER BY d.dept_no, s.subdept_no";

    if (!($DatabaseResult = mysqli_query($db_slave, $Query))) {
        print("The query failed!<br>\n");
        exit();
    }
    while ($row = mysqli_fetch_object($DatabaseResult)) {
        $selectorData[] = array(
                                CS_SOURCE_ID=>$row->dept_no,
                                CS_SOURCE_LABEL=>$row->dept_name,
                                CS_TARGET_ID=>$row->subdept_no,
                                CS_TARGET_LABEL=>$row->subdept_name);
    }

    $subdept = new chainedSelectors($selectorNames, $selectorData);
    ?>
    <script type="text/javascript" language="JavaScript">

    <?php
        $subdept->printUpdateFunction();
    ?>
    </script>
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/demo.css" />
        <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
        <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
        <script type="text/javascript" src="../includes/javascript/datepicker/jquery.datePicker.js"></script>
        <script type="text/javascript">
            Date.format = 'yyyy-mm-dd';
            $(function(){
                $('.datepick').datePicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true})
                .dpSetOffset(0, 125);
            });
        </script>
    </head>
    <body>
    <form name="pickSubDepartment" action="subdeptmovement.php" method="post" target="_blank">
    <h3>Please select a subdepartment:</h3>
    <?php
        $subdept->printSelectors();
    ?>
    <script type="text/javascript" language="JavaScript">
    <?php
        $subdept->initialize();
    ?>
    </script>
    <br /><br />
            <table border="0" cellspacing="3" cellpadding="3">
                    <tr>
                            <td align="right">
                                    <p><b>Date Start</b> </p>
                            <p><b>End</b></p>
                            </td>
                            <td>
                                    <p><input type="text" size="10" autocomplete="off" name="date1" class="datepick" />&nbsp;&nbsp;*</p>
                                    <p><input type="text" size="10" autocomplete="off" name="date2" class="datepick" />&nbsp;&nbsp;*</p>
                            </td>
                            <td colspan=2>
                                    <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
                            </td>
                    </tr>
            </table>
    <br />
    <p>Sort report by:
        <select name="sort" size="1">
            <option>PLU</option>
            <option>Quantity</option>
            <option>Sales</option>
        </select>
    </p>
    <p><b>Filter out items that are NOT currently in use.</b>
    <input type="checkbox" value="1" name="inUse"></p>
    <br />
    <input type="hidden" name="submitted" value="TRUE" />
    <input type="submit" name="submit" value="Submit" />
    </form>
    </body>
    <?php
    include ('../includes/footer.html');
    }
?>
