<?php
/********************************************************************************
*
*
*
*********************************************************************************/

$page_title = 'Fannie - Item Maintanence';
$header = 'Item Maintanence';
$debug = true;
include('../includes/header.html');
echo '<script type="text/javascript" src="../includes/javascript/jquery.js"></script>';
require_once('../includes/itemFunction.php');
?>
<html>
<head>
<SCRIPT LANGUAGE="JavaScript">

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->
<!-- John Munn  (jrmunn@home.com) -->

<!-- Begin
function putFocus(formInst, elementInst) {
    if (document.forms.length > 0) {
        document.forms[formInst].elements[elementInst].focus();
    }
}
// The second number in the "onLoad" command in the body
// tag determines the form's focus. Counting starts with '0'
//  End -->
</script>
</head>

<?php
require_once ('../includes/mysqli_connect.php'); // Connect to the database.
mysqli_select_db ($db_master, 'is4c_op');

if (isset($_REQUEST['submitted']) && $_REQUEST['submitted'] == 'search') { // On form submission or list link clicking...

    if (isset($_REQUEST['upc']) && !empty($_REQUEST['upc'])) {
        $upc = $_REQUEST['upc'];

        if (is_numeric($upc)) $where = "upc=$upc";
        else $where = "description LIKE '%$upc%'";

        $query = "SELECT upc, description, normal_price, qttyEnforced, foodstamp, deposit, scale, discount, discounttype, inUse, subdept, department, special_price, start_date, DATE_FORMAT(end_date, '%m/%d/%Y') AS end_date
            FROM products WHERE $where";
        $result = mysqli_query($db_master, $query);

        if ($result) {

            if (mysqli_num_rows($result) == 1) {  // Exact match.

                drawDetailsPage($upc, mysqli_fetch_array($result, MYSQLI_ASSOC));

            } elseif (mysqli_num_rows($result) > 1) {  // More than one match. List.

                echo "<h3>More than one match found for (<font color='green'>$upc</font>).</h3>
                    <h3>Choose one of the following:</h3><br />";

                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

                    echo '<a href="' . $_SERVER['PHP_SELF'] . '?upc=' . $row['upc'] . '&submitted=search">'
                        . $row['upc'] . '</a> - ' . $row['description'] . ' - $' . number_format($row['normal_price'], 2) . '<br />';

                }

                echo '<br />';

            } else { // No match, new product.
                drawDetailsPage($upc);
            }

        } else {

            // echo "<p>Query: $query</p><p>" . mysqli_error($dbc) . "</p>";
            drawSearchForm('There was an error retrieving the information for that product.');

        }

    } else { // Throw an error, quit the script.

	drawSearchForm('You left the search box empty.');

    }


} elseif (isset($_REQUEST['submitted']) && $_REQUEST['submitted'] == 'act') {
    // Update or insert for main info...
    $errors = array();

    if (isset($_POST['action']) && $_POST['action'] == 'insert') {
        // Error checking...data validation...
        if (!empty($_POST['upc']) && is_numeric($_POST['upc']) && $_POST['upc'] <= 99999999999999) $upc = escape_data($_POST['upc']);
        else $errors[] = "The UPC must be numeric (and less than or equal to 13 digits). Numbers!!";

        if (!empty($_POST['description']) && strlen($_POST['description']) <= 30) $description = escape_data($_POST['description']);
        else $errors[] = "The description can't be empty and must be less than or equal to 30 characters.";

        if (!empty($_POST['price']) && is_numeric($_POST['price']) && $_POST['price'] <= 1000) $price = escape_data(number_format($_POST['price'], 2));
        else $errors[] = "The price must be a number. Really.";

        if (!empty($_POST['deposit']) && is_numeric($_POST['deposit']) && $_POST['deposit'] <= 50) $deposit = escape_data(number_format($_POST['deposit'], 2));
        else $errors[] = "The deposit must be a number. Really.";

        if (is_numeric($_POST['department'])) $department = escape_data($_POST['department']);
        else $errors[] = "Seriously, what are you doing?";

        if (is_numeric($_POST['subdepartment'])) $subdepartment = escape_data($_POST['subdepartment']);
        else $errors[] = "Seriously, what are you doing?";

        $SPO = (isset($_POST['SPO']) && $_POST['SPO'] == "on") ? 3 : 0;
        $fs = (isset($_POST['FS']) && $_POST['FS'] == "on") ? 1 : 0;
        $scale = (isset($_POST['scale']) && $_POST['scale'] == "on") ? 1 : 0;
        $qty = (isset($_POST['quantity']) && $_POST['quantity'] == "on") ? 1 : 0;
        $nodisc = (isset($_POST['nodisc']) && $_POST['nodisc'] == "on") ? 0 : 1;
        $inUse = (isset($_POST['inUse']) && $_POST['inUse'] == "on") ? 1 : 0;

        $mainQ = "INSERT INTO products (upc, description, normal_price, pricemethod, groupprice, quantity, special_price, specialpricemethod, specialgroupprice, specialquantity, start_date, end_date, department, size, tax, foodstamp, scale, mixmatchcode, modified, advertised, tareweight, discount, discounttype, unitofmeasure, wicable, deposit, qttyEnforced, inUse, subdept) VALUES
                ($upc, '$description', $price, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, $department, 0, 0, $fs, $scale, 0, now(), 0, 0, $nodisc, $SPO, 0, 0, $deposit, $qty, $inUse, $subdepartment)";

    } elseif (isset($_POST['action']) && $_POST['action'] == 'update') {
        // Error checking...data validation...
        if (!empty($_POST['upc']) && is_numeric($_POST['upc']) && $_POST['upc'] <= 99999999999999) $upc = escape_data($_POST['upc']);
        else $errors[] = "The UPC must be numeric (and less than or equal to 13 digits). Numbers!!";

        if (!empty($_POST['description']) && strlen($_POST['description']) <= 30) $description = escape_data($_POST['description']);
        else $errors[] = "The description can't be empty and must be less than or equal to 30 characters.";

        if (!empty($_POST['price']) && is_numeric($_POST['price']) && $_POST['price'] <= 1000) $price = escape_data(number_format($_POST['price'], 2));
        else $errors[] = "The price must be a number. Really.";

        if (!empty($_POST['deposit']) && is_numeric($_POST['deposit']) && $_POST['deposit'] <= 50) $deposit = escape_data(number_format($_POST['deposit'], 2));
        else $errors[] = "The deposit must be a number. Really.";

        if (is_numeric($_POST['department'])) $department = escape_data($_POST['department']);
        else $errors[] = "Seriously, what are you doing?";

        if (is_numeric($_POST['subdepartment'])) $subdepartment = escape_data($_POST['subdepartment']);
        else $errors[] = "Seriously, what are you doing?";

        $SPO = (isset($_POST['SPO']) && $_POST['SPO'] == "on") ? ' discounttype = 3, ' : NULL;
        $fs = (isset($_POST['FS']) && $_POST['FS'] == "on") ? 1 : 0;
        $scale = (isset($_POST['scale']) && $_POST['scale'] == "on") ? 1 : 0;
        $qty = (isset($_POST['quantity']) && $_POST['quantity'] == "on") ? 1 : 0;
        $nodisc = (isset($_POST['nodisc']) && $_POST['nodisc'] == "on") ? 0 : 1;
        $inUse = (isset($_POST['inUse']) && $_POST['inUse'] == "on") ? 1 : 0;

        $mainQ = "UPDATE products SET
                    description = '$description',
                    normal_price = $price,
                    department = $department,
                    foodstamp = $fs,
                    scale = $scale,
                    modified = now(),
                    discount = $nodisc,
                    $SPO
                    deposit = $deposit,
                    qttyEnforced = $qty,
                    inUse = $inUse,
                    subdept = $subdepartment
                WHERE upc=$upc";
    }

    // Update or insert for extra details...
    if (isset($_POST['subAction']) && $_POST['subAction'] == 'insert') {
        // Error checking...data validation...
        if (!empty($_POST['brand'])) $brand = "'" . escape_data($_POST['brand']) . "'";
        else $brand = 'NULL';

        if (!empty($_POST['pack_size'])) $pack_size = "'" . escape_data($_POST['pack_size']) . "'";
        else $pack_size = 'NULL';

        if (!empty($_POST['product'])) $product = "'" . escape_data($_POST['product']) . "'";
        else $product = 'NULL';

        if (!empty($_POST['order_no'])) $order_no = "'" . escape_data($_POST['order_no']) . "'";
        else $order_no = 'NULL';

        if (!empty($_POST['distributor'])) $distributor = "'" . escape_data($_POST['distributor']) . "'";
        else $distributor = 'NULL';

        if (!empty($_POST['ingredients'])) $ingredients = "'" . escape_data($_POST['ingredients']) . "'";
        else $ingredients = 'NULL';

        if (is_numeric($_POST['cert'])) $cert = (int)$_POST['cert'];
        else $cert = 'NULL';

	if (isset($_POST['bitField'])) {
	    foreach ($_POST['bitField'] AS $index => $value) {
		echo $index . " " . $value . "\n";
	    }
	}

	if (!empty($_POST['origin'])) $origin = "'" . escape_data($_POST['origin']) . "'";
        else $origin = 'NULL';

	if (!empty($_POST['special'])) $special = "'" . escape_data($_POST['special']) . "'";
        else $special = 'NULL';

	if (!empty($_POST['cost']) && is_numeric($_POST['cost'])) $cost = escape_data($_POST['cost']);
        else $cost = 'NULL';

	if (!empty($_POST['margin']) && is_numeric($_POST['margin'])) $margin = escape_data($_POST['margin']);
        else $margin = 'NULL';

	if (!empty($_POST['net_weight']) && is_numeric($_POST['net_weight'])) $net_weight = escape_data($_POST['net_weight']);
        else $net_weight = 'NULL';

	if (isset($_POST['tagType']) && ($_POST['tagType'] === 0 || is_numeric($_POST['tagType']))) $tagType = escape_data($_POST['tagType']);
	else $tagType = 'NULL';

        $detailQ = "INSERT INTO product_details (upc, brand, product, distributor, pack_size, order_no, ingredients, certification, origin, special, cost, margin, net_weight, tag_type) VALUES
                    ($upc, $brand, $product, $distributor, $pack_size, $order_no, $ingredients, $cert, $origin, $special, $cost, $margin, $net_weight, $tagType)";

    } elseif (isset($_POST['subAction']) && $_POST['subAction'] == 'update') {
        // Error checking...data validation...
        if (!empty($_POST['brand'])) $brand = "'" . escape_data($_POST['brand']) . "'";
        else $brand = 'NULL';

        if (!empty($_POST['pack_size'])) $pack_size = "'" . escape_data($_POST['pack_size']) . "'";
        else $pack_size = 'NULL';

        if (!empty($_POST['product'])) $product = "'" . escape_data($_POST['product']) . "'";
        else $product = 'NULL';

        if (!empty($_POST['order_no'])) $order_no = "'" . escape_data($_POST['order_no']) . "'";
        else $order_no = 'NULL';

        if (!empty($_POST['distributor'])) $distributor = "'" . escape_data($_POST['distributor']) . "'";
        else $distributor = 'NULL';

        if (!empty($_POST['ingredients'])) $ingredients = "'" . escape_data($_POST['ingredients']) . "'";
        else $ingredients = 'NULL';

        if (is_numeric($_POST['cert'])) $cert = (int)$_POST['cert'];
        else $cert = 'NULL';

	if (!empty($_POST['origin'])) $origin = "'" . escape_data($_POST['origin']) . "'";
        else $origin = 'NULL';

	if (!empty($_POST['special'])) $special = "'" . escape_data($_POST['special']) . "'";
        else $special = 'NULL';

	if (!empty($_POST['cost']) && is_numeric($_POST['cost'])) $cost = escape_data($_POST['cost']);
        else $cost = 'NULL';

	if (!empty($_POST['margin']) && is_numeric($_POST['margin'])) $margin = escape_data($_POST['margin']);
        else $margin = 'NULL';

	if (!empty($_POST['net_weight']) && is_numeric($_POST['net_weight'])) $net_weight = escape_data($_POST['net_weight']);
        else $net_weight = 'NULL';

	if (isset($_POST['tagType']) && ($_POST['tagType'] === 0 || is_numeric($_POST['tagType']))) $tagType = escape_data($_POST['tagType']);
	else $tagType = 'NULL';

	$bitField = 0;

	if (isset($_POST['bitField']) && isset($_POST['bitCount'])) {
	    for ($i = 0; $i < (int)($_POST['bitCount']); $i++) {
		$bitField += ($_POST['bitField'][$i] == 'on' ? pow(2, $i) : 0);
	    }
	    //echo $bitField;
	}

	$detailQ = "UPDATE product_details SET
                        brand = $brand,
                        product = $product,
                        distributor = $distributor,
                        pack_size = $pack_size,
                        order_no = $order_no,
                        ingredients = $ingredients,
                        certification = $cert,
			bitField = $bitField,
			origin = $origin,
			special = $special,
			cost = $cost,
			margin = $margin,
			net_weight = $net_weight,
			tag_type = $tagType
                    WHERE upc = $upc";

    }

    // Now if no errors, run the queries...
    if (empty($errors)) {
        $mainR = mysqli_query($db_master, $mainQ);
        if (!$mainR || mysqli_affected_rows($db_master) != 1) {
            echo "<p>Error!!!</p>
                    <p>Query: $mainQ</p>
                    <p>MySQL Error: " . mysqli_error($db_master) . "</p>";
            include ('../includes/footer.html');
            exit();
        } else {
            $detailR = mysqli_query($db_master, $detailQ);
            if (!$detailR) {
                echo "<p>Error!!!</p>
                    <p>Query: $detailQ</p>
                    <p>MySQL Error: " . mysqli_error($db_master) . "</p>";
                include ('../includes/footer.html');
                exit();
            }
        }
    } else {
        echo '<h3>Sorry, the following errors were noticed...</h3><ul>';
        foreach ($errors as $msg) {
            echo "<li>$msg</li>";
        }
        echo "</ul><p>Please <a href=\"{$_SERVER['PHP_SELF']}?upc=$upc&submitted=search\">try again</a>.</p>";
        include ('../includes/footer.html');
        exit();
    }

    drawSearchForm('Product Was Added/Edited Successfully');

} else { // Show the form.

    drawSearchForm();

}

include ('../includes/footer.html');
?>
