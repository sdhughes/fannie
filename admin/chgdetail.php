<html>
<head>
<Title>House Charge Detail</Title>
<link rel="stylesheet" href="../includes/style.css" type="text/css" />
</head>
<body>
<?php
setlocale(LC_MONETARY, 'en_US');
require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_slave, 'is4c_op');

$ps = escape_data($_GET['ps']);
$pe = escape_data($_GET['pe']);
$cn = escape_data($_GET['cn']);

$year_start = substr($ps, 0, 4);
$year_end = substr($pe, 0, 4);

if ($year_start == $year_end) {

    $transtable = 'trans_' . $year_start;

    $query = "SELECT date(d.datetime) as date, -1 * ROUND(SUM(d.total),2) as charges
	FROM is4c_op.custdata AS c, is4c_log.$transtable AS d
	WHERE d.card_no = c.CardNo
	AND datetime BETWEEN '$ps' AND '$pe'
	AND d.card_no = $cn
	AND c.staff IN(1,2)
	AND d.trans_subtype = 'MI'
	AND d.emp_no <> 9999 AND d.trans_status <> 'X'
	AND c.CardNo <> 9999
	GROUP BY CONCAT(d.emp_no, '-', d.trans_no, '-', d.register_no, '-', date(d.datetime))
	ORDER BY date(d.datetime)";

} else {
    $query = "SELECT date, SUM(charges) FROM (";

    for ($year = $year_start; $year <= $year_end; $year++) {
        $query .= "SELECT date(d.datetime) as date, -1 * ROUND(SUM(d.total),2) as charges
            FROM is4c_op.custdata AS c, is4c_log.trans_$year AS d
            WHERE d.card_no = c.CardNo
            AND datetime BETWEEN '$ps' AND '$pe'
            AND d.card_no = $cn
            AND c.staff IN(1,2)
            AND d.trans_subtype = 'MI'
            AND d.emp_no <> 9999 AND d.trans_status <> 'X'
            AND c.CardNo <> 9999
            GROUP BY CONCAT(d.emp_no, '-', d.trans_no, '-', d.register_no, '-', date(d.datetime))";

        if ($year == $year_end)
            $query .= ") AS yearSpan GROUP BY date, charges ORDER BY date";
        else
            $query .= " UNION ALL ";
    }

}

$result = mysqli_query($db_slave, $query);

$nameQ = "SELECT CardNo, LastName, FirstName FROM custdata WHERE CardNo = $cn AND staff IN(1,2)";
$nameR = mysqli_query($db_slave, $nameQ);
if (!$nameR) echo "<p>$nameQ</p><p>" . mysqli_error($db_slave) . "</p>";
$nameRow = mysqli_fetch_array($nameR, MYSQLI_ASSOC);

echo "<h1>Staff: {$nameRow['LastName']}, {$nameRow['FirstName']}</h1>";
printf('<table border="1" cellspacing="3" width="100%%" cellpadding="3" style="background-color:eeeeee;"><tr><th>Date</th><th>Amount</th></tr>');
while (list($date, $amount) = mysqli_fetch_array($result, MYSQLI_NUM)) {
    printf('<tr><td align="center">%s</td><td align="center">$%s</td></tr>', $date, $amount);
}
echo "</table>";

?>
