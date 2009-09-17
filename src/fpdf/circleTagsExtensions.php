<?php
// Ellipse and Wordwrap Extensions...
class PDF extends FPDF {
    function Circle($x,$y,$r,$style='') {
        $this->Ellipse($x,$y,$r,$r,$style);
    }
    
    function Ellipse($x,$y,$rx,$ry,$style='D') {
        if ($style=='F')
            $op='f';
        elseif ($style=='FD' or $style=='DF')
            $op='B';
        else
            $op='S';
        $lx=4/3*(M_SQRT2-1)*$rx;
        $ly=4/3*(M_SQRT2-1)*$ry;
        $k=$this->k;
        $h=$this->h;
        $this->_out(sprintf('%.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c',
            ($x+$rx)*$k,($h-$y)*$k,
            ($x+$rx)*$k,($h-($y-$ly))*$k,
            ($x+$lx)*$k,($h-($y-$ry))*$k,
            $x*$k,($h-($y-$ry))*$k));
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
            ($x-$lx)*$k,($h-($y-$ry))*$k,
            ($x-$rx)*$k,($h-($y-$ly))*$k,
            ($x-$rx)*$k,($h-$y)*$k));
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
            ($x-$rx)*$k,($h-($y+$ly))*$k,
            ($x-$lx)*$k,($h-($y+$ry))*$k,
            $x*$k,($h-($y+$ry))*$k));
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c %s',
            ($x+$lx)*$k,($h-($y+$ry))*$k,
            ($x+$rx)*$k,($h-($y+$ly))*$k,
            ($x+$rx)*$k,($h-$y)*$k,
            $op));
    }

    function WordWrap(&$text, $maxwidth) {
	$text = trim($text);
	if ($text==='')
	    return 0;
	$space = $this->GetStringWidth(' ');
	$lines = explode("\n", $text);
	$text = '';
	$count = 0;

	foreach ($lines as $line) {
	    $words = preg_split('/ +/', $line);
            $width = 0;

	    foreach ($words as $word) {
		$wordwidth = $this->GetStringWidth($word);
		if ($wordwidth > $maxwidth) {
		    // Word is too long, we cut it
                    for ($i=0; $i<strlen($word); $i++) {
			$wordwidth = $this->GetStringWidth(substr($word, $i, 1));
			if ($width + $wordwidth <= $maxwidth) {
                            $width += $wordwidth;
                            $text .= substr($word, $i, 1);
			} else {
			    $width = $wordwidth;
                            $text = rtrim($text)."\n".substr($word, $i, 1);
                            $count++;
			}
		    }
		} elseif ($width + $wordwidth <= $maxwidth) {
		    $width += $wordwidth + $space;
                    $text .= $word.' ';
		} else {
		    $width = $wordwidth + $space;
                    $text = rtrim($text)."\n".$word.' ';
                    $count++;
		}
	    }
	$text = rtrim($text)."\n";
	$count++;
	}
    $text = rtrim($text);
    return $count;
    }
}
// End of class extensions.
?>