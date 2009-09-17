<?php # specialOrders.php - Module to help Richard deal with special orders more efficiently.
$page_title = 'Fannie - Item Maintenance';
$header = 'Special Orders';
include_once ('../includes/header.html');
require_once ('../includes/mysqli_connect.php');
mysqli_select_db ($db_master, 'is4c_op');

if (isset($_POST['submitted'])) {
    if (isset($_POST['dept'])) $dept_no = (int) $_POST['dept'];
    if ($dept_no > 0) {
        $query = "SELECT upc, description, normal_price, foodstamp, deposit, scale, spoDeposit
            FROM products
            WHERE department = $dept_no AND discounttype=3 AND upc BETWEEN 9999 AND 20000
            ORDER BY upc";
        $result = mysqli_query($db_master, $query);
        
        echo '<h1>Here is something to play with...</h1>
            <form action="specialOrders.php" method="post">
            <table cellspacing="3" cellpadding="3">
                <thead>
		<tr>
		    <th>UPC</th><th>Description</th><th>Price</th><th>Deposit</th><th>Foodstamp</th><th>Scale</th><th>Deposit Paid?</th>
		</tr>
		</thead>';
        // onChange="document.getElementByID(\'' . $upc . '\').value=\'TRUE\'" 
        while (list($upc, $desc, $price, $fs, $dep, $scale, $spoDeposit) = mysqli_fetch_array($result)) {
            echo "<tr>
                    <td>" . substr($upc, 8, 5) . "</td>" . 
                    '<td><input type="text" name="description[' . $upc . ']" size="20" maxlength="30" value="' . $desc . '" /></td>
                    <td align="right"><input type="text" name="price[' . $upc . ']" size="7" maxlength="6" value="' . number_format($price, 2) . '" /></td>
                    <td align="right"><input type="text" name="deposit[' . $upc . ']" size="5" maxlength="4" value="' . number_format($dep, 2) . '" /></td>
                    <input type="hidden" name="upc[' . $upc . ']" value="' . $upc . '" />
                    <input type="hidden" id="' . $upc . '" name="update[' . $upc . ']" value="TRUE" />
                    <td align="center"><input type="checkbox" name="foodstamp[' . $upc . ']"';
                    if ($fs == 1) echo ' CHECKED="CHECKED"';
                    echo '/></td>
                    <td align="center"><input type="checkbox" name="scale[' . $upc . ']"';
                    if ($scale == 1) echo ' CHECKED="CHECKED"';
                    echo '/></td>
		    <td align="center"><input type="checkbox" name="spoDep[' . $upc . ']"';
                    if ($spoDeposit == 1) echo ' CHECKED="CHECKED"';
                    echo '/></td>
                </tr>';
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
    $errors = array();
    foreach ($_POST["upc"] AS $key => $upc) {
        $upccount++;
        
        
        $scale = (isset($_POST["scale"]["$upc"])) ? 1 : 0;
        $fs = (isset($_POST["foodstamp"]["$upc"])) ? 1 : 0;
	$SPO = (isset($_POST["spoDep"]["$upc"])) ? 1 : 0;
        is_numeric($_POST["price"]["$upc"]) ? $price = $_POST["price"]["$upc"] : $errors["$upc"][] = "Your price wasn't a number.";
        !empty($_POST["description"]["$upc"]) ? $description = $_POST["description"]["$upc"] : $errors["$upc"][] = "Your description was left empty.";
        is_numeric($_POST["deposit"]["$upc"]) ? $deposit = $_POST["deposit"]["$upc"] : $errors["$upc"][] = "Your deposit wasn't a number.";
        
        if (empty($errors["$upc"])) {
            $query = "UPDATE products SET description = '$description',
                normal_price = $price,
                deposit = $deposit,
                foodstamp = $fs,
                scale = $scale,
		spoDeposit = $SPO
                WHERE upc=$upc";
            
            $result = mysqli_query($db_master, $query);
            if ($result) $successcount++;
        }
        // echo "<p>Key - $key</p><p>UPC - $upc</p>";
        // echo "<p>UPDATE? - " . $_POST["update"]["$upc"] . "</p>";
    }

    if ($successcount == $upccount) echo '<p>Success!</p><h1><a href="specialOrders.php">Edit SPOs for another department?</a></h1>';
    elseif (!empty($errors)) {
        echo "<p>The following errors occurred: </p><ul>";
        foreach ($errors AS $upc => $blah) {
            foreach ($errors["$upc"] AS $msg)
            echo "<li>[UPC# " . substr($upc,8,5) . "]: $msg</li>";
        }
        echo "</ul><p>Please try again.</p>";
    }
    else echo '<p class="error">There were problems updating those prices/descriptions. Try again later or let someone know about this error.</p>';
} else { // Show the form.
    
    $query = "SELECT dept_name, dept_no FROM departments WHERE dept_no BETWEEN 1 AND 16 ORDER BY dept_no";
    $result = mysqli_query($db_master, $query);
    
    echo '<div id="box"><form action="specialOrders.php" method="post"><p>Which Department?</p>
        <select name="dept"><option value="0">Select a Department</option>' . "\n";
    while (list($dept_name, $dept_no) = mysqli_fetch_array($result)) {
        echo '<option value="' . $dept_no . '">' . ucfirst(strtolower($dept_name)) . "</option>\n";
    }
    echo '</select><br /><br />
        <button name="next" type="submit">Next</button>
        <input type="hidden" name="submitted" value="TRUE" />
        </form></div>';
    
}

include_once ('../includes/footer.html');

?>