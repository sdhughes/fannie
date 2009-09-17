<?php
function select_to_table($db, $query, $border, $bgcolor) {
        
        $results = mysqli_query($db, $query) or
		die("<li>errorno=".mysqli_errno($db)."</li>"
			."<li>error=" .mysqli_error($db)."</li>"
			."<li>query=".$query)."</li>";
	$cols = mysqli_fetch_fields($results);
	
	echo "<font size = 2.5>";
	echo "<table border = $border bgcolor=$bgcolor>\n";
	echo "<tr align left>\n";
	foreach ($cols AS $col) {
		echo "<th><font size =2.5>" . $col->name . "</font></th>\n";
	}
	echo "</tr>\n"; //end table header
	//layout table body
        
	while ($row = mysqli_fetch_row($results)) {
            echo '<tr align="left">' . "\n";
            $i = 0;
            foreach($cols AS $col) {
                echo '<td width="225" align="right"><font size="2.5">';
                    if (!isset($row[$i])) {
                        echo "NULL";
                    } else {
                        echo $row[$i];
                    }
                    echo "</font></td>\n";
                    $i++;
            }
            echo "</tr>\n";
            
	} echo "</table>\n";
	echo "</font>";
}
?>