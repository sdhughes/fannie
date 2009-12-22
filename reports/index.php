<?php
   $page_title = 'Fannie - Reports Module';
   $header = 'Reports Section Index';
   include ('../includes/header.html');

echo '<body>
	<a href="/reports/deptSales.php">Department Sales</a></br>
	Product movements by department or group of departments
</br></br>
        <a href="/reports/itemSales.php">Item Sales</a></br>
	Product movement for a single item
</br></br>
	<a href="/reports/product_list.php">Product List</a></br>
	List all products for a department or group of departments
</br></br>
        <a href="/reports/subdeptmovement.php">Sub-department Movement Report</a></br>
	Generates a movement report for one sub-department.
</br><br />
        <a href="/reports/top5movement.php"><span class="imea imeas"><span></span></span>Top And Bottom Movers</a><br />
        Lists top and bottom movers in each department or subdepartment.
</br><br />
        <a href="/reports/activeMembers.php">Active Members Report</a><br />
        Calculates the number of actively shopping members in a given time period.
</br><br />
        <a href="/reports/dailyDepartment.php">Daily Department Report</a><br />
        Lists daily department gross totals per day in a given range.
</br><br />
        <a href="/reports/membershipReport.php">Membership Report</a><br />
        Generates an equity report for membership.
</br><br />
        <a href="/reports/boardMembershipReport.php">Board Membership Report</a><br />
        Generates an membership report with ability to add notes.
</br><br />
        <a href="/reports/laborHours.php">Labor Hours Report</a><br />
        Generates a report on labor hours in a given period.
</br><br />
	<a href="/reports/shrinkReport.php">Shrink Report</a><br />
        Generates a shrink report for a given department.
<br />';

include('../includes/footer.html');
?>
