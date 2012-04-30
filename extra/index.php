<?php
   $page_title = 'Fannie - Tools';
   $header = 'Useful Tools';
   include ('../includes/header.html');
?><body>

<script type='text/javascript' language='javascript'>

$(document).ready(function() {

	$('.tool_menu_link').live('click', function () {
		window.location = $(this).find('a').attr('href');
		return false;
	});
});

</script>
<div class='tool_menu_tile'>
    <h3>Customer Service</h3>
	<div class='tool_menu_link'>
		<a href="/extra/trans_lookup.php">Transaction Lookup</a></br>
		Look up a previous transaction
	</div>

	<div class='tool_menu_link'>
		<a href="/extra/tender_report.php">Tender Report Lookup</a></br>
		Look up a previous tender report
	</div>

	<div class='tool_menu_link'>
		<a href="/extra/owner_details.php">Owner Details Lookup</a></br>
		Look up an owner's account details
	</div>
</div>

<div class='tool_menu_tile'>
    <h3>Buyers Tools</h3>
	<div class='tool_menu_link'>
		<a href="/extra/shelftags.php">Generate Shelftags</a></br>
		Generate a printable PDF of shelftags for use with Avery 32-up sticker sheets
	</div>

<!--	<a href="/giftcert/giftcertadd.php">Gift Certificates</a><br>
	Play Around With Gift Certificates
</br></br>-->
	<div class='tool_menu_link'>
		<a href="/extra/shrinkTool.php">Shrink Tool</a></br>
		Examine today's shrink and edit if needed
	</div>
</div>
<div class='tool_menu_tile'>
    <h3>Owner Services</h3>

	<div class='tool_menu_link'>
		<a href="/extra/member_label.php">Generate Member Labels</a></br>
		Generate file folder labels for Membership 
	</div>

	<div class='tool_menu_link'>
		<a href="/extra/phoneList.php">Generate Phone Bank List</a><br>
		Generate Phone Bank Lists For Membership
	</div>
	<div class='tool_menu_link'>
		<a href="/extra/contactList.php">Generate Owner Email List</a><br>
		Generate Email List of Membership
	</div>
	<div class='tool_menu_link'>
		<a href="/extra/MADCoupons.php">Manage Owner Appreciation Coupons</a><br>
		Manage Owner-Appreciation-Month Coupons
	</div>
</div>
<?php
   include ('../includes/footer.html');
?>
