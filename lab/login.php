<?php
/*  
    $header = ""; //top of center DIV
    $page_title = ""; //top of window
    include('../includes/header.html');
*/


    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');
/*
    echo
    <<<EOS
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <!-- a script ref to jquery.js was removed bc it was double included from the header  -->
    <script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script type="text/javascript">
        Date.format = 'yyyy-mm-dd';
        $(function(){
            $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd', changeYear: true, changeMonth: true, duration: 0 });
        });

    $(document).ready(function() {
        $('#selectAll').click(function() {
        if ($(this).text() == 'All Departments') {
            $('input.deptCheck').attr('checked', true);
            $(this).text('Clear Selections');
        } else {
            $('input.deptCheck').attr('checked', false);
            $(this).text('All Departments');
        }
        });

        $('.deptCheck').click(function() {
        $('#selectAll').text('Clear Selections');
        });
    });

    </script>
    EOS;
*/
mysqli_select_db($db_master, 'is4c_op') or die("Select DB Error:" . mysqli_error($db_master));

//Pull out the vars from the POST or GET arrays
if (isset($_POST['submit'])) {
    foreach ($_POST as $key => $val) ${$key} = $val;

} elseif (isset($_GET['submit']))  {
    foreach ($_GET as $key => $val) ${$key} = $val;
}

//if it's submitted, then do the query work. // or change it to what you want
if ($submit == 'submit') {

    //enter your query here or query generation loop        
    $query = "SELECT * FROM is4c_op.employees where emp_no = $name and CashierPassword = $password";

    //get the results of the query or spit an error 
    $result = mysqli_query($db_master, $query) or die ("Error from query: <br /> $query : <br /><br />" . mysqli_error($db_master));

    //you can count the results if you want
    $row_count = mysqli_num_rows($result);
    
    echo "row count" . $row_count;
        
    //iterator

 
    echo "<table id=''>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<tr>";
        echo "<td>";
        //echo $row[0];
        print_r($row);
        echo "</td>";
        echo "</tr>";
    
    
      
    }
    echo "</table>";
    
 

} else { //what if it's not submitted?

}
?>
<form>
    <input type='text' name='name' value=''>
<!--    <input type='text' name='date1' value='' class='datepick' >
    <input type='text' name='date2' value='' class='datepick' >
-->    
    <input type='password' name='password' value=''>
    <input type='submit' name='submit' value='submit'>
    <input type='reset' name='reset' value='reset'>
    
</form>


<?php
/*
    include('../includes/footer.html');
*/
?>
