<?php
/***********************
 *
 *  A Tool to compare sales of various items 
 *
 *  Steven Hughes - Alberta Cooperative Grocery
 *  July 2011
 *  
 *  Licensed under the GPL.
 *
 **********************/

    $page_title = "Fannie's Awesome Sales Tool";
    $header = "Sales Tool";
    include('../includes/header.html');
?>
    <link rel="STYLESHEET" type="text/css" href="../includes/style.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/themes/blue/style.css" />
    <link rel="STYLESHEET" type="text/css" href="../includes/javascript/tablesorter/addons/pager/jquery.tablesorter.pager.css" />                                            
<!--    <script language='javascript' type="text/javascript" src="../includes/javascript/jquery.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/myquery.js"></script> -->
    <script language='javascript' type="text/javascript" src="../includes/javascript/salesTool.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/flot/jquery.flot.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/ui.core.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/jquery.tablesorter.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/jquery.tablesorter.pager.js"></script>
    <script language='javascript' type="text/javascript" src="../includes/javascript/jquery.metadata.js"></script>                                                                                 
    <style rel="STYLESHEET" type="text/css">                                            
        tr.alt td {
            background: #ecf6fc !important;
        }
        tr.over td {                                                                    
            background: #bcd4ec !important;                                             
        }                                                                               
    </style>
    <script language='javascript' type="text/javascript">

        Date.format = 'yyyy-mm-dd';

        $(function(){            
                $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, changeMonth:true, changeYear: true, dateFormat: 'yy-mm-dd', duration: 0});
        });

        $(document).ready(function(){                                                   

            $(".tablesorter")
            .tablesorter({widthFixed: true, debug: false, widgets:['zebra']});      
            $(".tablesorter tr").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});                                      
        });                                                                             

        function popup(mylink, windowname) {                                                
            if (! window.focus)return true;                                                 
            var href;                                                                       
            if (typeof(mylink) == 'string')                                                 
                href=mylink;                                                                
            else                                                                            
                href=mylink.href;                                                           
            window.open(href, windowname, 'width=500,height=300,scrollbars=yes,menubar=no,location=no,toolbar=no,dependent=yes');
                return false;
        }
    </script>                                                                           

<?php

    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');
    require_once('../includes/queries.php');

    //DEBUGGGGGGGGGGGGGGGGGGGGGGGGG

        //print_r($_POST);
        //echo "<br />";

    //DEBUGGGGGGGGGGGGGGGGGGGGGGGGG
    
    //if there is a $_POST variable, then convert it to an normal variable name for easier use
    if ($_POST) {

        //create the vars from POST
        foreach ($_POST as $key => $value) {
            ${$key} = $value;
//            if (is_array($value)) print_r($value); 
//            else echo "$key = $value<br />";
        }
    }



    //enter here
    if (isset($_POST['submit'])) {

        //create a string "object" to hold all the data
        $encode_vars = json_encode( array( 'dept' => $dept,
                                           'subdept_array' => $subdept_array,
                                           'vendor' => $vendor,
                                           'description' => $desc,
                                           'date1' => $date1,
                                           'date2' => $date2,
                                           'batchID' => $batch ) );

    //DEBUGGGGGGGGGGGGGGGGGGGGGGGGG
    //	echo $encode_vars;
    //DEBUGGGGGGGGGGGGGGGGGGGGGGGGG

	if (!$date1 || !$date2 ) {

		drawSearchForm("Please select valid Start and End dates!");

	} elseif ($encode_vars) {

		$report = returnItemSalesTable($encode_vars);
		echo $report;	
	} else {

		//Default case
		drawSearchForm("Something went wrong. Try again.");
	}

    } else {
    //if not submitted, just draw the form
        drawSearchForm();
    }

    function drawSearchForm($errorMessage = "") {

        //output the error if there was one
        if ($errorMessage) {
            echo "<p class='red'> $errorMessage</p>";
        }

        //choose to create a list of select one from the batch list
        echo "<div class='salesTool_container'><p>Use this tool to get sales totals by department, subdepartment, vendor and/or description. Or simply select a sales batch on which to report.</p>
        <form action='salesToolPlus.php' method='post'> 
        <div class='top_block'>
                    <label>Start Date:</label><input type='text' name='date1' value='' id='searchDate1' class='datepick' />
                    <label>End Date:</label><input type='text' name='date2' value='' id='searchDate2' class='datepick' />";
//        echo "  </div>";
//        echo "  <div> ";
        echo "      <input type='submit' name='submit' value='submit' >";
        echo "      <input type='reset' name='reset' value='reset' >";
//        echo "  </div>";
        echo "</div>";
        echo "<div id='sales_tool_container'>";
        echo "<div class='sales_column_left'>
        <div class='directions'>Please enter search criteria:
        <table>
            <tr>	
                    <td class='form_label'>Department:</td>";

        echo "      <td>
                		<div id='dept_picker'>";
        //generates the dept picker dynamically from the database
        dept_picker_plus('salesTool_dept_tile');
        echo "		    </div>";
        echo "      </td>
            </tr>
            <tr class='form_element'>
                <td class='form_label'>Vendor:</td>
                <td><input type='text' name='vendor' value='' id='searchVendor' /></td>
            </tr>
            <tr class='form_element'>
                 <td class='form_label'>Desc:</td>
                 <td><input type='text' name='desc' value='' id='searchDesc' /></td>
            </tr>
        </table></div>
        <div>
                <div class='directions'><p >Or choose a batch:<br />";

        //create the batch list select box
        global $db_slave;
        mysqli_select_db($db_slave, 'is4c_op') or die('error: ' . mysqli_error($db_master));

        $query = "SELECT batchName, DATE(startDate), endDate, batchID
                    FROM batches
                    ORDER BY batchID DESC";
        $result = mysqli_query ($db_slave, $query) or die('Error: ' . mysqli_error($db_master));

        echo '      <select name="batch">';
        
        if ($result) {
                while (list($batchName, $start, $end, $ID) = mysqli_fetch_row($result)) {
                    echo "          <option value=\"$ID\">$batchName ($start -> $end)</option>";
                }
        } else {
                echo "<p>Error...</p><p>Query: $query</p>" .  mysqli_error($db_slave);
        }

        echo "      </select>
                </p>";

        echo "</div>
        </div>";

        echo "</div>
        <div class='sales_column_right'><div class='subdept_container'><div><span class='normal_label'>Subdepts</span> (toggle when clicked):</div><div id='subdeptPane'> </div>"; 
        echo "</div></div></div> ";
        echo "</form>";
        echo "</div> ";

    } //end function drawSearchForm

    include('../includes/footer.html');

?>
