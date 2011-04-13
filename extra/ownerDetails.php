<?php
/********************************************************************************
*
*   Owner Details Module
* 
*   @author: steven hughes
*   @date: oct 16, 2010
* 
*
*********************************************************************************/

$page_title = 'Fannie - Owner Details';
$header = 'Owner Details';
$debug = true;
include('../includes/header.html');
echo '<script type="text/javascript" src="../includes/javascript/jquery.js"></script>';
echo '<script type="text/javascript" src="../includes/javascript/myquery.js"></script>';
?>
<html>
<head>

<?php
//going to move this to some other file for security
$h = 'localhost';
$u = 'comet';
$p = 'c0m3t';
$d = 'comet';

// Connect to the database.
$conn_comet = mysqli_connect($h,$u,$p, $d);


if ( isset($_REQUEST['submitted']) && ($_REQUEST['submitted'] == 'search') ) { // On form submission or list link clicking...

//need to filter this
$cardNo = $_POST['owner_no'];

if ( is_numeric($cardNo)) $where = 'd.cardNo = ' . $cardNo;
else $where = 'lastName LIKE \'%' . mysql_real_escape_string($cardNo) . '%\'';

    $query = sprintf("SELECT firstName AS first,lastName AS last, 
        DATE_FORMAT(nextPayment, '%%M %%e, %%Y') AS nextDue, 
        pp.amount AS plan, 
        d.sharePrice AS price, 
        SUM(p.amount) AS total,
	d.cardNo AS cardNo
        FROM comet.owners as o
        INNER JOIN comet.details as d ON o.cardNo = d.cardNo
        INNER JOIN comet.payments as p ON d.cardNo = p.cardNo
        INNER JOIN comet.paymentPlans as pp ON d.paymentPlan = pp.planID
        WHERE %s GROUP BY d.cardNo",$where);
//echo $query;

    $result = mysqli_query($conn_comet, $query) or die('bad query: ' . mysqli_error($conn_comet));
    $counter = 0;
    $numRows = mysqli_num_rows($result);	

    echo "Your search for (' <span id='owner_search_term'>" . mysql_real_escape_string($cardNo) . "</span> ') yielded $numRows results.";
  /*  if ($numRows > 1){
	 echo " $numRows results."; 
    } elseif ($numRows == 1) {
	echo " 1 result.";
   }
    else echo " no matches." 
   */
    echo '<p>Please click on a name to display associated details.</p>';
    
    while ($row = mysqli_fetch_array($result,MYSQL_ASSOC)){
	//echo $row;
	
	/*
		foreach ($row as $value) {
			$counter++;
			echo $value . '<br />';
		}
*/	$first = $row['first']; 
	$last = $row['last'];
	$nextDue = $row['nextDue'];
	$plan = $row['plan'];
	$price = $row['price'];
	$total = $row['total'];
	$cardNo = $row['cardNo'];
	
        echo "
	<table class='detailTable'>
			<tr class='owner_row'><td class='label'>Owner <span>($cardNo):</span></td><td class='results'>$first $last</td></tr>
			<tr><td class='label'>Next Due Date:</td><td class='results'>$nextDue</td></tr>
			<tr><td class='label'>Payment plan:</td><td class='results'>$plan</td></tr>
			<tr><td class='label'>Total Share Amount:</td><td class='results'>$price</td></tr>
			<tr><td class='label'>Total Paid to Date:</td><td class='results'>$total</td></tr>
		  </table>
		<hr />";
	}

//echo '<br />Connected successfully' . $card_no;
mysqli_close($conn_comet);

} else { // Show the form.

    if (!empty($error)) echo '<h3><font color="red">' . $error . '</font></h3>';
    
		echo '<BODY onLoad="putFocus(0,0);">
       <form action="' . $_SERVER['PHP_SELF'] . '" method="post">
       <input name="owner_no" type="text" id="detail_lookup" /> Enter Owner Number or Last Name<br /><br />
       <input name="submit" type="submit" value="Submit" />
       <input name="submitted" type="hidden" value="search" />
       </form>';
}
//close out the page
include ('../includes/footer.html');
?>
