<?php # specialOrders.php - Module to help Richard deal with special orders more efficiently.
$debug = true;
$page_title = 'Fannie - Item Maintenance';
$header = 'Special Order Maintenance';
include_once ('../includes/header.html');
require_once ('../includes/mysqli_connect.php');
mysqli_select_db ($db_master, 'is4c_op');

?>
    <link href="../style.css" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript">
	function updateChange(id) {
	    $('#' + id).val('TRUE');
	    //alert('Changing status of:' + id);
	}
    </script>
<?php

if (isset($_POST['submitted'])) {
    if (isset($_POST['dept'])) $dept_no = (int) $_POST['dept'];
    if ($dept_no > 0) {
        $query = "SELECT upc, description, normal_price, foodstamp, deposit, scale, discount, discounttype, DATE_FORMAT(modified, '%m-%d-%Y %r')
            FROM products
            WHERE department = $dept_no AND upc BETWEEN 10000 AND 11000
            ORDER BY upc";
        $result = mysqli_query($db_master, $query);

	if (!$result) {
	    printf('Error: %s, Query: %s', mysqli_error($db_master), $query);
	    exit();
	}

        echo '<h1>Special Orders</h1>
            <form action="specialOrders.php" method="post">
            <table cellspacing="3" cellpadding="3">
                <thead>
		<tr>
		    <th>PLU</th><th>Description</th><th>Price</th><th>Deposit</th><th>Foodstamp</th><th>Scale</th><th>CAP Sale?</th><th>Last Modified (Hover for time)</th>
		</tr>
		</thead>';
        while (list($upc, $desc, $price, $fs, $dep, $scale, $disc, $discType, $modified) = mysqli_fetch_array($result)) {
            printf('<tr>
                    <td>%s</td>
                    <td><input type="text" name="description[%s]" size="20" maxlength="30" value="%s" onchange="updateChange(%u);" /></td>
                    <td align="right"><input type="text" name="price[%s]" size="7" maxlength="6" value="%s" onchange="updateChange(%u);" /></td>
                    <td align="right"><input type="text" name="deposit[%s]" size="5" maxlength="4" value="%s" onchange="updateChange(%u);" /></td>
                    <input type="hidden" name="upc[%s]" value="%s" />
                    <input type="hidden" id="%u" name="update[%s]" value="FALSE" />
                    <td align="center"><input type="checkbox" name="foodstamp[%s]" %s onclick="updateChange(%u);" /></td>
                    <td align="center"><input type="checkbox" name="scale[%s]" %s onclick="updateChange(%u);" /></td>
		    <td align="center"><input type="checkbox" name="capSale[%s]" %s onclick="updateChange(%u);" /></td>
		    <td align="center" title="%s" alt="%s">%s</td>
                </tr>',
		substr($upc, 8, 5), // UPC cell
		$upc, $desc, $upc, // Description cell
		$upc, number_format($price, 2), $upc, // Price cell
		$upc, number_format($dep, 2), $upc, // Deposit cell
		$upc, $upc, // Hidden input #1
		$upc, $upc, // Hidden input #2
		$upc, ($fs == 1 ? ' CHECKED="CHECKED"' : NULL), $upc, // Foodstamp checkbox
		$upc, ($scale == 1 ? ' CHECKED="CHECKED"' : NULL), $upc,  // Scale checkbox
		$upc, ($disc == 1 && $discType == 0 ? ' CHECKED="CHECKED"' : NULL), $upc, // checkbox
		substr($modified, 11, 11), substr($modified, 11, 11), substr($modified, 0, 10) // Modified cell
		);
        }
        echo '</table>
            <input type="hidden" name="changed" value="TRUE" />
            <button name="submit" type="submit">Submit</button>
            </form>';

    } else { // Error
        echo '<div id="box">
            <p>Invalid department, please <font color="red"><u><b><a href="specialOrders.php">try again</a></b></u></font>.</p>
            </div>';
    }
} elseif (isset($_POST['changed'])) {
    $upccount = 0;
    $successcount = 0;
    $errors = array();
    foreach ($_POST["upc"] AS $key => $upc) {
	if (isset($_POST["update"]["$upc"]) && $_POST["update"]["$upc"] == "TRUE") {
	    $upccount++;

	    $scale = (isset($_POST["scale"]["$upc"])) ? 1 : 0;
	    $fs = (isset($_POST["foodstamp"]["$upc"])) ? 1 : 0;
	    $cap = (isset($_POST["capSale"]["$upc"])) ? 'discountType = 0, discount = 1' : 'discountType = 3, discount = 0';
	    is_numeric($_POST["price"]["$upc"]) ? $price = $_POST["price"]["$upc"] : $errors["$upc"][] = "Your price wasn't a number.";
	    !empty($_POST["description"]["$upc"]) ? $description = escape_data($_POST["description"]["$upc"]) : $errors["$upc"][] = "Your description was left empty.";
	    is_numeric($_POST["deposit"]["$upc"]) ? $deposit = $_POST["deposit"]["$upc"] : $errors["$upc"][] = "Your deposit wasn't a number.";

	    if (empty($errors["$upc"])) {
		$query = "UPDATE products SET description = '$description',
		    normal_price = $price,
		    deposit = $deposit,
		    foodstamp = $fs,
		    scale = $scale,
		    $cap,
		    modified=now()
		    WHERE upc=$upc";

		$result = mysqli_query($db_master, $query);
		if ($result) $successcount++;
		else $errors["$upc"][] = sprintf('Error: %s, Query: %s', mysqli_error($db_master), $query);
	    }
	    //echo "<p>Key - $key</p><p>UPC - $upc</p>";
            //echo "<p>UPDATE? - " . $_POST["update"]["$upc"] . "</p>";
	}
    }

    if ($successcount == $upccount) drawForm('Success!');
    elseif (!empty($errors)) {
        $msg = "The following errors occurred: <ul>";
        foreach ($errors AS $upc => $blah) {
            foreach ($errors["$upc"] AS $msg)
            $msg .= sprintf("<li>[UPC# %s]: %s</li>", substr($upc,8,5), $msg);
        }
        $msg .= "</ul>Please try again.";

	drawForm($msg);
    }
    else echo '<p class="error">There were problems updating those prices/descriptions. Try again later or let someone know about this error.</p>';
} else { // Show the form.
    drawForm();
}

include_once ('../includes/footer.html');

function drawForm($msg = NULL) {
    global $db_master;

    $query = "SELECT dept_name, dept_no FROM departments WHERE dept_no BETWEEN 1 AND 16 ORDER BY dept_no";
    $result = mysqli_query($db_master, $query);

    printf('<div id="box"><h2>%s</h2><form action="specialOrders.php" method="post"><p>Which Department?</p>
        <select name="dept"><option value="0">Select a Department</option>' . "\n", $msg);
    while (list($dept_name, $dept_no) = mysqli_fetch_array($result)) {
        printf('<option value="%s">%s</option>' . "\n", $dept_no, ucfirst(strtolower($dept_name)));
    }
    echo '</select><br /><br />
        <button name="next" type="submit">Next</button>
        <input type="hidden" name="submitted" value="TRUE" />
        </form></div>';

}

?>
