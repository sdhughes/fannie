<?php


ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

//echo "wtf";
$header = 'Hourly Sales';
$page_title = 'Fannie - Reporting';
include('../includes/header.html');

require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_master,'is4c_log');

if (isset($_POST['submit'])) {

$date1 = $_POST['date1']; 
$date2 = $_POST['date2'];

$year1 = substr($date1,0,4);
$year2 = substr($date2,0,4);
    
    
    $CountQ = "SELECT date(datetime), hour(datetime), upc FROM (";
        
    for ($i = $year1; $i <= $year2; $i++) { 
        $CountQ .= "SELECT datetime, COUNT(upc) as upc FROM is4c_log.trans_$i WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN' GROUP BY date(datetime), hour(datetime)";
        if ($i == $year2) {
            if ($date2==date('Y-m-d')) { 

        	    //only add this if on the same day to get 'dtransactions'
            
                $CountQ .= " UNION ALL SELECT datetime, COUNT(upc) as upc FROM is4c_log.dtransactions WHERE DATE(datetime) BETWEEN '$date1' AND '$date2' AND upc = 'DISCOUNT' AND emp_no <> 9999 AND trans_status <> 'X' AND trans_subtype <> 'LN' GROUP BY date(datetime), hour(datetime)";
            }
            $CountQ .= ") AS yearSpan GROUP BY date(datetime), hour(datetime),upc";
        } else {
            $CountQ .= " UNION ALL "; 
        }
    }

$query = "
        SELECT 
            date(datetime),
            hour(datetime),
            SUM(total) 
        FROM (";


    $year = $year1;

    while ($year <= $year2) {
        $query .= "
                SELECT 
                    d.dept_name AS dept, 
                    d.dept_no AS dept_no, 
                    ROUND(SUM(t.total),2) AS total, 
                    t.datetime as datetime 
                FROM 
                    is4c_op.departments AS d, 
                    is4c_log.trans_$year AS t 
                WHERE 
                    d.dept_no = t.department 
                        AND 
                    DATE(t.datetime) 
                        BETWEEN '$date1' 
                            AND '$date2' 
                        AND
                    t.department IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 18, 40) 
                        AND 
                    t.trans_status <> 'X' 
                        AND 
                    t.trans_subtype <> 'MC' 
                        AND 
                    t.emp_no <> 9999 
                GROUP BY 
                    YEAR(datetime), 
                    MONTH(datetime), 
                    DAY(datetime), 
                    HOUR(datetime) ";

        $year++;
    } //end while ($year1 <= $year2)
                    
    $query .= " )  
        AS yearSpan 
        GROUP BY 
            YEAR(datetime), 
            MONTH(datetime), 
            DAY(datetime), 
            HOUR(datetime);
";


    $result = mysqli_query($db_master,$query) or die("Query Error: <br /> $query <br />" . mysqli_error($db_master));

    $countR = mysqli_query($db_master,$CountQ) or die("Query Error: <br /> $CountQ <br />" . mysqli_error($db_master));
   
    echo "<table id='hourly_sales' class='thinborder'>";
    echo "
    <td>Date</td>
    <td>from</td>
    <td>til</td>
    <td>Sales</td>
    ";
    while ($row = mysqli_fetch_row($result)) {
 
        echo "<tr>";
        echo "<td>$row[0]</td>";
        echo "<td>$row[1]</td>";
        echo "<td>" . ($row[1] + 1) ."</td>";
        echo "<td>$$row[2]</td>";
        echo "</tr>";

    }
    echo "</table>";
    echo "<table id='hourly_custs' class='thinborder'>";
    echo "
    <td>Date</td>
    <td>from</td>
    <td>til</td>
    <td>Custs</td>
    ";
    while ($custrow = mysqli_fetch_row($countR)) {
 
        echo "<tr>";
        echo "<td>$custrow[0]</td>";
        echo "<td>$custrow[1]</td>";
        echo "<td>" . ($custrow[1] + 1) ."</td>";
        echo "<td>$custrow[2]</td>";
        echo "</tr>";

    }
    echo "</table>";

}

?>
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
            $(function(){
                $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd', changeYear: true, changeMonth: true, duration: 0 });
            });
    </script>

<form action='<?php //echo $_SERVER['PHP_SELF'] ?>' method='post'>
    <input type='text' name='date1' value='' class='datepick' />
    <input type='text' name='date2' value='' class='datepick' />
    <input type='submit' name='submit' value='submit' />
</form>

<?php

   include('../includes/footer.html'); 
?>
