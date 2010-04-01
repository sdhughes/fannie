<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

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

setlocale(LC_MONETARY, 'en_US');
require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db ($db_slave, 'is4c_log');

if (isset($_POST['submit'])) {
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
} else {
      foreach ($_GET AS $key => $value) {
          $$key = $value;
      }
}

echo '<html>
	<head>
	<Title>Receipt</Title>
	<link rel="stylesheet" href="../includes/style.css" type="text/css" />
	</head>
	<body>';

$trans_array = explode('-',$t_id);
	$year = $trans_array[0];
	$month = $trans_array[1];
	$day = $trans_array[2];
	$emp_no = $trans_array[3];
	$register_no = $trans_array[4];
	$trans_no = $trans_array[5];

        if ($year . '-' . $month . '-' . $day == DATE('Y-m-d')) {
                $transtable = 'dtransactions';
        } else {
                $transtable = "trans_$year";
        }

$query = "SELECT * FROM $transtable
	WHERE DATE(datetime) = '" . $year."-".$month."-".$day . "'
	    AND emp_no = $emp_no
	    AND register_no = $register_no
	    AND trans_no = $trans_no
	    AND upc NOT IN ('DISCOUNT', 'CASEDISCOUNT', 'MADISCOUNT', 'TAX')
	ORDER BY trans_id";

$result = mysqli_query ($db_slave, $query);

$empQ = "SELECT CONCAT(firstname, ' ', SUBSTR(lastname, 1, 1), '.') FROM is4c_op.employees WHERE emp_no=$emp_no";
$empR = mysqli_query($db_slave, $empQ);

list($emp_name) = mysqli_fetch_row($empR);

echo "<table border=0 cellpadding=0 width='375px'>
    <tr><td align='center' colspan='3'><h3>A L B E R T A&nbsp;&nbsp;&nbsp;C O - O P&nbsp;&nbsp;&nbsp;G R O C E R Y</h3></td></tr>
    <tr><td align='center' colspan='3'>1500 NE 15th Avenue</td></tr>
    <tr><td align='center' colspan='3'>5 0 3 . 2 8 7 - 4 3 3 3</td></tr>
    <tr><td colspan='3'>&nbsp;</td></tr>
    <tr>";
echo "<td style='color:gray;' align='left'>" . $month . "/" . $day . "/" . substr($year,2,2) . " " . $time . "</td>";
echo "<td style='color:gray;' align='center'>" . $emp_no."-".$register_no."-".$trans_no . "</td>";
echo "<td style='color:gray;' align='right'>$emp_name</td></tr>
    </table><br />";

echo "<table border=0 cellpadding=0 width='375px'>";

while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
	echo '<tr>
		<td align="left">' . $row['description'] . '</td>
		<td align="center">';
	if ($row['Scale'] == 1) {
		echo $row['quantity'] . " @ " . $row['unitPrice'];
	} else { echo "&nbsp;"; }
	echo '</td><td align="right">';

	if ($row['total'] == 0 && $row['description'] == 'Change')
	    echo '0.00';
	elseif ($row['total'] == 0)
	    echo "&nbsp;";
	else
	    echo money_format('%n',$row['total']);

	echo '</td><td>';
	if ($row['trans_status'] == 'V') { echo " VD";}
	elseif ($row['foodstamp'] == 1) { echo " F";}
	else { echo "&nbsp;";}
	echo '</td>';
	echo '</tr>';
}
echo "</table><br /><br />";

echo '<table border="0" cellpadding="0" width="375px"><tr><td align="center">';
if ($card_no != 99999) { echo "Thank You Member # " . $card_no . "."; }
else { echo 'Thank You.'; }
echo '</td></tr><tr><td>&nbsp</td></tr>';
$query = "SELECT * FROM is4c_op.messages WHERE id LIKE 'receiptFooter%' ORDER BY id ASC";
$result = mysqli_query($db_slave, $query);
while ($row = mysqli_fetch_array($result)) {
    echo "<tr><td align='center'>{$row['message']}</td></tr>";
}
echo "<tr><td>&nbsp</td></tr><tr><td>&nbsp</td></tr>";
echo '<tr><td align="center">Receipt generated on: ' . date("D M j G:i:s T Y") . '</td></tr></table>';
