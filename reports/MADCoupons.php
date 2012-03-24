<?php
    
    $page_title = "Fannie - Owner Service Module"; //top of window
    $header = "MAD Coupon Admin"; //top of center DIV
    include('../includes/header.html');

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

            var couponOks = $('.couponOk');
            var couponNotOks = $('.couponNotOk');

            $('#enableAll').bind('click',function(event) {

                event.stopPropagation();
                event.preventDefault();

                couponOks.attr('checked','checked');
                couponNotOks.removeAttr('checked');
             });
            $('#disableAll').bind('click',function(event) {
                event.stopPropagation();
                event.preventDefault();

                couponNotOks.attr('checked','checked');
                couponOks.removeAttr('checked');
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


    $arraySize = sizeOf($_POST['CardNo']);
    $cardNoArray = $_POST['CardNo'];

    mysqli_select_db($db_master, "is4c_op") or die("Select DB Error: " . mysqli_error($db_master));

    for ($i = 0 ; $i < $arraySize ; $i++ ) {

        $arrayName = 'couponok' . $cardNoArray[$i];
        $couponokArray = $_POST[$arrayName];

        $updateQ = "UPDATE custdata SET CouponOK = " . $_POST[$arrayName]  . " WHERE cardNo = " . $cardNoArray[$i];

//        echo $updateQ . "<br />";

        mysqli_query($db_master, $updateQ) or die("Query Error:  $updateQ <br />" . mysqli_error($db_master));
    }

    //echo $arraySize . "<br />";


} else { //what if it's not submitted?

}


    mysqli_select_db($db_master, 'is4c_op') or die("Select DB error: " . mysqli_error($db_master));

    //Get all the data about the custs and their coups       
    //$CouponQ = "SELECT CardNo, FirstName, LastName, CASE CouponOK WHEN 1 THEN 'Available' WHEN 0 THEN 'Used' END as 'CouponOK' FROM custdata WHERE PersonNum = 1 AND memtype IN (1,2)";
    $CouponQ = "SELECT CardNo, FirstName, LastName, CouponOK FROM custdata WHERE PersonNum = 1 AND memtype IN (1,2)";
    $CountQ = "SELECT count(CouponOK) FROM custdata WHERE CouponOK = 1 AND PersonNum = 1 AND memtype IN (1,2)";

    //get the results of the query or spit an error 
    $CouponR = mysqli_query($db_master, $CouponQ) or die ("Error from query: <br /> $CouponQ : <br /><br />" . mysqli_error($db_master));
    $CountR = mysqli_query($db_master, $CountQ) or die ("Error from query: <br /> $CountQ : <br /><br />" . mysqli_error($db_master));

    //you can count the results if you want
    $coupon_count = mysqli_fetch_row($CountR);
    $cust_count = mysqli_num_rows($CouponR);
    $used_count = $cust_count - $coupon_count[0];
    
    echo "<table id='MAD_Coupon_totals' class='thinborder'>
    <tr><td>Used Coupons</td><td>Available Coupons</td><td>Total Active Members</td></tr><tr><td> $used_count </td><td>" . $coupon_count[0] . "</td><td>$cust_count </td></tr>";
    echo "<tr><td colspan=3>Percentage of total: " . sprintf("%.2f",(($coupon_count[0] / $cust_count )*100)) . "%</td></tr></table>";
    echo "<br />";
    echo "<form action='" . $_SERVER['PHP_SELF'] . "' method='post'>"; 
    echo "
    Turn on/off MAD Coupons individually or change en masse: <button id='enableAll' value='all'>Enable All</button>
    <button id='disableAll' value='none'>Disable All</button><br />
    Then click 'submit' to save your changes, or reset to start over: <input type='submit' name='submit' value='submit'>
    <input  type='reset' name='reset' value='reset'>
    <br />(don't forget to synchronize 'members'!)
    <table id='MAD_owner_coupons' class='thinborder'>";
    echo "<td>Card No</td><td>First Name</td><td>Last Name</td><td>MAD Coupon</td>";
    while ($row = mysqli_fetch_row($CouponR)) {

        $cardNo = $row[0];
        $couponOk = $row[3];

        echo "<tr>";
        echo "<td>";
        echo "<input type='hidden' name='CardNo[]' value='" . $cardNo . "' >";
        echo $cardNo;
        echo "</td>";
        echo "<td>";
        echo $row[1];
        echo "</td>";
        echo "<td>";
        echo $row[2];
        echo "</td>";
        echo "<td>";
        echo (($couponOk==1)?'<span class="green">':'') . "<input class='couponOk' type='radio' id='couponok$cardNo' name='couponok$cardNo' value=1 " . (($couponOk == 1)?" checked='checked' ><label for='couponok$cardNo'>unused</span> ":"><label for='couponok$cardNo'>unused</label>");
        echo (($couponOk==1)?'':'<span class="red">') . "<input class='couponNotOk' type='radio' id='couponNotOk$cardNo' name='couponok$cardNo' value=0 " . (($couponOk == 1)?"><label for='couponNotOk$cardNo'>used</label>":" checked='checked' ><label for='couponNotOk$cardNo' >used</label></span>");
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
?>
    
</form>


<?php

    include('../includes/footer.html');
?>
