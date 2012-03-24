<?php
//header("Location:/item/itemMaint.php")
$page_title ='Fannie - the IS4C Backend';
$header = 'welcome home';
include('./includes/header.html');
require_once('./includes/mysqli_connect.php');

global $db_master;
?>
    <script type='text/javascript' language='javascript' src='../includes/javascript/index.js'></script>
        <div id='main_content'>
		<div class='column' id='column1'>
					<div class='content_header'>Useful Links</div>
			<div id='main_links'>
				<ul>
					<li><a href="http://mail.google.com/a/albertagrocery.coop/" target="_blank">ACG Email</a></li>
					<li><a href="/timesheet/timesheet.php">Timesheets</a></li>
					<li><a href="/documents/schedule.xlsx" target="_blank">Work Schedule</a></li> 
				</ul>
                <ul>
					<li><a href="/documents/bell_schedule.docx" target="_blank">Bell Schedule</a></li>
					<li><a href="/documents/Staff Phone List.ods" target="_blank">Staff Phone List </a></li>
					<li><a href="https://spreadsheets.google.com/a/albertagrocery.coop/spreadsheet/ccc?key=0AvQPKvYhG_yjcC1UMkdUSlFLS3dwclNIclpRaTg3YkE&hl=en_US&pli=1#gid=0" target="_blank">Working Owner Calendar</a></li>
			</ul>
			</div>
			<!--put announcements here -->
			<div id='main_announcements'>
<?php

	//pull the announcements and format them

	$query = "SELECT author,title,message,enabled FROM fannie.announcements WHERE enabled = 1";
	$result = mysqli_query($db_master, $query) or die('Query Error: ' . mysqli_error($db_master));
	echo "<ul>";
	while ($row = mysqli_fetch_row($result)) {
		echo "<li class='announcement_item'>
			<div class='content_header'>$row[1]</div>
			<p>$row[2]</p>
			</li>";
			//<span>- $row[0]</span> - taken out for ....whatever reason.
	}
	echo "</ul>";


?>			</div>
	        <div id='sales_calendar_box'>
			</div> <!-- end sales calendar  -->
		</div><!-- end 1st column -->
<!-- START 2nd COLUMN -->
		<div class='column' id='column2'>
			<div class='acg_calendar'><div class='content_header'>&nbsp; Upcoming Meetings & Events &nbsp;</div>
<iframe src="https://www.google.com/calendar/hosted/albertagrocery.coop/embed?showTitle=0&amp;showDate=0&amp;showPrint=0&amp;showCalendars=0&amp;showTz=0&amp;mode=AGENDA&amp;height=300&amp;wkst=1&amp;bgcolor=%23ff9900&amp;src=albertagrocery.coop_c2eron5lkosg1os72m4p4bgdns%40group.calendar.google.com&amp;color=%238C500B&amp;ctz=America%2FLos_Angeles" style=" border:solid 1px #777 " width="300" height="300" frameborder="0" scrolling="no"></iframe>
</div>
			<div class='duplex_calendar'><div class='content_header'>&nbsp; Duplex Availability</div>
<iframe src="https://www.google.com/calendar/b/0/embed?showTitle=0&amp;showDate=0&amp;showPrint=0&amp;showTabs=0&amp;showCalendars=0&amp;showTz=0&amp;mode=AGENDA&amp;height=300&amp;wkst=1&amp;bgcolor=%239999ff&amp;src=albertagrocery.coop_gkr5g0sk3fbd0a9ubgslrtse64%40group.calendar.google.com&amp;color=%231B887A&amp;ctz=America%2FLos_Angeles" style=" border:solid 1px #777 " width="300" height="200" frameborder="0" scrolling="no"></iframe>
</div>		
        </div><!-- END 2nd COLUMN-->
    </div><!-- end main content-->
        

<?php
	include('./includes/footer.html');
?>
