<?php

	// 
	// $blank = "             ";
	// 
	// $query_ckq = "select * from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no AND trans_subtype = 'CK' order by TIME(datetime)";
	// $query_ccq = "select * from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no AND trans_subtype = 'CC' order by TIME(datetime)";
	// $query_dcq = "select * from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no AND trans_subtype = 'DC' order by TIME(datetime)";
	// $query_miq = "select * from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no AND trans_subtype = 'MI' order by TIME(datetime)";
	// $query_bp = "select * from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no AND trans_subtype = 'CK' order by TIME(datetime)";
	// 
	// $fieldNames = "<table width=100% border=0 align=center><tr><th>Time</th><th>Lane</th><th>Trans #</th><th>Emp #</th><th>Change</th><th>Amount</th></tr>";

	// debug_p($_REQUEST, "all the data coming in");
	
	foreach (explode(",", $_GET['shift_array']) as $sid) {
		// echo "Current value of \$key: $sid\n";
//		$sid = $check_list;
		$emp_no = substr($sid,11,2);
		$date = substr($sid,0,10);		

		$receipt .= '<br><br><center>T E N D E R &nbsp;&nbsp; R E P O R T</center><br>';
		$receipt .= '<center>Cashier #: ' . $emp_no . ' Date: ' . strftime("%a %m/%d/%Y",strtotime($date)) . '</center><br>';
		$receipt .=	'<center>-----------------------------------------------</center>';
		$receipt .= str_repeat("<br>", 2);

		$netQ = "SELECT SUM(total) AS net
			from is4c_log.dtransactions
			where DATE(datetime) = '$date'
			and emp_no = $emp_no
			and trans_type IN('I','D')
			and trans_subtype <> 'IC'
			AND emp_no <> 9999";

	//	$netR = mysql_query($netQ);
	//	$row = mysql_fetch_row($netR);
		$receipt .= '<table border=0 width=89%>';

	//	$receipt .= "<tr><td>NET Total: </td><td align=right>";
	//	$receipt .= money_format("%n",$row[0])."</td><td>&nbsp;</td></tr>";
		$receipt .= "<tr><td colspan=3>&nbsp;</td></tr>";

		$tendertotalsQ = "SELECT t.TenderName as tender_type,ROUND(sum(d.total),2) as total,COUNT(*) as count
			FROM is4c_log.dtransactions d RIGHT JOIN is4c_op.tenders t
			ON d.trans_subtype = t.TenderCode
			AND DATE(d.datetime) = '$date'
			and d.emp_no = $emp_no
			AND d.emp_no <> 9999
			GROUP BY t.TenderName";

		$results_ttq = mysql_query($tendertotalsQ);


		while($row = mysql_fetch_row($results_ttq))	{
			if(!isset($row[0]))	{
				$receipt .= "<tr><td>NULL</td>";
			}else{
				$receipt .= "<tr><td>".$row[0]."</td>";
			}
			if(!isset($row[1])) { 
				$receipt .= "<td align=right>0.00</td>";
			}else{
				$receipt .= "<td align=right>".money_format("%n",-$row[1]). "</td>";
			}
			if(!isset($row[2])) { 
				$receipt .= "<td>NULL</td>";
			}else{
				if(!isset($row[1])) {
					$row[2] = 0;
				}
				$receipt .= "<td align=right>".$row[2]."</td>";
			}
			$receipt .= "</tr>";
		} $receipt .= "<tr><td colspan=3>&nbsp;</td></tr>";

		$cack_tot = "SELECT ROUND(SUM(total),2) AS gross
			FROM is4c_log.dtransactions
			where DATE(datetime) = '$date'
			and emp_no = $emp_no
			AND trans_subtype IN ('CA','CK')";
		$results_tot = mysql_query($cack_tot);
		$row = mysql_fetch_row($results_tot);

		$receipt .= "<tr><td>CA &amp; CK Total: </td><td align=right>";
		$receipt .= money_format("%n",-$row[0])."</td><td>&nbsp;</td></tr>";

		$card_tot = "SELECT ROUND(SUM(total),2) AS gross
			FROM is4c_log.dtransactions
			where DATE(datetime) = '$date'
			and emp_no = $emp_no	
			AND trans_subtype IN ('DC','CC','FS','EC')";
		$results_tot = mysql_query($card_tot);
		$row = mysql_fetch_row($results_tot);

		$receipt .= "<tr><td>DC / CC / EBT Total: </td><td align=right>";
		$receipt .= money_format("%n",-$row[0])."</td><td>&nbsp;</td></tr>";

		$hchrg_tot = "SELECT ROUND(SUM(total),2) AS gross
			FROM is4c_log.dtransactions
			where DATE(datetime) = '$date'
			and emp_no = $emp_no
			AND trans_subtype = 'MI'
			AND card_no <> 9999";
		$results_tot = mysql_query($hchrg_tot);
		$row = mysql_fetch_row($results_tot);

		$receipt .= "<tr><td>House Charge Total: </td><td align=right>";
		$receipt .= money_format("%n",-$row[0])."</td><td>&nbsp;</td></tr>";

		$schrg_tot = "SELECT ROUND(SUM(total),2) AS gross
			FROM is4c_log.dtransactions
			where DATE(datetime) = '$date'
			and emp_no = $emp_no
			AND trans_subtype = 'MI'
			AND card_no = 9999";
		$results_tot = mysql_query($schrg_tot);
		$row = mysql_fetch_row($results_tot);

		$receipt .= "<tr><td>Store Charge Total: </td><td align=right>";
		$receipt .= money_format("%n",-$row[0])."</td><td>&nbsp;</td></tr>";

		$receipt .= "</table><br><br><br><br>";

		mysql_close();		
	}
//}
// ----------------------------------------------------------------------------------------------------
/*
// 	$receipt .= '<center>C H E C K &nbsp;&nbsp;  T E N D E R S</center><br>';
// 
// 	$receipt .=	'<center>------------------------------------------------------</center>';
//  
// 	$result_ckq = mysql_query($query_ckq);
// 	$num_rows_ckq = mysql_num_rows($result_ckq);
// 
// 	if ($num_rows_ckq > 0) {
// 
// 		$receipt .= '<center>'.$fieldNames.'</center>';
// 
// 		for ($i = 0; $i < $num_rows_ckq; $i++) {
// 
// 			$row_ckq = mysql_fetch_array($result_ckq);
// 			$timeStamp = timeStamp($row_ckq["tdate"]);
// 			$receipt .= "  ".substr($timeStamp.$blank, 0, 10)
// 				.substr($row_ckq["register_no"].$blank, 0, 7)
// 				.substr($row_ckq["trans_no"].$blank, 0, 6)
// 				.substr($row_ckq["emp_no"].$blank, 0, 6)
// 				.substr($blank.number_format($row_ckq["changeGiven"], 2), -10)
// 				.substr($blank.number_format($row_ckq["ckTender"], 2), -10)."<br>";
// 		}
// 
// 		$receipt .=	'<center>------------------------------------------------------</center>';
// 
// //		$query_ckq = "select * from cktendertotal where register_no = ".$_SESSION["laneno"];
// //		$result_ckq = sql_query($query_ckq);
// //		$row_ckq = sql_fetch_array($result_ckq);
// 
// 		$query_ckq = "select SUM(total) from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no and trans_subtype = 'CK'";
// 		$result_ckq = mysql_query($query_ckq);
// 		$row_ckq = mysql_fetch_array($result_ckq);
// 
// 		$receipt .= substr($blank.$blank.$blank.$blank."Total: ".number_format($row_ckq[0],2), -56)."<br>";
// 
// 	}
// 	else {
// 		$receipt .= '<br><br><center>* * * &nbsp;  N O N E &nbsp; * * * <br><br>
// 			------------------------------------------------------</center>';
// 	}
// 
// 	$receipt .= str_repeat("<br>", 3);	// apbw/tt 3/16/05 Franking II
// 
// 	$receipt .= '<center>D E B I T &nbsp;&nbsp; C A R D &nbsp;&nbsp; T E N D E R S</center><br>';
// 
// 	$receipt .=	'<center>------------------------------------------------------</center>';
//  
// 	$result_dcq = mysql_query($query_dcq);
// 	$num_rows_dcq = mysql_num_rows($result_dcq);
// 
// 	if ($num_rows_dcq > 0) {
// 
// 		$receipt .= $fieldNames;
// //		$receipt .= "<table width=100% align=center border=0>";
// 		for ($i = 0; $i < $num_rows_dcq; $i++) {
// 			$row_dcq = mysql_fetch_array($result_dcq);
// 			$receipt .= "<tr><td>" . strftime("%I:%M %p",strtotime($row_dcq['datetime'])) . "</td><td>" .
// 				$row_dcq["register_no"] . "</td><td>" . 
// 				$row_dcq["trans_no"] . "</td><td>" . 
// 				$row_dcq["emp_no"] . "</td><td align=right>" . 
// 				money_format("%n", $row_dcq["unitPrice"])."</td><td align=right>" . 
// 				money_format("%n",$row_dcq["total"])."</td></tr>";
// 		} $receipt .= "</table>";
// 		
// 		$receipt .=	'<center>------------------------------------------------------</center>';
// 
// //		$query_dcq = "select * from dctendertotal where emp_no = ".$_SESSION["CashierNo"];
// //		$result_dcq = sql_query($query_dcq);
// //		$row_dcq = sql_fetch_array($result_dcq);
// 
// 		$query_dcq = "select SUM(total) from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no and trans_subtype = 'DC'";
// 		$result_dcq = mysql_query($query_dcq);
// 		$row_dcq = mysql_fetch_array($result_dcq);
// 
// 		$receipt .= "<p align=right>Total: ".money_format("%n",$row_dcq[0])."</p>";
// 	}
// 	else {
// 		$receipt .= '<br><br><center>* * * &nbsp; N O N E &nbsp; * * * <br><br>
// 			------------------------------------------------------</center>';
// 	}
// 
// 	$receipt .= str_repeat("<br>", 3);	// apbw/tt 3/16/05 Franking II
// 
// 	$receipt .= '<center>C R E D I T  &nbsp;&nbsp; C A R D  &nbsp;&nbsp; T E N D E R S</center><br>';
// 	$receipt .=	'<center>------------------------------------------------------</center>';
//  
// 	$result_ccq = mysql_query($query_ccq);
// 	$num_rows_ccq = mysql_num_rows($result_ccq);
// 
// 	if ($num_rows_ccq > 0) {
// 
// 		$receipt .= $fieldNames;
// 
// 		for ($i = 0; $i < $num_rows_dcq; $i++) {
// 			$row_dcq = mysql_fetch_array($result_ccq);
// 			$receipt .= "<tr><td>" . strftime("%I:%M %p",strtotime($row_dcq['datetime'])) . "</td><td>" .
// 				$row_dcq["register_no"] . "</td><td>" . 
// 				$row_dcq["trans_no"] . "</td><td>" . 
// 				$row_dcq["emp_no"] . "</td><td align=right>" . 
// 				money_format("%n", $row_dcq["unitPrice"])."</td><td align=right>" . 
// 				money_format("%n",$row_dcq["total"])."</td></tr>";
// 		} $receipt .= "</table>";
// 
// 		$receipt .=	'<center>------------------------------------------------------</center>';
// 
// 		$query_ccq = "select SUM(total) from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no and trans_subtype = 'CC'";
// 		$result_ccq = mysql_query($query_ccq);
// 		$row_ccq = mysql_fetch_array($result_ccq);
// 
// 		$receipt .= "<p align=right>Total: ".money_format("%n",$row_ccq[0])."</p>";
// 	}
// 	else {
// 		$receipt .= '<br><br><center>* * *  &nbsp; N O N E  &nbsp; * * * <br><br>
// 			------------------------------------------------------</center>';
// 	}
// 
// 	$receipt .= str_repeat("<br>", 3);	// apbw/tt 3/16/05 Franking II
// 
// 	$receipt .= "<center>C H A R G E &nbsp;&nbsp;  T E N D E R S</center><br>";
// 	$receipt .=	'<center>------------------------------------------------------</center>';
// 
// 	$result_miq = mysql_query($query_miq);
// 	$num_rows_miq = mysql_num_rows($result_miq);
// 
// 	if ($num_rows_miq > 0) {
// 		
// 		$chgFieldNames = "  ".substr("Time".$blank, 0, 10)
// 				.substr("Lane".$blank, 0, 7)
// 				.substr("Trans #".$blank, 0, 6)
// 				.substr("Emp #".$blank, 0, 8)
// 				.substr("Member #".$blank, 0, 10)
// 				.substr("Amount".$blank, 0, 10)."<br>";
// 		
// 		$receipt .= $chgFieldNames;
// 
// 		for ($i = 0; $i < $num_rows_miq; $i++) {
// 			$row_miq = mysql_fetch_array($result_miq);
// 			$timeStamp = timeStamp($row_miq["tdate"]);
// 			$receipt .= "  ".substr($timeStamp.$blank, 0, 10)
// 				.substr($row_miq["register_no"].$blank, 0, 7)
// 				.substr($row_miq["trans_no"].$blank, 0, 6)
// 				.substr($row_miq["emp_no"].$blank, 0, 6)
// 				.substr($row_miq["card_no"].$blank, 0, 6)
// 				.substr($blank.number_format($row_miq["MiTender"], 2), -10)."<br>";
// 
// 		}
// 
// 		$receipt .=	'<center>------------------------------------------------------</center>';
// 
// //		$query_miq = "select * from mitendertotal where register_no = ".$_SESSION["laneno"];
// //		$result_miq = sql_query($query_miq);
// //		$row_miq = sql_fetch_array($result_miq);
// 
// 		$query_miq = "select SUM(total) from is4c_log.dtransactions where DATE(datetime) = '$date' and emp_no = $emp_no and trans_subtype = 'MI'";
// 		$result_miq = mysql_query($query_miq);
// 		$row_miq = mysql_fetch_array($result_miq);
// 
// 		$receipt .= substr($blank.$blank.$blank.$blank."Total: ".number_format($row_miq[0],2), -56)."<br>";
// 	}
// 	else {
// 		$receipt .= '<br><br><center>* * * &nbsp;  N O N E &nbsp;  * * * <br><br>
// 			------------------------------------------------------</center>';
// 	}
// 
// 	$receipt .= str_repeat("<br>", 3);	// apbw/tt 3/16/05 Franking II
// 
// //--------------------------------------------------------------------
// 
// 		$receipt .= '<center>T R A N S I T &nbsp;&nbsp;  P A S S E S  &nbsp;&nbsp; S O L D</center><br>';
// 		$receipt .=	'<center>------------------------------------------------------</center>';
// 
// 	$result_bp = mysql_query($query_bp);
// 	$num_rows_bp = mysql_num_rows($result_bp);
// 
// 	if ($num_rows_bp > 0) {
// 
// 		$receipt .= $fieldNames;
// 
// 		for ($i = 0; $i < $num_rows_bp; $i++) {
// 
// 			$row_bp = mysql_fetch_array($result_bp);
// 			$timeStamp = timeStamp($row_bp["tdate"]);
// 			$receipt .= "  ".substr($timeStamp.$blank, 0, 10)
// 				.substr($row_bp["register_no"].$blank, 0, 7)
// 				.substr($row_bp["trans_no"].$blank, 0, 6)
// 				.substr($row_bp["emp_no"].$blank, 0, 6)
// 				.substr($blank.($row_bp["upc"]), -10)
// 				.substr($blank.number_format($row_bp["total"], 2), -10)."<br>";
// 		}
// 
// 		$receipt .=	'<center>------------------------------------------------------</center>';
// 	}
// 	else {
// 		$receipt .= '<br><br><center>* * * &nbsp;  N O N E &nbsp;  * * * <br><br>
// 			------------------------------------------------------</center>';
// 	}
// 
// 	$receipt .= str_repeat("<br>", 8);	// apbw/tt 3/16/05 Franking II
// 
// //	writeLine($receipt.chr(27).chr(105));	// apbw/tt 3/16/05 Franking II
// 	mysql_close();
// 
// 
*/
echo '<HTML><HEAD></HEAD>';
echo '<BODY onLoad="window.print()">';

echo '<table align=center width=350px border=1 cellspacing=0 cellpadding=10><tr><td>';
echo $receipt;
echo '</td></tr></table>';
echo "<br><center>Report generated: " . date("g:i:s a - n/j/y") . "</center>";

echo '</BODY></HTML>';

function debug_p($var, $title) 
{
    print "<p>$title</p><pre>";
    print_r($var);
    print "</pre>";
}  

?>