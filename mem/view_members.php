<?php # view_members.php (07-29-2007 (haus))

// This script retrieves all the records from the membership table.
$page_title = 'Fannie - Membership Module';
$header = 'View Members';
include ('../includes/header.html');

$page_title = 'View the Current Members';
include('./includes/header.html');

// Page header.
echo'<h1 id="mainhead">Membership</h1>';

require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_slave, 'is4c_op');

// How many records per page.
$display = 25;

$query = "SELECT COUNT(id) FROM custdata ORDER BY cardno ASC"; // Count the number of records.
$result = mysqli_query($db_slave, $query); // Run the query.
$row = mysqli_fetch_array($result, MYSQLI_NUM); // Retrieve the query.
$num_records = $row[0]; // Store the results.

// How many active members, how many inactive ones.
$activeQ = "SELECT * FROM custdata WHERE memType in (1,2) AND cardno <> 3000 GROUP BY cardno"; // Active
$subscribersQ = "SELECT * FROM custdata WHERE memType = 2 AND cardno <> 3000 GROUP BY cardno"; // Subscribers
$shareholdersQ = "SELECT * FROM custdata WHERE memType = 1 AND cardno <> 3000 GROUP BY cardno"; // Sharholders
$inactiveQ = "SELECT * FROM custdata WHERE memType = 5 AND cardno <> 3000 GROUP BY cardno"; // Inactive
$refundQ = "SELECT * FROM custdata WHERE memType = 4 AND cardno <> 3000 GROUP BY cardno"; // Refund
$activeR = mysqli_query($db_slave, $activeQ);
$inactiveR = mysqli_query($db_slave, $inactiveQ);
$refundR = mysqli_query($db_slave, $refundQ);
$subscribersR = mysqli_query($db_slave, $subscribersQ);
$shareholdersR = mysqli_query($db_slave, $shareholdersQ);
$subscribers = mysqli_num_rows($subscribersR);
$shareholders = mysqli_num_rows($shareholdersR);
$active = mysqli_num_rows($activeR);
$inactive = mysqli_num_rows($inactiveR);
$refund = mysqli_num_rows($refundR);

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
$query = "SELECT LastName, FirstName, CardNo, id FROM custdata WHERE CardNo != 9999 AND CardNo != 99999 ORDER BY $order_by LIMIT $start, $display";

$result = mysqli_query($db_slave, $query);

// Display the current number of registered users.
echo "<p>There are currently $num_records records.</p>\n";
echo "<p>There are currently $active active members ($subscribers subscribers, $shareholders shareholders), $inactive inactive members and $refund refunded members.</p>\n";

// Table header.
echo '<table align="center" width="90%" cellspacing="0" cellpadding="5">
<tr>
<td align="center"><b></b></td>
<td align="center"><b><a href="' . $link1 . '&s=' . $start . '&np=' . $num_pages . '">Last Name</a></b></td>
<td align="center"><b><a href="' . $link2 . '&s=' . $start . '&np=' . $num_pages . '">First Name</a></b></td>
<td align="center"><b><a href="' . $link3 . '&s=' . $start . '&np=' . $num_pages . '">Member Number</a></b></td>
</tr>';

// Fetch and print all the records.
$bg = '#eeeeee'; // Set background color.
while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo '<tr bgcolor="' . $bg . '">
	<td align="center"><a href="edit_member.php?id=' . $row['id'] . '">Edit</a></td>
	<td align="left">' . $row['LastName'] . '</td>
	<td align="left">' . $row['FirstName'] . '</td>
	<td align="center"><a href="modify_household.php?cardno=' . $row['CardNo'] . '">' . $row['CardNo'] . ' </a></td>
	</tr>';
}

echo '</table>';

mysqli_free_result ($result); // Free up the resources.

mysqli_close($db_slave); // Close the database connection.

// Make the links to other pages, if necessary.
if ($num_pages > 1) {
	echo '<br /><p>';
	// Determine what page the script is on.
	$current_page = ($start/$display) + 1;
	
	// If it's not on the first page, make a Previous button.
	if ($current_page != 1) {
		echo '<a href="view_members.php?s=' . ($start - $display) . '&np=' . $num_pages . '&sort=' . $sort . '">Previous</a> ';
	}
	
	// Make all the numbered pages.
	for ($i = 1; $i <= $num_pages; $i++) {
		if ($i != $current_page) {
		echo '<a href="view_members.php?s=' . ($display * ($i - 1)) . '&np=' . $num_pages . '&sort=' . $sort . '">' . $i . '</a> ';
		} else {
			echo $i . ' ';
		}
	}
	
	// If it's not the last page, make a Next button.
	if ($current_page != $num_pages) {
		echo '<a href="view_members.php?s=' . ($start + $display) . '&np=' . $num_pages . '&sort=' . $sort . '">Next</a> ';
	}
	echo '</p>';
} // End of links section.

include ('./includes/footer.html'); // Include the HTML footer.
include ('../includes/footer.html'); // Include the HTML footer.

?>
