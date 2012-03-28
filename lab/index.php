<?php
   $page_title = 'Fannie - Administration Module';
   $header = 'IT Development Section Index';
   include ('../includes/header.html');

echo '<body>';
   echo '<link rel="STYLESHEET" type="text/css" href="./style.css">';
   echo '<script type="text/javascript" language="javascript">';
   include ('../includes/javascript/lab.js');
   echo '</script>';

///////////////// not yet //////////////////
function make_link($link_name) {
    
    
 //$short_link = str_replace(".php","",str_replace("_"," ",substr($link_name,2,-1)));
$len = strlen($link_name) * -1;
 $short_link = substr($link_name, $len + 2);
$short_link = str_replace("_","&nbsp;",$short_link);

$list = array(".php",".js",".html");
$short_link = str_replace(".", "&nbsp;(", $short_link);
$short_link = $short_link . ")";

 echo '	<li class="lab_link">
		<a href="' . $link_name . '">' . $short_link . '</a>

	</li>';



}

function createIndex() {

    $directory = "./";

    $links = new DirectoryIterator($directory);
echo "<ul>";
    foreach ($links as $link) {
        if ((!$link->isDot()) && $link->isFile()) {
            
            $filename = $link->getPath() . DIRECTORY_SEPARATOR . $link->getFilename();
	
            make_link($filename);
        }
    }
echo "</ul>";

}

createIndex();
////////////////////////




       include ('../includes/footer.html');
?>
