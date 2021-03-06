<?php
    require_once ('./makefont.php');
    $fontArray = array(
		       './HelveticaFontFiles/Helvetica',
		       './HelveticaFontFiles/HelveticaBold',
		       './HelveticaFontFiles/HelveticaBoldOblique',
		       './HelveticaFontFiles/HelveticaOblique',
		       './HelveticaNeueFontFiles/HelveticaNeue',
		       './HelveticaNeueFontFiles/HelveticaNeueBold',
		       './HelveticaNeueFontFiles/HelveticaNeueBoldItalic',
		       './HelveticaNeueFontFiles/HelveticaNeueCondensedBold',
		       './HelveticaNeueFontFiles/HelveticaNeueCondensedBlack',
		       './HelveticaNeueFontFiles/HelveticaNeueItalic',
		       './HelveticaNeueFontFiles/HelveticaNeueLight',
		       './HelveticaNeueFontFiles/HelveticaNeueLightItalic',
		       './HelveticaNeueFontFiles/HelveticaNeueUltraLight',
		       './HelveticaNeueFontFiles/HelveticaNeueUltraLightItalic'
		      );
    
    function MakeFontWrapper($name, $fonts) {
	foreach ($fonts AS $font) {
	    MakeFont($name . '.ttf', $name . '.afm');
	}
    }
?>
