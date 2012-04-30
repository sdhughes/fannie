<?php
require_once "Auth.php";

    $header = "Fannie - Login"; //top of center DIV
    $page_title = "Welcome to Fannie"; //top of window
    include('../includes/header.html');

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




// Takes three arguments: last attempted username, the authorization
// status, and the Auth object. 
// We won't use them in this simple demonstration -- but you can use them
// to do neat things.
function loginFunction($username = null, $status = null, &$auth = null)
{
    /*
     * Change the HTML output so that it fits to your
     * application.
     */
    echo "<form method=\"post\" action=\"" . $_SERVER['PHP_SELF'] . "\">";
    echo "    <input type=\"text\" name=\"username\">";
    echo "    <input type=\"password\" name=\"password\">";
    echo "    <input type=\"submit\">";
    echo "</form>";
}

require_once('../../config.php');
$mysql_user = MASTER_USER;
$mysql_password = MASTER_PASS;
$mysql_host = MASTER_HOST;
$mysql_database = "fannie";


$options = array(
  'dsn' => "mysql://$mysql_user:$mysql_password@$mysql_host/$mysql_database",
  );
$a = new Auth("MDB2", $options, "loginFunction");

$a->start();

if ($a->checkAuth()) {
    /*
     * The output of your site goes here.
     */

  



    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');

    //print_r($_GET);
    //print_r($_SESSION);

if ($_GET['action'] == "logout" && $a->checkAuth()) {
    $a->logout();
    $a->start();
//    echo "haaaaay";

}else {

        mysqli_select_db($db_master, 'is4c_op') or die("Select DB Error:" . mysqli_error($db_master));

        //Pull out the vars from the POST or GET arrays
        if (isset($_POST['submit'])) {
        foreach ($_POST as $key => $val) ${$key} = $val;

        } elseif (isset($_GET['submit']))  {
        foreach ($_GET as $key => $val) ${$key} = $val;
        }

        $submit = 'submit';
        //if it's submitted, then do the query work. // or change it to what you want
        if (!empty($submit)) {

        //enter your query here or query generation loop        
        $query = "SELECT * FROM is4c_op.employees";

        //get the results of the query or spit an error 
        $result = mysqli_query($db_master, $query) or die ("Error from query: <br /> $query : <br /><br />" . mysqli_error($db_master));

        //you can count the results if you want
        $row_count = mysqli_num_rows($result);

        echo "row count" . $row_count;

        //iterator


        echo "<table id=''>";
        while ($row = mysqli_fetch_row($result)) {
                echo "<tr>";
                for ($i = 0; $i < sizeof($row); $i++) {
                        echo "<td>";
                        echo $row[$i];
                        echo "</td>";
                }

                echo "</tr>";
        }
        echo "</table>";
    
        echo "
            <form action=\"" . $_SERVER['PHP_SELF'] . "\" method=GET>
                <input type='submit' name='action' value='logout' />
            </form>
        "; 
    } //end not logout

}


}
    include('../includes/footer.html');
?>
