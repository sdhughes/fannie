<?php
$page_title = 'Fannie - Administration Module';
$header = 'Substitute Hours';
include ('../includes/header.html');
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_op');


if(isset($_POST['submit'])){
	foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
}else{
      foreach ($_GET AS $key => $value) {
          $$key = $value;
      }
}

if(isset($_POST['submit'])){
//	print_r(array_combine($id,$hours));
	$comb_arr = array_combine($id,$hours);
	foreach ($comb_arr as $key => $value) {
		mysqli_query($db_master, "UPDATE custdata SET SSI = (SSI + $value) WHERE id = $key");
                mysqli_query($db_master, "UPDATE subs SET hours = (hours + $value), updated = now() WHERE id = $key");
	}
}

setlocale(LC_MONETARY, 'en_US');

$query = "SELECT CardNo, LastName, FirstName, SSI, id FROM custdata WHERE staff = 2 ORDER BY LastName";

$queryR = mysqli_query($db_master, $query);
echo "<form action=subs.php method=POST>";
$updateQ = "SELECT MAX(updated), DATEDIFF(now(), MAX(updated)) FROM subs";
$updateR = mysqli_query($db_master, $updateQ);
$row = mysqli_fetch_row($updateR);
echo "<p>Last updated: $row[0] ($row[1] days ago)</p>";
echo "<table border=0 width=95% cellspacing=0 cellpadding=5 align=center>";
echo "<th>Card No<th>Last Name<th>First Name<th>Total Hours Worked<th>Hours<th>ADD<th>&nbsp;";
$bg = '#eeeeee';
while($query = mysqli_fetch_row($queryR)){
	$existQ = "SELECT * FROM subs WHERE id = $query[4]";
	$existR = mysqli_query($db_master, $existQ);
	if (mysqli_num_rows($existR) == 0) {
		$addQ = "INSERT INTO subs (id, hours) VALUES ($query[4], 0)";
		$addR = mysqli_query($db_master, $addQ);
	}
	$moreinfoRow = mysqli_fetch_row($existR);
	$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
	echo "<tr bgcolor='$bg'>";
	if (($moreinfoRow[2] > 950 && $moreinfoRow[2] < 1050)
            || ($moreinfoRow[2] > 1950 && $moreinfoRow[2] < 2050)
            || ($moreinfoRow[2] > 2950 && $moreinfoRow[2] < 3050)
            || ($moreinfoRow[2] > 3950 && $moreinfoRow[2] < 4050)
            || ($moreinfoRow[2] > 4950 && $moreinfoRow[2] < 5050)) {
            $bold = "<b><font color = 'RED'>";
            $unbold = "</b></font>";
        } else { $bold = NULL; $unbold = NULL;
        }
        echo "<td>".$query[0]."</td>";
	echo "<td>".$query[1]."</td>";
	echo "<td>".$query[2]."</td>";
	if ($moreinfoRow[2] == 0) {$moreinfoRow[2] = "New";}
	echo "<td>$bold".$moreinfoRow[2]."$unbold</td>";
	echo "<td align='right'>".number_format($query[3],2)."</td>";
	echo "<td align='right'><input size='4' name='hours[]' id='hours'></td>";
	echo "<td><input type='hidden' name='id[]' value='".$query[4]."'>&nbsp;</td></tr>";
}
echo "<tr><td><input type=submit name=submit value=submit></td></tr>";
echo "</table></form>";

include('../includes/footer.html');
?>
