<?php
   $page_title = 'Fannie - Administration Module';
   $header = 'Admin Section Index';
   include ('../includes/header.html');
echo '<body>
	<a href="/admin/volunteers.php">Volunteer Hours</a></br>
	Enter volunteer hours worked
</br></br>
	<a href="/admin/subs.php">Substitute Hours</a></br>
	Enter sub hours worked
</br></br>
	<a href="/CoMET/">CoMET</a></br>
	Access Co-op Membership Equity Tracking utility
</br></br>
	<a href="/admin/charges.php">Staff Charges</a><br>
	View staff charge totals
<br /><br />
	<a href="/admin/employees.php">Employee Management</a><br />
	Manage Employees
<br /><br />
	<a href="/timesheet/payroll.php">Payroll Report</a><br />
	Generate a Payroll Report For a pay period
<br /><br />
        <a href="/admin/messages.php">Edit Register Messages</a><br />
        Edit Register Messages

</br>';

   include ('../includes/footer.html');
?>
