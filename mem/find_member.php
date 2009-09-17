<?php # find_member.php - Member Search Page

$page_title = 'Fannie - Membership Module';
$header = 'Find A Member';
include ('../includes/header.html');

// A page to search the member base.
$page_title='Find a Member';
include ('./includes/header.html');



require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_slave, 'is4c_op');

// If the form has been submitted or sort columns have been clicked, check the data and display the results.
if ((isset($_POST['submitted'])) || (isset($_GET['sort']))) {
	
	// Initialize the errors array.
	$errors = array();
	
	// Validate the form data.
	if (isset($_POST['search_method'])){
		switch ($_POST['search_method']) { // How do they want to search?
			
			case 'fn':
			if (empty($_POST['first_name'])) {
			
				$errors[] = 'You left their first name blank.';
			
			} else {
				$fn = escape_data($_POST['first_name']); // Store the first name.
				$sm = "FirstName LIKE '$fn%'";
			}
			break;
		
			case 'ln':
			if (empty($_POST['last_name'])) {
			
				$errors[] = 'You left their last name blank.';
			
			} else {
				$ln = escape_data($_POST['last_name']); // Store the last name.
				$sm = "LastName LIKE '$ln%'";
			}
			break;
			
			case 'cn':
			if (empty($_POST['card_no'])) {
				
				$errors[] = 'You left their member number blank.';
				
			} else {
				$cn = escape_data($_POST['card_no']); // Store the member number.
				$sm = "cardno = $cn";
			}
			break;
				
			case 'st':
				$sm = "staff = {$_POST['staff']}";
			break;
		
			case 'mt':
				$sm = "memType = {$_POST['memtype']}";
			break;
		}
	} else {$sm = $_GET['sm'];}
	if (empty($errors)) {
		$sm = stripslashes($sm);
		$query = "SELECT * FROM custdata WHERE " . $sm;
		$result = mysqli_query($db_slave, $query);
		
		if (mysqli_num_rows($result) == 0) { // No results
			echo '<h1 id="mainhead">Error!</h1>
			<p class="error">Your search yielded no results.</p>';
		} else { // Results!
			
			// How many records per page.
			$display = 25;
			
			$query = "SELECT COUNT(id) FROM custdata WHERE $sm"; // Count the number of records.
			$result = mysqli_query($db_slave, $query); // Run the query.
			list($num_records) = mysqli_fetch_array($result, MYSQLI_NUM);
			
			// Determine how many pages there are.
			if (isset($_GET['np'])) { // Already been determined.
				$num_pages = $_GET['np'];
			} else { // Need to determine.
				
				// Calculate the number of pages.
				if ($num_records > $display) { // If there are more than one page of records.
					$num_pages = ceil ($num_records/$display);
				} else {
					$num_pages = 1; // There is only one page.
				}
			} // End of page count IF.
			
			// Determine where the page is starting.
			if (isset($_GET['s'])) { // If we've been through this before.
				$start = $_GET['s'];
			} else { // If this is the first time.
				$start = 0;
			}
			
			$link1 = "{$_SERVER['PHP_SELF']}?sort=lna";
			$link2 = "{$_SERVER['PHP_SELF']}?sort=fna";
			$link3 = "{$_SERVER['PHP_SELF']}?sort=cna";
			
			// Determine the sorting order.
			if (isset($_GET['sort'])) { // If a non-default sort has been chosen.
				
				// Use existing sorting order.
				switch ($_GET['sort']) {
					
					case 'lna':
					$order_by = 'LastName ASC';
					$link1 = "{$_SERVER['PHP_SELF']}?sort=lnd";
					break;
					
					case 'lnd':
					$order_by = 'LastName DESC';
					$link1 = "{$_SERVER['PHP_SELF']}?sort=lna";
					break;
					
					case 'fna':
					$order_by = 'FirstName ASC';
					$link2 = "{$_SERVER['PHP_SELF']}?sort=fnd";
					break;
					
					case 'fnd':
					$order_by = 'FirstName DESC';
					$link2 = "{$_SERVER['PHP_SELF']}?sort=fna";
					break;
					
					case 'cna':
					$order_by = 'CardNo ASC';
					$link3 = "{$_SERVER['PHP_SELF']}?sort=drd";
					break;
					
					case 'cnd':
					$order_by = 'CardNo DESC';
					$link3 = "{$_SERVER['PHP_SELF']}?sort=dra";
					break;
					
					default:
					$order_by = 'CardNo DESC';
					break;
					
				}
				
				// $sort will be appended to the pagination links.
				$sort = $_GET['sort'];
				
			} else { // Use the default sorting order.
				$order_by = 'CardNo DESC';
				$sort = 'cnd';
			}
					
			
			// Make the query using the LIMIT function and the $start information.
			if ($_POST['search_method'] == 'mt') {$sm = "custdata.memType = {$_POST['memtype']}";}
			$query = "SELECT LastName, FirstName, CardNo, custdata.memType, memtype.memDesc as Mem_Type, id FROM custdata INNER JOIN memtype ON memtype.memtype = custdata.memType WHERE $sm ORDER BY $order_by LIMIT $start, $display";
			
			$result = mysqli_query($db_slave, $query);

			// Display the  number of matches.
			echo '<h1 id="mainhead">Search Results</h1>
			<p>The following <b>( ' . $num_records . ' )</b> members matched your search string:</p>';
						
			// Table header.
			echo '<table align="center" width="90%" cellspacing="0" cellpadding="5">
			<tr>
			<td align="left"></td>
			<td align="left"><a href="' . $link1 . '&s=' . $start . '&np=' . $num_pages . '&sm=' . $sm . '"><b>Last Name</b></a></td>
			<td align="left"><a href="' . $link2 . '&s=' . $start . '&np=' . $num_pages . '&sm=' . $sm . '"><b>First Name</b></a></td>
			<td align="left"><a href="' . $link3 . '&s=' . $start . '&np=' . $num_pages . '&sm=' . $sm . '"><b>Member #</b></a></td>
			<td align="left">Member Type</td>
			</tr>';
			
			// Fetch and print all the records.
			$bg = '#eeeeee'; // Set background color.
			while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
				$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
				echo '<tr bgcolor="' . $bg . '">
				<td align="left"><a href="edit_member.php?id=' . $row['id'] . '">Edit</a></td>
				<td align="left"><b>' . $row['LastName'] . '</b></td>
				<td align="left"><b>' . $row['FirstName'] . '</b></td>
				<td align="left"><a href="modify_household.php?cardno=' .$row['CardNo']. '">' . $row['CardNo'] . '</a></td>
				<td align="left">' . $row['Mem_Type'] . '</td>
				</tr>';
			}
			
			echo '</table>';
			
			mysqli_free_result ($result); // Free up the resources.
			
			
			// Make the links to other pages, if necessary.
			if ($num_pages > 1) {
				echo '<br /><p>';
				// Determine what page the script is on.
				$current_page = ($start/$display) + 1;
				
				// If it's not on the first page, make a Previous button.
				if ($current_page != 1) {
					echo '<a href="find_member.php?s=' . ($start - $display) . '&np=' . $num_pages . '&sort=' . $sort . '&sm=' . $sm . '">Previous</a> ';
				}
				
				// Make all the numbered pages.
				for ($i = 1; $i <= $num_pages; $i++) {
					if ($i != $current_page) {
					echo '<a href="find_member.php?s=' . ($display * ($i - 1)) . '&np=' . $num_pages . '&sort=' . $sort . '&sm=' . $sm . '">' . $i . '</a> ';
					} else {
						echo $i . ' ';
					}
				}
				
				// If it's not the last page, make a Next button.
				if ($current_page != $num_pages) {
					echo '<a href="find_member.php?s=' . ($start + $display) . '&np=' . $num_pages . '&sort=' . $sort . '&sm=' . $sm . '">Next</a> ';
				}
				echo '</p>';
			} // End of links section.
			
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



	
	// Create the form.
	$staffQ = "SELECT * FROM is4c_op.staff ORDER BY staff_no ASC";
	$staffR = mysqli_query($db_slave, $staffQ);
	$memtypeQ = "SELECT * FROM is4c_op.memtype ORDER BY memtype ASC";
	$memtypeR = mysqli_query($db_slave, $memtypeQ);
	
	echo '<h2>Find a Member.</h2>
	<p>Select <b>one</b> of the below search options.</p>
	<form action="find_member.php" method="post">
	<p><input type="radio" id="fn" name="search_method" value="fn" />First Name: <input type="text" name="first_name" size="15" maxlength="15" onfocus="document.getElementById(\'fn\').checked = \'checked\'"';
	if (isset($_POST['first_name'])) {echo ' value="' . $_POST['first_name'] . '"';}
	echo ' /></p>
	<p><input type="radio" id="ln" name="search_method" value="ln" />Last Name: <input type="text" name="last_name" size="15" maxlength="30" onfocus="document.getElementById(\'ln\').checked = \'checked\'"';
	if (isset($_POST['last_name'])) {echo ' value="' . $_POST['last_name'] . '"';}
	echo ' /></p>
	<p><input type="radio" id="cn" name="search_method" value="cn" />Member Number: <input type="text" name="card_no" size="4" maxlength="4" onfocus="document.getElementById(\'cn\').checked = \'checked\'"';
	if (isset($_POST['card_no'])) {echo ' value="' . $_POST['card_no'] . '"';}
	echo ' /></p>
	<p><input type="radio" id="st" name="search_method" value="st" />Member Type: <select name="staff" onfocus="document.getElementById(\'st\').checked = \'checked\'">';
	while ($row = mysqli_fetch_array($staffR, MYSQLI_ASSOC)) {
		echo '<option value='. $row['staff_no'] . '>' . $row['staff_desc'] . "</option>\n";
	}
	echo '</select></p>
	<p><input type="radio" id="mt" name="search_method" value="mt" />Member Status: <select name="memtype" onfocus="document.getElementById(\'mt\').checked = \'checked\'">';
	while ($row = mysqli_fetch_array($memtypeR, MYSQLI_ASSOC)) {
		echo '<option value='. $row['memtype'] . '>' . $row['memDesc'] . "</option>\n";
	}
	echo '</select></p>
		<p><input type="submit" name="submit" value="Submit" /></p>
		<input type="hidden" name="submitted" value="TRUE" />
		</form>';

mysqli_close($db_slave); // Close the DB connection.

include ('./includes/footer.html');
include ('../includes/footer.html');
?>
