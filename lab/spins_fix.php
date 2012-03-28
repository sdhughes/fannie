<?php
/********
 *
 *  spins date generator
 *
 *  dynamically calculates weekend start and end dates for the next year 
 *  author: Matthaus Litteken
 *  date: Jan 8, 2011
 *
 ********/


    ini_set('display_errors', 'on');
    ini_set('error_reporting', E_ALL);

    $page_title = 'Fannie - Admin Module';
    $header = 'SPINS - Reporting Periods';

    include('../includes/header.html');
    require_once('../includes/mysqli_connect.php');

    mysqli_select_db($db_master, 'is4c_log');
?>
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
<!--    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
-->
<script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
<script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
<script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
<script type="text/javascript">
Date.format = 'yyyy-mm-dd';
    $(function(){
        $('.datepick').datepicker({ 
                    startDate:'2007-08-01',
                    endDate: (new Date()).asString(), 
                    clickInput: true, 
                    dateFormat: 'yy-mm-dd', 
                    changeMonth: true, 
                    changeYear: true,
                    duration: 0
         });

    // $('.datepick').focus();
    });
</script>

<?php

if (isset($_POST['submit'])) {
if ($_POST['submit']) {

$spinsYear = substr($_POST['date1'],0,4);
echo $spinsYear . "<br /> <hr>";

$q = "SELECT MAX(end_date) FROM SPINS_$spinsYear";
    $r = mysqli_query($db_master, $q);

    if ($r) {
        list($start) = mysqli_fetch_row($r);
        $date = new DateTime($start);
        $date->add(new DateInterval('P1D'));
        printf("Max 2011 Date: %s<br />", $date->format('Y-m-d'));
        $start = new DateTime($date->format('Y-m-d'));
    } else {
        printf('Error! %s', mysqli_error($db_master));
    }

    $end = new DateTime($start->format('Y-m-d'));
    $endDate = new DateTime($start->format('Y-m-d'));
    $endDate->add(new DateInterval('P365D'));

    $weekCount = 0;
    $success = 0;
    $spinsNextYear = $spinsYear + 1;

    //strtotime($end->format('Y-m-d')) < strtotime($endDate->format('Y-m-d')) && 
     echo '<table border=1><tr><td>Period:</td><td>WeekCount:</td><td>Start:</td><td>End:</td></tr>';

    while ($weekCount <= 52) {
        $weekCount++;
        $period = floor(($weekCount - 1) / 4) + 1;
        
        $end = new DateTime($start->format('Y-m-d'));
        $end->add(new DateInterval('P6D'));
        
        printf('<tr><td>%u</td><td>%u</td><td>%s</td><td>%s</td></tr>', $period, $weekCount, $start->format('Y-m-d'), $end->format('Y-m-d'));
if ($_POST['submit'] == 'insert') {
        $q2 = sprintf("INSERT INTO SPINS_%s (period, week_tag, start_date, end_date) VALUES (%u, %u, '%s', '%s')", $spinsNextYear, $period, $weekCount, $start->format('Y-m-d'), $end->format('Y-m-d'));
        
        $r2 = mysqli_query($db_master, $q2);
        
        if ($r2 && mysqli_affected_rows($db_master) == 1) {
            $success++;
        } else {
            printf("Error: %s, Query: %s", mysqli_error($db_master), $q2);
        }
}        
        $start = new DateTime($end->format('Y-m-d'));
        $start->add(new DateInterval('P1D'));
    }
    echo "</table>";
    echo "<h2>Inserted ". $success . " of " . $weekCount . " weeks.</h2>";
}
} else {

    echo "
    <div class='directions'>This tool calcuates the period breakdown for SPINS reporting for a chosen fiscal year. It should be run before the start of each new year. Please ensure the table is created already and that each year has the correct amount of weeks.</div>

    <form method='post' action='" . $_SERVER['PHP_SELF'] . "'><br/>

        Select date in current year, from which to generate dates for next year:<br />
        <input type='text' id='date1' name='date1' class='datepick' />
        <br /><input type='submit' name='submit' id='submit' value='print' />
        <input type='submit' name='submit' id='insert' value='insert' />
        <input type='reset' name='reset' id='reset' value='reset' />
    </form>
    ";
}

include('../includes/footer.html');
