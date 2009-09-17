<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

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
require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_op');
$getBatchIDQ = "SELECT max(batchID) FROM batches";
$getBatchIDR = mysqli_query($db_master, $getBatchIDQ);
$getBatchIDW = mysqli_fetch_row($getBatchIDR);

$batchID = $_GET['batchID'];

foreach ($_POST AS $key => $value) {
    $$key = $value;
}

if ($getBatchIDW[0] < $batchID) {
   if ($batchType == 6) {
      $discounttype = 2;
   } else {
      $discounttype = 1;
   }
   if ($endDate > $startDate) {
         $insBatchQ = "INSERT INTO batches(startDate,endDate,batchName,batchType,discounttype) 
                 VALUES('$startDate','$endDate','$batchName',$batchType,$discounttype)";
        $insBatchR = mysqli_query($db_master, $insBatchQ);
   } else {
        $header = 'Batch Maintanence';
        $page_title = 'Fannie - Batch Module';
        include ('../includes/header.html');
        echo "<p><font color='red'>Error: Your sale batch can't end before it's started. Please try again.</font></p>";
        include ('../includes/footer.html');
        exit();
   }
}
?>
	<FRAMESET rows='40,*' frameborder='0'>
	   <FRAME src='addItems.php?batchID=<? echo $batchID; ?>' name='add' border='0' scrolling='no'>
	   <FRAME src='batches.php?batchID=<? echo $batchID; ?>' name='items' border='0' scrolling='yes'>
	</FRAMESET>
