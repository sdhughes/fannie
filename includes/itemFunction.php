<?php
function drawSearchForm($error = NULL) {
    if (!empty($error)) echo '<h3><font color="red">' . $error . '</font></h3>';
    echo '<BODY onLoad="putFocus(0,0);">
       <form action="' . $_SERVER['PHP_SELF'] . '" method="post">
       <input name="upc" type="text" id="upc" /> Enter UPC/PLU or product name here<br /><br />
       <input name="submit" type="submit" value="Submit" />
       <input name="submitted" type="hidden" value="search" />
       </form>';
    include ('../includes/footer.html');
    exit();
}

function drawDetailsPage($upc, $rowItem = NULL) {
    global $db_master;
    if ($rowItem) {
        $query = "SELECT brand, product, distributor, pack_size, order_no, ingredients, certification, bitField, cost, margin, net_weight, origin, special, tag_type
            FROM product_details WHERE upc={$rowItem['upc']}";
        $result = mysqli_query($db_master, $query);
        $detailRow = mysqli_fetch_array($result, MYSQLI_ASSOC);
    }

    $certQ = "SELECT certID, certDesc FROM certList ORDER BY certID ASC";
    $certR = mysqli_query($db_master, $certQ);
    while (list($certID, $certDesc) = mysqli_fetch_array($certR, MYSQLI_NUM)) {
        $cert[$certID] = $certDesc;
    }

    if (isset($rowItem)) { // Wine specific checkboxes...
	$bitFieldQ = "SELECT fieldIndex, name FROM bitFields WHERE department = {$rowItem['department']} ORDER BY fieldIndex";
	$bitFieldR = mysqli_query($db_master, $bitFieldQ);

	$bitField = sprintf('%b', (int) $detailRow['bitField']);

	for ($i = 1; $i <= strlen($bitField); $i++) {
	   $bitFieldArray[] = substr($bitField, -$i, 1);
	}

	if ($bitFieldR && mysqli_num_rows($bitFieldR) > 0) {
	    $bitFieldCount = mysqli_num_rows($bitFieldR);
	    $bitFieldString = '';
	    while (list($index, $name) = mysqli_fetch_row($bitFieldR)) {
		$bitFieldString .= sprintf('<td align="center"><input type="hidden" name="bitCount" value="%u" /><b>%s</b><br /><input type="checkbox" name="bitField[%u]" %s /></td>',
					   $bitFieldCount, $name, $index, (isset($bitFieldArray[$index]) && $bitFieldArray[$index] == 1 ? 'checked="checked"' : '')
					   );
	    }
	    ?>
	    <script type="text/javascript">
		$(document).ready(function() {
		    $('#bitField').append('<?php echo $bitFieldString; ?>');
		});
	    </script>
	    <?php
	}
    }

    // Depending on if it's new or not, let's make some pretties.
    if (!isset($rowItem) && is_numeric($upc)) $bodyString = '<font color="red">' . str_pad($upc, 13, 0, STR_PAD_LEFT) . '</font><input type="hidden" name="upc" value="' . $upc . '" /></td><td align = "right"><b>Deposit</b></td><td align="right">$&nbsp;<input type="text" name="deposit" size="3" maxlength="6" value="0.00" /></td></tr>
        <tr><td align="right"><b>Description</b></td><td align="left"><input type="text" name="description" size="30" maxlength="30" value="Enter Description Here" /></td><td align="right"><b>Price</b></td><td align="right">$&nbsp;<input type="text" name="price" size="3" maxlength="6" /></td>';
    elseif (!isset($rowItem) && !is_numeric($upc)) $bodyString = '<input type="text" name="upc" size="8" maxlength="13" /></td><td align = "right"><b>Deposit</b></td><td align="right">$&nbsp;<input type="text" name="deposit" size="3" maxlength="6" value="0.00" /></td></tr>
        <tr><td align="right"><b>Description</b></td><td align="left"><input type="text" name="description" size="30" maxlength="30" value="' . $upc . '" /></td><td align="right"><b>Price</b></td><td align="right">$&nbsp;<input type="text" name="price" size="3" maxlength="6" /></td>';
    else $bodyString = '<font color="red">' . $rowItem["upc"] . '</font><input type="hidden" name="upc" value="' . $rowItem["upc"] . '" /></td><td align = "right"><b>Deposit</b></td><td align="right">$&nbsp;<input type="text" name="deposit" size="3" maxlength="6" value="' . number_format($rowItem["deposit"], 2) . '" /></td></tr>
        <tr><td align="right"><b>Description</b></td><td align="left"><input type="text" name="description" size="30" maxlength="30" value="' . htmlentities($rowItem["description"]) . '" /></td><td align="right"><b>Price</b></td><td align="right">$&nbsp;<input type="text" name="price" size="3" maxlength="6" value="' . number_format($rowItem["normal_price"], 2) . '" /></td>';
    echo '<form action="' . $_SERVER['PHP_SELF'] . '" name="pickSubDepartment" method="post">
        <div id="box">
            <table cellspacing="2" cellpadding="2" width="100%">
                <tr>
                    <td align="right"><b>UPC</b></td>
                    <td align="left">' . $bodyString . '
                </tr></table>';

    // Sale info if active...
    if ($rowItem["end_date"] != 0 && $rowItem["special_price"] != 0) {
        echo '<center><font color="green">Item is on sale at $' . number_format($rowItem["special_price"], 2) . ' through ' . $rowItem["end_date"] . '.</font></center>';
    }

    // Define bulk row if necessary...
    if ($rowItem["department"] == 2) {
	$bulkQ = "SELECT tagIndex, name FROM is4c_op.tagTypes WHERE department = 2";
	$bulkR = mysqli_query($db_master, $bulkQ);

	$bulkRows = '<tr>
		    <td align="left"><strong>Origin</strong></td>
		    <td align="left"><input type="text" name="origin" size="25" maxlength="75" value="' . htmlentities($detailRow["origin"]) . '" /></td>
		    <td align="left"><strong>Tag Type</strong></td>
		    <td align="left"><select name="tagType">';

	while (list($no, $name) = mysqli_fetch_row($bulkR)) {
	    $bulkRows .= sprintf('<option value="%u" %s>%s</option>' . "\n", $no, ($detailRow["tag_type"] == $no ? ' SELECTED="SELECTED"' : NULL), $name);
	}

	    $bulkRows .= '</select></td>
		</tr>
		<tr>
		    <td align="left"><strong>Special?</strong></td>
		    <td align="left" colspan="3"><input type="text" name="special" size="50" maxlength="500" value="' . htmlentities($detailRow["special"]) . '" /></td>
		</tr>';
    } else {
	$bulkRows = NULL;
    }

    // Now Subdept, dept, checkboxes, etc.
    echo    '<br />
            <table cellspacing="2" cellpadding="2" width="100%">
                <tr>
                    <th>Department & Sub-Department</th><th>SPO</th><th>FS</th><th>Scale</th><th>QtyFrc</th><th>NoDisc</th><th>In Use</th>
                </tr>
                <tr>
                    <td align="left">';
    chainedSelector($rowItem["department"], $rowItem["subdept"]);
    echo            '</td>
                    <td align="center"><input type="checkbox" ';
    if ($rowItem["discounttype"] == 1) echo 'disabled="true" ';
    echo            'name="SPO" id="SPO"
                        onClick="if (document.getElementById(\'SPO\').checked) document.getElementById(\'NoDisc\').checked=true; else document.getElementById(\'NoDisc\').checked=false;" ';
    if ($rowItem["discounttype"] == 3) echo ' checked="checked"';
    echo ' /></td>
                    <td align="center"><input type="checkbox" name="FS" ';
    if ($rowItem["foodstamp"] == 1) echo ' checked="checked"';
    echo ' /></td>
                    <td align="center"><input type="checkbox" name="scale" id="scale"
                        onClick="if (document.getElementById(\'quantity\').checked && document.getElementById(\'scale\').checked) {
                            alert(\'You cannot have both scale and quantity enforced set at once.\'); this.checked=false;}" ';
    if ($rowItem["scale"] == 1) echo ' checked="checked"';
    echo ' /></td>
                    <td align="center"><input type="checkbox" name="quantity" id="quantity"
                        onClick="if (document.getElementById(\'quantity\').checked && document.getElementById(\'scale\').checked) {
                            alert(\'You cannot have both scale and quantity enforced set at once.\'); this.checked=false;}" ';
    if ($rowItem["qttyEnforced"] == 1) echo ' checked="checked"';
    echo ' /></td>
                    <td align="center"><input type="checkbox" name="nodisc" id="NoDisc" ';
    if (isset($rowItem["discount"]) && $rowItem["discount"] == 0) echo ' checked="checked"';
    echo ' /></td>
                    <td align="center"><input type="checkbox" name="inUse" ';
    if (isset($rowItem["inUse"]) && $rowItem["inUse"] == 0) echo '';
    else echo ' checked="checked"';
    echo ' /></td>
                </tr>
            </table><br />
            <p><center>
            <table cellspacing="2" cellpadding="2">
                <tr>
                    <td align="left"><b>Brand Name</b></td>
                    <td align="left"><input type="text" name="brand" size="25" maxlength="30" value="' . htmlentities($detailRow["brand"]) . '" /></td>
                    <td align="left"><b>Pack Size</b></td>
                    <td align="left"><input type="text" name="pack_size" size="15" maxlength="20" value="' . htmlentities($detailRow["pack_size"]) . '" /></td>
                </tr>
                <tr>
                    <td align="left"><b>Product Name</b></td>
                    <td align="left"><input type="text" name="product" size="25" maxlength="25" value="' . htmlentities($detailRow["product"]) . '" /></td>
                    <td align="left"><b>Order Number</b></td>
                    <td align="left"><input type="text" name="order_no" size="20" maxlength="20" value="' . htmlentities($detailRow["order_no"]) . '" /></td>
                </tr>
                <tr>
                    <td align="left"><b>Distributor</b></td>
                    <td align="left"><input type="text" name="distributor" size="20" maxlength="20" value="' . htmlentities($detailRow["distributor"]) . '" /></td>
                    <td align="left"><strong>Net Wt (in oz)</strong></td>
		    <td align="left"><input type="text" name="net_weight" size="10" maxlength="10" value="' . htmlentities($detailRow["net_weight"]) . '" /></td>
                </tr>
		<tr>
		    <td align="left"><strong>Cost</strong></td>
		    <td align="left"><input type="text" name="cost" size="10" maxlength="10" value="' . htmlentities($detailRow["cost"]) . '" /></td>
		    <td align="left"><strong>Margin</strong></td>
		    <td align="left"><input type="text" name="margin" size="5" maxlength="5" value="' . htmlentities($detailRow["margin"]) . '" /></td>
		</tr>
		<tr>
		    <td align="left"><strong>Recommended Price</strong></td>
		    <td align="left"><strong>$' . number_format($detailRow["cost"] / (1 - ($detailRow["margin"]/100)), 2) . '</strong></td>
		    <td align="left"><strong>Current Markup</strong></td>
		    <td align="left"><strong>' . number_format((($rowItem['normal_price'] - $detailRow['cost'])/$rowItem['normal_price']) * 100,2) . '%</strong></td>
		</tr>
		<tr id="bitField"></tr>' . $bulkRows . '

                <tr class="extraDetail">
                    <td align="left" colspan="2"><b>Ingredients</b></td>
                    <td align="left"><b>Certification</b></td>
                    <td align="left">
                        <select name="cert">
                            <option value="0">Pick one...</option>';
                            foreach ($cert AS $certID => $certDesc) printf('<option value="%s"%s>%s</option>', $certID, ($detailRow["certification"] == $certID) ? ' SELECTED="SELECTED"' : NULL, $certDesc);
        echo '          </select>
                    </td>
                </tr>
                <tr class="extraDetail">
                    <td colspan="4"><textarea name="ingredients" cols="80" rows="5">' . htmlentities($detailRow["ingredients"]) . '</textarea></td>
                </tr>
            </table><br />
            <center><button name="submit" type="submit">Submit!</button></center>
            <input type="hidden" name="submitted" value="act" />' . "\n";
        if (isset($rowItem)) echo '<input type="hidden" name="action" value="update" />' . "\n";
        elseif (!isset($rowItem)) echo '<input type="hidden" name="action" value="insert" />' . "\n";
        if (isset($detailRow)) echo '<input type="hidden" name="subAction" value="update" />' . "\n";
        elseif (!isset($detailRow)) echo '<input type="hidden" name="subAction" value="insert" />' . "\n";
        echo '</div>
        </form>';

	/* Local drop down...
	    <td align="left"><b>Local?</b></td>
	    <td align="left">
		<select name="local" disabled="true">
		    <option>Coming soon...stay tuned.</option>
		    <option value="0">Is it special?</option>
		    <option value="1">Regional</option>
		    <option value="2">Local</option>
		    <option value="3">Superlocal</option>
		</select>
	    </td>
	*/

}

function chainedSelector($department = NULL, $subdepartment = NULL) {
    /**
    **	BEGIN CHAINEDSELECTOR CLASS
    **/
    require("../src/chainedSelectors.php");

    global $db_master;

    $selectorNames = array(
        CS_FORM=>"pickSubDepartment",
        CS_FIRST_SELECTOR=>"department",
        CS_SECOND_SELECTOR=>"subdepartment");

    $Query = "SELECT d.dept_no AS dept_no,d.dept_name AS dept_name,s.subdept_no AS subdept_no,s.subdept_name AS subdept_name
        FROM is4c_op.departments AS d, is4c_op.subdepts AS s
        WHERE d.dept_no = s.dept_ID
        ORDER BY d.dept_no, s.subdept_no";

    if (!($DatabaseResult = mysqli_query($db_master, $Query))) {
        print("The query failed!<br>\n");
        exit();
    }
    while ($row = mysqli_fetch_object($DatabaseResult)) {
        $selectorData[] = array(
            CS_SOURCE_ID=>$row->dept_no,
            CS_SOURCE_LABEL=>ucfirst(strtolower($row->dept_name)),
            CS_TARGET_ID=>$row->subdept_no,
            CS_TARGET_LABEL=>$row->subdept_name);
    }

    $subdept = new chainedSelectors(
    $selectorNames,
    $selectorData);
    ?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html40/loose.dtd">
    <html>
    <head>
    <script type="text/javascript" language="JavaScript">
    <?php
    $subdept->printUpdateFunction($department, $subdepartment);
    ?>
    </script>
    </head>
    <body>
    <?php
    $subdept->printSelectors($department);
    ?>
    <script type="text/javascript" language="JavaScript">
    <?php
    $subdept->initialize();
    ?>
    </script>
    </body>
    </html>
    <?php
    /**
    **	CHAINEDSELECTOR CLASS ENDS . . . . . . . NOW
    **/
    // echo '<p>' . $department . '</p>Is it there?<p>' . $subdepartment . '</p>';
}

?>
