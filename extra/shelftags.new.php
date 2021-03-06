<?php
// echo "hi";
if (isset($_POST['submitted'])) {
    ini_set('display_errors', 'on');
    ini_set('error_reporting', E_ALL);
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

    if (!isset($_POST['dept'])) $_POST['dept'] = array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,40);

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
    
    if (isset($_POST['allDepts']) && $_POST['allDepts'] == 1) {
        $dArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,40";
    } else {
        $allDepts = 0;
    }


    if (is_array($_POST['dept'])) {
        $upcString = implode(",",$_POST['dept']);
    }
    if (is_array($_POST['upcArray'])) {
        $upcString = '';
	
	//copy the arrays from the POST
	//$checkedArray = $_POST['checkedArray'];
	$upcArray = $_POST['upcArray'];
	$qtyArray = $_POST['qtyArray'];

	//$upcString = implode(",",$_POST['upcArray']);
	$index = 0;

	//loop through to create the upc,string
    	for($i = 0;$i < sizeof($upcArray); ++$i) {

	    //only add it if it's checked.
	    if ( $qtyArray[$i] > 0) {
		//	for ($j = 0; $j = $qtyArray['$i']; ++$j) 
		$upcString .= $upcArray[$i];
		//don't put a comma after the last one
		if ($i != sizeof($upcArray)-1) $upcString .= ",";
	    }
        }
    }

    foreach ($dept as $deptno) {
        if ($deptno == 7 || $deptno == 3 || $deptno == 4 || $deptno == 16) {$small = 'SMALL';}
    }

    if (!isset($small) || $small != 'SMALL') {$small = 'LARGE';}

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
        WHERE p.upc IN($upcString)
        AND p.inUse = 1
        AND p.discounttype <> 3";

        //"ORDER BY department";


    $result = mysqli_query($db_slave, $testQ);
    if (!$result) {
       $message  = 'Invalid query: ' . mysqli_error($db_slave) . "\n";
       $message .= 'Whole query: ' . $testQ;
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
	    if (isset($certification[$cert]))
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
        $check = '../includes/checkmark.png';
        
        // Basic layout settings ///Old Wine sheets
        $height = 33; //41.4;
        $width = 88.9;	//98;
        $left = 10.9; 	//9;
        $top = 12.7;	//14;
        $right = 1.6; //5.5;
        $x = $left;
        $y = $top;
        $rightShift = $width + 9.2;	//6.5;
        $downShift = $height + 3.2;	//6;
        $orientation = 'P';
        $xMax = 160;
        $yMax = 245;
        
	// Inititialize the object
	$pdf=new PDF($orientation, 'mm', 'Letter');
        
        // Add special fonts.
	$pdf->AddFont('Helvetica', '', 'Helvetica.php');
	$pdf->AddFont('Helvetica', 'B', 'HelveticaBold.php');
        
	$pdf->SetMargins($left ,$top);
	$pdf->SetAutoPageBreak('off', 0);
        
        // Set up feach field...
/*        $tagFields[] = array('height' => 2.5, 'width' => 2.5, 'x-offset' => -1.0, 'y-offset' => 10, 'field' => 'organic', 'type' => 'image');
        $tagFields[] = array('height' => 2.5, 'width' => 2.5, 'x-offset' => 20.5, 'y-offset' => 10, 'field' => 'vegan', 'type' => 'image');
        $tagFields[] = array('height' => 2.5, 'width' => 2.5, 'x-offset' => 39, 'y-offset' => 10, 'field' => 'sulfite-free', 'type' => 'image');
        $tagFields[] = array('height' => 2.5, 'width' => 2.5, 'x-offset' => 67.5, 'y-offset' => 10, 'field' => 'biodynamic', 'type' => 'image');
*/                                
        $tagFields[] = array('height' => 5, 'width' => 92, 'x-offset' => 3, 'y-offset' => 13, 'justify' => 'C', 'field' => 'description', 'font' => 'Helvetica', 'font-weight' => 'B', 'font-size' => 11, 'type' => 'cell');
        $tagFields[] = array('height' => 5, 'width' => 92, 'x-offset' => 3, 'y-offset' => 17, 'justify' => 'C', 'field' => 'product', 'font' => 'Helvetica', 'font-weight' => '', 'font-size' => 11, 'type' => 'cell');
        $tagFields[] = array('height' => 5, 'width' => 92, 'x-offset' => 3, 'y-offset' => 21, 'justify' => 'C', 'field' => 'price', 'font' => 'Helvetica', 'font-weight' => '', 'font-size' => 11, 'type' => 'cell');

	if (isset($_POST['wineType']) && $_POST['wineType'] == 1 ) {
		$wineType = $_POST['wineType'];
	} else {
		$wineType = '0,2,3,4,5,6';
	}

	//$mainQ = "SELECT CONCAT_WS(' ', d.brand, d.product) AS description, CONCAT('$', ROUND(p.normal_price, 2)) as price, p.department, d.bitField as bit
	$mainQ = "SELECT d.brand as description, d.product AS product, CONCAT('$', ROUND(p.normal_price, 2)) as price, p.department, d.bitField as bit
	    FROM products AS p
		INNER JOIN product_details AS d ON p.upc=d.upc
	    WHERE p.inuse = 1
		AND p.department IN ($dArray)
		AND p.discounttype <> 3
                AND d.certification IN ($wineType)
	        AND DATE(p.modified) BETWEEN '$date1' AND '$date2'
	    ORDER BY p.modified";

	$mainR = mysqli_query($db_slave, $mainQ);

	if ($mainR && mysqli_num_rows($mainR) > 0) {
	    // Print tags...
	    $pdf->AddPage('P');

	    while ($tagRow = mysqli_fetch_array($mainR, MYSQLI_ASSOC)) {
	        if ($x > $xMax) {
                    $x = $left;
                    $y += $downShift;
                }
    
                if ($y > $yMax) {
                    $pdf->AddPage($orientation);
                    $x = $left;
                    $y = $top;
                }
                
                $bitFieldQ = "SELECT fieldIndex, LOWER(name) FROM bitFields WHERE department = {$tagRow['department']} ORDER BY fieldIndex";
		$bitFieldR = mysqli_query($db_slave, $bitFieldQ);

		$bitField = sprintf('%b', (int) $tagRow['bit']);

		$bitFieldArray = array();

		for ($i = 1; $i <= strlen($bitField); $i++) {
		   $bitFieldArray[] = substr($bitField, -$i, 1);
		}

		while (list($index, $name) = mysqli_fetch_row($bitFieldR)) {
		    $tagRow[$name] = (isset($bitFieldArray[$index]) && $bitFieldArray[$index] == 1 ? $name : FALSE);
		}
                
                foreach ($tagFields AS $field) {
                    if ($field['type'] == 'cell') {
                        if (strlen($tagRow[$field['field']]) > 32)
                            $fSize = $field['font-size'] - 2;
                        else
                            $fSize = $field['font-size'];
                            
                        $pdf->SetFont($field['font'], $field['font-weight'], $fSize);
                        $pdf->SetXY($x + $field['x-offset'], $y + $field['y-offset']);
                        $pdf->Cell($field['width'], $field['height'], $tagRow[$field['field']], 0, 0, $field['justify']);
                    } elseif ($field['type'] == 'multicell') {
                        $pdf->SetFont($field['font'], $field['font-weight'], $field['font-size']);
                        $pdf->SetXY($x + $field['x-offset'], $y + $field['y-offset']);
                        $pdf->MultiCell($field['width'], (isset($curHeight) ? $curHeight : $field['height']), $tagRow[$field['field']], 0, $field['justify']);
                    } elseif ($field['type'] == 'image') {
                        $pdf->SetXY($x + $field['x-offset'], $y + $field['y-offset']);
                        if (isset($tagRow[$field['field']]) && $tagRow[$field['field']] == $field['field']) {
                            //$pdf->Image($check, $x + $field['x-offset'], $y + $field['y-offset'], $field['width'], $field['height']);
                        }
                    }
                    
                }
                
                $x += $rightShift;
	    }
	} else {
	    drawForm('Search returned no results.');
	//sprintf('Query: %s<br />Error: %s', $mainQ, mysqli_error($db_slave)));
	}
    } elseif ($_POST['type'] == 'BULK') {
	$tagCert = (int) $_POST['tagCert'];
	$tagType = (int) $_POST['tagSize'];

	$bulkQ = sprintf("SELECT IF(pd.brand IS NULL,'',SUBSTRING(pd.brand,1,20)) AS brand,
            IF(pd.order_no IS NULL,'', pd.order_no) AS order_no,
            IF(pd.pack_size IS NULL,'',pd.pack_size) AS size,
            CASE WHEN pd.upc IS NULL THEN '' WHEN pd.upc < 1000 THEN SUBSTRING(pd.upc, 11, 3) WHEN pd.upc < 10000 THEN SUBSTRING(pd.upc, 10, 4) ELSE '' END AS upc,
            IF(pd.product IS NULL, SUBSTRING(p.description,1,25),SUBSTRING(pd.product,1,25)) AS description,
            RIGHT(p.upc,12) AS pid,
            IF(pd.distributor IS NULL, 'Misc', pd.distributor) AS vendor,
            ROUND(normal_price,2) AS price,
	    IF(pd.ingredients IS NULL, '', pd.ingredients) AS ingredients,
	    IF(pd.origin IS NULL, '', pd.origin) AS origin,
	    IF(pd.special IS NULL, '', pd.special) AS special
        FROM products AS p LEFT OUTER JOIN product_details AS pd ON p.upc = pd.upc
        WHERE p.department IN (%s)

        AND p.inUse = 1
        AND p.discounttype <> 3
	AND p.upc NOT BETWEEN 10000 AND 15000
	AND pd.certification = %u
	AND pd.tag_type = %u
        AND DATE(modified) BETWEEN '%s' AND '%s'
        ORDER BY department", $dArray, $tagCert, $tagType, $date1, $date2);

	$bulkR = mysqli_query($db_slave, $bulkQ);

	if (!$bulkR) printf('Query: %s, Error: %s', $bulkQ, mysqli_error($db_slave));

	$pdf=new PDF('P', 'mm', 'Letter');

	// Add special fonts.
	$pdf->AddFont('Helvetica', '', 'Helvetica.php');
	$pdf->AddFont('Helvetica', 'B', 'HelveticaBold.php');
	$pdf->AddFont('HelveticaNeue', '', 'HelveticaNeue.php');
	$pdf->AddFont('HelveticaNeue', 'B', 'HelveticaNeueBold.php');
	$pdf->AddFont('HelveticaNeue', 'BI', 'HelveticaNeueBoldItalic.php');
	$pdf->AddFont('HelveticaNeue', 'CBl', 'HelveticaNeueCondensedBlack.php');
	$pdf->AddFont('HelveticaNeue', 'CB', 'HelveticaNeueCondensedBold.php');
	$pdf->AddFont('HelveticaNeue', 'I', 'HelveticaNeueItalic.php');
	$pdf->AddFont('HelveticaNeue', 'L', 'HelveticaNeueLight.php');
	$pdf->AddFont('HelveticaNeue', 'LI', 'HelveticaNeueLightItalic.php');
	$pdf->AddFont('HelveticaNeue', 'UL', 'HelveticaNeueUltraLight.php');
	$pdf->AddFont('HelveticaNeue', 'ULI', 'HelveticaNeueUltraLightItalic.php');

	switch ($tagType) {
	    case 0: // Wide
		$height = 76;
		$width = 127;
		$left = 9.5;
		$top = 16;
		$right = 9;
		$x = $left;
		$y = $top;
		$rightShift = $width + 6;
		$downShift = $height + 15.5;
		$orientation = 'L';
		$xMax = 160;
		$yMax = 120;

		// Set up feach field...
		$tagFields[] = array('height' => 7, 'width' => 120, 'x-offset' => 3, 'y-offset' => 23.5, 'justify' => 'C', 'field' => 'description', 'font' => 'HelveticaNeue', 'font-weight' => 'CB', 'font-size' => 16, 'type' => 'cell');
		$tagFields[] = array('height' => 4, 'width' => 120, 'x-offset' => 3, 'y-offset' => 37.5, 'justify' => 'L', 'field' => 'ingredients', 'font' => 'HelveticaNeue', 'font-weight' => '', 'font-size' => 10, 'type' => 'multicell');
		$tagFields[] = array('height' => 11, 'width' => 33.5, 'x-offset' => 57, 'y-offset' => 8, 'justify' => 'C', 'field' => 'upc', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 35, 'type' => 'cell');
		$tagFields[] = array('height' => 11, 'width' => 33.5, 'x-offset' => 91, 'y-offset' => 8, 'justify' => 'C', 'field' => 'price', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 25, 'type' => 'cell');
		$tagFields[] = array('height' => 4.5, 'width' => 17, 'x-offset' => 23.5, 'y-offset' => 69, 'justify' => 'C', 'field' => 'order_no', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 14, 'type' => 'cell');
		$tagFields[] = array('height' => 11, 'width' => 52, 'x-offset' => 3, 'y-offset' => 58, 'justify' => 'C', 'field' => 'origin', 'font' => 'HelveticaNeue', 'font-weight' => 'CB', 'font-size' => 12, 'type' => 'cell');
		$tagFields[] = array('height' => 4.5, 'width' => 17, 'x-offset' => 106.5, 'y-offset' => 69, 'justify' => 'C', 'field' => 'size', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 12, 'type' => 'cell');
		$tagFields[] = array('height' => 5.5, 'width' => 68, 'x-offset' => 58, 'y-offset' => 58, 'justify' => 'C', 'field' => 'special', 'font' => 'HelveticaNeue', 'font-weight' => 'CB', 'font-size' => 12, 'type' => 'multicell');

		break;

	    case 1: // Narrow

		$height = 100;
		$width = 98;
		$left = 5;
		$right = 5;
		$top = 33;
		$x = $left;
		$y = $top;
		$rightShift = $width + 6.5;
		$downShift = $height + 16;
		$orientation = 'P';
		$xMax = 140;
		$yMax = 180;

		// Set up feach field...
		$tagFields[] = array('height' => 7.5, 'width' => 90, 'x-offset' => 5, 'y-offset' => 21, 'justify' => 'C', 'field' => 'description', 'font' => 'HelveticaNeue', 'font-weight' => 'CB', 'font-size' => 16, 'type' => 'cell');
		$tagFields[] = array('height' => 4, 'width' => 90, 'x-offset' => 5, 'y-offset' => 35, 'justify' => 'L', 'field' => 'ingredients', 'font' => 'HelveticaNeue', 'font-weight' => '', 'font-size' => 10, 'type' => 'multicell');
		$tagFields[] = array('height' => 11, 'width' => 33.5, 'x-offset' => 4, 'y-offset' => 60, 'justify' => 'C', 'field' => 'upc', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 35, 'type' => 'cell');
		$tagFields[] = array('height' => 11, 'width' => 33.5, 'x-offset' => 40, 'y-offset' => 60, 'justify' => 'C', 'field' => 'price', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 25, 'type' => 'cell');
		$tagFields[] = array('height' => 5.5, 'width' => 12, 'x-offset' => 80.5, 'y-offset' => 61, 'justify' => 'C', 'field' => 'order_no', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 14, 'type' => 'cell');
		$tagFields[] = array('height' => 5.5, 'width' => 70, 'x-offset' => 5, 'y-offset' => 76, 'justify' => 'C', 'field' => 'origin', 'font' => 'HelveticaNeue', 'font-weight' => 'CB', 'font-size' => 12, 'type' => 'cell');
		$tagFields[] = array('height' => 5.5, 'width' => 12, 'x-offset' => 80.5, 'y-offset' => 76, 'justify' => 'C', 'field' => 'size', 'font' => 'HelveticaNeue', 'font-weight' => 'B', 'font-size' => 12, 'type' => 'cell');
		$tagFields[] = array('height' => 11, 'width' => 90, 'x-offset' => 5, 'y-offset' => 89, 'justify' => 'C', 'field' => 'special', 'font' => 'HelveticaNeue', 'font-weight' => 'CB', 'font-size' => 12, 'type' => 'multicell');

		break;

	default:
		drawForm('You need to select a tag type.');
		break;
	}

	$pdf->SetMargins($left, $top, $right);
	$pdf->SetAutoPageBreak('off', 0);
	$pdf->AddPage($orientation);

	while ($tagRow = mysqli_fetch_array($bulkR, MYSQLI_ASSOC)) {
	    if ($x > $xMax) {
		$x = $left;
		$y += $downShift;
	    }

	    if ($y > $yMax) {
		$pdf->AddPage($orientation);
		$x = $left;
		$y = $top;
	    }

	    foreach ($tagFields AS $field) {
		$pdf->SetFont($field['font'], $field['font-weight'], $field['font-size']);
		$pdf->SetXY($x + $field['x-offset'], $y + $field['y-offset']);

		if ($field['field'] == 'ingredients') {
		    $curSize = $field['font-size'];
                    $curHeight = $field['height'];
		    
                    if (strlen($tagRow['ingredients']) >= 350) {
                        $curSize -= 5;
                        $curHeight -= 1;
                    } elseif (strlen($tagRow['ingredients']) >= 300) {
                        $curSize -= 4;
                        $curHeight -= 1;
                    } elseif (strlen($tagRow['ingredients']) >= 250) {
                        $curSize -= 3;
                    } elseif (strlen($tagRow['ingredients']) >= 200) {
                        $curSize -= 2;
                    }
                    
                    $pdf->SetFont($field['font'], $field['font-weight'], $curSize);
		}

		if ($field['type'] == 'cell')
		    $pdf->Cell($field['width'], $field['height'], $tagRow[$field['field']], 0, 0, $field['justify']);
		elseif ($field['type'] == 'multicell')
		    $pdf->MultiCell($field['width'], (isset($curHeight) ? $curHeight : $field['height']), $tagRow[$field['field']], 0, $field['justify']);
	    }

	    $x += $rightShift;
	}
    }

    /**
     * write to PDF
     */
    $pdf->Output();


} else { // Show the form.
    drawForm();
}

function drawForm($error = NULL) {
    $page_title = 'Fannie - Administration Module';
    $header = 'Shelftag Generator';
    include('../includes/header.html');
    require_once('../includes/mysqli_connect.php');
    require_once('../includes/dept_picker_generator.php');
    ?>
  <!--  <script src="../src/CalendarControl.js" language="javascript"></script> -->
<!--    <script src="../src/putfocus.js" language="javascript"></script> -->
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
   <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
                Date.format = 'yyyy-mm-dd';
                $(function(){
                                $('.datepick').datepicker({ 
                                                startDate:'2007-08-01',
                                                endDate: (new Date()).asString(), 
                                                clickInput: true, 
                                                dateFormat: 'yy-mm-dd', 
                                                changeMonth: true, 
                                                changeYear: true,
                                                duration: 0
                                });   
		// $('.datepick').focus();
                });

        $(document).ready(function() {
/*            $('#tagType').change(function() {
                val = $('#tagType').val();
                if (val == 'BULK') {
                    $('.bulkOptions').show();
                } else   {
                    $('.bulkOptions').hide();
                }

		//hide all other things
		$('.wineOptions, .bulkOptions').hide();

		switch (val) {
			case 'BULK':
                    		$('.bulkOptions').show();
				break;
			case 'WINE':
                    		$('.wineOptions').show();
				break;
			default:
				$('.bulkOptions, .wineOptions').hide();
		}

            });

	    //initially hide the thingies
            $('.bulkOptions').hide();
            $('.wineOptions').hide();
*/
        });
    </script>
    </head>
    <body onLoad="putFocus(0,0);">


    <form method="post" action="editList.php" target="_blank">
 
    <?php 

	if (isset($error) && !empty($error)) {
	echo '<p><font color="red">' . $error . '</font></p><br />';
    	}
    ?>

    <h2>Shelftag Generator</h2>

    <?php
	$data = 'is4c_op'; 
	mysqli_select_db($db_master,$data) or die('Database Select Error: ' . mysqli_error($db_master));
	echo '<div id="dept_picker">';
	dept_picker('dept_tile');
	echo '</div>';

    ?>

    <table border="0" cellspacing="3" cellpadding="3">
    <tr>
            <td align="right">
                    <p><b>Date Start</b> </p>
            <p><b>End</b></p>
            </td>
            <td>
                    <p><input type='text' size='10' name='date1' class='datepick' /></p>
                    <p><input type='text' size='10' name='date2' class='datepick' /></p>
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
	<p class="thinborder">REMINDER: When printing, be sure to change Page Scaling to "None" and uncheck "Auto Rotate and Center" in the Page Handling Section of the Print Options.</p>
    <?php

    include('../includes/footer.html');
}

?>
