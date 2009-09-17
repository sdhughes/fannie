<?php
$page_title = 'Fannie - Administration Module';
$header = 'Volunteer Hours';
include ('../includes/header.html');
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_op');

if (isset($_POST['submit'])) {
        foreach ($_POST AS $key => $value) {
		$$key = $value;
	}
} else {
        foreach ($_GET AS $key => $value) {
                $$key = $value;
        }
}

if (isset($_POST['submit'])) {
//	print_r(array_combine($id,$hours));
	$comb_arr = array_combine($id,$hours);
	foreach ($comb_arr as $key => $value) {
		mysqli_query($db_master, "UPDATE custdata SET SSI = (SSI + ".$value.") WHERE id = ".$key);
                if ($value > 0) {
                        mysqli_query($db_master, "UPDATE workingMem SET hours = (hours + $value), updated = now() WHERE id = $key");
                        mysqli_query($db_master, "INSERT INTO wmHoursLog (id, datetime, hours) VALUES ($key, now(), $value)");
                }
	}
}

setlocale(LC_MONETARY, 'en_US');

$query = "SELECT CardNo, LastName, FirstName, SSI, id FROM custdata WHERE staff IN (3,6) ORDER BY LastName";

$queryR = mysqli_query($db_master, $query);
if (!isset($_POST["showinactive"])) {$_POST["showinactive"] = "hide";}
echo '<form action="volunteers.php" method="POST">';
if ($_POST['showinactive'] == 'hide') {
	echo '<p align="center"><BUTTON name=showinactive type=submit value="show">Show Inactive Volunteers</BUTTON></p>';
} elseif ($_POST['showinactive'] == 'show') {
	echo '<p align="center"><BUTTON name=showinactive type=submit value="hide">Hide Inactive Volunteers</BUTTON></p>';
}
echo '</form>';
echo '<form action="volunteers.php" method="POST">';
echo "<table border=0 width=95% cellspacing=0 cellpadding=5 align=center>";
echo "<th>Card No<th>Last Name<th>First Name<th>Hours<th>ADD<th>Active?<th>&nbsp;";
$bg = '#eeeeee';
while ($query = mysqli_fetch_row($queryR)) {
	$existQ = "SELECT * FROM workingMem WHERE id = $query[4]";
        $existR = mysqli_query($db_master, $existQ);
        if (mysqli_num_rows($existR) == 0) {
                $addQ = "INSERT INTO workingMem (id, updated, hours) VALUES ($query[4], '2007-01-01 12:31:00', 0.0)";
                $addR = mysqli_query($db_master, $addQ);
        }
        $activeQ = "SELECT DATEDIFF(now(), updated) FROM workingMem WHERE id = $query[4]";
        $activeR = mysqli_query($db_master, $activeQ);
        $active = mysqli_fetch_row($activeR);
        if ($_POST["showinactive"] == "show") {
                $bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
                echo "<tr bgcolor='$bg'>";
                echo "<td>".$query[0]."</td>";
                echo "<td>".$query[1]."</td>";
                echo "<td>".$query[2]."</td>";
                echo "<td align=right>".number_format($query[3],2)."</td>";
                echo "<td align=right><input size=4 name='hours[]' id='hours'></td>";
                if ($active[0] > 90) {echo "<td align=center><p><font color=red>INACTIVE</font></p></td>";}
                elseif ($active[0] <= 90) {echo "<td align=center><p><font color=green>ACTIVE</font></p></td>";}
                echo "<td><input type=hidden name='id[]' value=".$query[4].">&nbsp;</td></tr>";
        } elseif (($active[0] <= 90) && ($_POST["showinactive"] == "hide")) {
                $bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
                echo "<tr bgcolor='$bg'>";
                echo "<td>".$query[0]."</td>";
                echo "<td>".$query[1]."</td>";
                echo "<td>".$query[2]."</td>";
                echo "<td align=right>".number_format($query[3],2)."</td>";
                echo "<td align=right><input size=4 name='hours[]' id='hours'></td>";
                if ($active[0] > 90) {echo "<td align=center><p><font color=red>INACTIVE</font></p></td>";}
                elseif ($active[0] <= 90) {echo "<td align=center><p><font color=green>ACTIVE</font></p></td>";}
                echo "<td><input type=hidden name='id[]' value=".$query[4].">&nbsp;</td></tr>";
        }
}
echo '<tr><td><input type="submit" name="submit" value="submit"></td></tr>';
echo "</table></form>";

include ('../includes/footer.html');
?>
