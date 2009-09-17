<?php
require_once ('../src/fpdf/fpdf.php');
define('FPDF_FONTPATH','font/');
require_once ('../src/fpdf/circleTagsExtensions.php');

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

while ($page < 6) {
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
    
    $cell = 0;
    
    $pdf->AddPage('P');

    while ($y < $ymax) {
        while ($x < $xmax) {
            // This is the actual tag generation.
            $pdf->Circle($x + $r, $y + $r, $r);
            
            // Organic, Non-Organic, Wildcrafted
            $pdf->SetFont('Georgia','B',14);
            $pdf->SetXY($x, $y + $certTop);
            $pdf->Cell($r * 2,6,$testingArray[$cell][0],0,0,'C');
            
            // Brand Name
            $pdf->SetFont('Georgia','B',15);
            $pdf->SetXY($x, $y + $brandTop);
            $pdf->Cell(($r * 2),6,$testingArray[$cell][1],0,0,'C');
            
            // Check # of lines for description and ingredients, limit to 2 for description and 5 for ingredients
            // Product description
            $pdf->SetFont('Georgia','B',20);
            $pdf->SetXY($x+2, $y + $descTop);
            $pdf->MultiCell(($r * 2)-4,8,$testingArray[$cell][2],0,'C');
            
            // Ingredient list
            /*$ingredients = ;'Ingredients: ' . $testingArray[$cell][3]*/
            $pdf->SetFont('Georgia','B',8);
            $pdf->SetXY($x, $y + $ingredTop);
            $pdf->MultiCell($r * 2,3.5, 'Ingredients: ' . $testingArray[$cell][3],0,'C');
            
            // PLU Number
            $pdf->SetFont('Georgia','B',13);
            $pdf->SetXY($x, $y + $pluTop);
            $pdf->Cell($r * 2,6,"Code #{$testingArray[$cell][4]}",0,0,'C');
            
            // Price per oz
            $pdf->SetXY($x, $y + $ozTop);
            $pdf->Cell($r * 2,6,"$" . number_format($testingArray[$cell][5]/16, 2) . "/oz",0,0,'C');
            
            // Price per lb
            $pdf->SetXY($x, $y + $lbTop);
            $pdf->Cell($r * 2,6,"$" . number_format($testingArray[$cell][5], 2) . "/lb",0,0,'C');
            
            // Incrementation
            $x += $leftShift;
            ++$cell;
        }
        
        $y += $downShift;
        $x = $left;
        
    }
    ++$page;
}

$pdf->Output();

?>