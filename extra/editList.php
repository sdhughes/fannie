<?php  
	require_once('../includes/mysqli_connect.php');


	global $db_slave;
	mysqli_select_db($db_slave, 'is4c_op');
	//DEBUG
	//print_r($_POST);


if ( isset($_POST['submitted'] )) {


	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}

	if (isset($_POST['dept'])) {
		$deptArray = $_POST['dept'];
	} else {
		$deptArray = array('1,2,3,4,5,6,7,8');
	}
	$deptString = implode(",", $deptArray);

	$tagQ = "SELECT IF(pd.brand IS NULL,'',SUBSTRING(pd.brand,1,20)) AS brand,
            IF(pd.order_no IS NULL,'', pd.order_no) AS sku,
            IF(pd.pack_size IS NULL,'',pd.pack_size) AS size,
            IF(pd.upc IS NULL,'',pd.upc) AS upc,
            IF(pd.product IS NULL, SUBSTRING(p.description,1,25),SUBSTRING(pd.product,1,25)) AS description,
            RIGHT(p.upc,12) AS pid,
            IF(pd.distributor IS NULL, 'Misc', pd.distributor) AS vendor,
            ROUND(normal_price,2) AS normal_price,
            p.scale AS scale
        FROM products AS p LEFT OUTER JOIN product_details AS pd ON p.upc = pd.upc
        WHERE p.department IN($deptString)
        AND p.inUse = 1
        AND p.discounttype <> 3
        AND DATE(modified) BETWEEN '$date1' AND '$date2'
        ORDER BY department";

	echo "query: $tagQ <br/>";
	echo '<p>depts: <br />' . $deptString . '</p>';

	$result = mysqli_query($db_slave, $tagQ) or die("Query Error: " . mysqli_error($db_slave));

 echo "Rows  ". mysqli_num_rows($result) . "<br />";
	?> <form action='shelftags.php' method='post' id='editListForm'>
    <table border="0" cellspacing="3" cellpadding="3">
        <tr>
            <th align="center"> <p><b>Select Department(s)</b></p></th>
            <th><p><b>Select A Tag Style: <select name="type" id="tagType">
                <option value="TINY">HABA Style</option>
                <option value="BIG" SELECTED>Standard Style</option>
		<option value="CIRCLE">Bulk Herbs Tags</option>
		<option value="WINE">Wine Tags</option>
		<option value="BULK">Bulk Tags</option>
            </select></b></p></th>
	    <th class="bulkOptions">
		<p><strong>Tag Type: <select name="tagSize"><option value="0">Wide</option><option value="1">Standard</option></select></strong></p>
	    </th>
	    <th class="bulkOptions">
		<p><strong>Tag Cert: <select name="tagCert"><option value="1">Organic</option><option value="2">Non-Organic</option></select></strong></p>
	    </th>
	    <th class="wineOptions">
		<p><strong>Tag Cert: <select name="wineType"><option value="1">Organic</option><option value="2">Conventional</option></select></strong></p>
	    </th>
        </tr>
    </table>
<?php
echo "	<table><tr>
		<th></th>
		<th>Description</th>
		<th>Label Qty.</th>
		<th>Selected</th>
</tr>";
	while ( $row = mysqli_fetch_row($result) ) {
		
//		echo "<li>" . print_r($row) .  "</li>";
//		echo "<li>" . $row[4] .  "</li>";
//have to make the appropriate form for the shit to be selected and modified

echo "			<tr>
				<td><input type='text' name='' value='$row[3]' ></td>
				<td>$row[4]</td>
				<td><input type='text' name='qtyArray[]' value='1' size='2'></td>
				<td><input type='checkbox' name='upcArray[]' value='$row[3]' checked='checked'></td></tr>";
}
	}

echo "		
		</table>

	<input type='submit' name='submit' value='submitted' />
	<input type='hidden' name='submitted' value='TRUE' />
	<input type='reset' name='reset' value='reset' />
</form>";



 echo "</body></html>";

//end if (submit)



?>
