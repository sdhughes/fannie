<?php
require_once("/pos/fannie/includes/mysqli_connect.php");
mysqli_select_db($db_slave, 'is4c_log');

/////////  O P T I O N S 	//////////////////
//	Pick a year	
//	(leave blank for current)
// $year = "";								
if (!$year) {$year = date('Y');}				
//	Options for reporting:
//		## == enter a week tag number
//		YY == output entire year (slow!)
//		(leave blank for current output)
//$week_tag = "12";
if (!$week_tag) $week_tag = date('W') - 1;

//	format datetime
$timestamp = date('Y-m-d H:i:s');
//	specify a log file to direct stdout
$log_file = '/pos/fannie/logs/spins.log';
//	Which SPINS table will we use?
$SPINS = "SPINS_" . $year;
//	Which dlog archive will we use?	
$table = "trans_" . $year;
//	Directory to put .csv files into
//	(make sure this already exists)	
$outpath = "/pos/fannie/SPINS/" . $year . "/";
//	filename prefix (incl _wk)
$prefix = "alb_wk";	
///////////////////////////////////////////////

if (is_numeric($week_tag) || !$week_tag) {

        //	Get start_ and end_date info
        if (!$week_tag) {
                $query = "SELECT * FROM is4c_log.$SPINS
                        WHERE end_date < CURDATE()
                        ORDER BY week_tag DESC LIMIT 1";
        } else {
                $query = "SELECT * FROM is4c_log.$SPINS
                        WHERE week_tag = $week_tag";	
        }

        $result = mysqli_query($db_slave, $query);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        //	fill vars to use in main query
        $start_date = $row['start_date'];
        $end_date = $row['end_date'];
        $tag = str_pad($row['week_tag'], 2, "0", STR_PAD_LEFT);

        //	Echo the matched week data
        error_log("[$timestamp] -- Week tag #$tag selected.  \$start_date = $start_date. \$end_date = $end_date\n",3,$log_file);

        //	Specify /path/to/file and filename
        $outfile = $outpath . $prefix . $tag . ".csv";
        error_log("[$timestamp] -- File path and name set.  \$outfile = $outfile\n",3,$log_file);

        //	free result resources
        mysqli_free_result($result);

        //	The main query
        $query = "SELECT upc, description, SUM(quantity) AS qty, SUM(total) AS total
                FROM is4c_log.$table
                WHERE DATE(datetime) BETWEEN '$start_date' AND '$end_date'
                AND upc > 99999 AND scale = 0 
                AND emp_no <> 9999 AND trans_status <> 'X'
                GROUP BY upc HAVING qty > 0";
        // echo $query;
        $result = mysqli_query($db_slave, $query);
        $num = mysqli_num_rows($result);

        if ($num == 0) {
                error_log("[$timestamp] ** Error: Your query returned no results.  Exiting\n",3,$log_file);
                exit;
        } elseif (!$write = fopen($outfile,"w")) {
                error_log("[$timestamp] ** Error: Cannot open file $outfile.  Exiting\n",3,$log_file);
            exit;
        } else {
                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                        $output .= $row['upc'] . "|\"" . $row['description'] . "\"|" . $row['qty'] . "|" . $row['total'] . "\n";
                }	

                if (fwrite($write, $output) === FALSE) {
                error_log("[$timestamp] ** Error: Cannot write to file $outfile.  Exiting\n",3,$log_file);
                exit;
                }
        }
        error_log("[$timestamp] ++ Success, wrote $num rows to file $outfile\n",3,$log_file);

        fclose($write);

        //	free result resources
        mysqli_free_result($result);

} elseif ($week_tag == "YY") {
        
        $query = "SELECT * FROM is4c_log.$SPINS";
        $result = mysqli_query($db_slave, $query);
        $num = mysqli_num_rows($result);

        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC)) {
                $start_date = $row['start_date'];
                $end_date = $row['end_date'];
                $tag = str_pad($row['week_tag'], 2, "0", STR_PAD_LEFT);

                //	Echo the matched week data
                error_log("[$timestamp] -- Week tag #$tag selected.  \$start_date = $start_date. \$end_date = $end_date\n",3,$log_file);

                //	Specify /path/to/file and filename
                $outfile = $outpath . $prefix . $tag . ".csv";
                error_log("[$timestamp] -- File path and name set.  \$outfile = $outfile\n",3,$log_file);

                //	The main query
                $dataQ = "SELECT upc, description, SUM(quantity) AS qty, SUM(total) AS total
                        FROM is4c_log.$table
                        WHERE DATE(datetime) BETWEEN '$start_date' AND '$end_date'
                        AND upc > 99999 AND scale = 0 
                        AND emp_no <> 9999 AND trans_status <> 'X'
                        GROUP BY upc HAVING qty > 0";
                // echo $query;
                $dataR = mysqli_query($db_slave, $dataQ);
                $dataNum = mysqli_num_rows($dataR);

                if ($num == 0) {
                        error_log("[$timestamp] ** Error: Your query returned no results.  Exiting\n",3,$log_file);
                        exit;
                } elseif (!$write = fopen($outfile,"w")) {
                        error_log("[$timestamp] ** Error: Cannot open file $outfile.  Exiting\n",3,$log_file);
                    exit;
                } else {
                        $output = NULL;
                        while ($dataRow = mysqli_fetch_array($dataR, MYSQLI_ASSOC)) {
                                $output .= $dataRow['upc'] . "|\"" . $dataRow['description'] . "\"|" . $dataRow['qty'] . "|" . $dataRow['total'] . "\n";
                        }
                        if (fwrite($write, $output) === FALSE) {
                        error_log("[$timestamp] ** Error: Cannot write to file $outfile.  Exiting\n",3,$log_file);
                        exit;
                        }
                }
                error_log("[$timestamp] ++ Success, wrote $dataNum rows to file $outfile\n",3,$log_file);
        }
        fclose($write);		
} else {
        echo "sumthings broked.  check your settings and tries it again.";
        exit;
}
?>
