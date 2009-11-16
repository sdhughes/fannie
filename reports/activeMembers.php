<?php
$page_title = 'Fannie - Reports Module';
$header = 'Active Members Report';
include ('../includes/header.html');

if (isset($_POST['submitted'])) {
    $date1 = $_POST['date1'];
    $date2 = $_POST['date2'];
    
    require_once('../includes/mysqli_connect.php');
    mysqli_select_db($db_slave, 'is4c_log');
    
    $query = "SELECT * FROM is4c_log.transarchive
        WHERE date(datetime) BETWEEN '$date1' AND '$date2'
        AND memtype IN (1,2) AND emp_no <> 9999 AND trans_status <> 'X'
        GROUP BY card_no";
    $result = mysqli_query($db_slave, $query);
    $answer = mysqli_num_rows($result);
    
    echo "<p>There were $answer unique member shoppers from $date1 to $date2.</p>";



} else { // Show the form
echo '<link href="../style.css"
        rel="stylesheet" type="text/css">';

?>
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/datePicker.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/datepicker/demo.css" />
    <script type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/datepicker/jquery.datePicker.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){    
            $('.datepick').datePicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true})
            .dpSetOffset(0, 125);
        });
    </script>
<form target="_blank" action="activeMembers.php" method="POST">
    <p>What range do you want a report for?&nbsp&nbsp</p>
    <table>
        <tr>
            <td>From: </td>
            <td><input type="text" size="10" name="date1" class="datepick" autocomplete="off" /></td>
        </tr>
        <tr>
            <td>To: </td>
            <td><input type="text" size="10" name="date2" class="datepick" autocomplete="off" /></td>
        </tr>
    </table>
    <input type="hidden" name="submitted" value="TRUE" /><br />
    <button name="submit" type="submit">Show me the numbers!</button>
</form>
<?php
}
include ('../includes/footer.html');
?>