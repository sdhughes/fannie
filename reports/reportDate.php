<?php
//
//
// Copyright (C) 2007
// authors: Christof Van Rabenau - Whole Foods Cooperative,
// Joel Brock - People's Food Cooperative,
// Matthaus Litteken - Alberta Cooperative Grocery
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
//
//
echo '<link rel="stylesheet" href="../style.css" type="text/css" />';
if ( isset($_POST['submitted']) || isset($_GET['today']) ) {

        echo '<BODY BGCOLOR = "FFCC99" > <font SIZE=2><link rel="STYLESHEET" href="../reports/style.css" type="text/css">';
        setlocale(LC_MONETARY, 'en_US');
        if (isset($_POST['date'])) {
                $date = $_POST['date'];
                //echo "Date entered: ".$date;
        }
        if ((!isset($_POST['date'])) || ($_POST['date'] == '1969-12-31')) {
                $date = date('Y-m-d');
                //echo "Date entered: ".$date;
        }

        if (strpbrk($date, "-") == false) {
            $dateArray = explode("/",$date);
            $db_date = date('Y-m-d', mktime(0, 0, 0, $dateArray[0], $dateArray[1], $dateArray[2]));
        } elseif (strpbrk($date, "/") == false) {
            $dateArray = explode("-",$date);
            $db_date = date('Y-m-d', mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]));
        }

        require_once('../includes/mysqli_connect.php');
        require_once('../includes/selectToTable.php');
        mysqli_select_db($db_slave, 'is4c_log');

        //////////////////////////////////
        //
        //
        //  Let's crunch some numbers...
        //
        //
        //////////////////////////////////

        if ($db_date == DATE('Y-m-d')) {$transtable = 'dtransactions';}
        elseif ($db_date > DATE('Y-m-d')) {echo '<p>Error. There is no data for future events.</p>'; exit;}
        else {$transtable = 'trans_' . substr($db_date, 0, 4);}

        $MADQ = "SELECT * FROM is4c_op.MADays WHERE MADate = '$db_date' LIMIT 1";
        $MADR = mysqli_query($db_slave, $MADQ);
        if ($MADR && mysqli_num_rows($MADR) == 1) $MAD = true;
        else $MAD = false;

        $grossQ = "SELECT ROUND(sum(total),2) as 'Gross Sales'
                FROM $transtable
                WHERE date(datetime) = '$db_date'
                AND department <= 35
                AND department <> 0
                AND trans_status <> 'X'
                AND emp_no <> 9999";

                $results = mysqli_query($db_slave, $grossQ);
                list($gross) = mysqli_fetch_row($results);
                if (!$gross) $gross = -1;

        /**
         * sales of inventory departments
         */
	/**
	 * replaced with better groupings
	 *
        $inventoryDeptQ = "SELECT t.dept_no AS Department ,t.dept_name AS 'Department Name',ROUND(sum(d.total),2) AS 'Department Total'
                FROM $transtable AS d RIGHT JOIN is4c_op.departments AS t
                ON d.department = t.dept_no
                AND date(d.datetime) = '$db_date'
                AND d.department <> 0
                AND d.trans_status <> 'X'
                AND d.trans_subtype <> 'MC'
                AND d.emp_no <> 9999
                GROUP BY t.dept_no
                ORDER BY t.dept_no";
	*/

	$deptArray = array(
	    array('name' => 'Grocery', 'depts' => '1, 13, 28, 30'),
	    array('name' => 'Bulk', 'depts' => '2, 31'),
	    array('name' => 'Perishables', 'depts' => '3, 15, 16, 32'),
	    array('name' => 'Dairy', 'depts' => '4'),
	    array('name' => 'Wine', 'depts' => '5, 29'),
	    array('name' => 'Frozen', 'depts' => '6'),
	    array('name' => 'Cheese', 'depts' => '7'),
	    array('name' => 'Produce', 'depts' => '8, 33'),
	    array('name' => 'Supplements', 'depts' => '9, 12, 35'),
	    array('name' => 'Non-Foods', 'depts' => '10, 34'),
	    array('name' => 'Personal Care', 'depts' => '11'),
	    array('name' => 'Beer', 'depts' => '14'),
	    array('name' => 'Marketing', 'depts' => '18'),
	    array('name' => 'Tri-Met', 'depts' => '40'),
	    array('name' => 'Bottle Return', 'depts' => '41'),
	    array('name' => 'Bottle Deposit', 'depts' => '42, 43'),
	    array('name' => 'Gift Card Sales', 'depts' => '44'),
	    array('name' => 'Member Equity', 'depts' => '45')
	);

	$dept_subtotalQ = "SELECT ROUND(SUM(d.total),2) AS 'Department Subtotal'
                FROM $transtable d
                WHERE date(d.datetime) = '$db_date'
                AND d.department <= 45 AND d.department <> 0 AND d.trans_subtype <> 'MC'
                AND d.emp_no <> 9999 AND d.trans_status <> 'X'";

	$dept_subtotalR = mysqli_query($db_slave, $dept_subtotalQ);
	list($dept_subtotal) = mysqli_fetch_row($dept_subtotalR);

	$deptTable = '<table>';

	foreach ($deptArray AS $dept) {
	    $deptQ = "SELECT ROUND(sum(d.total),2) AS 'Department Total'
                FROM $transtable AS d
                WHERE
		    date(d.datetime) = '$db_date'
		    AND d.department IN ({$dept['depts']})
		    AND d.trans_status <> 'X'
		    AND d.trans_subtype <> 'MC'
		    AND d.emp_no <> 9999";
	    $deptR = mysqli_query($db_slave, $deptQ);
	    if (!$deptR) printf('Query: %s, Error: %s', $deptQ, mysqli_error($db_slave));
	    else {
		list($deptTotal) = mysqli_fetch_row($deptR);
		$deptTable .= sprintf('<tr align="left">
				      <td width="225" align="right"><font size = "2.5">%s</font></td>
				      <td width="225" align="right"><font size = "2.5">%s</font></td>
				      </tr>', $dept['name'], number_format(is_null($deptTotal) ? 0.00 : $deptTotal, 2));
	    }
	}
	$deptTable .= sprintf('<tr>
				<th width="225" align="right"><font size = "2.5">Department Subtotal</font></th>
				<th width="225" align="right"><font size = "2.5">%s</font></th>
				</tr>
			    </font></table>', number_format($dept_subtotal, 2));

        /*
         * pull tender report.
         */

        $tendersQ = "SELECT t.TenderName as 'Tender Type',ROUND(-sum(d.total),2) as Total,COUNT(*) as Count
                FROM $transtable as d,is4c_op.tenders as t
                WHERE d.trans_subtype = t.TenderCode
                AND date(d.datetime) = '$db_date'
                AND d.trans_status <> 'X'
		AND d.trans_subtype NOT IN ('MI', 'FS', 'EC')
                AND d.emp_no <> 9999
                GROUP BY t.TenderName";
	$tendersR = mysqli_query($db_slave, $tendersQ);

	$ebtQ = "SELECT COUNT(total), ROUND(-SUM(d.total), 2)
		FROM $transtable as d
		    WHERE d.trans_subtype IN ('FS', 'EC')
		    AND date(d.datetime) = '$db_date'
		    AND d.trans_status <> 'X'
		    AND d.emp_no <> 9999";
	$ebtR = mysqli_query($db_slave, $ebtQ);
	list($ebtCount, $ebtTotal) = mysqli_fetch_row($ebtR);

        $storeChargeQ = "SELECT COUNT(total) AS 'Store Charge Count', ROUND(-SUM(d.total),2) AS 'Store Charge Total'
                FROM $transtable AS d
                WHERE d.trans_subtype = 'MI'
                AND card_no = 9999
                AND d.trans_status <> 'X'
                AND date(d.datetime) = '$db_date'
                AND d.emp_no <> 9999";
	$storeChargeR = mysqli_query($db_slave, $storeChargeQ);
	list($storeCount, $storeTotal) = mysqli_fetch_row($storeChargeR);

        $houseChargeQ = "SELECT COUNT(total) AS 'House Charge Count', ROUND(-SUM(d.total),2) AS 'House Charge Total'
                FROM $transtable AS d
                WHERE d.trans_subtype = 'MI'
                AND card_no != 9999
                AND d.trans_status <> 'X'
                AND date(d.datetime) = '$db_date'
                AND d.emp_no <> 9999";
	$houseChargeR = mysqli_query($db_slave, $houseChargeQ);
	list($houseCount, $houseTotal) = mysqli_fetch_row($houseChargeR);

        ////////////////////////////
        //
        //
        //  NOW....SPIT IT ALL OUT....
        //
        //
        ////////////////////////////

        printf('<h3>Sales - Gross & NET (Report run %s for %s)</h3>', date('Y-m-d'), $db_date);

       // Haus edit 08-06-07
       $gross2Q = "SELECT ROUND(SUM(total),2) AS 'Gross Sales'
               FROM $transtable
               WHERE date(datetime) = '" . $db_date . "'
               AND department <> 0
               AND trans_status <> 'X'
               AND trans_subtype <> 'MC'
               AND emp_no <> 9999";
               $gross2R = mysqli_query($db_slave, $gross2Q);
               list($gross2) = mysqli_fetch_row($gross2R);

       // end haus edit.

       $net2Q = "SELECT SUM(total) FROM $transtable
               WHERE DATE(datetime) = '$db_date'
               AND trans_type IN ('I', 'D')
               AND trans_status <> 'X'
               AND trans_subtype <> 'MC'
               AND emp_no <> 9999
               AND upc <> 'MADISCOUNT'";
               $net2R = mysqli_query($db_slave, $net2Q);
               list($net2) = mysqli_fetch_row($net2R);

       // End haus edit

       //
       //	BEGIN STAFF_TOTAL
       //
       $staffQ = "SELECT (SUM(d.unitPrice)) AS 'Staff Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND d.staff IN (1,2,5)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$staffR = mysqli_query($db_slave, $staffQ);
	list($staff_total) = mysqli_fetch_row($staffR);

	if (is_null($staff_total)) {
		$staff_total = 0;
	}

	$staffMADQ = "SELECT (SUM(d.unitPrice)) AS 'Staff Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc = 'MADISCOUNT'
               AND d.staff IN (1,2,5)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$staffMADR = mysqli_query($db_slave, $staffMADQ);
	list($staff_mad) = mysqli_fetch_row($staffMADR);

	if (is_null($staff_mad)) {
		$staff_mad = 0;
	}

	$staff_total -= $staff_mad;

       //
       //	END STAFF_TOTAL
       //

       //
       //	BEGIN MEM_TOTAL
       //
       $memQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND d.memType IN (1,2,3,4,5)
               AND d.staff = 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

	$memR = mysqli_query($db_slave, $memQ);
	list($mem_total) = mysqli_fetch_row($memR);

	if(is_null($mem_total)) {
		$mem_total = 0;
	}

	$memMADQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc = 'MADISCOUNT'
               AND d.memType IN (1,2,3,4,5)
               AND d.staff <> 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

	$memMADR = mysqli_query($db_slave, $memMADQ);
	list($mem_mad) = mysqli_fetch_row($memMADR);

	if(is_null($mem_mad)) {
		$mem_mad = 0;
	}

	$mem_total += $mem_mad;

       //
       //	BEGIN WM_TOTAL
       //
       $wmQ = "SELECT SUM(d.unitPrice) AS 'Working Member'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND d.staff IN (3,6)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$wmR = mysqli_query($db_slave, $wmQ);
	list($wm_total) = mysqli_fetch_row($wmR);


	if (is_null($wm_total)) {
		$wm_total = 0;
	}

	$wmMADQ = "SELECT (SUM(d.unitPrice)) AS ' Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc = 'MADISCOUNT'
               AND d.staff IN (3,6)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$wmMADR = mysqli_query($db_slave, $wmMADQ);
	list($wm_mad) = mysqli_fetch_row($wmMADR);

	if (is_null($wm_mad)) {
		$wm_mad = 0;
	}

	$wm_total -= $wm_mad;

       //
       //	END WM_TOTAL
       //

       //
       //       BOARD DISCOUNT
       //

        $boardQ = "SELECT SUM(d.unitPrice) AS 'Board Member'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND d.staff = 4
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$boardR = mysqli_query($db_slave, $boardQ);
	list($board) = mysqli_fetch_row($boardR);

	if (is_null($board)) {
		$board = 0;
	}

	$bdMADQ = "SELECT (SUM(d.unitPrice)) AS 'Staff Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc = 'MADISCOUNT'
               AND d.staff = 4
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$bdMADR = mysqli_query($db_slave, $bdMADQ);
	list($bd_mad) = mysqli_fetch_row($bdMADR);

	if (is_null($bd_mad)) {
		$bd_mad = 0;
	}

	$board -= $bd_mad;


       //
       //       BOARD DISCOUNT
       //

       //
       //	NON-OWNER/WORKER DISCOUNTS
       //
       $nonQ = "SELECT SUM(d.total) as non
               FROM $transtable d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND d.memType IN (0, 7, 8)
               AND d.staff = 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

	$nonR = mysqli_query($db_slave, $nonQ);
	list($non_total) = mysqli_fetch_row($nonR);

	if(is_null($non_total)) {
		$non_total = 0;
	}

	$nonMADQ = "SELECT (SUM(d.unitPrice)) AS 'Staff Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc = 'MADISCOUNT'
	       AND d.memType IN (0, 7, 8)
               AND d.staff = 0
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$nonMADR = mysqli_query($db_slave, $nonMADQ);
	list($non_mad) = mysqli_fetch_row($nonMADR);

	if (is_null($non_mad)) {
		$non_mad = 0;
	}

	$non_total -= $non_mad;


       //
       //	BEGIN SISTER_ORGS
       //
       $sisterQ = "SELECT (ROUND(SUM(d.unitPrice),2)) AS 'Sister Orgs'
               FROM $transtable AS d
               WHERE (date(d.datetime) = '$db_date')
               AND d.upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND d.memType = 6
	       AND d.staff NOT IN (3,6)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

               $sisterR = mysqli_query($db_slave, $sisterQ);
               list($sister_org) = mysqli_fetch_row($sisterR);

               if (is_null($sister_org)) {
                       $sister_org = 0;
               }

       	$sisterMADQ = "SELECT (SUM(d.unitPrice)) AS 'Staff Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '$db_date'
               AND d.upc = 'MADISCOUNT'
	       AND d.memType = 0
               AND d.staff NOT IN (3,6)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

	$sisterMADR = mysqli_query($db_slave, $sisterMADQ);
	list($sister_mad) = mysqli_fetch_row($sisterMADR);

	if (is_null($sister_mad)) {
		$sister_mad = 0;
	}

	$sister_org -= $sister_mad;

       //
       //	END SISTER_ORGS
       //

       // Haus - 08-03-2007
       $totaldiscount2Q = "SELECT SUM(total) AS totaldisc
               FROM $transtable
               WHERE date(datetime) = '" . $db_date . "'
               AND upc IN ('DISCOUNT', 'CASEDISCOUNT')
               AND trans_status <> 'X'
               AND emp_no <> 9999";
       $totaldiscount2R = mysqli_query($db_slave, $totaldiscount2Q);
       list($totaldiscount2) = mysqli_fetch_row($totaldiscount2R);
       // End Haus

       // Haus edit 08-06-2007
       $coupons2Q = "SELECT ROUND(SUM(total),2) AS Coupons
               FROM $transtable
               WHERE date(datetime) = '" . $db_date . "'
               AND trans_subtype = 'IC'
               AND trans_status <> 'X'
               AND emp_no <> 9999";
               $coupons2R = mysqli_query($db_slave, $coupons2Q);
               list($coupons2) = mysqli_fetch_row($coupons2R);

               if (is_null($coupons2)) {
                       $coupons = 0;
               }
               $net2 = $net2 + $coupons2;
       // End haus edit.

       $totalDiscQ = "SELECT ($mem_total + $staff_total + $sister_org + $wm_total + $board + $non_total) as total_discounts";

               $totalDiscR = mysqli_query($db_slave, $totalDiscQ);
               list($totalDisc) = mysqli_fetch_row($totalDiscR);

       // Haus edit...checking (08-06-07)
       printf('<table border="0">
		<tr>
		    <td width="225" align="right"><font size="2.5">Gross Total</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>
		<tr>
		    <td width="225" align="right"><font size="2.5">Total Discount</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>
		<tr>
		    <td width="225" align="right"><font size="2.5">Instore Coupons</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>
		<tr>
		    <td width="225" align="right"><font size="2.5"><strong>Net Total</strong></font></td>
		    <td width="225" align="right"><font size="2.5"><strong>%s</strong></font></td>
		</tr>
	      </table>', number_format($gross2, 2), number_format($totaldiscount2, 2), number_format($coupons2, 2), number_format($net2, 2));

       // End haus edit.

        echo '<p>Sales by Inventory Dept.</p>';
        //select_to_table($db_slave, $inventoryDeptQ,0,'FFCC99');
	echo $deptTable;
        // Haus add 08-03-2007
        //select_to_table($db_slave, $dept_subtotalQ,0,'FFCC99');
        // end Haus add 08-03-2007

        echo '<p>Tender Report</p>
		<table border="0">';

	while (list($name, $total, $count) = mysqli_fetch_row($tendersR)) {
	    printf('<tr>
			<td width="225" align="right"><font size="2.5">%s</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>',
			$name, number_format($total, 2), $count);
	}

	printf('<tr>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>', 'EBT', number_format(is_null($ebtTotal) ? 0.00 : $ebtTotal, 2), $ebtCount);
	//select_to_table($db_slave, $tendersQ,0,'FFCC99');									// sales by tender type
        //select_to_table($db_slave, $storeChargeQ,0,'FFCC99');								// store charges
	printf('<tr>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>', 'Store Charge', number_format(is_null($storeTotal) ? 0.00 : $storeTotal, 2), $storeCount);
        //select_to_table($db_slave, $houseChargeQ,0,'FFCC99');								// house charges
	printf('<tr>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>', 'House Charge', number_format(is_null($houseTotal) ? 0.00 : $houseTotal, 2), $houseCount);

	echo '</table>';


	echo '<p>Discount Totals</p>';
        echo "<table border=0><font size=2>";

        // Specialize reporting based upon whether it is a Member Appreciation Day or not.
        if ($MAD == true) {
            printf('<tr>
			<td width="225" align="right"><font size="2.5">Member Total</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>
		    <tr>
			<td width="225" align="right"><font size="2.5">Staff Total</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>
		    <tr>
			<td width="225" align="right"><font size="2.5">Working Member Total</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>', number_format($mem_total + $wm_total, 2), number_format($staff_total, 2), number_format(0, 2));
        } elseif ($MAD == false) {
            printf('<tr>
			<td width="225" align="right"><font size="2.5">Member Total</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>
		    <tr>
			<td width="225" align="right"><font size="2.5">Staff Total</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>
		    <tr>
			<td width="225" align="right"><font size="2.5">Working Member Total</font></td>
			<td width="225" align="right"><font size="2.5">%s</font></td>
		    </tr>', number_format($mem_total, 2), number_format($staff_total, 2), number_format($wm_total, 2));
        }

        printf('<tr>
		    <td width="225" align="right"><font size="2.5">Board Member Total</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>
		<tr>
		    <td width="225" align="right"><font size="2.5">Sister Organizations Total</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>
		<tr>
		    <td width="225" align="right"><font size="2.5">Non-owner Total</font></td>
		    <td width="225" align="right"><font size="2.5">%s</font></td>
		</tr>
	        <tr>
		    <td width="225" align="right"><font size="2.5"><strong>Total Discount</strong></font></td>
		    <td width="225" align="right"><font size="2.5"><strong>%s</strong></font></td>
		</tr>
		</table>', number_format($board, 2), number_format($sister_org, 2), number_format($non_total, 2), number_format($totalDisc, 2));



} else { // Show the form.

        $page_title = 'Fannie - Reports Module';
        $header = 'Day End Report';
        include('../includes/header.html');
        ?>
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/demo.css" />
        <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
        <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
        <script type="text/javascript" src="../includes/javascript/datepicker/jquery.datePicker.js"></script>
        <script type="text/javascript">
            Date.format = 'yyyy-mm-dd';
            $(function(){
                $('.datepick').datePicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true})
                .dpSetOffset(0, 125);
            });
        </script>
        <script src="../src/putfocus.js" language="javascript"></script>

        </head>
        <body>

        <form action="reportDate.php" name="datelist" method="post" target="_blank">
            <p>Pick a date to run that days dayend report</p>
            <span style="width: 175px !important;float:left;"><input type="text" size="10" name="date" class="datepick" autocomplete="off" /></span>
            <br />

            <input type="hidden" name="submitted" value="TRUE" />
            <br />
            <input name="Submit" type="submit" value="submit" />
        </form>
        <?php

        include('../includes/footer.html');
}
?>
