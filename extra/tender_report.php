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



if (isset($_POST['submitted'])) {
    $header = 'Tender Report Lookup';
    $page_title = 'Tender Reports';

    require_once('../includes/header.html');

    require_once('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, 'is4c_log');

    if (isset($_POST['reg_no']))
	$reg = (int)$_POST['reg_no'];
    else
	$reg = NULL;

    if (isset($_POST['shift']))
	$shift = escape_data($_POST['shift']);
    else
	$shift = NULL;

    if ( isset($_POST['date']) && checkdate(substr($_POST['date'], 5, 2), substr($_POST['date'], 8, 2), substr($_POST['date'], 0, 4)) && (strtotime($_POST['date']) < time())) {
	$date = $_POST['date'];
	$year = '_' . substr($date, 0, 4);
	if (strtotime($date) == strtotime((date('Y-m-d')))) {
	    $transtable = 'dlog';
	    $year = NULL;
	} else {
	    $transtable = "tlog$year";
	}
    } else {
	$date = NULL;
    }

    if ($reg && $shift && $date) {
	$blank = "             ";

	switch ($shift) {
	    case 'DAY':
		$eosQ = "SELECT MAX(tdate) FROM $transtable WHERE register_no = $reg AND upc = 'ENDOFSHIFT' AND DATE(tdate) = '$date'";
		$eosR = mysqli_query($db_slave, $eosQ);
		list($EOS) = mysqli_fetch_row($eosR);
		$where = " register_no=$reg AND DATE(tdate)='$date'";
		$shiftname = "Full Day";
		break;
	    case 'AM':
		$eosQ = "SELECT MAX(tdate) FROM $transtable WHERE register_no = $reg AND upc = 'ENDOFSHIFT' AND tdate < '$date 16:00:00'";
		$eosR = mysqli_query($db_slave, $eosQ);
		list($EOS) = mysqli_fetch_row($eosR);
		$where = " register_no=$reg AND DATE(tdate) = '$date' AND tdate < '$EOS'";
		$shiftname = "AM Shift";
		break;
	    case 'PM':
		$eos1Q = "SELECT MAX(tdate) FROM $transtable WHERE register_no = $reg AND upc = 'ENDOFSHIFT' AND tdate < '$date 16:00:00'";
		$eos1R = mysqli_query($db_slave, $eos1Q);
		list($EOS1) = mysqli_fetch_row($eos1R);
		$eos2Q = "SELECT MAX(tdate) FROM $transtable WHERE register_no = $reg AND upc = 'ENDOFSHIFT' AND DATE(tdate)='$date'";
		$eos2R = mysqli_query($db_slave, $eos2Q);
		list($EOS2) = mysqli_fetch_row($eos2R);
		if ($EOS1 == $EOS2)
		    $EOS2 = "$date 23:59:59";
		$where = " register_no=$reg AND tdate BETWEEN '$EOS1' and '$EOS2'";
		$shiftname = "PM Shift";
		break;
	    default:
		break;
	}

	$query_ckq = "select tdate, register_no, trans_no, emp_no, changeGiven, ckTender from cktenders$year where $where order by emp_no, tdate";
	$query_ccq = "select tdate, register_no, trans_no, emp_no, changeGiven, ccTender from cctenders$year where $where order by emp_no, tdate";
	$query_dcq = "select tdate, register_no, trans_no, emp_no, changeGiven, dcTender from dctenders$year where $where order by emp_no, tdate";
	$query_miq = "select tdate, register_no, trans_no, emp_no, card_no, miTender from mitenders$year where $where order by emp_no, tdate";
	$query_bp = "select tdate, register_no, trans_no, emp_no, upc, total from buspasstotals$year where $where order by emp_no, tdate";

	$emptyLine = '<tr><td colspan="6">&nbsp;</td></tr>';

	$receipt = '<table width="400px">';
	sixCellLine('TENDER REPORT', $receipt);

	$receipt .= '<tr><th colspan="2">' . "Lane " . trim($reg) . '</th><th colspan="2">' . $shiftname . '</th><th colspan="2">' . $date . "</th></tr>";
	sixCellLine('------------------------------------------------------------------------------------', $receipt);

	$receipt .= str_repeat($emptyLine, 2);

	//////////////////
	/* Main Queries */
	//////////////////

	$netQ = "SELECT SUM(total) AS net
		FROM $transtable
		WHERE $where
		AND trans_type IN('I','D')
		AND emp_no <> 9999";

        $couponsQ = "SELECT ROUND(SUM(total),2) AS Coupons
                FROM $transtable
                WHERE $where
        	AND trans_subtype = 'IC'
                AND trans_status <> 'X'
        	AND emp_no <> 9999";
	$couponsR = mysqli_query($db_slave, $couponsQ);
	list($coupons) = mysqli_fetch_row($couponsR);

	if (is_null($coupons)) {
		$coupons = 0;
	}

	$netR = mysqli_query($db_slave, $netQ);
	list($net) = mysqli_fetch_row($netR);
        $net += $coupons;

	$cashQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'CA'
		AND $where
		AND d.emp_no <> 9999";
	$cashR = mysqli_query($db_slave, $cashQ);

	$checkQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'CK'
		AND $where
		AND d.emp_no <> 9999";
	$checkR = mysqli_query($db_slave, $checkQ);

	$loanQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'LN'
		AND $where
		AND d.emp_no <> 9999";
	$loanR = mysqli_query($db_slave, $loanQ);

	$creditQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'CC'
		AND $where
		AND d.emp_no <> 9999";
	$creditR = mysqli_query($db_slave, $creditQ);

	$debitQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'DC'
		AND $where
		AND d.emp_no <> 9999";
	$debitR = mysqli_query($db_slave, $debitQ);

	$ebtQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'FS'
		AND $where
		AND d.emp_no <> 9999";
	$ebtR = mysqli_query($db_slave, $ebtQ);

	$ebtcashQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'EC'
		AND $where
		AND d.emp_no <> 9999";
	$ebtcashR = mysqli_query($db_slave, $ebtcashQ);

	$houseQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'MI'
		AND card_no <> 9999
		AND $where
		AND d.emp_no <> 9999";
	$houseR = mysqli_query($db_slave, $houseQ);

	$storeQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'MI'
		AND card_no = 9999
		AND $where
		AND d.emp_no <> 9999";
	$storeR = mysqli_query($db_slave, $storeQ);

	$giftcertQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'TC'
		AND $where
		AND d.emp_no <> 9999";
	$giftcertR = mysqli_query($db_slave, $giftcertQ);

	$vendorQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'MC'
		AND $where
		AND d.emp_no <> 9999";
	$vendorR = mysqli_query($db_slave, $vendorQ);

	$dropQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'DR'
		AND $where
		AND d.emp_no <> 9999";
	$dropR = mysqli_query($db_slave, $dropQ);

	$couponQ = "SELECT ROUND(-sum(d.total),2) as total,COUNT(*) as count
		FROM $transtable AS d
		WHERE d.trans_subtype = 'IC'
		AND $where
		AND d.emp_no <> 9999";
	$couponR = mysqli_query($db_slave, $couponQ);

	///////////////////
	/* More Printing */
	///////////////////

	twoCellLine("NET Total:", $net, $receipt);
	$receipt .= $emptyLine;

	$receipt .= '<tr align="left"><th colspan="2">Tender Type</th><th colspan="2">Amount</th><th colspan="2">Count</th></tr>';

	tenderSummary("Cash", $cashR, $cash, $receipt);
	tenderSummary("Check", $checkR, $check, $receipt);
	tenderSummary("Credit", $creditR, $credit, $receipt);
	tenderSummary("Debit", $debitR, $debit, $receipt);
	tenderSummary("EBT", $ebtR, $garbage, $receipt);
	tenderSummary("EBT Cash", $ebtcashR, $garbage, $receipt);
	tenderSummary("House Charges", $houseR, $garbage, $receipt);
	tenderSummary("Store Charges", $storeR, $garbage, $receipt);
	tenderSummary("Gift Certificates", $giftcertR, $garbage, $receipt);
	tenderSummary("Vendor Coupons", $vendorR, $garbage, $receipt);
	tenderSummary("In-Store Coupons", $couponR, $garbage, $receipt);
	tenderSummary("Loan", $loanR, $loan, $receipt);
	tenderSummary("Drop", $dropR, $drop, $receipt);


	twoCellLine("Cash & Check Total:", $cash + $check + $loan, $receipt);
	twoCellLine("Credit & Debit Total:", $debit + $credit, $receipt);

	$receipt .= str_repeat($emptyLine, 2);	// apbw/tt 3/16/05 Franking II

// ----------------------------------------------------------------------------------------------------

	tenderDetail("CHECK TENDERS", $query_ckq, $receipt, 1);
	tenderDetail("DEBIT CARD TENDERS", $query_dcq, $receipt, 1);
	tenderDetail("CREDIT CARD TENDERS", $query_ccq, $receipt, 1);
	tenderDetail("HOUSE/STORE CHARGE TENDERS", $query_miq, $receipt, 2);
	tenderDetail("TRI-MET PASSES SOLD", $query_bp, $receipt, 3);

	sixCellLine("Report generated: " . date("g:i:s a - n/j/y"), $receipt);
	$receipt .= '</table>';
	echo $receipt;	// apbw/tt 3/16/05 Franking II

	printf('<form name="print" method="post" target="_blank" action="%s">
		<input type="hidden" name="receipt" value="%s" />
		<center><button name="print">Print</button></center>
		</form>', $_SERVER['PHP_SELF'], base64_encode($receipt));

    } else {
	echo 'Errors.';

    }
    require_once('../includes/footer.html');

} elseif (isset($_POST['receipt'])) {
    $receipt = base64_decode($_POST['receipt']);
    echo $receipt;
} else {
        ?>

    <script type="text/javascript" language="javascript">
	window.onload = initAll;
	function initAll() {
	    for (var i = 1; i < 4; i++) {
		document.getElementById("shift_" + i).disabled = true;
	    }
	}

	function update_shift(shiftIndex) {
	    if (shiftIndex != "1") { // If not reg #1, enable the first option and disable the next two.
		document.getElementById("shift_1").disabled = false;
		document.getElementById("shift_1").selected = true;
		document.getElementById("shift_2").disabled = true;
		document.getElementById("shift_3").disabled = true;
	    } else {
		document.getElementById("shift_1").disabled = true;
		document.getElementById("shift_2").disabled = false;
		document.getElementById("shift_3").disabled = false;
	    }
	}

	function checkSubmission() {
	    if (document.tenderForm.reg_no.value == 0 || document.tenderForm.shift.value == 0) {
		alert("Please select a Register No and Shift before submitting.");
		return false;
	    }
	}
    </script>
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
<?php
    // Create the form.
    $header = 'Tender Report Lookup';
    $page_title = 'Tender Reports';

    require_once('../includes/header.html');
    ?>
    <h2>Re-print Tender Report</h2>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post" name="tenderForm" onsubmit="return checkSubmission();">
	<table cellpadding="5" border="0" width="100%">
	    <tr>
		<td>Date: </td>
		<td><input type="text" size="10" name="date" class="datepick" autocomplete="off" /></td>
	    </tr>
	    <tr>
		<td>Register No: </td>
		<td>
		    <select name="reg_no" onchange="update_shift(this.value);">
			<option value="0" selected="selected">Which Register?</option>
			<?php
			    for ($i = 1; $i < 4; $i++)
				printf('<option value="%s">%s</option>' . "\n", $i, $i);
			?>
		    </select>
		</td>
	    </tr>
	    <tr>
		<td>Shift: </td>
		<td>
		    <select name="shift">
			<option id="shift_0" value="0">Which Shift?</option>
			<option id="shift_1" value="DAY">Full Day (only one shift)</option>
			<option id="shift_2" value="AM">AM Shift</option>
			<option id="shift_3" value="PM">PM Shift</option>
		    </select>
		</td>
	    </tr>
	    <tr>
		<td>&nbsp;</td>
		<td>
		    <input type="submit" name="submit" value="Submit" />
		    <input type="hidden" name="submitted" value="TRUE" />
		</td>
	    </tr>
	</table>
    </form>
<?php
    require_once('../includes/footer.html');
}

function build_time($timestamp) {

	return strftime("%m/%d/%y %I:%M %p", $timestamp);
}

function timeStamp($time) {

	return strftime("%I:%M %p", strtotime($time));
}

function tenderSummary($name, $result, &$var, &$string) {
    list($amount, $count) = mysqli_fetch_row($result);
    $string .= '<tr><td colspan="2">' . $name . '</td>';
    if (!$amount) {
	$string .= '<td colspan="2">0.00</td>';
    } else {
	$string .= '<td colspan="2">' . number_format($amount, 2) . '</td>';
    }

    if (!$count) {
	$string .= '<td colspan="2">0</td>';
    } else {
	$string .= '<td colspan="2">' . $count . '</td>';
    }
    $var = $amount;
    $string .= "</tr>";
}

function tenderDetail($label, $query, &$string, $type) {
    global $emptyLine;
    global $db_slave;

    switch ($type) {
	case 1:
	    $fieldNames = "<tr><th>Time</th><th>Lane</th><th>Trans #</th><th>Emp #</th><th>Change</th><th>Amount</th></tr>";
	    break;
	case 2:
	    $fieldNames = "<tr><th>Time</th><th>Lane</th><th>Trans #</th><th>Emp #</th><th>Card No</th><th>Amount</th></tr>";
	    break;
	case 3:
	    $fieldNames = "<tr><th>Time</th><th>Lane</th><th>Trans #</th><th>Emp #</th><th>UPC</th><th>Amount</th></tr>";
	    break;
	default:
	    break;
    }

    sixCellLine($label, $string);
    sixCellLine('------------------------------------------------------------------------------------', $string);

    $result = mysqli_query($db_slave, $query);

    if (mysqli_num_rows($result) > 0) {

		$string .= $fieldNames;
		$total = 0;
		while (list($tdate, $reg_no, $trans, $emp_no, $change, $tender) = mysqli_fetch_array($result)) {
			$change = round($change, 2);
			$string .= "<tr><td>" . timeStamp($tdate) . "</td>"
				. "<td>$reg_no</td>"
				. "<td>$trans</td>"
				. "<td>$emp_no</td>"
				. "<td>" . ($type == 1 ? number_format($change, 2) : ($type == 2 ? trim($change) : trim($change, '0'))) . "</td>"
				. "<td>" . number_format($tender, 2) . "</td>"
				. "</tr>";
				$total += $tender;
		}

		sixCellLine('------------------------------------------------------------------------------------', $string);

		twoCellLine("Total:", $total, $string, true);

    } else {
		sixCellLine(" * * *   N O N E   * * * ", $string);
		sixCellLine('------------------------------------------------------------------------------------', $string);
    }
    $string .= $emptyLine;
}

function twoCellLine($label, $amount, &$string, $bold = false) {
    $string.= '<tr' . ($bold ? ' style="font-weight:bold;"' : '') . '><td colspan="2">' . $label . '</td>';
    $string .= '<td colspan="2">' . number_format($amount, 2) . '</td>';
    $string.= '<td colspan="2">&nbsp;</td></tr>';
}

function sixCellLine($label, &$string) {
    $string .= '<tr><th colspan="6">' . $label .'</th></tr>';
}

?>
