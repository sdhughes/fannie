<?php
/*******************************************************************************

    Copyright 2007 Alberta Cooperative Grocery, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$header = 'Transaction Lookup';
$page_title='Search Transaction History';
include_once ('../includes/header.html');

require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_slave, 'is4c_log');

echo '<HEAD>';
echo "<link rel=\"STYLESHEET\" type=\"text/css\" href=\"../includes/javascript/ui.core.css\" />
    <link rel=\"STYLESHEET\" type=\"text/css\" href=\"../includes/javascript/ui.theme.css\" />
    <link rel=\"STYLESHEET\" type=\"text/css\" href=\"../includes/javascript/ui.datepicker.css\" />
    <script type=\"text/javascript\" src=\"../includes/javascript/jquery.js\"></script>
    <script type=\"text/javascript\" src=\"../includes/javascript/datepicker/date.js\"></script>
    <script type=\"text/javascript\" src=\"../includes/javascript/ui.datepicker.js\"></script>
    <script type=\"text/javascript\" src=\"../includes/javascript/ui.core.js\"></script>
    <script type=\"text/javascript\">
                Date.format = 'yyyy-mm-dd';
                $(function(){
                                $('.datepick').datepicker({ 
                                                startDate:'2007-08-01',
                                                endDate: (new Date()).asString(), 
                                                clickInput: true, 
                                                dateFormat: 'yy-mm-dd', 
                                                changeMonth: true, 
                                                changeYear: true,
                                                duration: 0
                                                 });
                   
// $('.datepick').focus();
                });
    </script>
";
	
echo '
<SCRIPT TYPE="text/javascript">
	<!--
	function popup(mylink, windowname)
	{
	if (! window.focus)return true;
	var href;
	if (typeof(mylink) == "string")
	   href=mylink;
	else
	   href=mylink.href;
	window.open(href, windowname, "width=400,height=600,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes");
	return false;
	}
	//-->
	</SCRIPT>
	</HEAD><BODY>';

// If the form has been submitted or sort columns have been clicked, check the data and display the results.
if ((isset($_POST['submitted'])) || (isset($_GET['sort']))) {

    // Initialize the errors array.
    $errors = array();

    // Validate the form data.


    if (empty($_POST['date']) && empty($_GET['date'])) {
        $errors[] = 'You left the date field blank.';
    } else {
        if (isset($_POST['date'])) {
            $da = escape_data($_POST['date']); // Store the date.
        } elseif (isset($_GET['date'])) {
            $da = escape_data($_GET['date']); // Store the date.
        }
        $sm = "DATE(datetime) = '$da'";
        if ($da == DATE('Y-m-d')) {
            $transtable = 'dtransactions';
        } else {
	    $year = substr($da, 0, 4);
            $transtable = 'trans_' . $year;
        }
    }

    if (isset($_POST['submitted'])) {
        $having = NULL;

        if (isset($_POST['ti']) && $_POST['ti'] == 'ti') {
            if (empty($_POST['trans_id'])) {
                $error[] = 'You left the transaction number field blank.';
		$dtn = false;
            } else {
                $dtn = escape_data($_POST['trans_id']);
                $having = "HAVING daytrans_no = '$dtn'";
            }
        }

        if (isset($_POST['rn']) && $_POST['rn'] == 'rn') {
            if (empty($_POST['reg_no']) || !is_numeric($_POST['reg_no'])) {
                $error[] = 'You left the register number field blank or didn\'t enter a number.';
		$rn = false;
            } else {
                $rn = escape_data($_POST['reg_no']);
                $sm .= " AND register_no = $rn";
            }
        }

        if (isset($_POST['cn']) && $_POST['cn'] == 'cn') {
            if (empty($_POST['card_no']) || !is_numeric($_POST['card_no'])) {
                $error[] = 'You left the member number field blank or didn\'t enter a number.';
		$cn = false;
            } else {
                $cn = escape_data($_POST['card_no']);
                $sm .= " AND card_no = $cn";
            }
        }

        if (isset($_POST['em']) && $_POST['em'] == 'em') {
            if (empty($_POST['cashier'])) {
                $error[] = 'You didn\'t select a cashier from the list.';
		$em = false;
            } else {
                $em = escape_data($_POST['cashier']);
                $sm .= " AND emp_no = $em";
            }
        }
    }
    
    if (empty($errors)) {
        $sm = stripslashes($sm);

        // Results!
	$order_by = 'time DESC';

        $query = "SELECT DATE(datetime) AS date,
                TIME(datetime) AS time,
                emp_no,
                register_no,
                trans_no,
                CONCAT(DATE(datetime),'-',emp_no,'-',register_no,'-',trans_no) AS t_id,
                CONCAT(emp_no,'-',register_no,'-',trans_no) AS daytrans_no,
                card_no,
                unitPrice
                FROM $transtable
                WHERE trans_type = 'C'
                AND description LIKE 'Subtotal%'
                AND $sm
                AND emp_no <> 9999
                GROUP BY t_id
                $having
                ORDER BY $order_by";

        $result = mysqli_query ($db_slave, $query);
        // Display the  number of matches.

        if (!$result || mysqli_num_rows($result) == 0) {
            echo '<div id="alert"><p class="error">Your search yielded no results.</p></div>';
        } else {

            $num_records = mysqli_num_rows($result);

            echo '<h1 id="mainhead">Search Results</h1>
                    <p>The following <b>( ' . $num_records . ' )</b> transactions matched your search:</p>';

            // Table header.
            echo '<table align="center" width="100%" cellspacing="0" cellpadding="5">';
            echo '<tr>
                    <th>Time (24hr)</th>
                    <th>Trans ID</th>
                    <th>Emp No</th>
                    <th>Member #</th>
                    <th><b>Subtotal</th>
                    </tr>';

            // Fetch and print all the records.
            $bg = '#eeeeee'; // Set background color.
            while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
                $bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
                echo '<tr bgcolor="' . $bg . '">';
                echo '<td>' . $row['time'] . '</td>
                        <td align="left"><a href="trans_receipt.php?t_id=' .$row['t_id']. '&time=' .$row['time']. '&card_no=' .$row['card_no']. '" onClick="return popup(this, \'trans_receipt\')";><b>' . $row['daytrans_no'] . '</b></a></td>
                        <td align="left">' . $row['emp_no'] . '</td>
                        <td align="left">';
                if ($row['card_no'] == 99999) { echo "NON-MEMBER"; }
                else { echo $row['card_no']; }
                echo '</td><td align="right">' . money_format('%n',$row['unitPrice']) . '</td>
                        </tr>';
            }

            echo '</table>';

            mysqli_free_result ($result); // Free up the resources.
        }

    } else { // Report the errors.

        echo '<h1 id="mainhead">Error!!</h1>
                <p class="error">The following error(s) occurred:<br />';
        foreach ($errors as $msg) { // Print each error.
            echo " - $msg<br />\n";
        }
        echo '</p><p>Please try again.</p><p><br /></p>';

    } // End of if (empty($errors)) IF.

}

// Always show the form.
$query = "SELECT * FROM is4c_op.employees WHERE EmpActive = 1 ORDER BY FirstName ASC";
$result = mysqli_query($db_slave, $query);


	// Create the form.
	echo '<h2>Search Transaction History.</h2>
		<form action="trans_lookup.php" method="post">';
	echo '<table cellpadding=5 border=0><tr><td>
		<p>Date: </td><td><input type="text" name="date" class="datepick" size="11" maxlength="11" /> * Required</p></td></tr>';
	echo '<tr><td><p><input type="checkbox" id="ti" name="ti" value="ti">';
	echo 'Transaction ID: </input></td><td><input type="text" name="trans_id" size="15" maxlength="15" onfocus="document.getElementById(\'ti\').checked = \'checked\'"';
	if (isset($_POST['daytrans_no'])) {echo ' value="' . $_POST['daytrans_no'] . '"';}
	echo ' /></p></td></tr><td>
		<p><input type="checkbox" name="rn" id="rn" value="rn">Register Number: </input></p></td><td><input type="text" name="reg_no" size="2" maxlength="2" onclick="document.getElementById(\'rn\').checked = \'checked\'"></td></tr><tr><td>
		<p><input type="checkbox" name="cn" id="cn" value="cn">Member Number: </input></td><td><input type="text" name="card_no" size="5" maxlength="5" onclick="document.getElementById(\'cn\').checked = \'checked\'"';
	if (isset($_POST['card_no'])) {echo ' value="' . $_POST['card_no'] . '"';}
	echo ' /></td></tr><tr><td><p><input type="checkbox" name="em" id="em" value="em">Cashier: </input></td><td><select name="cashier" onclick="document.getElementById(\'em\').checked = \'checked\'">';
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		echo '<option value='. $row['emp_no'] . '>' . $row['FirstName'] . ' ' . substr($row['LastName'],0,1) . '.';
	}
	echo '</select></p></td></tr>
		<tr><td><input type="submit" name="submit" value="Submit" />
		<td> <input type=reset name=reset value="Start Over"> </td>
		<input type="hidden" name="submitted" value="TRUE" /></td>
		</tr></table></form>';

mysqli_close($db_slave); // Close the DB connection.
include('../includes/footer.html');
?>
