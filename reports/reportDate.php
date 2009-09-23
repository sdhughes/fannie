<?php
//
//
// Copyright (C) 2007
// authors: Christof Van Rabenau - Whole Foods Cooperative,
// Joel Brock - People's Food Cooperative
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
                echo "Date entered: ".$date;
        }
        if (($_POST['date'] == '1969-12-31') || (!isset($_POST['date']))) {
                $date = date('Y-m-d');
                echo "Date entered: ".$date;
        }

        echo "<br />";

        if (strpbrk($date, "-") == false) {
            $dateArray = explode("/",$date);
            $db_date = date('Y-m-d', mktime(0, 0, 0, $dateArray[0], $dateArray[1], $dateArray[2]));
        } elseif (strpbrk($date, "/") == false) {
            $dateArray = explode("-",$date);
            $db_date = date('Y-m-d', mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]));
        }

        echo "<br>Report run " .date('Y-m-d'). " for ";

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

        $inventoryDeptQ = "SELECT t.dept_no AS Department ,t.dept_name AS 'Department Name',ROUND(sum(d.total),2) AS 'Department Total',ROUND((SUM(d.total)/$gross)*100,2) as 'Percent of Store Sales'
                FROM $transtable AS d RIGHT JOIN is4c_op.departments AS t
                ON d.department = t.dept_no
                AND date(d.datetime) = '".$db_date."'
                AND d.department <> 0
                AND d.trans_status <> 'X'
                AND d.trans_subtype <> 'MC'
                AND d.emp_no <> 9999
                GROUP BY t.dept_no
                ORDER BY t.dept_no
                LIMIT 27";

        /**
         * Sales for non-inventory departments
         */

        $noninventoryDeptQ = "SELECT d.department AS Department,t.dept_name AS 'Department Name',ROUND(sum(total),2) as 'Department Total'
                FROM $transtable as d RIGHT JOIN is4c_op.departments as t
                ON d.department = t.dept_no
                AND date(d.datetime) = '".$db_date."'
                AND d.department > 35
                AND d.trans_status <> 'X'
                AND d.trans_subtype <> 'MC'
                AND d.emp_no <> 9999
                GROUP BY t.dept_no
                ORDER BY t.dept_no
                LIMIT 31,7";

        $dept_subtotalQ = "SELECT ROUND(SUM(d.total),2) AS 'Department Subtotal'
                FROM $transtable d
                WHERE date(d.datetime) = '".$db_date."'
                AND d.department <= 45 AND d.department <> 0 AND d.trans_subtype <> 'MC'
                AND d.emp_no <> 9999 AND d.trans_status <> 'X'";

        /*
         * pull tender report.
         */

        $tendersQ = "SELECT t.TenderName as 'Tender Type',ROUND(-sum(d.total),2) as Total,COUNT(*) as Count
                FROM $transtable as d,is4c_op.tenders as t
                WHERE d.trans_subtype = t.TenderCode
                AND date(d.datetime) = '".$db_date."'
                AND d.trans_status <> 'X'
                AND d.emp_no <> 9999
                GROUP BY t.TenderName";

        $storeChargeQ = "SELECT COUNT(total) AS 'Store Charge Count', ROUND(-SUM(d.total),2) AS 'Store Charge Total'
                FROM $transtable AS d
                WHERE d.trans_subtype = 'MI'
                AND card_no = 9999
                AND d.trans_status <> 'X'
                AND date(d.datetime) = '".$db_date."'
                AND d.emp_no <> 9999";

        $houseChargeQ = "SELECT COUNT(total) AS 'House Charge Count', ROUND(-SUM(d.total),2) AS 'House Charge Total'
                FROM $transtable AS d
                WHERE d.trans_subtype = 'MI'
                AND card_no != 9999
                AND d.trans_status <> 'X'
                AND date(d.datetime) = '".$db_date."'
                AND d.emp_no <> 9999";

        $transCountQ = "SELECT COUNT(d.total) as 'Transaction Count'
                FROM $transtable AS d
                WHERE date(d.datetime) = '".$db_date."'
                AND d.trans_status <> 'X'
                AND d.emp_no <> 9999
                AND d.upc = 'DISCOUNT'";

                $transCountR = mysqli_query($db_slave, $transCountQ);
                list($count) = mysqli_fetch_row($transCountR);


        $basketSizeQ = "SELECT ROUND(($gross/$count),2) AS 'Average Bag'";

        /**
         * Sales of equity
         * ACG Equity dept. = 45
        */

        $sharePaymentsQ = "SELECT d.card_no AS 'Card Number',t.dept_name AS 'Department Name',ROUND(sum(total),2) as Total
                FROM $transtable as d JOIN is4c_op.departments as t ON d.department = t.dept_no
                WHERE date(datetime) = '".$db_date."'
                AND d.department = 45
                AND d.trans_status <> 'X'
                AND d.emp_no <> 9999
                GROUP BY d.card_no, t.dept_name";

        /*
        $shareCountQ = "SELECT COUNT(total) AS peopleshare_count
                FROM $transtable
                WHERE date(datetime) = '".$db_date."'
                AND description = 'MEMBERSHIP EQUITY'
                AND trans_status <> 'X'
                AND emp_no <> 9999";

                $shareCountR = mysqli_query($db_slave, $shareCountQ);
                $row = mysql_fetch_row($shareCountR);
                $shareCount = $row[0];
        */
        /**
         * Discounts by member type;
         */

        $memtypeQ = "SELECT m.memDesc as memType,ROUND(SUM(d.total),2) AS Sales
                FROM $transtable d INNER JOIN
                        is4c_op.custdata c ON d.card_no = c.CardNo INNER JOIN
                        is4c_op.memtype m ON c.memType = m.memtype
                WHERE date(d.datetime) = '".$db_date."'
                AND d.trans_type IN('I','D')
                AND d.trans_status <>'X'
                AND d.department <= 35 AND d.department <> 0
                AND d.upc <> 'DISCOUNT'
                AND d.emp_no <> 9999
                GROUP BY c.memtype";

        /*
        $MADcouponQ = "SELECT (ROUND(SUM(unitPrice),2)) AS MAD_Coupon_total
                FROM $transtable
                WHERE date(datetime) = '".$db_date."'
                AND trans_subtype = 'IC'
                AND voided = 9
                AND trans_status <> 'X'
                AND emp_no <> 9999";
        */

        /**
         * Customer Services - Tri-met, stamps, sisters of the road coupons
         */

        $trimetQ = "SELECT SUM(quantity) AS 'Trimet Count', ROUND(SUM(total),2) AS 'Trimet Sales'
                FROM $transtable
                WHERE date(datetime) = '".$db_date."'
                AND department = 40
                AND trans_status <> 'X'
                AND emp_no <> 9999";

        /**
         * Miscellaneus - store charges, R/As, returns
         */

        $returnsQ = "SELECT SUM(quantity) AS 'Returns Count', ROUND(sum(l.total),2) as 'Returns Total'
                FROM $transtable as l
                WHERE date(datetime) = '".$db_date."'
                AND l.department < 20 AND l.department <> 0
                AND l.trans_status = 'R'
                AND l.emp_no <> 9999";


        ////////////////////////////
        //
        //
        //  NOW....SPIT IT ALL OUT....
        //
        //
        ////////////////////////////


        echo $db_date . '<br>';
        echo '<font size = 2>';
        echo '<h4>Sales - Gross & NET</h4>';
        /**
        * total sales
        * Gross = total of all inventory depts. 1-15 (at PFC)
        * Hash = People Shares + General Donations + Customers Svcs. + gift certs. sold + Bottle Deposits & Returns + Comm. Rm. fees
        * Net = Gross + Everything else + R/A (45) - Market EBT (37) - Charge pmts.(35) - All discounts - Coupons(IC & MC) -
        * 		Gift Cert. Tender - Store Charge
        */

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
       //	Total Staff discount given less the needbased and MAD discount
       //
       $staffQ = "SELECT (SUM(d.unitPrice)) AS 'Staff Total'
               FROM $transtable AS d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'DISCOUNT'
               AND d.staff IN (1,2,5)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

               $staffR = mysqli_query($db_slave, $staffQ);
               list($staff_total) = mysqli_fetch_row($staffR);

               if (is_null($staff_total)) {
                       $staff_total = 0;
               }

       //
       //	END STAFF_TOTAL
       //

       //
       //	BEGIN MEM_TOTAL
       //
       $memQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'DISCOUNT'
               AND d.memType IN (1,2,3,4,5)
               AND d.staff = 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

               $memR = mysqli_query($db_slave, $memQ);
               list($mem_total) = mysqli_fetch_row($memR);

               if(is_null($mem_total)) {
                       $mem_total = 0;
               }
       //
       //	BEGIN WM_TOTAL
       //
       $wmQ = "SELECT SUM(d.unitPrice) AS 'Working Member'
               FROM $transtable AS d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'DISCOUNT'
               AND d.staff IN (3,6)
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

               $wmR = mysqli_query($db_slave, $wmQ);
               list($wms) = mysqli_fetch_row($wmR);


               if (is_null($wms)) {
                       $wms = 0;
               }

       $wm_totalQ = "SELECT ($wms) AS hoo_total";

               $wm_totalR = mysqli_query($db_slave, $wm_totalQ);
               list($wm_total) = mysqli_fetch_row($wm_totalR);

               if (is_null($wm_total)) {
                       $wm_total = 0;
               }
       //
       //	END WM_TOTAL
       //

       //
       //       BOARD DISCOUNT
       //

              $boardQ = "SELECT SUM(d.unitPrice) AS 'Board Member'
               FROM $transtable AS d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'DISCOUNT'
               AND d.staff = 4
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

               $boardR = mysqli_query($db_slave, $boardQ);
               list($board) = mysqli_fetch_row($boardR);

               if (is_null($board)) {
                       $board = 0;
               }


       //
       //       BOARD DISCOUNT
       //

       //
       //	NON-OWNER/WORKER DISCOUNTS
       //
       $nonQ = "SELECT SUM(d.total) as non
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'DISCOUNT'
               AND d.memType IN (0, 7)
               AND d.staff = 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

               $nonR = mysqli_query($db_slave, $nonQ);
               list($non_total) = mysqli_fetch_row($nonR);

               if(is_null($non_total)) {
                       $non_total = 0;
               }



       //
       //	BEGIN SISTER_ORGS
       //
       $sisterQ = "SELECT (ROUND(SUM(d.unitPrice),2)) AS 'Sister Orgs'
               FROM $transtable AS d
               WHERE (date(d.datetime) = '".$db_date."')
               AND d.upc = 'DISCOUNT'
               AND d.memType = 6
               AND d.trans_status <> 'X'
               AND d.emp_no <> 9999";

               $sisterR = mysqli_query($db_slave, $sisterQ);
               list($sister) = mysqli_fetch_row($sisterR);

               if (is_null($sister)) {
                       $sister = 0;
               }

       $sister_orgQ = "SELECT ($sister) AS 'Sister Orgs'";

               $sister_orgR = mysqli_query($db_slave, $sister_orgQ);
               list($sister_org) = mysqli_fetch_row($sister_orgR);

               if (is_null($sister_org)) {
                       $sister_org = 0;
               }
       //
       //	END SISTER_ORGS
       //
       /* Case Discount Tracking */
        // Non-Member
        $nonCaseQ = "SELECT SUM(d.total) as non
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'CASEDISCOUNT'
               AND d.memType in (0, 7)
               AND d.staff = 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

        $nonCaseR = mysqli_query($db_slave, $nonCaseQ);
        list($nonCase) = mysqli_fetch_row($nonCaseR);

        if (is_null($nonCase)) {
                $nonCase = 0;
        }

        // Member
        $memCaseQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'CASEDISCOUNT'
               AND d.memType IN (1,2,3,4,5)
               AND d.staff = 0
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

        $memCaseR= mysqli_query($db_slave, $memCaseQ);
        list($memCase) = mysqli_fetch_row($memCaseR);

        if (is_null($memCase)) {
                $memCase = 0;
        }

        // Working Member & NOV
        $wmemCaseQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'CASEDISCOUNT'
               AND d.staff IN (3,6)
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

        $wmemCaseR= mysqli_query($db_slave, $wmemCaseQ);
        list($wmemCase) = mysqli_fetch_row($wmemCaseR);

        if (is_null($wmemCase)) {
                $wmemCase = 0;
        }

        // Board
        $bdmemCaseQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'CASEDISCOUNT'
               AND d.staff = 4
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

        $bdmemCaseR= mysqli_query($db_slave, $bdmemCaseQ);
        list($bdmemCase) = mysqli_fetch_row($bdmemCaseR);

        if (is_null($bdmemCase)) {
                $bdmemCase = 0;
        }

        // Sister Orgs
        $sisCaseQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'CASEDISCOUNT'
               AND d.memType = 6
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

        $sisCaseR= mysqli_query($db_slave, $sisCaseQ);
        list($sisCase) = mysqli_fetch_row($sisCaseR);

        if (is_null($sisCase)) {
                $sisCase = 0;
        }

        // Staff, Sub, SO
        $staffCaseQ = "SELECT SUM(d.total) as 'Member'
               FROM $transtable d
               WHERE date(d.datetime) = '".$db_date."'
               AND d.upc = 'CASEDISCOUNT'
               AND d.staff IN (1,2,5)
               AND d.emp_no <> 9999
               AND d.trans_status <> 'X'";

        $staffCaseR= mysqli_query($db_slave, $staffCaseQ);
        list($staffCase) = mysqli_fetch_row($staffCaseR);

        if (is_null($staffCase)) {
                $staffCase = 0;
        }

        // Add it all up.
        $totalCaseQ = "SELECT SUM(total) AS totaldisc
               FROM $transtable
               WHERE date(datetime) = '" . $db_date . "'
               AND upc = 'CASEDISCOUNT'
               AND trans_status <> 'X'
               AND emp_no <> 9999";

        $totalCaseR = mysqli_query($db_slave, $totalCaseQ);
        list($totalCase) = mysqli_fetch_row($totalCaseR);
        if (is_null($totalCase)) {
                $totalCase = 0;
        }
       // End Case Discount Queries

       // Haus - 08-03-2007
       $totaldiscount2Q = "SELECT SUM(total) AS totaldisc
               FROM $transtable
               WHERE date(datetime) = '" . $db_date . "'
               AND upc = 'DISCOUNT'
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

       $chgQ = "SELECT ROUND(SUM(total),2) AS chg
               FROM $transtable
               WHERE date(datetime) = '".$db_date."'
               AND trans_subtype IN ('MI')
               AND trans_status <> 'X'
               AND emp_no <> 9999";

               $chgR = mysqli_query($db_slave, $chgQ);
               list($chg) = mysqli_fetch_row($chgR);

               if (is_null($chg)) {
                       $chg = 0;
               }

       $totalDiscQ = "SELECT ($mem_total + $staff_total + $sister_org + $wm_total + $board + $non_total) as total_discounts";

               $totalDiscR = mysqli_query($db_slave, $totalDiscQ);
               list($totalDisc) = mysqli_fetch_row($totalDiscR);

       //$netQ = "SELECT ($gross + $hash + $totalDisc + $coupons + $strchg + $RA + $other) as NET_TOTAL";
       $netQ = "SELECT ($gross + $totalDisc + $totalCase) as NET_TOTAL";

               $netR = mysqli_query($db_slave, $netQ);
               list($net) = mysqli_fetch_row($netR);

       // Haus edit...checking (08-06-07)
       echo "<table border=0><tr><td>Gross Total</td><td align=right>".money_format('%n',$gross2)."</td></tr>";
       echo "<tr><td>Total Discount</td><td align=right>".money_format('%n',$totaldiscount2)."</td></tr>";
       echo "<tr><td>Total Case Discount</td><td align=right>".money_format('%n',$totalCase)."</td></tr>";
       echo "<tr><td>Instore Coupons</td><td align=right>".money_format('%n',$coupons2)."</td></tr>";
       echo "<tr><td>&nbsp;</td><td align=right>+___________</td></tr>";
       echo "<tr><b><td><b>Net Total</b></td><td align=right><b>".money_format('%n',$net2)."</b></td></tr></table>";

       // End haus edit.

        echo '------------------------------<br>';
        echo '<h4>Sales by Inventory Dept.</h4>';
        select_to_table($db_slave, $inventoryDeptQ,0,'FFCC99');
        echo '<h4>Sales by Non-Inventory Dept.</h4>';
        select_to_table($db_slave, $noninventoryDeptQ,0,'FFCC99');
        // Haus add 08-03-2007
        echo '<h4>Department Subtotal:</h4>';
        select_to_table($db_slave, $dept_subtotalQ,0,'FFCC99');
        // end Haus add 08-03-2007
        echo '------------------------------<br>';
        echo '<h4>Tender Report</h4>';
        select_to_table($db_slave, $tendersQ,0,'FFCC99');									// sales by tender type
        select_to_table($db_slave, $storeChargeQ,0,'FFCC99');								// store charges
        select_to_table($db_slave, $houseChargeQ,0,'FFCC99');								// house charges
        select_to_table($db_slave, $transCountQ,0,'FFCC99');								// transaction count
        select_to_table($db_slave, $basketSizeQ,0,'FFCC99');								// basket size
        echo '------------------------------<br>';
        echo '<h4>Membership & Discount Totals</h4><br>';
        echo "<p><b>Discount Totals</b></p>";
        echo "<table border=0><font size=2>";

        // Specialize reporting based upon whether it is a Member Appreciation Day or not.
        if ($MAD == true) {
            echo "<tr><td>Member Total</td><td align=right>".money_format('%n',$mem_total + $wm_total)."</td></tr>";
            echo "<tr><td>Staff Total</td><td align=right>".money_format('%n',$staff_total)."</td></tr>";
            echo "<tr><td>Working Member Total</td><td align=right>".money_format('%n',0)."</td></tr>";
        } elseif ($MAD == false) {
            echo "<tr><td>Member Total</td><td align=right>".money_format('%n',$mem_total)."</td></tr>";
            echo "<tr><td>Staff Total</td><td align=right>".money_format('%n',$staff_total)."</td></tr>";
            echo "<tr><td>Working Member Total</td><td align=right>".money_format('%n',$wm_total)."</td></tr>";
        }

        echo "<tr><td>Board Member Total</td><td align=right>".money_format('%n',$board)."</td></tr>";
        echo "<tr><td>Sister Organizations Total</td><td align=right>".money_format('%n',$sister_org)."</td></tr>";
        echo "<tr><td>Non-owner Total</td><td align=right>".money_format('%n',$non_total)."</td></tr>";
        echo "<tr><td>&nbsp;</td><td align=right>+___________</td></tr>";
        echo "<tr><td><b>Total Discount</td><td align=right>".money_format('%n',$totalDisc)."</b></td></tr></font></table><br />";
        echo "<p><b>Case Discount Totals</b></p>";
        echo "<table border=0><font size=2>";
        echo "<tr><td>Member Total</td><td align=right>".money_format('%n',$memCase)."</td></tr>";
        echo "<tr><td>Staff Total</td><td align=right>".money_format('%n',$staffCase)."</td></tr>";
        echo "<tr><td>Working Member Total</td><td align=right>".money_format('%n',$wmemCase)."</td></tr>";
        echo "<tr><td>Board Member Total</td><td align=right>".money_format('%n',$bdmemCase)."</td></tr>";
        echo "<tr><td>Sister Organizations Total</td><td align=right>".money_format('%n',$sisCase)."</td></tr>";
        echo "<tr><td>Non-owner Total</td><td align=right>".money_format('%n',$nonCase)."</td></tr>";
        echo "<tr><td>&nbsp;</td><td align=right>+___________</td></tr>";
        echo "<tr><td><b>Total Case Discount</td><td align=right>".money_format('%n',$totalCase)."</b></td></tr></font></table><br />";
        echo "<p><b>Shares Sales</b></p>";
        select_to_table($db_slave, $sharePaymentsQ,0,'FFCC99');							// peopleshare payments
        echo '<br>------------------------------<br>';
        echo '<h4>Miscellaneous</h4>';
        select_to_table($db_slave, $returnsQ,0,'FFCC99');									// total returns
        select_to_table($db_slave, $trimetQ,0,'FFCC99');									// Tri-Met sales
        select_to_table($db_slave, $sistersQ,0,'FFCC99');									// Sisters sales
        echo '</font>';



} else { // Show the form.

        $page_title = 'Fannie - Reports Module';
        $header = 'Day End Report';
        include('../includes/header.html');
        ?>
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
        <link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/demo.css" />
        <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
        <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
        <script type="text/javascript" src="../includes/javascript/datepicker/jquery.datePicker-2.1.2.js"></script>
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
