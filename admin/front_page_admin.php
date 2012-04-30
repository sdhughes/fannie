<?php
	require_once('../includes/mysqli_connect.php');
	global $db_master;
	$data = 'fannie';
	mysqli_select_db($db_master, $data) or die("Select DB Error: " . mysqli_error($db_master));

	function display_announcement_table() { 
	global $db_master;
    
            //pull the announcements for display, making sure to clean up the message from collecting newlines (due to how it's fetched in by jQuery
            $announcementQuery = 'SELECT author,title,replace(replace(message,"\n",""),"\r",""),id,enabled,modified FROM fannie.announcements ORDER BY enabled DESC;';

            $announcementResult = mysqli_query($db_master, $announcementQuery) or die('Announcement Query error: ' . mysqli_error($db_master));

            //$numMsgs = mysqli_num_rows($announcementResult);

            //output the form/table with the messages
            echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>
                        <table id='announcement_display'>
                        <tr>
                            <th>On?</th>
                            <th>Author</th>
                            <th>Title</th>
                            <th>Message</th>
                            <th>ID</th>
                        </tr>";

            //	print_r($announcementResult);
            //display the plans in editable columns
            while ( $row = mysqli_fetch_row($announcementResult)) {
        //		print_r($row);
                $string = sprintf("<tr class='ann_row'>
                <td class='enablebox'><input type='checkbox' name='announcement_enabled[]' %s /></td>
                <td class='authorbox'><input type='text' name='announcement_author[]' value=\"%s\" /></td>
                <td class='titlebox'><input type='text'  name='announcement_title[]' value=\"%s\" /></td>
                <td class='messagebox'><textarea wrap='physical' name='announcement_message[]' >%s</textarea></td>
                <td><label class='id' name='announcement_id[]' >%u</label></td>
                </tr>",(($row[4]==1)?"checked=checked":""),$row[0],$row[1],$row[2],$row[3]);
                
                echo $string;
                
            }
            echo "</table>";

    }

	function display_link_table() { 
    
            //pull the announcements for display, making sure to clean up the message from collecting newlines (due to how it's fetched in by jQuery
           $linkQuery = 'SELECT title,url,enabled,modified,id FROM fannie.links ORDER BY enabled DESC;';

            $linkResult = mysqli_query($db_master, $linkQuery) or die('Link Query error: ' . mysqli_error($db_master));

            //$numLink = mysqli_num_rows($linkResult);

            //output the form/table with the messages
            echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>
                        <table id='link_display'>
                        <tr>
                            <th>On?</th>
                            <th>Title</th>
                            <th>URL</th>
                            <th>ID</th>
                        </tr>";

            //	print_r($linkResult);
            //display the plans in editable columns
            while ( $row = mysqli_fetch_row($linkResult)) {
        //		print_r($row);
                $string = sprintf("<tr class='link_row'>
                <td class='link_enablebox'><input type='checkbox' name='link_enabled[]' %s /></td>
                <td class='link_titlebox'><input type='text'  name='link_title[]' value=\"%s\" /></td>
                <td class='link_urlbox'><textarea wrap='physical' name='link_url[]' >%s</textarea></td>
                <td><label class='id' name='link_id[]' >%u</label></td>
                </tr>",(($row[2]==1)?"checked=checked":""),$row[0],$row[1],$row[2],$row[4]);
                
                echo $string;
                
            }
            echo "</table>";

    }

	$header = "Announcement Administration";
	$page_title = "Fannie - Announcement Admin";
	include('../includes/header.html');
    echo "<script type=\"text/javascript\" src=\"../includes/javascript/announcements.js\"></script>";
	//connect to the database

	echo "<p class='directions'>Create, Edit or Update an Announcement that appears on Fannie's Welcome page. Please be careful. Changes are live once you click Submit.</p>";

	foreach ($_POST AS  $key => $value) {
		$$key = $value;
//DEBUGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG
//		echo "$key = " . $value . "<br />"; 
//DEBUGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGGG
	}
	
	if (isset($_POST['submit']) && $_POST['submit']=='add_announcement') {
		$updateQuery = "INSERT INTO fannie.announcements (author, title, message, enabled) VALUES (\"" . $new_announcement_author ."\",\"" . $new_announcement_title . "\",\"" . mysql_real_escape_string($new_announcement_message) . "\",\"" . (($new_announcement_enabled=='on')?1:0) . "\")";
		
	} elseif (isset($_POST['submit']) && $_POST['submit']=='add_link') {
		$updateQuery = "INSERT INTO fannie.links (title, url, enabled) VALUES (\"" . $new_link_title ."\",\"" . mysql_real_escape_string($new_link_url) . "\",\"" .  (($new_announcement_enabled=='on')?1:0) . "\")";
		
    }
    if ($updateQuery) $result = mysqli_query($db_master,$updateQuery) or die('Query Error: ' . mysqli_error($db_master));


    display_announcement_table();


//echo "<input type='submit' name='submit' value='update' />
//	<input type='hidden' name='oldMaxSize' value='$numMsgs' />";
    echo "<p class='directions'>Enter a new announcement:</p>";
    echo "<table class='add_announcement_table'><tr><th>on?</th><th>Author</th><th>Title</th><th>Message</th></tr>";
    echo "		<tr>
		<td><input type='checkbox' class='new_enablebox' name='new_announcement_enabled' /></td>
		<td><input type='text' class='new_authorbox' name='new_announcement_author' value=\"\" /> </td>
		<td><input type='text' class='new_titlebox' name='new_announcement_title' value=\"\" /></td>
		<td><textarea wrap='physical' class='new_messagebox' name='new_announcement_message' value=\"\" ></textarea></td>
		<td><input type='submit' name='submit' value='add_announcement' /></td>
		</tr>";
    echo "</table>";
    echo "<table class='add_link_table'>
            <tr>
                <td><input type='checkbox' class='new_link_enable' name='new_link_enabled' /></td>
                <td><input type='text' class='new_link_title' name='new_link_title' /></td>
                <td><input type='text' class='new_link_url' name='new_link_url' /></td>
                <td><input type='submit' name='submit' value='add_link' /></td>
            </tr>
        </table>";


    echo "</form>";

    //close the things
	mysqli_close($db_master);


	include('../includes/footer.html');
?>
