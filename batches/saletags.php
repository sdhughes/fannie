<?php # saletags.php - Generate sales tags from sales batches.
if (!isset($_POST['tags']) || !isset($_POST['batchID'])) { // Accessed in error.
    $page_title = 'Fannie - Sales Batch Module';
    $header = 'Sales Tag Creator';
    include ('../includes/header.html');
    echo '<p><font color="red">This page has been accessed in error.</font></p>';
    include ('../includes/footer.html');
    exit;
} else { // Valid page hit, grab info, print tags.
    require_once ('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, 'is4c_op');

    $batchID = escape_data($_POST['batchID']);

    $query = "SELECT b.upc, p.description, p.normal_price
            FROM batchList AS b INNER JOIN products AS p ON (b.upc = p.upc)
            WHERE batchID = $batchID
            AND b.upc NOT IN (SELECT upc FROM product_details)";
    $result = mysqli_query($db_slave, $query);

    if (mysqli_num_rows($result) != 0) { // Failure!
        $page_title = 'Fannie - Sales Batch Module';
        $header = 'Sales Tag Creator';
        include ('../includes/header.html');
        echo '<p><strong><font color="red">The following items do not have details set yet, please set these details before printing tags.</font></strong></p>
        <ul>';
        while ($row = mysqli_fetch_row($result)) {
            printf('<li><a href="../item/itemMaint.php?submitted=search&upc=%s">%s - %s - $%s</a></li>',$row[0], $row[1], $row[0], $row[2]);
        }
        echo '</ul>';
        include ('../includes/footer.html');
    } else {

        $query = "SELECT DATE_FORMAT(endDate, '%c-%d-%y') FROM batches WHERE batchID=$batchID";
        $result = mysqli_query($db_slave, $query);

        if (mysqli_num_rows($result) == 1) { // Success, set variables.
            list($endDate) = mysqli_fetch_row($result);
        } else { // Problem!
            $page_title = 'Fannie - Sales Batch Module';
            $header = 'Sales Tag Creator';
            include ('../includes/header.html');
            echo '<p><font color="red">That sales batch could not be found.</font></p>';
            include ('../includes/footer.html');
            exit;
        }
        $typeQ = "SELECT batchType FROM batches WHERE batchID=$batchID";
        $typeR = mysqli_query($db_slave, $typeQ);
        list($type) = mysqli_fetch_row($typeR);

        $query = "SELECT pd.brand AS brand, pd.product AS description, p.normal_price AS nprice, b.salePrice AS sprice
                    FROM product_details AS pd
                    INNER JOIN batchList AS b ON (pd.upc = b.upc)
                    INNER JOIN products AS p ON (p.upc = b.upc)
                    WHERE b.batchID=$batchID
                    ORDER BY b.added ASC";
        $result = mysqli_query($db_slave, $query);

        require('../src/fpdf/fpdf.php');
        define('FPDF_FONTPATH','font/');

       /**
       * begin to create PDF file using fpdf functions
       **/

	// Constants...these won't change.
        $height = 63.5;
        $width = 50.8;
        $left = 6.35;
	$top = 7.8;
        $xStart = 6.35;
        $yStart = 7.8;
	$xMax = 250;
	$yMax = 175;
	$brandTop = 12;
	$productTop = 20;
	$spriceTop = 33;
	$npriceTop = 45;
	$endDateTop = 49;
	$gap = 3.175;
	$xShift = $width + $gap;
	$yShift = $height + $gap;


	if ($type == 1) {
	    $endDate = 'on sale thru ' . $endDate;
	    $image = '/pos/fannie/batches/SaleS_M.png';
	    $pdf=new FPDF('L', 'mm', 'Letter');

	    // Add special fonts.
	    $pdf->AddFont('Helvetica', '', 'Helvetica.php');
	    $pdf->AddFont('Helvetica', 'B', 'HelveticaBold.php');
	    $pdf->AddFont('HelveticaNeue', 'CB', 'HelveticaNeueCondensedBold.php');
	    $pdf->AddFont('HelveticaNeue', 'L', 'HelveticaNeueLight.php');
	    $pdf->AddFont('HelveticaNeue', 'LI', 'HelveticaNeueLightItalic.php');

	    $pdf->SetMargins($left ,$top);
	    $pdf->SetAutoPageBreak('off', 0);
	    $pdf->AddPage('L');

	    // Set up location starts...
	    $x = $xStart;
	    $y = $yStart;

	    // Go through the items...
	    while ($row = mysqli_fetch_array($result)) {

		// Make pretties out of the fields...
		$product = ucwords(strtolower($row['description']));
		$brand = strtoupper($row['brand']);
		$nprice = '$' . number_format($row['nprice'],2);
		$sprice = '$' . number_format($row['sprice'],2);

		// Debugging outline...
		$pdf->SetLineWidth(.2);
		$pdf->Rect($x, $y, $width, $height);


		// Brand...
		$pdf->SetFont('Helvetica','',10);
		$pdf->SetXY($x, $y + $brandTop);
		$pdf->Cell($width, 8, substr($brand, 0, 21), 0, 0, 'C');

		// Product...
		$pdf->SetFont('HelveticaNeue', 'CB', 15);
		$pdf->SetXY($x, $y + $productTop);
		$pdf->Cell($width, 3, substr($product, 0, 22), 0, 0, 'C');

		// Sale Price...
		$pdf->SetFont('Helvetica', 'B', 40);
		$pdf->SetXY($x ,$y + $spriceTop);
		$pdf->Cell($width, 4, $sprice, 0, 0, 'C');

		// Normal Price...
		$pdf->SetFont('HelveticaNeue', 'L', 10);
		$pdf->SetXY($x, $y + $npriceTop);
		$pdf->Cell($width, 3,'Regular Price ' . $nprice,0,0,'C');

		// End Date...
		$pdf->SetFont('HelveticaNeue', 'LI', 10);
		$pdf->SetXY($x, $y + $endDateTop);
		$pdf->Cell($width, 3, $endDate, 0, 0, 'C');

		// Increment to the right...
		$x += $xShift;

		// If we're at the end of a row, move down a row and reset $x
		if ($x > $xMax) {
		    $y += $yShift;
		    $x = $xStart;
		}

		// If we're at the end of the page, add a new page and reset $x and $y
		if ($y > $yMax) {
		      $pdf->AddPage('L');
		      $y = $yStart;
		      $x = $xStart;
		}
	    }
	} elseif ($type == 2) {
	    $endDate = 'while supplies last';
	    $image = '/pos/fannie/batches/discoTag.png';
	    /**
	    * begin to create PDF file using fpdf functions
	    **/
	     $h = 63.5;
	     $w = 79.375;
	     $top = 15;
	     $left = 6;
	     $x = 15;
	     $y = 15;

	   $pdf=new FPDF('P', 'mm', 'Letter');
	   $pdf->SetMargins($left ,$top);
	   $pdf->SetAutoPageBreak('off',0);
	   $pdf->AddPage('P');
	   $pdf->SetFont('Arial','',10);

	   /**
	    * set up location variable starts
	    **/

	   $brandTop = 24.4;
	   $productTop = 31;
	   $priceLeft = $x + 12.7;
	   $spriceTop = 43;
	   $npriceTop = 54;
	   $endDateTop = 60;
	   $tagCount = 0;
	   $down = 60;
	   $LeftShift = 80;
	   /*
	     $lineStartX = $x + 10;
	     $lineStopX = $x + $w - 10;
	     $lineStartY = 38;
	     $lineStopY = 38;
	   */

	   /**
	    * increment through items in query
	    **/

	   while ($row = mysqli_fetch_array($result)) {
	      /**
	       * check to see if we have made 8 tags.
	       * if we have start a new page....
	       */

	      if ($tagCount == 8) {
		 $pdf->AddPage('P');
		 $y = 15;
		 $x = 15;
		 $brandTop = 24.4;
		 $productTop = 31;
		 $priceLeft = $x + 12.7;
		 $spriceTop = 43;
		 $npriceTop = 54;
		 $endDateTop = 60;
		 $tagCount = 0;
		 /*
		  $lineStartX = $x + 10;
		 $lineStopX = $x + $w - 10;
		 $lineStartY = 38;
		 $lineStopY = 38;
		 */
	      }

	      /**
	       * check to see if we have reached the right most label
	       * if we have reset all left hands back to initial values
	       */
	      if ($x > 165) {
		 $y = $y + $down;
		 $x = 15;
		 $brandTop = $brandTop + $down;
		 $lineStartX = $x + 10;
		 $lineStopX = $x + $w - 10;
		 $lineStartY = $lineStartY + $down;
		 $lineStopY = $lineStopY + $down;
		 $priceLeft = $x + 12.7;
		 $spriceTop = $spriceTop + $down;
		 $npriceTop = $npriceTop + $down;
		 $productTop = $productTop + $down;
		 $endDateTop = $endDateTop + $down;

	      }

	   /**
	    * instantiate variables for printing on barcode from
	    * $testQ query result set
	    */
	      $product = ucwords(strtolower($row['description']));
	      $brand = ucwords(strtolower($row['brand']));
	      $nprice = '$' . number_format($row['nprice'],2);
	      $sprice = '$' . number_format($row['sprice'],2);

	   /**
	    * begin creating tag
	    */
	   $pdf->SetLineWidth(.2);
	   $pdf->Rect($x, $y, $w, $h-4);
	   $pdf->Image($image, $x, $y, $w, $h-4);
	   $pdf->SetFont('Arial','',13);
	   $pdf->SetXY($x, $brandTop);
	   $pdf->Cell($w,8,$brand,0,0,'C');
	   // $pdf->SetLineWidth(.4);
	   // $pdf->Line($lineStartX, $lineStartY, $lineStopX, $lineStopY);
	   $pdf->SetFont('Arial','B',42);
	   $pdf->SetXY($priceLeft,$spriceTop);
	   // $pdf->Cell($w-25.4,4,'Sale Price',0,0,'L');
	   // $pdf->SetXY($priceLeft,$spriceTop);
	   $pdf->Cell($w-25.4,4,$sprice,0,0,'C');
	   $pdf->SetFont('Arial','',14);
	   $pdf->SetXY($priceLeft,$npriceTop);
	   $pdf->Cell($w-25.4,4,'Regular Price  ' . $nprice,0,0,'C');
	   // $pdf->SetXY($priceLeft,$npriceTop);
	   // $pdf->Cell($w-25.4,4,$nprice,0,0,'R');
	   $pdf->SetFont('Arial','B',16);
	   $pdf->SetXY($x, $productTop);
	   $pdf->Cell($w,6,substr($product,0,26),0,0,'C');
	   $pdf->SetFont('Arial','I',10);
	   $pdf->SetXY($x, $endDateTop);
	   $pdf->Cell($w,3,$endDate,0,0,'C');

	   /**
	    * increment label parameters for next label
	    */
	     $x = $x + $LeftShift;
	     $priceLeft = $priceLeft + $LeftShift;
	     $lineStartX = $lineStartX + $LeftShift;
	     $lineStopX = $lineStopX + $LeftShift;
	     $tagCount++;
	   }
	}

	// Write the PDF...
	$pdf->Output();
    }
}
?>
