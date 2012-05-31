<?php
/********************************************************************************
 *
 * itemMaint.php - IS4C/Fannie's Item Maintenance Tool
 *
 * Authors: Some guys at the Wedge (CVR?, Tak?, JP?)
 *          Matthaus Litteken (ACG)
 *          Steve Hughes (ACG)
 *
 * This page has gone through multiple iterations as new features are added.
 * There are notes throughout where you can see the development.
 * Basically don't feel bad about tearing this POS up.
 *
 * Protected under the GPL.
 *
 *********************************************************************************/

$page_title = 'Fannie - Item Maintenance';
$header = 'Item Maintenance';
$debug = false;
include('../includes/header.html');
echo "<div id=''>";

//echo '<script type="text/javascript" src="../includes/javascript/jquery.js"></script>'; //- moved to header.html
//echo '<script type="text/javascript" src="../includes/javascript/myquery.js"></script>';
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
/*
    if (!isset($_REQUEST['upc']) || empty($_REQUEST['upc'])) {
		$_REQUEST['upc'] = 1;
	}
*/

    if (isset($_REQUEST['upc']) && !empty($_REQUEST['upc'])) {
        $upc = $_REQUEST['upc'];

	//if ( $upc == 1 ) $where = "upc <= 9999";
	//else
        if (is_numeric($upc)) $where = "upc=$upc";
        else {
            $where = "description LIKE '%" . mysql_real_escape_string($upc) . "%'";
        }
    } else {//if the upc was left blank, list all products

        $upc = "";
	    $where = " (upc <= 9000 OR upc >= 90001 ) ";
    }



       //2010-11-08 - sdh - added the option to filter by 'in use' 
    if (isset($_REQUEST['inUse']) && $_REQUEST['inUse']==1) {	
		$where = $where . " AND inUse=1";
		$antiInUse = 0;
	} else {
		$antiInUse = 1;
	}

        //2010-12-09 - sdh - added an advanced dept filter option
	if (isset($_POST['dept'])) {
	    $selectedDeptArray = $_POST['dept'];

//      	print_r ($_POST['dept']);

		$selectedDeptString = implode(",", $selectedDeptArray);

        $where .= " AND department IN ($selectedDeptString)";
	} else { 
            $selectedDeptArray = array(); 
    }



        $query = "SELECT p.upc, p.description, p.normal_price, p.qttyEnforced, p.foodstamp, p.deposit, p.scale, p.discount, p.discounttype, p.inUse, p.subdept, p.department as department, p.special_price, p.start_date, DATE_FORMAT(p.end_date, '%m/%d/%Y') AS end_date, substr(d.dept_name,1,13) as dept_name
            FROM products as p INNER JOIN departments as d ON p.department = d.dept_no WHERE $where ORDER BY p.department, p.description";
        $result = mysqli_query($db_master, $query) or die ("<p>" . mysqli_error($db_master) . "</p>");

        if ($result) {

            if (mysqli_num_rows($result) == 1) {  // Exact match.

                drawDetailsPage($upc, mysqli_fetch_array($result, MYSQLI_ASSOC));

            } elseif (mysqli_num_rows($result) > 1) {  // More than one match. List.

                //2010-11-08 - sdh - changed this to a table instead of just listing them.

//print_r($_REQUEST);
                echo "<form action=" . $_SERVER['PHP_SELF'] . " method='post'>";
                if ($upc != "") echo "<p><span id='searchTerm'>More than one match found for (<font color='green'>$upc</font>).</span>&nbsp;&nbsp;&nbsp;";
                else echo "<p><span id='searchTerm'>No search term entered. All products returned.</span>";
                if (isset($_REQUEST['inUse']) && $_REQUEST['inUse']==1) {	
                    echo " <input type='submit' name='submit' value='Show Unused Items' />"; 
                    echo "<input type='hidden' name='inUse' value='$antiInUse' />";
                } else {

                    echo " <input type='submit' name='submit' value='Hide Unused Items' />"; 
                    echo "<input type='hidden' name='inUse' value='$antiInUse' />";
                }
$escaped_upc = mysqli_real_escape_string($db_master,$upc);
            echo "testing: " . $escaped_upc;

                echo "<input type='hidden' name='upc' value=\"$upc\" />
                <input type='hidden' name='submitted' value='search' />";

                //create hidden input boxes to hold the values of an array.
                foreach ($selectedDeptArray AS $value) {
		            echo "<input type='hidden' name='dept[]' value='$value' />";
	            }

	            echo "</form>";

                echo '<form action="changeInUse.php" method="post">'; 

		//2011-02-09 - sdh - added <form> and <inputs> so you can maintain item en masse
	            echo "<div id='itemMaint_toolbar'>
    <div class='left'>
        <input type='submit' name='submit' action='changeInUse.php' value='Put In Use' />
        <input type='submit' name='submit' action='changeInUse.php' value='Take Out of Use' />
    </div>
    <div class='right'>
        <input type='button' name='' id='toggleAll' value='Select All' />
    </div>
</div>";


		        echo '<table id="item_results"><tr><th>UPC/PLU</th><th>Description</th><th>Price</th><th>Dept.</th><th>In Use</th><th>Alter?</th></tr>';

                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    echo '<tr class="itemMaint_row"><td class="itemMaint_link"><a href="' . $_SERVER['PHP_SELF'] . '?upc=' 
                        . $row['upc'] . '&submitted=search">'
                        . $row['upc'] . '</a></td><td class="itemMaint_desc">' 
                        . $row['description'] . '</td><td class="itemMaint_price">$' 
                        . number_format($row['normal_price'], 2) . '</td><td class="itemMaint_dept">' 
                        . $row['dept_name'] . '</td><td class="itemMaint_inUse">'
                        . $row['inUse'] . '</td>'
			. "<td class='itemMaint_select'><input class='itemMaint_checkbox' type='checkbox' name='inUse[]' value='" . $row['upc'] . "' /></td>" 
                        . '</tr>';
                
                }
                echo '</table>';
                echo "<br />
<input type='hidden' name='searchTerm' value='$upc' />
";
	            foreach ($selectedDeptArray as $value) {

		            echo "<input type='hidden' name='dept[]' value='$value' />";
	            }   

		        echo "</form>";

                } else { // No match, new product.
                    drawDetailsPage($upc);
                }

            } else {

            // echo "<p>Query: $query</p><p>" . mysqli_error($dbc) . "</p>";
            drawSearchForm('There was an error retrieving the information for that product.');
        }

    /*} else { // Throw an error, quit the script.

	drawSearchForm('You left the search box empty.');

    }taken out bc if nothing is entered then it searches for everything */


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

	//logAction('0001',1);

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
		$bitField += ( (isset($_POST['bitField'][$i]) && $_POST['bitField'][$i] == 'on') ? pow(2, $i) : 0);
	    }
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

	//logAction('0001',2);
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
echo "</div>";
include ('../includes/footer.html');
?>
