<?php
    

    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');

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
		$('#login_form').hide();


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

	$('#update_button').live('click',function(event){
	$('#login_form').show();


	});

    });

	

    </script>
EOS;


//Pull out the vars from the POST or GET arrays
if (isset($_POST['submit'])) {
    foreach ($_POST as $key => $val) ${$key} = $val;

} elseif (isset($_GET['submit']))  {
    foreach ($_GET as $key => $val) ${$key} = $val;
}

//if it's submitted, then do the query work. // or change it to what you want
if ($submit == 'submit') {

    require_once('../includes/itemFunction.php');
    
    $no = $_POST['emp_no'];
echo $no ."<br/>";
    logAction($no, 3);

    //enter your query here or query generation loop        
    $query = "SELECT * FROM is4c_log.update_log";

    //get the results of the query or spit an error 
    $result = mysqli_query($db_master, $query) or die ("Error from query: <br /> $query : <br /><br />" . mysqli_error($db_master));

    //you can count the results if you want
    //$row_count = mysqli_num_rows($result);
   

print_r($_POST); 
    //iterator
    echo "<table id=''>";
    echo "<th>Rec No</th>";
    echo "<th>Emp No</th>";
    echo "<th>Action</th>";
    echo "<th>When</th>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<tr>";
        echo "<td>";
        echo $row[0];
        echo "</td>";
        echo "<td>";
        echo $row[1];
        echo "</td>";
        echo "<td>";
        echo $row[2];
        echo "</td>";
        echo "<td>";
        echo $row[3];
        echo "</td>";
        echo "</tr>";
    
    
      
    }
    echo "</table>";
    

} else { //what if it's not submitted?
}
?>
<button id='update_button'>UPDATE</button>

<form id='login_form' method='POST' action='<?php echo $_SERVER['PHP_SELF']; ?>'>
    <input type='text' name='emp_no' value=''>
    <input type='submit' name='submit' value='submit'>
    <input type='reset' name='reset' value='reset'>
    
</form>


<?php

    include('../includes/footer.html');
?>
