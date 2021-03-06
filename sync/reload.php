<?php

function reloadtable($table) {

    $aLane = array(
            "192.168.1.51",
            "192.168.1.52",
            "192.168.1.53"
            );

//    $aLane = array ("192.168.1.51");

    require_once('../../config.php');    
    $server = MASTER_HOST;
    $opDB = "is4c_op";
    $serveruser = MASTER_USER;
    $serverpass = MASTER_PASS;
    $opDB = "is4c_op";

    $laneuser = "root";
    $lanepass = "";

    $file = "/pos/is4c/download/".$table.".out";
    $dump = "select * into outfile '".$file."' from ".$table;
    $load = "load data infile '".$file."' into table is4c_op.".$table;

    $is4c_op_truncate = "truncate is4c_op.".$table;
    $opdata_truncate = "truncate opdata.".$table;
    $opdata_insert = "insert into opdata. ".$table." select * from is4c_op.".$table;

    echo "<font color='#004080' face=helvetica><b>".$table."</b></font>";
    echo "<p>";


    $continue = 0;

    /* establish connection to server */

    if ($s_conn = mysql_connect($server, $serveruser, $serverpass)) {
        $continue = 1;
	//DEBUG - Please comment out
//    	echo "<p>Successfully Connected to Server</p>";
    } else {
        echo "<p><font color='#800000' face=helvetica size=-1>Failed to connect to server</font>";
    }

    if ($continue == 1) {
        $continue = 0;
        if (mysql_select_db("is4c_op", $s_conn)) $continue = 1;
        else echo "<p><font color='#800000' face=helvetica size=-1>Failed to connect to server database</font>";
    }

    if ($continue == 1) {
        $continue = 0;
        $result = mysql_query("select count(*) from ".$table, $s_conn);
        $row = mysql_fetch_array($result);
        $server_num_rows = $row[0];
        // echo "<p><font color='#004080' face=helvetica size=-1>There are ".$server_num_rows." record(s) on server database</font>";
        if ($server_num_rows >= 10) $continue = 1;
        else echo "<p><font color='#800000' face=helvetica size=-1>There are only ".$server_num_rows." records on the server.<br>No way</font>";
    }

    // synchronize lanes

    //DEBUG - Please comment out
  //  echo "<p>Server Data Ready. Starting Synchronization.</p>";
    if ($continue == 1) {

        $continue = 0;
        echo "<p><font color='#004080' face=helvetica size=-1>".$server_num_rows." records downloaded from server</font>";
        echo "<p>";

        $i = 1;
        foreach ($aLane as $lane) {
            $lane_num = "lane ".$i;
            
            $i++;

            $lane_continue = 0;
            if ($lane_conn = mysql_connect($lane, $laneuser, $lanepass)) $lane_continue = 1;
            else echo "<br><font color='#800000' face=helvetica size=-1>Unable to connect to ".$lane_num."</font>";

            if ($lane_continue == 1) {
                $lane_continue = 0;
                if (mysql_select_db("opdata", $lane_conn)) $lane_continue = 1;
                else echo "<br><font color='#800000' face=helvetica size=-1>Unable to select database on ".$lane_num."</font>";
            }

            if ($lane_continue == 1) {
                $lane_continue = 0;
                mysql_query($is4c_op_truncate, $lane_conn);

                if (synctable($table,$serveruser,$opDB,$lane,$serverpass) == 1) {
                    $result = mysql_query("select count(*) from is4c_op.".$table, $lane_conn);
                    $row = mysql_fetch_array($result);
                    $lane_num_rows = $row[0];
                    if ($lane_num_rows == $server_num_rows) $lane_continue = 1;
                    else echo "<br><font color='#800000' face=helvetica size=-1>".$lane_num.": Number of records do not match. Synchronization refused</font>";
                }
                else echo "<br><font color='#800000' face=helvetica size=-1>Unable to load new data onto ".$lane_num."</font>";
            }

            if ($lane_continue == 1) {
                $lane_continue = 0;
                if (mysql_query($opdata_truncate, $lane_conn)) {
                    if (mysql_query($opdata_insert, $lane_conn)) {
                        $qresult = mysql_query("select * from ".$table, $lane_conn);
                        $lane_num_rows = mysql_num_rows($qresult);
                        echo "<br><font color='#004080' face=helvetica size=-1>".$lane_num.": ".$lane_num_rows." records successfully synchronized";
                    } else {
                        echo "<br><font color='#800000' face=helvetica size=-1>Unable to synchronize ".$lane_num."</font>";
                    }
                }
            }
        }
    }
    $time = strftime("%m-%d-%y %I:%M %p", time());
    echo "<p> <p><font color='#004080' face=helvetica size=-1>last run: ".$time."</font>";

}

function synctable($table,$serveruser,$opDB,$lane,$serverpass) {
    openlog("is4c_connect", LOG_PID | LOG_PERROR, LOG_LOCAL0);
    exec('mysqldump -u '.$serveruser.' -p' . $serverpass . ' -t '.$opDB.' '.$table.' | mysql -h '.$lane.' '.$opDB." 2>&1", $result, $return_code);
    foreach ($result as $v) {$output .= "$v\n";}
    if ($return_code == 0) {
        return 1;
    } else {
        syslog(LOG_WARNING, "synctable($table) failed; rc: '$return_code', output: '$output'");
        return 0;
    }
}

$allowed = array('employees', 'products', 'custdata', 'subdepts', 'departments', 'tenders', 'messages');
if (in_array($_GET['table'], $allowed)) {
    $page_title = 'Fannie - Synchronize Module';
    $header = 'Synchronize';
    include ('../includes/header.html');
    reloadtable("{$_GET['table']}");
    include ('../includes/footer.html');
}

?>
