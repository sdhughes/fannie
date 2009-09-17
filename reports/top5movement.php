<?php # Top and Bottom Movers By Department/Subdepartment

require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

if (isset($_POST['submitted'])) { // If the form has been submitted.

    $department = $_POST['department'];
    $limit = $_POST['limit'];
    $subdept_no = array();
    $kind = $_POST['kind'];
    $table = $_POST['table'];
    $type = $_POST['type'];
    $subtype = $_POST['subtype'];

    switch ($kind) {
        case "bottom":
            if ($subtype == 'both') { $order_value = array('Quantity Ascending'=>'Quantity ASC', 'Total Ascending'=>'Total ASC');
            } elseif ($subtype == 'quantity') { $order_value = array('Quantity Ascending'=>'Quantity ASC');
            } elseif ($subtype == 'value') { $order_value = array('Total Ascending'=>'Total ASC');
            }
            break;
        case "top":
            if ($subtype == 'both') { $order_value = array('Quantity Descending'=>'Quantity DESC', 'Total Descending'=>'Total DESC');
            } elseif ($subtype == 'quantity') { $order_value = array('Quantity Descending'=>'Quantity DESC');
            } elseif ($subtype == 'value') { $order_value = array('Total Descending'=>'Total DESC');
            }
            break;
        case "both":
            if ($subtype == 'both') { $order_value = array('Quantity Ascending'=>'Quantity ASC', 'Total Ascending'=>'Total ASC', 'Quantity Descending'=>'Quantity DESC', 'Total Descending'=>'Total DESC');
            } elseif ($subtype == 'quantity') { $order_value = array('Quantity Ascending'=>'Quantity ASC', 'Quantity Descending'=>'Quantity DESC');
            } elseif ($subtype == 'value') { $order_value = array('Total Ascending'=>'Total ASC', 'Total Descending'=>'Total DESC');
            }
            break;
    }

    if ($type == 'subdept') {
        $query = "SELECT s.subdept_no
        FROM is4c_op.subdepts AS s
            INNER JOIN is4c_op.products AS p ON (p.department = s.dept_ID)
        WHERE p.department = $department
        GROUP BY s.subdept_no
        ORDER BY s.subdept_no ASC";

        $result = mysqli_query($db_slave, $query);

        while ($row = mysqli_fetch_array($result)) {
            $subdept_no[] = $row[0];
        }

    } elseif ($type == 'dept') {

        $subdept_no[] = $department;

    }

    echo '<font face="times">';

    foreach ($subdept_no AS $subdept) {

        foreach ($order_value AS $order_name=>$order) {

            if ($type == 'subdept') {

                $headerQ = "SELECT subdept_name, dept_name, dept_ID
                    FROM is4c_op.subdepts AS s
                        INNER JOIN is4c_op.departments AS d ON (s.dept_ID = d.dept_no)
                    WHERE subdept_no = $subdept";

                $headerR = mysqli_query($db_slave, $headerQ);

                $header = mysqli_fetch_array($headerR);
                $department_name = ucfirst(strtolower($header[1]));
                $subdepartment_name = '- ' . $header[0] . ' ';
                $where = 'p.subdept';

            } elseif ($type == 'dept')  {

                $headerQ = "SELECT dept_name
                    FROM is4c_op.departments
                    WHERE dept_no = $subdept";

                $headerR = mysqli_query($db_slave, $headerQ);

                $header = mysqli_fetch_array($headerR);
                $department_name = ucfirst(strtolower($header[0]));
                $subdepartment_name = '';
                $where = 'p.department';

            }

            $query = "SELECT p.upc AS upc,
                p.description AS description,
                IFNULL(ROUND(f.Quantity, 2), 0) AS Quantity,
                IFNULL(ROUND(f.Total, 2), 0) AS Total,
                IFNULL(ROUND(f.Average_Price, 2), 0) AS Average_Price
                FROM is4c_op.products AS p
                    LEFT JOIN is4c_log.$table AS f ON (p.upc=f.upc)
                WHERE $where = $subdept
                AND p.inUse = 1
                AND p.discounttype <> 3
                ORDER BY $order
                LIMIT $limit";

            $result = mysqli_query($db_slave, $query);

            echo "<p><b>$department_name $subdepartment_name </b>(Sorted by: $order_name)</p>";
            echo "<table border=1 cellpadding=3 cellspacing=3>";

            if (mysqli_num_rows($result) == 0) {
                echo '<p>There are no active products in this sub-department.</p>';
            } else {
                echo "<tr><td>UPC</td><td>Description</td><td>Quantity Sold</td><td>Total Value</td><td>Average Price</td></tr>";

                while ($row = mysqli_fetch_array($result)) {

                    echo "<tr><td><a href='/item/itemMaint.php?submitted=search&upc=$row[0]'>$row[0]</a></td><td>$row[1]</td><td>$row[2]</td><td>$row[3]</td><td>$row[4]</td></tr>";

                }

            }

            echo "</table>";

        }

    }
    echo '</font>';
} else { // Draw the form.
    $page_title = 'Fannie - Reports Module';
    $header = 'Top and Bottom Movers';
    include ('../includes/header.html');
    $query = "SELECT dept_no, dept_name FROM is4c_op.departments WHERE dept_no < 19 AND dept_no <> 17 ORDER BY dept_no ASC";
    $result = mysqli_query($db_slave, $query);

    echo '<h3>Top and Bottom Movers</h3>
        <p>Warning: This will be a slow report, give it a few minutes to complete.</p><br />
        <form name="top5movement" action="top5movement.php" method="post" target="_blank">
        <p>What kind of report?
            <select name="kind">
                <option value="top">Top</option>
                <option value="bottom">Bottom</option>
                <option value="both" selected="selected">Top And Bottom</option>
            </select>
        </p>
        <p>What department?
            <select name="department">';
            while ($row = mysqli_fetch_array($result)) {
                echo "<option value=\"$row[0]\">$row[1]</option>";
            }
        echo '</select></p>
        <p>How many results would you like?
            <select name="limit">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15" selected="selected">15</option>
                <option value="20">20</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </p>
        <p>Every subdepartment, or just the department as a whole?
            <select name="type">
                <option value="dept">Department</option>
                <option value="subdept" selected="selected">Subdepartment</option>
            </select>
        </p>
        <p>What kind of report?
            <select name="table">
                <option value="YTD_2008" selected="selected">Year-To-Date (2008)</option>
                <option value="YTD_2009" selected="selected">Year-To-Date (2009)</option>
                <option value="1stquarter">First Quarter \'08</option>
                <option value="2ndquarter">Second Quarter \'08</option>
                <option value="3rdquarter">Third Quarter \'08</option>
                <option value="4thquarter">Fourth Quarter \'08</option>
                <option value="1stquarter_2009">First Quarter \'09</option>
                <option value="2ndquarter_2009">Second Quarter \'09</option>
                <option value="3rdquarter_2009">Third Quarter \'09</option>
                <option value="4thquarter_2009">Fourth Quarter \'09</option>
            </select>
        </p>
        <p>Order by quantity sold, value sold, or both?
            <select name="subtype">
                <option value="both">Both</option>
                <option value="quantity" selected="selected">Quantity Sold</option>
                <option value="value">Value Sold</option>
            </select>
        </p>
        <input type="hidden" name="submitted" value="TRUE" />
        <input type="submit" name="submit" value="Submit" />
        </form>';
        include ('../includes/footer.html');
}
?>
