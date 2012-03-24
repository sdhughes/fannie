<?php
//ini_set('display_errors',1); 
//error_reporting(E_ALL);

$page_title = "Fannie's Employee Management Module";
$header = "Employee Management Module";
include('../includes/header.html');

require_once('../includes/mysqli_connect.php');
//echo '<script type="text/javascript" src="../includes/javascript/jquery.js"></script>';
echo '<script type="text/javascript" src="../includes/javascript/emp_mgmt.js"></script>';
global $db_master;

    if (isset($_GET['emp_no'])) $DEBUG = $_GET['debug'];



    foreach ($_POST AS $key => $val) {       
        ${$key} = $val;
//        echo $key . " = " . $val . " <br/>";
    }


//get minimum and max record values
    $min_query = 'SELECT MIN(emp_no) AS min FROM fannie.emp_details;';
    $min_result = mysqli_query($db_master, $min_query) or die("Min Query Error ($min_query): " . mysqli_error($db_master));

    $min_row = mysqli_fetch_array($min_result,MYSQLI_ASSOC) or die ("Min Fetch Error:" . mysqli_error());
    $min_emp_no = $min_row['min'];

    $max_query = 'SELECT MAX(emp_no) AS max FROM fannie.emp_details;';
    $max_result = mysqli_query($db_master, $max_query) or die("Max Query Error ($max_query): " . mysqli_error($db_master));

    $max_row = mysqli_fetch_array($max_result,MYSQLI_ASSOC) or die("Max Fetch Error: " . mysqli_error());

    $max_emp_no = $max_row['max'];

//get any passed in record # or default to the last one
if (isset($_GET['emp_no'])) $emp_no = $_GET['emp_no'];
elseif (isset($_POST['emp_no'])) $emp_no = $_POST['emp_no'];
else $emp_no = $max_emp_no;


if ($emp_no != $min_emp_no) {
    $previous_query = "SELECT emp_no FROM fannie.emp_details WHERE emp_no < $emp_no ORDER BY emp_no DESC LIMIT 1 ;";
    $previous_result = mysqli_query($db_master, $previous_query) or die("Previous Query Error ($previous_query): " . mysqli_error($db_master));

    $previous_row = mysqli_fetch_array($previous_result,MYSQLI_ASSOC) or die("Previous Fetch Error: " . mysqli_error());

    $previous_emp_no = $previous_row['emp_no'];
} else {

    $previous_emp_no = $min_emp_no;
}

if ($emp_no != $max_emp_no) {
    $next_query = "SELECT emp_no FROM fannie.emp_details WHERE emp_no > $emp_no ORDER BY emp_no ASC LIMIT 1 ;";
    $next_result = mysqli_query($db_master, $next_query) or die("Next Query Error ($next_query): " . mysqli_error($db_master));

    $next_row = mysqli_fetch_array($next_result,MYSQLI_ASSOC) or die("Next Fetch Error" . mysqli_error());

    $next_emp_no = $next_row['emp_no'];
} else {
    $next_emp_no = $max_emp_no;
}

if ($DEBUG) {
echo "<br/ >";
echo $min_emp_no;
echo " <br />";
echo $previous_emp_no;
echo " <br />";
echo $next_emp_no;
echo " <br />";
echo $max_emp_no;
echo "<br/ >";
}
//create navigation form 
echo "<div id='emp_mgmt_nav' class='thinborder'>
<a href='" . $_SERVER['PHP_SELF'] . "?emp_no=$min_emp_no' ><button>first</button></a>
<a href='" . $_SERVER['PHP_SELF'] . "?emp_no=$previous_emp_no' ><button>previous</button></a>
<span class='thinborder'><input type='text' name='curr_emp_no' value='$emp_no' id='curr_emp_no' width='5em' /><button id='change_curr_record'>jump!</button></span>
<a href='" . $_SERVER['PHP_SELF'] . "?emp_no=$next_emp_no' ><button>next</button></a>
<a href='" . $_SERVER['PHP_SELF'] . "?emp_no=$max_emp_no' ><button>last</button></a>
</div>";



if ($_POST['submit'] == 'save') {

    //update the fields here 
    if ($emp_benefits=='on') $bene = 1;
    else $bene = 0;
    //echo $bene;


    $update_details_query = "UPDATE fannie.emp_details SET " 
        . "FirstName='" . $FirstName[$emp_no] . "'," 
        . "RealFirstName='" . $RealFirstName[$emp_no] . "'," 
        . "LastName='" . $LastName[$emp_no] . "'," 
        . "PhoneNumber='" . $emp_phonenum . "'," 
        . "Address='" . $emp_address . "'," 
        . "Benefits=" . $bene . "," 
        . "SSN='$emp_ssn'," 
        . "job=" . $JobTitle[$emp_no] . " " . 
        "WHERE emp_no = $emp_no";

    //if ($DEBUG)
         echo "<p>" . $update_details_query . "</p>";

   $update_result = mysqli_query($db_master,$update_details_query) or die("Update Query Error" . mysqli_error($db_master));

   $update_masterQ = "UPDATE is4c_op.employees SET "
        . "FirstName='" . $FirstName[$emp_no] . "'," 
        . "LastName='" . $LastName[$emp_no] . "'," 
        . "RealFirstName='" . $RealFirstName[$emp_no] . "'" 
            . "WHERE emp_no = $emp_no";
   
   //if ($DEBUG) 
   echo "<p>" . $update_masterQ . "</p>";
   
   $update_master_result = mysqli_query($db_master,$update_masterQ) or die("Update Master DB Query Error" . mysqli_error($db_master));

    if (!empty($update_result)) echo "<p>Record Updated Successfully. </p>";
    else echo "<p>Error Updating. </p>";

}

//display the fields here
if ($DEBUG) echo "<p>emp_no = " . $emp_no . "</p>";
$query = "SELECT 
            d.emp_no as emp_no,
            d.FirstName as first, 
            d.LastName as last, 
            d.RealFirstName as realfirst, 
            d.Address as address,
            d.PhoneNumber as phone,
            d.SSN as ssn,
            d.benefits as benefits,
            b.Insurance_ID as insurance_no,
            a.active as active,
            p.CashierPassword as pass,
            p.AdminPassword as admin_pass,
            CASE j.job_title WHEN 0 THEN 1 ELSE j.job_title END as job,
            ac.card_no as card_no,
            ec.wage as wage,
            ec.budgeted_hours as hours,
           ec.direct_deposit as direct_deposit
        FROM fannie.emp_details as d, fannie.emp_benefits as b, fannie.emp_active as a, fannie.emp_passwords as p, fannie.jobs as j, fannie.emp_acct as ac, fannie.emp_compensation as ec
        WHERE d.emp_no = $emp_no
        AND d.emp_no = b.emp_no
        AND d.emp_no = a.emp_no
        AND d.emp_no = p.emp_no
        AND d.job = j.job_id
        AND d.emp_no = ac.emp_no
        AND d.emp_no = ec.emp_no;";
$result = mysqli_query($db_master, $query) or die("Error: ". mysqli_error($db_master));
$row = mysqli_fetch_array($result,MYSQLI_ASSOC);


if ($DEBUG) echo $query;
if ($DEBUG) print_r($row);

if ($result) {
        
        $id = $row['emp_no'];

    echo "<div class='column'>";
    echo '<form action="'.$_SERVER['PHP_SELF'] . '?emp_no=' . $emp_no . '" method="POST">';
    echo '<input type="hidden" name="debug" value="' . (($_POST['debug']==1)?1:0). '" />';
    echo "<table class='emp_details'>";
    echo "<tr><td>Employee No.</td><td id='emp_no_label'>".$row['emp_no']."</td></tr>";
    echo "</tr><tr>";
    echo '<td>Last Name</td><td><input type="text" name="LastName[' . $id . ']" maxlength="20" size="10" value="' . $row['last'] . '"></td>';
    echo "</tr><tr>";
    echo '<td>First Name</td><td><input type="text" name="FirstName[' . $id . ']" maxlength="20" size="10" value="' . $row['first'] . '"></td>';
    echo "</tr><tr>";
    echo '<td>Real First Name</td><td><input type="text" name="RealFirstName[' . $id . ']" maxlength="20" size="10" value="' . $row['realfirst'] . '"></td>';
    echo "</tr><tr>";
    echo "<td>Job Title</td>
    <td>
        <select name='JobTitle[$id]'>
            <option value='1'";
        if ($row['job'] == 'STAFF') echo ' SELECTED';
        echo ">Staff</option>
            <option value='2'";
        if ($row['job'] == 'SUB') echo ' SELECTED';
        echo ">Sub</option>
            <option value='3'";
        if ($row['job'] == 'WORKING MEMBER') echo ' SELECTED';
        echo ">Working Member</option>
            <option value='4'";
        if ($row['job'] == 'BOARD DIRECTOR') echo ' SELECTED';
        echo ">Board Director</option>
            <option value='0'";
        if ($row['job'] == 'CUSTOMER') echo ' SELECTED';
        echo ">Customer</option>
            </select>
            </td>";
    echo "</tr><tr>";
        echo '<td>Member No.</td><td><input type="text" name="CardNo[' . $id . ']" maxlength="5" size="5" value="' . $row['card_no'] . '" /></td>';
    echo "</tr><tr>";
        echo "<td class='wage_element'>Wage</td><td class=\"wage_element\"><input type=\"text\" name=\"Wage['$id']\" maxlength=\"6\" size=\"6\" value=\"" . number_format($row['wage'], 2) . '" /></td>';

    echo "</tr><tr>";
    echo "<td>Budgeted Hours</td><td><input type='text' name=\"Budgeted_Hours['$id']\" maxlength='6 size='6' value=\"" . number_format($row['hours'], 2) . '" /></td>';

    echo "</tr><tr>";
        echo "<td>Active?</td><td><input type='checkbox' name='EmpActive[" . $id . "]'";
        if ($row['active'] == 1) echo ' checked="checked" ';
        echo "/></td>";
    //	echo "<td><input type=hidden name='id[]' value=".$row[0].">&nbsp;</td>";
    echo "</tr>
    ";

    echo "
    <tr>
    <td>Address:</td>
    <td>
    <input type='text'  name='emp_address' value='" . trim($row['address'],' ') . "' />
    </td>
    </tr>
    ";
    echo "
    <tr>
    <td>Phone Number:</td>
    <td>
    <input type='text'  name='emp_phonenum' value='" . trim($row['phone'],' ') . "' />
    </td>
    </tr>
    ";
    echo "
    <tr>
    <td>SSN:</td>
    <td>
    <input type='text'  name='emp_ssn' value='" . $row['ssn'] . "' />
    </td>
    </tr>
    ";
    echo "
    <tr>
    <td>Benefits:</td>
    <td>
    <input type='checkbox'  name='emp_benefits'";
    if ($row['benefits']==1) echo " checked='checked' ";
    echo " />";
    echo "</td>
    </tr>";
    echo "
    <tr>
    <td>Passwords:</td>
    <td>
    <label name='emp_password' >" . $row['pass'] . "," . $row['admin_pass'] . "</label>
    </td>
    </tr>
    ";


    echo "</table>";
    echo "<input type='submit' name='submit' value='save' />";
    echo "</form>";

    echo "</div><div class='column'>";
    echo "<button type='submit' id='show_schedule' >show scheduled shifts</button>";
    echo "<div id='schedule_pane'></div></div>";


    /*	
    echo "<form><table>";    
	echo '<td><input type="text" name="LastName[' . $max . ']" maxlength="20" size="10"></td>';
	echo '<td><input type="text" name="FirstName[' . $max . ']" maxlength="20" size="10"></td>';
	echo "<td><select name='JobTitle[$max]'>
		<option value='STAFF'>Staff</option>
		<option value='SUB'>Sub</option>
		<option value='WORKING MEMBER'>Working Member</option>
		</select></td>";
	echo '<td><input type="text" name="CardNo[' . $max . ']" maxlength="5" size="5" /></td>';
	echo '<td class="wage_element"><input type="text" name="Wage[' . $max . ']" maxlength="6" size="6" /></td>';
	echo '<td><input type="text" name="Budgeted_Hours[' . $max . ']" maxlength="6" size="6" /></td>';
	echo "<td><input type='checkbox' name='EmpActive[" . $max . "]' /></td>";
	echo "<td><input type='hidden' name='add' value='" . $max . "'>&nbsp;</td></tr>\n";

echo "<tr><td><input type=submit name=submit value=submit></td></tr>";
echo "</table></form>";
*/
} else {
    
    echo "error";
    
}

include('../includes/footer.html');
mysqli_close($db_master);
?>
