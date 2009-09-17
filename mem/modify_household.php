<?php # Household Editing Page
$page_title = 'Fannie - Membership Module';
$header = 'Edit A Household';
include ('../includes/header.html');
// A page to view and edit a household's details.
$page_title = 'Edit a Household';
include ('./includes/header.html');

// Check for a valid user ID, through GET or POST.

if ( (isset($_GET['cardno'])) && (is_numeric($_GET['cardno'])) ) { // Accessed through view_users.php.
	$cn = $_GET['cardno'];
} elseif ( (isset($_POST['cardno'])) && (is_numeric($_POST['cardno'])) ) { // Accessed through form submission.
	$cn = $_POST['cardno'];
} else { // No valid Card Number, ask for one.
	echo '<form action="modify_household.php" method="post"><br /><br />
	<h3><center>Which household would you like to modify?</center></h3>
	<h3><center><input type="text" name="cardno" size="4" maxlength="4" /></center><br /><br /></h3>
	<center><input type="submit" name="submit" value="Submit!" /></center>
	</form>';
	include ('./includes/footer.html');
	include ('../includes/footer.html');
	exit();
	
}

require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_master, 'is4c_op');
mysqli_select_db($db_slave, 'is4c_op');


if (isset($_POST['label'])) { // Update household modified date.
	$query = "UPDATE custdata SET modified = now() WHERE cardno=$cn";
	$result = mysqli_query($db_master, $query);
	if ($result) echo '<p>Household number ' . $cn . ' has been added to the next label batch.</p>';
	else echo '<p><font color="red">The household could not be added to the next label batch due to a system error. Please try again later.</font></p>';
}

if (isset($_POST['submitted'])) { // If the form has been submitted, check the new data and update the record.
	
	// Initialize the errors array.
	$errors = array();
	
	// How many records are we editing?
	if ((isset($_POST['ps'])) && (isset($_POST['ss']))) { // Both Primary and Secondary. 
		if (is_numeric($_POST['ssid'])) { 
			$num_records = 2;
		} elseif ($_POST['ssid'] = 'insert') { // Mixed Update and Insert
			$num_records = 'mixed';
		}
		// Validate the form data.
		if ((empty($_POST['ps_first_name'])) || (empty($_POST['ss_first_name']))){
			
			$errors[] = 'You left one or both of their first names blank.';
			
		} else {
			$psfn = escape_data($_POST['ps_first_name']); // Store the first names.
			$ssfn = escape_data($_POST['ss_first_name']); // Store the first names.
		}
		
		if ((empty($_POST['ps_last_name'])) || (empty($_POST['ss_last_name']))){
			
			$errors[] = 'You left one or both of their last names blank.';
			
		} else {
			$psln = escape_data($_POST['ps_last_name']); // Store the last names.
			$ssln = escape_data($_POST['ss_last_name']); // Store the last names.
		}
		if (!isset($_POST['ps_checks_ok'])) {$_POST['ps_checks_ok'] = 'off';}
		if (($_POST['ps_checks_ok']) == 'on') {$psWriteCheck = 1;} else {$psWriteCheck = 0;}
		if (!isset($_POST['ss_checks_ok'])) {$_POST['ss_checks_ok'] = 'off';}
		if (($_POST['ss_checks_ok']) == 'on') {$ssWriteCheck = 1;} else {$ssWriteCheck = 0;}
		if (!isset($_POST['ps_charge_ok'])) {$_POST['ps_charge_ok'] = 'off';}
		if (!isset($_POST['ss_charge_ok'])) {$_POST['ss_charge_ok'] = 'off';}
		if (($_POST['ps_charge_ok'] == 'on') && (($_POST['ps_staff'] == 0) || ($_POST['ps_staff'] == 3) || ($_POST['ps_staff'] == 4) || ($_POST['ps_staff'] == 6))) {
			$errors[] = 'Non-staff members cannot house charge.';
		} else {
			if ($_POST['ps_charge_ok'] == 'on') {$pscharge = 1; $pslimit = 9999;}
			else {$pscharge = 0; $pslimit=0;}
		}
		if (($_POST['ss_charge_ok'] == 'on') && (($_POST['ss_staff'] == 0) || ($_POST['ss_staff'] == 3) || ($_POST['ss_staff'] == 4) || ($_POST['ss_staff'] == 6))) {
			$errors[] = 'Non-staff members cannot house charge.';
		} else {
			if ($_POST['ss_charge_ok'] == 'on') {$sscharge = 1; $sslimit = 9999;}
			else {$sscharge = 0; $sslimit=0;}
		}
		if ((!is_numeric($_POST['ps_discount'])) || (!is_numeric($_POST['ss_discount']))) {
			
			$errors[] = 'You left one of the discounts blank.';
		
		} else {
			$psd = escape_data($_POST['ps_discount']); // Store the discounts.
			$ssd = escape_data($_POST['ss_discount']); // Store the discounts.
		}
		$psmemtype = $_POST['ps_memtype'];
		$psstaff = $_POST['ps_staff'];
		if ($psstaff == 6) {$psType='reg';} else {$psType='pc';}
		$ssmemtype = $_POST['ss_memtype'];
		$ssstaff = $_POST['ss_staff'];
		if ($ssstaff == 6) {$ssType='reg';} else {$ssType='pc';}

		
	} elseif ((isset($_POST['ps'])) && (!isset($_POST['ss']))) {// Only a primary.
		// Validate the form data.
		$num_records = 1;
		if (empty($_POST['ps_first_name'])) {
			
			$errors[] = 'You left their first name blank.';
			
		} else {
			$psfn = escape_data($_POST['ps_first_name']); // Store the first name.
		}
		
		if (empty($_POST['ps_last_name'])) {
			
			$errors[] = 'You left their last name blank.';
			
		} else {
			$psln = escape_data($_POST['ps_last_name']); // Store the last name.
			
		}
		if (!isset($_POST['ps_checks_ok'])) {$_POST['ps_checks_ok'] = 'off';}
		if (($_POST['ps_checks_ok']) == 'on') {$psWriteCheck = 1;} else {$psWriteCheck = 0;}
		if (!isset($_POST['ps_charge_ok'])) {$_POST['ps_charge_ok'] = 'off';}
		if (($_POST['ps_charge_ok'] == 'on') && (($_POST['ps_staff'] == 0) || ($_POST['ps_staff'] == 3) || ($_POST['ps_staff'] == 4) || ($_POST['ps_staff'] == 6))) {
			$errors[] = 'Non-staff members cannot house charge.';
		} else {
			if ($_POST['ps_charge_ok'] == 'on') {$pscharge = 1;}
			else {$pscharge = 0;}
		}
		if (!is_numeric($_POST['ps_discount'])) {
			
			$errors[] = 'You must enter a number here.';
		
		} else {
			$psd = escape_data($_POST['ps_discount']); // Store the discount.
			
		}
		$psmemtype = $_POST['ps_memtype'];
		$psstaff = $_POST['ps_staff'];
		if ($psstaff == 6) {$psType='reg';} else {$psType='pc';}
		if ($pscharge == 1) {$pslimit=9999;} else {$pslimit=0;}
	}
	
		if (isset($_POST['phone1']) && ($_POST['phone1'] <= 999)) {
		$ph1 = (int)(escape_data($_POST['phone1']));
		$ph1 = str_pad($ph1, 3, '0', STRPADLEFT);
	} else {
		$ph1 = NULL;
	}
	
	if (isset($_POST['phone2']) && ($_POST['phone2'] <= 999)) {
		$ph2 = (int)(escape_data($_POST['phone2']));
		$ph2 = str_pad($ph2, 3, '0', STRPADLEFT);
	} else {
		$ph2 = NULL;
	}
	
	if (isset($_POST['phone3']) && ($_POST['phone3'] <= 9999)) {
		$ph3 = (int)(escape_data($_POST['phone3']));
		$ph3 = str_pad($ph3, 4, '0', STRPADLEFT);
	} else {
		$ph3 = NULL;
	}
		
	if (($ph1 && $ph2 && $ph3) && (strlen($ph1 . $ph2 . $ph3) == 10)) {
		$phone = $ph1 . $ph2 . $ph3;
		// echo $phone;
	} else {
		$errors[] = 'You left their phone number blank or something else is happening.';
	}
	
	if (empty($errors)) {
		$psid = $_POST['psid'];
		$ssid = $_POST['ssid'];
		switch ($num_records) {
			case '2':
				$query1 = "UPDATE custdata SET FirstName='$psfn', LastName='$psln', WriteChecks=$psWriteCheck, discount=$psd, memType=$psmemtype,
						Type='$psType', staff=$psstaff, ChargeOk=$pscharge, memDiscountLimit=$pslimit, phone=$phone
						WHERE id=$psid";
				$query2 = "UPDATE custdata SET FirstName='$ssfn', LastName='$ssln', WriteChecks=$ssWriteCheck, discount=$ssd, memType=$ssmemtype,
						Type='$ssType', staff=$ssstaff, ChargeOk=$sscharge, memDiscountLimit=$sslimit, phone=$phone
						WHERE id=$ssid";
				$result1 = mysqli_query($db_master, $query1);
				$affected = mysqli_affected_rows($db_master);
				$result2 = mysqli_query($db_master, $query2);
				$affected += mysqli_affected_rows($db_master);

			break;
			
			case '1':
				$query1 = "UPDATE custdata SET FirstName='$psfn', LastName='$psln', WriteChecks=$psWriteCheck, discount=$psd, memType=$psmemtype,
						Type='$psType', staff=$psstaff, ChargeOk=$pscharge, memDiscountLimit=$pslimit, phone=$phone
						WHERE id=$psid";
				$result1 = mysqli_query($db_master, $query1);
				$affected = mysqli_affected_rows($db_master);
			break;

			case 'mixed':
				$query1 = "UPDATE custdata SET FirstName='$psfn', LastName='$psln', WriteChecks=$psWriteCheck, discount=$psd, memType=$psmemtype,
					Type='$psType', staff=$psstaff, ChargeOk=$pscharge, memDiscountLimit=$pslimit, phone=$phone
					WHERE id=$psid";
				$query2 = "INSERT INTO custdata
					(CardNo, FirstName, LastName, WriteChecks, discount, memType, Type, staff, ChargeOk, memDiscountLimit, personNum, phone)
					VALUES
					($cn, '$ssfn', '$ssln', $ssWriteCheck, $ssd, $ssmemtype, '$ssType', $ssstaff, $sscharge, $sslimit, 2, $phone)";
				$result1 = mysqli_query($db_master, $query1);
				$affected = mysqli_affected_rows($db_master);
				$result2 = mysqli_query($db_master, $query2);
				$affected += mysqli_affected_rows($db_master);

			break;
		}

			
		if ($affected != 0) { // If the query was successful.
				
			echo '<h1 id="mainhead">Edit a Household</h1>
			<p>The household has been edited.</p><p><br /><br /></p>';
				
		} else { // The query was unsuccessful.
				
			echo '<h1 id="mainhead">System Error</h1>
			<p class="error">There are two possibilities:<br />
			<b>1.)</b> The household could not be edited due to a system error.<br />
			<b>2.)</b> Nothing was changed.</p>';
			// echo '<p>' . mysql_error() . '<br /><br />Query: ' . $query1 . '</p>';

		}
	} else { // Report the errors.
		
		echo '<h1 id="mainhead">Error!!</h1>
		<p class="error">The following error(s) occurred:<br />';
		foreach ($errors as $msg) { // Print each error.
			echo " - $msg<br />\n";
		}
		echo '</p><p>Please try again.</p><p><br /></p>';
			
	} // End of if (empty($errors)) IF.
		
} // End of submit conditional.

// Always show the form.

// Retrieve the user's information.
$query = "SELECT * FROM custdata WHERE cardno=$cn ORDER BY personNum ASC";
$result = mysqli_query($db_slave, $query);

$num_rows = mysqli_num_rows($result);
if (($num_rows == 2)) {  // Valid id show the form.
	echo '<h2>Edit a Household.</h2>
	<h3>Card Number: ' . $cn . '</h3>';
	// Get the user's information.
	echo '<form action="modify_household.php" method="post">
	<button type="submit" name="label">Include this Household in the next label batch</button>
	<input type="hidden" value="' . $cn . '" name="cardno">
	</form>';
	echo '<form action="delete_member.php" method="post">
	<button type="submit" name="delete">Delete this Household</button>
	<input type="hidden" value="' . $cn . '" name="cardno">
	</form>
	<form action="modify_household.php" method="post">';
	
	while ($row = mysqli_fetch_array($result)) {
		
		$query2 = "SELECT staff_no, staff_desc FROM staff ORDER BY staff_no ASC";
		$query3 = "SELECT memtype, memDesc FROM memtype ORDER BY memtype ASC";
		$result2 = mysqli_query($db_slave, $query2);
		$result3 = mysqli_query($db_slave, $query3);
		if ($row['personNum'] == 1) {$position = 'ps_'; $title = 'Primary Shareholder';} elseif ($row['personNum'] == 2) {$position = 'ss_'; $title = 'Secondary Shareholder';}
		// Create the form.
		if ($row["ChargeOk"] == 1) {$ChargeOk[$position] = ' CHECKED';} else {$ChargeOk[$position] = '';}
		if ($row["WriteChecks"] == 1) {$ChecksOk[$position] = ' CHECKED';} else {$ChecksOk[$position] = '';}
		if ($row["personNum"] == 1 && (!is_null($row["phone"]))) {
			echo '<p>Phone Number: (<input type="text" name="phone1" size="3" maxlength="3" value="' . substr($row["phone"],0 , 3) . '" />)
				<input type="text" name="phone2" size="3" maxlength="3" value="' . substr($row["phone"], 3, 3) . '" />-
				<input type="text" name="phone3" size="4" maxlength="4" value="' . substr($row["phone"], 6, 4) . '" /></p>';
		}
		echo '<h3><u><input type="checkbox" name="' . substr($position, 0, -1) . '" CHECKED />  ' . $title . '</u></h3>
		<p>First Name: <input type="text" name="' . $position . 'first_name" size="15" maxlength="15" value="' . $row["FirstName"] . '" /></p>
		<p>Last Name: <input type="text" name="' . $position . 'last_name" size="15" maxlength="30" value="' . $row["LastName"] . '" /></p>';
		if ($row["staff"] == 1 || $row["staff"] == 2 || $row["staff"] == 5) {echo '<p>House Charge? <input type="checkbox" name="' . $position . 'charge_ok"' . $ChargeOk[$position] . ' /></p>';}
		echo '<p>Write Checks? <input type="checkbox" name="' . $position . 'checks_ok"' . $ChecksOk[$position] . ' /></p>
		<p>Discount: 
		<select name="' . $position . 'discount">
			<option value="error"></option>
			<option value="0"';
		if ($row["Discount"] == 0) echo ' SELECTED';
		echo '>0%</option>
			<option value="2"';
		if ($row["Discount"] == 2) echo ' SELECTED';
		echo '>2%</option>
			<option value="5"';
		if ($row["Discount"] == 5) echo ' SELECTED';
		echo '>5%</option>
			<option value="15"';
		if ($row["Discount"] == 15) echo ' SELECTED';
		echo '>15%</option>
		</select></p>
		<p>Member Type: <select name="' . $position . 'staff">';
		while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
			echo '<option value='. $row2['staff_no'];
			if ($row2['staff_no'] == $row['staff']) {echo ' SELECTED';}
			echo '>' . $row2['staff_desc'];
		}
		echo '</select>
		<p>Member Status: <select name="' . $position . 'memtype">';
		while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
			echo '<option value='. $row3['memtype'];
			if ($row3['memtype'] == $row['memType']) {echo ' SELECTED';}
			echo '>' . $row3['memDesc'];
		}
		echo '</select>';
		echo '<input type="hidden" name="' . substr($position, 0, -1) . 'id" value="' . $row['id'] . '" /><br /><br />';
		
	}
	echo '<p><input type="submit" name="submit" value="Submit" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		<input type="hidden" name="cardno" value="' . $cn . '" /></form><br /><br />';
} elseif ($num_rows == 1) { // One member listed.
	echo '<h2>Edit a Household.</h2>
	<h3>Card Number: ' . $cn . '</h3>';
	// Get the user's information.
	echo '<form action="modify_household.php" method="post">
	<button type="submit" name="label">Include this Household in the next label batch</button>
	<input type="hidden" value="' . $cn . '" name="cardno">
	</form>';
	echo '<form action="delete_member.php" method="post">
	<button type="submit" name="delete">Delete this Household</button>
	<input type="hidden" value="' . $cn . '" name="cardno">
	</form>';
	
	while ($row = mysqlI_fetch_array($result)) {
		
		$query2 = "SELECT staff_no, staff_desc FROM staff ORDER BY staff_no ASC";
		$query3 = "SELECT memtype, memDesc FROM memtype ORDER BY memtype ASC";
		$result2 = mysqli_query($db_slave, $query2);
		$result3 = mysqli_query($db_slave, $query3);
		if ($row['personNum'] == 1) {$position = 'ps_'; $title = 'Primary Shareholder';}
		// Create the form.
		if ($row["ChargeOk"] == 1) {$ChargeOk[$position] = ' CHECKED';} else {$ChargeOk[$position] = '';}
		if ($row["WriteChecks"] == 1) {$ChecksOk[$position] = ' CHECKED';} else {$ChecksOk[$position] = '';}
		
		echo '<form action="modify_household.php" method="post">';
		if (!is_null($row["phone"])) {
			echo '<p>Phone Number: (<input type="text" name="phone1" size="3" maxlength="3" value="' . substr($row["phone"],0 , 3) . '" />)
				<input type="text" name="phone2" size="3" maxlength="3" value="' . substr($row["phone"], 3, 3) . '" />-
				<input type="text" name="phone3" size="4" maxlength="4" value="' . substr($row["phone"], 6, 4) . '" /></p>';
		}
		echo '<h3><u><input type="checkbox" name="' . substr($position, 0, -1) . '" CHECKED />  ' . $title . '</u></h3>
		<p>First Name: <input type="text" name="' . $position . 'first_name" size="15" maxlength="15" value="' . $row["FirstName"] . '" /></p>
		<p>Last Name: <input type="text" name="' . $position . 'last_name" size="15" maxlength="30" value="' . $row["LastName"] . '" /></p>';
		if ($row["staff"] == 1 || $row["staff"] == 2 || $row["staff"] == 5) {echo '<p>House Charge? <input type="checkbox" name="' . $position . 'charge_ok"' . $ChargeOk[$position] . ' /></p>';}
		echo '<p>Write Checks? <input type="checkbox" name="' . $position . 'checks_ok"' . $ChecksOk[$position] . ' /></p>
		<p>Discount: 
		<select name="' . $position . 'discount">
			<option value="error"></option>
			<option value="0"';
		if ($row["Discount"] == 0) echo ' SELECTED';
		echo '>0%</option>
			<option value="2"';
		if ($row["Discount"] == 2) echo ' SELECTED';
		echo '>2%</option>
			<option value="5"';
		if ($row["Discount"] == 5) echo ' SELECTED';
		echo '>5%</option>
			<option value="15"';
		if ($row["Discount"] == 15) echo ' SELECTED';
		echo '>15%</option>
		</select></p>
		<p>Member Type: <select name="' . $position . 'staff">';
		while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
			echo '<option value='. $row2['staff_no'];
			if ($row2['staff_no'] == $row['staff']) {echo ' SELECTED';}
			echo '>' . $row2['staff_desc'];
		}
		echo '</select>
		<p>Member Status: <select name="' . $position . 'memtype">';
		while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
			echo '<option value='. $row3['memtype'];
			if ($row3['memtype'] == $row['memType']) {echo ' SELECTED';}
			echo '>' . $row3['memDesc'];
		}
		echo '</select>';
		echo '<input type="hidden" name="' . substr($position, 0, -1) . 'id" value="' . $row['id'] . '" /><br /><br />';
		
	}
	$position = 'ss_';
	$title = 'Secondary Shareholder';
	$query2 = "SELECT staff_no, staff_desc FROM staff ORDER BY staff_no ASC";
	$query3 = "SELECT memtype, memDesc FROM memtype ORDER BY memtype ASC";
	$result2 = mysqli_query($db_slave, $query2);
	$result3 = mysqli_query($db_slave, $query3);
	$ChecksOk[$position] = ' CHECKED';
	echo '<h3><u><input type="checkbox" name="' . substr($position, 0, -1) . '" />  ' . $title . '</u></h3>
	<p>First Name: <input type="text" name="' . $position . 'first_name" size="15" maxlength="15" /></p>
	<p>Last Name: <input type="text" name="' . $position . 'last_name" size="15" maxlength="30" /></p>';
	echo '<p>Write Checks? <input type="checkbox" name="' . $position . 'checks_ok"' . $ChecksOk[$position] . ' /></p>
	<p>Discount: 
	<select name="'. $position . 'discount">
		<option value="error">Please select a discount.</option>
		<option value="0">0%</option>
		<option value="2">2%</option>
		<option value="5">5%</option>
		<option value="15">15%</option>
	</select></p>
	<p>Member Type: <select name="' . $position . 'staff">';
	while ($row2 = mysqli_fetch_array($result2, MYSQLI_ASSOC)) {
		echo '<option value='. $row2['staff_no'];
		if ($row2['staff_no'] == 0) {echo ' SELECTED';}
		echo '>' . $row2['staff_desc'];
	}
	echo '</select>
	<p>Member Status: <select name="' . $position . 'memtype">';
	while ($row3 = mysqli_fetch_array($result3, MYSQLI_ASSOC)) {
		echo '<option value='. $row3['memtype'];
		if ($row3['memtype'] == 1) {echo ' SELECTED';}
		echo '>' . $row3['memDesc'];
	}
	echo '</select>';
	echo '<input type="hidden" name="' . substr($position, 0, -1) . 'id" value="insert" /><br /><br />';
	echo '<p><input type="submit" name="submit" value="Submit" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		<input type="hidden" name="cardno" value="' . $cn . '" /></form><br /><br />';
	
} elseif ($num_rows > 2) { // Too many matches
	echo '<h1 id="mainhead">Page Error</h1>
	<p class="error">You are trying to modify a household with more than two members.</p><p><br /></p>';
} else { // Not a valid Member ID
	echo '<h1 id="mainhead">Page Error</h1>
	<p class="error">You are trying to modify a non-existant household.</p><p><br /><br /></p>';
}

mysqli_close($db_master); // Close the DB connection.
mysqli_close($db_slave); // Close the DB connection.

include ('./includes/footer.html');
include ('../includes/footer.html');
?>
