<?php # Edited (04-17-2009) By Matthaus.
require_once('/pos/fannie/includes/config.php');
// This file also establishes a connection to MySQL.
	
// Make the connection to the master DB (to be used for writing [updating/inserting products, members, sales batches]
$db_master = mysqli_connect (MASTER_HOST, MASTER_USER, MASTER_PASS) or die ('Could not connect to MySQL Master: ' . mysqli_connect_error() );
$db_slave = mysqli_connect (SLAVE_HOST, SLAVE_USER, SLAVE_PASS) or die ('Could not connect to MySQL Slave: ' . mysqli_connect_error() );
	
// Create a function for escaping the data.
if (!function_exists('escape_data')) {
    function escape_data ($data) {
            
        // Address Magic Quotes.
        if (ini_get('magic_quotes_gpc')) {
            $data = stripslashes($data);
        }
        
        global $db_master; // Need the connection.
        $data = mysqli_real_escape_string ($db_master, trim($data));
        
        // Return the escaped value.
        return $data;
    } // End of function.
}
function filter($data) {
    $data = trim(htmlentities(strip_tags($data)));
 
    if (get_magic_quotes_gpc())
        $data = stripslashes($data);
 
    $data = mysql_real_escape_string($data);
 
    return $data;
}
?>
