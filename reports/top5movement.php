<?php # Top and Bottom Movers By Department/Subdepartment

require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

if (isset($_POST['submitted'])) { // If the form has been submitted.

    $department = $_POST['department'];
    $limit = (int)$_POST['limit'];
    $subdept_no = array();
    $kind = $_POST['kind'];
    $table = $_POST['table'];
    $type = $_POST['type'];
    $subtype = $_POST['subtype'];

    switch ($kind) {
        case "bottom":
            if ($subtype == 'both') { $order_value = array("Bottom $limit by Quantity"=>'Quantity ASC', "Bottom $limit by Sales"=>'Total ASC');
            } elseif ($subtype == 'quantity') { $order_value = array("Bottom $limit by Quantity"=>'Quantity ASC');
            } elseif ($subtype == 'value') { $order_value = array("Bottom $limit by Sales"=>'Total ASC');
            }
            break;
        case "top":
            if ($subtype == 'both') { $order_value = array("Top $limit by Quantity"=>'Quantity DESC', "Top $limit by Sales"=>'Total DESC');
            } elseif ($subtype == 'quantity') { $order_value = array("Top $limit by Quantity"=>'Quantity DESC');
            } elseif ($subtype == 'value') { $order_value = array("Top $limit by Sales"=>'Total DESC');
            }
            break;
        case "both":
            if ($subtype == 'both') { $order_value = array("Bottom $limit by Quantity"=>'Quantity ASC', "Bottom $limit by Sales"=>'Total ASC', "Top $limit by Quantity"=>'Quantity DESC', "Top $limit by Sales"=>'Total DESC');
            } elseif ($subtype == 'quantity') { $order_value = array("Bottom $limit by Quantity"=>'Quantity ASC', "Top $limit by Quantity"=>'Quantity DESC');
            } elseif ($subtype == 'value') { $order_value = array("Bottom $limit by Sales"=>'Total ASC', "Top $limit by Sales"=>'Total DESC');
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

            echo "<h3><strong>$department_name $subdepartment_name </strong>($order_name)</h3>";
            echo "<table border=1 cellpadding=3 cellspacing=3>";

            if (mysqli_num_rows($result) == 0) {
                echo '<p>There are no active products in this sub-department.</p>';
            } else {
                echo "<thead>
			<tr>
			    <th>UPC</th>
			    <th>Description</th>
			    <th>Quantity Sold</th>
			    <th>Total Value</th>
			    <th>Average Price</th>
			</tr>
		    </thead>";

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

    ?>
    <h3>Top and Bottom Movers</h3>
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
            <select name="department">
	    <?php
            while ($row = mysqli_fetch_array($result)) {
                echo "<option value=\"$row[0]\">$row[1]</option>";
            }
	    ?>
	    </select>
	</p>
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
	    <?php
		$quarters = array(
				  array('short' => '1st', 'long' => 'First'),
				  array('short' => '2nd', 'long' => 'Second'),
				  array('short' => '3rd', 'long' => 'Third'),
				  array('short' => '4th', 'long' => 'Fourth')
				 );
		for ($year = 2008; $year <= date('Y'); $year++) {
		    printf('<option value="YTD_%u">Year-To-Date (%u)</option>' . "\n", $year, $year);
		    if ($year != date('Y')) {
			foreach ($quarters AS $qtr => $qtrname)
			    printf('<option value="%squarter_%u">%s Quarter %u</option>' . "\n", $qtrname['short'], $year, $qtrname['long'], $year);
		    } else {
			for ($i = 0; $i <= floor((date('m') - 1) / 3); $i++) {
			    printf('<option value="%squarter_%u">%s Quarter %u</option>' . "\n", $quarters[$i]['short'], $year, $quarters[$i]['long'], $year);
			}
		    }
		}
	    ?>
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
        </form>
	<?php
        include ('../includes/footer.html');
}
?>
