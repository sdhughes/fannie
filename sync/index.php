<?php
$header = 'Syncronization Page Index';
$page_title = 'Fannie - Syncronization Module';
include ('../includes/header.html');
echo '<div id="box">	<p>
		<a href="reload.php?table=products">Sync Products</a></p>
	<p>
		<a href="reload.php?table=custdata">Sync Member Records</a></p>
	<p>
		<a href="reload.php?table=employees">Sync Employee Records</a></p>
	<p>
		<a href="reload.php?table=departments">Sync Departments</a></p>
	<p>
		<a href="reload.php?table=subdepts">Sync Subdepartments</a></p>
	<p>
		<a href="reload.php?table=tenders">Sync Tenders</a></p>
	<p>
		<a href="reload.php?table=messages">Sync Messages</a></p>
</div>';
include ('../includes/footer.html');
?>
