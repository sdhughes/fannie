<?php

if (isset($_POST['submitted'])) {
    /**
     * fpdf is the pdf creation class doc
     * manual and tutorial can be found in fpdf dir
     */
    require_once ('../src/fpdf/fpdf.php');
    //require_once ('../src/fpdf/circleTagsExtensions.php');

    /**--------------------------------------------------------
     *            begin  barcode creation class from
     *--------------------------------------------------------*/

    /*******************************************************************************
    * Software: barcode                                                            *
    * Author:   Olivier PLATHEY                                                    *
    * License:  Freeware                                                           *
    * URL: www.fpdf.org                                                            *
    * You may use, modify and redistribute this software as you wish.              *
    *******************************************************************************/
    define('FPDF_FONTPATH','font/');
    require_once ('../includes/shelftags.fpdf');

    /**------------------------------------------------------------
     *        Start creation of PDF Document here
     *------------------------------------------------------------*/

    if (isset($_POST['submit'])) {
        foreach ($_POST AS $key => $value) {
            $$key = $value;
        }
    } else {
        foreach ($_GET AS $key => $value) {
            $$key = $value;
        }
    }

    $_SESSION['deptArray'] = 0;

    if ($_POST['allDepts'] == 1) {
        $dArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,40";
    } else {
        $allDepts = 0;
    }

    if (is_array($_POST['dept'])) {
        $dArray = implode(",",$_POST['dept']);
    }

    foreach ($dept as $deptno) {
        if ($deptno == 7 || $deptno == 3 || $deptno == 4 || $deptno == 16) {$small = 'SMALL';}
    }

    if ($small != 'SMALL') {$small = 'LARGE';}

    /**
     * connect to mysql server and then
     * set to database with UNFI table ($data) in it
     * other vendors could be added here, as well.
     * NOTE: upc in UNFI is without check digit to match standard in
     * products.
     */

    $data = 'is4c_op';

    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, $data);

    /**
     * $testQ query creates select for barcode labels for items
     */

    $testQ = "SELECT IF(pd.brand IS NULL,'',SUBSTRING(pd.brand,1,20)) AS brand,
            IF(pd.order_no IS NULL,'', pd.order_no) AS sku,
            IF(pd.pack_size IS NULL,'',pd.pack_size) AS size,
            IF(pd.upc IS NULL,'',pd.upc) AS upc,
            IF(pd.product IS NULL, SUBSTRING(p.description,1,25),SUBSTRING(pd.product,1,25)) AS description,
            RIGHT(p.upc,12) AS pid,
            IF(pd.distributor IS NULL, 'Misc', pd.distributor) AS vendor,
            ROUND(normal_price,2) AS normal_price,
            p.scale AS scale
        FROM products AS p LEFT OUTER JOIN product_details AS pd ON p.upc = pd.upc
        WHERE p.department IN($dArray)

        AND p.inUse = 1
        AND p.discounttype <> 3
        AND DATE(modified) BETWEEN '$date1' AND '$date2'
        ORDER BY department";


    $result = mysqli_query($db_slave, $testQ);
    if (!$result) {
       $message  = 'Invalid query: ' . mysqli_error() . "\n";
       $message .= 'Whole query: ' . $query;
       die($message);
    }

    if ($_POST['type'] == 'BIG') {
        /**
         * begin to create PDF file using fpdf functions
         */
        if ($small == 'SMALL') {
            $hspace = 3.571875;
            $h = 23.8125;
        } elseif ($small == 'LARGE') {
            $hspace = 0.79375;
            $h = 29.36875;
        }
        $top = 12.7 + 2.5;
        $left = 4.85 + 1.25;
        $space = 1.190625 * 2;

        $pdf=new PDF('P', 'mm', 'Letter');
        $pdf->SetMargins($left ,$top + $hspace);
        $pdf->SetAutoPageBreak('off',0);
        $pdf->AddPage('P');
        $pdf->SetFont('Arial','',10);

        /**
         * set up location variable starts
         */

        $barLeft = $left + 4;
        $descTop = $top + $hspace;
        $barTop = $descTop + 16;
        $priceTop = $descTop + 4;
        $labelCount = 0;
        $brandTop = $descTop + 4;
        $sizeTop = $descTop + 8;
        $genLeft = $left;
        $skuTop = $descTop + 12;
        $vendLeft = $left + 13;
        $down = 30.95625;
        $LeftShift = 51.990625;
        $w = 49.609375;
        $priceLeft = ($w / 2) + ($space);

        /**
         * Increment through items in query
         */

        while ($row = mysqli_fetch_array($result)) {
           /**
            * check to see if we have made 32 labels.
            * if we have start a new page....
            */

            if ($labelCount == 32) {
                $pdf->AddPage('P');
                $descTop = $top + $hspace;
                $barLeft = $left + 4;
                $barTop = $descTop + 16;
                $priceTop = $descTop + 4;
                $priceLeft = ($w / 2) + ($space);
                $labelCount = 0;
                $brandTop = $descTop + 4;
                $sizeTop = $descTop + 8;
                $genLeft = $left;
                $skuTop = $descTop + 12;
                $vendLeft = $left + 13;
            }

            /**
             * check to see if we have reached the right most label
             * if we have reset all left hands back to initial values
             */
            if($barLeft > 175){
                $barLeft = $left + 4;
                $barTop = $barTop + $down;
                $priceLeft = ($w / 2) + ($space);
                $priceTop = $priceTop + $down;
                $descTop = $descTop + $down;
                $brandTop = $brandTop + $down;
                $sizeTop = $sizeTop + $down;
                $genLeft = $left;
                $vendLeft = $left + 13;
                $skuTop = $skuTop + $down;
            }

            /**
             * instantiate variables for printing on barcode from
             * $testQ query result set
             */
            if ($row['scale'] == 0) {$price = $row['normal_price'];}
            elseif ($row['scale'] == 1) {$price = $row['normal_price'] . "/lb";}
            $desc = strtoupper(substr($row['description'],0,27));
            $brand = ucwords(strtolower(substr($row['brand'],0,13)));
            $size = $row['size'];
            $sku = $row['sku'];
            $upc = $row['pid'];
            /**
             * determine check digit using barcode.php function
             */
            $check = $pdf->GetCheckDigit($upc);
            /**
             * get tag creation date (today)
             */
            $tagdate = date('m/d/y');
            $vendor = substr($row['vendor'],0,10);

            /**
             * begin creating tag
             */
            $pdf->SetXY($genLeft, $descTop);
            $pdf->Cell($w,4,substr($desc,0,20),0,0,'L');
            $pdf->SetXY($genLeft,$brandTop);
            $pdf->Cell($w/2,4,$brand,0,0,'L');
            $pdf->SetXY($genLeft,$sizeTop);
            $pdf->Cell($w/2,4,$size,0,0,'L');
            $pdf->SetXY($priceLeft+9,$skuTop);
            $pdf->Cell($w/3,4,$tagdate,0,0,'R');
            $pdf->SetXY($genLeft,$skuTop);
            $pdf->Cell($w/3,4,$sku,0,0,'L');
            $pdf->SetXY($vendLeft,$skuTop);
            $pdf->Cell($w/3,4,$vendor,0,0,'C');
            $pdf->SetFont('Arial','B',20);
            $pdf->SetXY($priceLeft,$priceTop);
            $pdf->Cell($w/2,8,$price,0,0,'R');
            /**
             * add check digit to pid from testQ
             */
            $newUPC = $upc . $check;
            $pdf->UPC_A($barLeft,$barTop,$upc,7);
            /**
             * increment label parameters for next label
             */
            $barLeft =$barLeft + $LeftShift;
            $priceLeft = $priceLeft + $LeftShift;
            $genLeft = $genLeft + $LeftShift;
            $vendLeft = $vendLeft + $LeftShift;
            $labelCount++;
        }
    } elseif ($_POST['type'] == 'TINY') {
        /**
         * begin to create PDF file using fpdf functions
         */
        $h = 30.1625;
        $w = 32;
        $top = 12.7 + 2.5;
        $left = 4.85 + 1.35;

        $pdf=new PDF('P', 'mm', 'Letter');
        $pdf->SetMargins($left ,$top);
        $pdf->SetAutoPageBreak('off',0);
        $pdf->AddPage('P');
        $pdf->SetFont('Arial','',10);

        /**
         * set up location variable starts
         */

        $labelCount = 0;
        $genLeft = $left;
        $brandTop = $top + 3;
        $descTop = $brandTop + 5;
        $sizeTop = $brandTop + 14;
        $priceTop = $brandTop + 14;
        $skuTop = $brandTop + 19;
        $vendTop = $brandTop + 19;
        $priceLeft = ($w / 2) + $left;
        $skuLeft = ($w / 2) + $left;
        $dateTop = $brandTop + 24;
        $down = $h;
        $LeftShift = $w;
        /**
         * increment through items in query
         */

        while ($row = mysqli_fetch_array($result)) {
            /**
             * check to see if we have made 56 labels.
             * if we have start a new page....
             */

            if ($labelCount == 48) {
                $pdf->AddPage('P');

                $labelCount = 0;
                $genLeft = $left;
                $brandTop = $top + 3;
                $descTop = $brandTop + 5;
                $sizeTop = $brandTop + 14;
                $priceTop = $brandTop + 14;
                $skuTop = $brandTop + 19;
                $vendTop = $brandTop + 19;
                $priceLeft = ($w / 2) + $left;
                $skuLeft = ($w / 2) + $left;
                $dateTop = $brandTop + 24;
                $down = $h;
                $LeftShift = $w;
            }

            /**
             * check to see if we have reached the right most label
             * if we have reset all left hands back to initial values
             */
            if ($genLeft > 185) {

                $brandTop = $brandTop + $down;
                $descTop = $descTop + $down;
                $sizeTop = $sizeTop + $down;
                $priceTop = $priceTop + $down;
                $skuTop = $skuTop + $down;
                $vendTop = $vendTop + $down;
                $dateTop += $down;
                $priceLeft = ($w / 2) + $left;
                $skuLeft = ($w / 2) + $left;
                $genLeft = $left;

            }

            /**
             * instantiate variables for printing on barcode from
             * $testQ query result set
             */
            if ($row['scale'] == 0) {$price = '$' . $row['normal_price'];}
            elseif ($row['scale'] == 1) {$price = '$' . $row['normal_price'] . "/lb";}
            $W = 25;

            while ($pdf->WordWrap(strtoupper(substr($row['description'],0,$W)), $w-2) > 2) {
                $W = $W - 1;
            }
            $desc = strtoupper(substr($row['description'],0,$W));
            $brand = ucwords(strtolower(substr($row['brand'],0,17)));
            $size = $row['size'];
            $sku = $row['sku'];
            $upc = $row['upc'];
            $tagdate = date('m/d/y');

            $vendor = substr($row['vendor'],0,8);

            /**
             * begin creating tag
             */
            $pdf->SetXY($genLeft, $brandTop);
            $pdf->Cell($w,4,substr($brand,0,21),0,0,'C');
            $pdf->SetXY($genLeft,$descTop);
            $pdf->MultiCell($w,4,$desc,0,'C');
            $pdf->SetXY($genLeft,$sizeTop);
            $pdf->Cell($w/2,4,$size,0,0,'L');
            $pdf->SetXY($priceLeft,$priceTop);
            $pdf->SetFont('Arial','B',10);
            $pdf->Cell($w/2,4,$price,0,0,'R');
            $pdf->SetFont('Arial','',10);
            $pdf->SetXY($genLeft,$vendTop);
            $pdf->Cell($w/2,4,$vendor,0,0,'L');
            $pdf->SetXY($skuLeft,$skuTop);
            $pdf->Cell($w/2,4,$sku,0,0,'R');
            $pdf->SetXY($genLeft,$dateTop);
            $pdf->Cell($w,4,$tagdate,0,0,'C');
            $pdf->Rect($genLeft, $brandTop - 1, $w, $h);

            /**
             * increment label parameters for next label
             */
            $priceLeft = $priceLeft + $LeftShift;
            $genLeft = $genLeft + $LeftShift;
            $skuLeft = $skuLeft + $LeftShift;
            $labelCount++;
        }
    } elseif ($_POST['type'] == 'CIRCLE') {
	// Basic static settings
	$top = 12.7;
	$left = 4.85;
	$r = 34.925;
	$xmax = 150;
	$ymax = 200;
	$leftShift = 80;
	$downShift = 80;

	// Inititialize the object
	$pdf=new PDF('P', 'mm', 'Letter');
	$pdf->SetMargins($left ,$top);
	$pdf->SetAutoPageBreak('off',0);

	// Include our specific fonts.
	$pdf->AddFont('Georgia', '', 'georgia.php');
	$pdf->AddFont('Georgia', 'B', 'georgiab.php');
	$pdf->AddFont('Georgia', 'I', 'georgiai.php');


	$pdf->SetFont('Georgia','',10);

	// Dynamic initial settings
	$page = 0;

	$testingArray = array(
	    array('Organic', 'Choice', 'Bancha Green Tea', 'Green tea leaves', 491, 31.70),
	    array('Non-Organic', 'Starwest', 'Chai Tea Blend', 'Black tea, cinnamon, assam tea, cardamom seed, cloves, ginger root, black pepper', 205, 21.60),
	    array('Wildcrafted', 'A Tea Cup Dropped', 'Jasmine Green Tea', 'Gunpowder green tea, jasmine flowers', 345, 69.70),
	    array('Organic', 'Dragonfly Chai', 'English Breakfast Tea', 'Black tea, cinnamon, assam tea, cardamom seed, cloves, ginger root, black pepper, Black tea, cinnamon, assam tea, cardamom seed, cloves, ginger root, black pepper', 491, 31.70),
	    array('Non-Organic', 'Frontier', 'Bancha Green Tea', 'Green tea leaves', 491, 31.70),
	    array('Wildcrafted', 'Woodstock Farms', 'Bancha Green Tea', 'Green tea leaves', 491, 31.70),
	);

	$query = "SELECT d.certification, d.brand, d.product, d.ingredients,
	    CASE WHEN p.upc BETWEEN 0 AND 999 THEN SUBSTR(p.upc,11,3) WHEN p.upc BETWEEN 1000 AND 9999 THEN SUBSTR(p.upc, 10,4) ELSE p.upc END AS upc, p.normal_price
	    FROM is4c_op.products AS p
		RIGHT OUTER JOIN is4c_op.product_details AS d ON (p.upc = d.upc)
	    WHERE p.inUse = 1
	    AND p.department IN ($dArray)
	    AND p.discounttype <> 3
	    AND DATE(p.modified) BETWEEN '$date1' AND '$date2'
	    ORDER BY p.modified";

	$result = mysqli_query($db_slave, $query);

	$certQ = "SELECT certID, certDesc FROM certList ORDER BY certID";
	$certR =  mysqli_query($db_slave, $certQ);

	while (list($id, $certDesc) = mysqli_fetch_row($certR)) {
	    $certification[$id] = $certDesc;
	}


	$tagCount = 0;

	// Initialize variables.
	$x = $left;
	$y = $top;

	$certTop = 4;
	$brandTop = 11;
	$descTop = 18;
	$ingredTop = 34;
	$pluTop = 52;
	$ozTop = 57;
	$lbTop = 62;

	$pdf->AddPage('P');

	while (list($cert, $brand, $desc, $ingredients, $upc, $price) = mysqli_fetch_row($result)) {


	    // This is the actual tag generation.
	    $pdf->Circle($x + $r, $y + $r, $r);

	    // Organic, Non-Organic, Wildcrafted
	    $pdf->SetFont('Georgia','B',14);
	    $pdf->SetXY($x, $y + $certTop);
	    $pdf->Cell($r * 2,6,$certification[$cert],0,0,'C');

	    // Brand Name
	    $pdf->SetFont('Georgia','B',15);
	    $pdf->SetXY($x, $y + $brandTop);
	    $pdf->Cell(($r * 2),6,$brand,0,0,'C');

	    // Check # of lines for description and ingredients, limit to 2 for description and 5 for ingredients
	    // Product description
	    $pdf->SetFont('Georgia','B',20);
	    $pdf->SetXY($x+2, $y + $descTop);
	    $pdf->MultiCell(($r * 2)-4,8,$desc,0,'C');

	    // Ingredient list
	    $pdf->SetFont('Georgia','B',8);
	    $pdf->SetXY($x, $y + $ingredTop);
	    $pdf->MultiCell($r * 2,3.5, 'Ingredients: ' . $ingredients,0,'C');

	    // PLU Number
	    $pdf->SetFont('Georgia','B',13);
	    $pdf->SetXY($x, $y + $pluTop);
	    $pdf->Cell($r * 2,6,"Code #$upc",0,0,'C');

	    // Price per oz
	    $pdf->SetXY($x, $y + $ozTop);
	    $pdf->Cell($r * 2,6,"$" . number_format($price/16, 2) . "/oz",0,0,'C');

	    // Price per lb
	    $pdf->SetXY($x, $y + $lbTop);
	    $pdf->Cell($r * 2,6,"$" . number_format($price, 2) . "/lb",0,0,'C');

	    // Incrementation
	    $x += $leftShift;

	    if ($x > $xmax) {
		$y += $downShift;
		$x = $left;
	    }

	    if ($y > $ymax) {
		$x = $left;
		$y = $top;

		$certTop = 4;
		$brandTop = 11;
		$descTop = 18;
		$ingredTop = 34;
		$pluTop = 52;
		$ozTop = 57;
		$lbTop = 62;

		$pdf->AddPage('P');
	    }
	}
    } elseif ($_POST['type'] == 'WINE') {
	// Basic static settings
	$top = 12.7;
	$left = 12.7;
	$brandTop = 12.7;
	$varietalTop = 17.9625;
	$priceTop = 23.8125;
	$extraTop = 38.10;
	$bioW = 25.4;
	$localLeft = $bioW;
	$localW = 22.225;
	$veganLeft = $localLeft + $localW;
	$veganW = 19.05;
	$orgLeft = $veganLeft + $veganW;
	$orgW = $localW;

	$xmax = 150;
	$ymax = 220;
	$leftShift = 101.6;
	$downShift = 50.80;

	$x = $left;
	$y = $top;
	$w = 88.9;
	$h = 44.45;

	// Inititialize the object
	$pdf=new PDF('P', 'mm', 'Letter');
	$pdf->SetMargins($left ,$top);
	$pdf->SetAutoPageBreak('off',0);

	// Include our specific fonts.
	$pdf->AddFont('Georgia', '', 'georgia.php');
	$pdf->AddFont('Georgia', 'B', 'georgiab.php');
	$pdf->AddFont('Georgia', 'I', 'georgiai.php');
	$pdf->AddFont('Century', '', 'CenturyRegular.php');
	$pdf->AddFont('Century', 'B', 'CenturyBold.php');
	$pdf->AddFont('Century', 'I', 'CenturyItalic.php');


	$pdf->SetFont('Georgia','',10);

	// Dynamic initial settings
	$count = 1;
	while ($count < 25) {
	    $pdf->AddPage('P');
	    while ($y < $ymax) {
	        while ($x < $xmax) {
		    $pdf->Rect($x, $y, $w, $h);
		    // Brand
		    $pdf->SetFont('Century','',16);
		    $pdf->SetXY($x, $y + $brandTop);
		    $pdf->Cell($w,6,'Brand',0,0,'C');

		    // Varietal
		    $pdf->SetFont('Century','I',13);
		    $pdf->SetXY($x, $y + $varietalTop);
		    $pdf->Cell($w,6,'Varietal',0,0,'C');

		    // Price
		    $pdf->SetFont('Arial','',13);
		    $pdf->SetXY($x, $y + $priceTop);
		    $pdf->Cell($w,6,'$12.99',0,0,'C');

		    // Extra Bits
		    // Biodynamic
		    $pdf->SetFont('Arial','',10.5);
		    $pdf->SetXY($x, $y + $extraTop);
		    $pdf->Cell($bioW,4,'Biodynamic',0,0,'C');

		    // Local
		    $pdf->SetFont('Arial','',10.5);
		    $pdf->SetXY($x + $localLeft, $y + $extraTop);
		    $pdf->Cell($localW,4,'Local',0,0,'C');

		    // Vegan
		    $pdf->SetFont('Arial','',10.5);
		    $pdf->SetXY($x + $veganLeft, $y + $extraTop);
		    $pdf->Cell($veganW,4,'Vegan',0,0,'C');

		    // Organic
		    $pdf->SetFont('Arial','',10.5);
		    $pdf->SetXY($x + $orgLeft, $y + $extraTop);
		    $pdf->Cell($orgW,4,'Organic',0,0,'C');

		    $x += $leftShift;
		    $count++;
		}

		$y += $downShift;
		$x = $left;
	    }

	    $x = $left;
	    $y = $top;
	}

	$bitFieldQ = "SELECT fieldIndex, name FROM bitFields WHERE department = 5 ORDER BY fieldIndex";
	$bitFieldR = mysqli_query($db_slave, $bitFieldQ);

	$bitField = sprintf('%b', (int) $detailRow['bitField']);

	for ($i = 1; $i <= strlen($bitField); $i++) {
	   $bitFieldArray[] = substr($bitField, strlen($output)-$i, 1);
	}

	while (list($index, $name) = mysqli_fetch_row($bitFieldR)) {
	    $bF[$index] = (isset($bitFieldArray[$index]) && $bitFieldArray[$index] == 1 ? $name : NULL);
	}
    }

    /**
     * write to PDF
     */
    $pdf->Output();


} else { // Show the form.

    $page_title = 'Fannie - Administration Module';
    $header = 'Shelftag Generator';
    include ('../includes/header.html');
    ?><link href="../style.css" rel="stylesheet" type="text/css" />
    <script src="../src/CalendarControl.js" language="javascript"></script>
    <script src="../src/putfocus.js" language="javascript"></script>
    </head>
    <body onLoad="putFocus(0,0);">
    <link href="../style.css" rel="stylesheet" type="text/css">
    <script src="../src/CalendarControl.js" language="javascript"></script>

    <form method="post" action="shelftags.php" target="_blank">

    <h2>Shelftag Generator</h2>

    <table border="0" cellspacing="3" cellpadding="3">
        <tr>
            <th align="center"> <p><b>Select Department(s)</b></p></th>
            <th><p><b>for to make pretty tags.</b></p></th>
            <th><p><b>Select A Tag Style: <select name="type">
                <option value="TINY">HABA Style</option>
                <option value="BIG" SELECTED>Standard Style</option>
		<option value="CIRCLE">Bulk Herbs Tags</option>
		<option value="WINE">Wine Tags</option>
            </select></b></p></th>
        </tr>
        <tr>
            <td><font size="-1"><p>
                <input type="checkbox" value=1 name="allDepts"><b>All Departments</b><br>
                <input type="checkbox" name="dept[]" value="1">Grocery<br>
                <input type="checkbox" name="dept[]" value="2">Bulk<br>
                <input type="checkbox" name="dept[]" value="3">Perishable<br>
                <input type="checkbox" name="dept[]" value="4">Dairy<br>
                <input type="checkbox" name="dept[]" value="7">Cheese<br>
                <input type="checkbox" name="dept[]" value="15">Deli<br>
                <input type="checkbox" name="dept[]" value="16">Bread & Juice<br>
                <input type="checkbox" name="dept[]" value="14">Beer<br>
                <input type="checkbox" name="dept[]" value="5">Wine<br>
                </p></font>
            </td>
            <td><font size="-1"><p>
                <input type="checkbox" name="dept[]" value="8">Produce<br>
                <input type="checkbox" name="dept[]" value="6">Frozen<br>
                <input type="checkbox" name="dept[]" value="12">NF-Supplements<br>
                <input type="checkbox" name="dept[]" value="11">NF-Personal Care<br>
                <input type="checkbox" name="dept[]" value="10">NF-General<br>
                <input type="checkbox" name="dept[]" value="9">Bulk Herbs<br>
                <input type="checkbox" name="dept[]" value="13">NF-Pet<br>
                <input type="checkbox" name="dept[]" value="17">Floral<br>
                <input type="checkbox" name="dept[]" value="40">Tri-Met
                </p></font>
            </td>
        </tr>
    </table>
    <table border="0" cellspacing="3" cellpadding="3">
    <tr>
            <td align="right">
                    <p><b>Date Start</b> </p>
            <p><b>End</b></p>
            </td>
            <td>
                    <p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                    <p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
            </td>
            <td colspan=2>
                    <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
            </td>
    </tr>
    <tr>
            <td>&nbsp;</td>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <input type="hidden" name="submitted" value="TRUE">
    </tr>
    </table>
    </form>
    <?php

    include('../includes/footer.html');
}

?>
