<?php # - Show gift certificates in use and current balances.
$page_title = 'Fannie - Admin Module';
$header = 'Gift Certificate Display';
include_once ('../includes/header.html');
include_once ('./includes/header.html');

require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_log');

$query = "SELECT * FROM giftcertdetail WHERE expire > curdate() ORDER BY GiftCertNum";
$result = mysqli_query($db_slave, $query);
echo "<table border=0 width=95% cellspacing=0 cellpadding=5 align=center>";
$bg = '#eeeeee';
echo '<th>Gift Certificate Number</th><th>Purchaser</th><th>Recipient</th><th>Initial Value</th><th>Issue Date</th><th>Expiration Date</th><th>Current Value</th>';
while ($row = mysqli_fetch_array($result)) {
   $bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
   echo "<tr bgcolor='$bg'>";
   echo "<td align='center'><a href='giftcertedit.php?num=$row[0]'>$row[0]</a></td>";
   for ($i = 1; $i <= 5; $i++) {
      echo "<td align='center'>$row[$i]</td>";
   }
   $detailQ = "SELECT SUM(amount) FROM giftcerts WHERE GiftCertNum=$row[0] AND SUBSTR(trans_ID, 1, 4) <> 9999";
   $detailR = mysqli_query($db_slave, $detailQ);
   list($amount) = mysqli_fetch_row($detailR);
   $curval = number_format($row[3] - $amount, 2);
   echo "<td align='center'>$curval</td></tr>";
}
echo '</table>';

include('./includes/footer.html');
include('../includes/footer.html');
?>