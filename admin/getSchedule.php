<?php 

if (isset($_POST['emp_no'])) $emp_no = $_POST['emp_no'];
elseif (isset($_GET['emp_no'])) $emp_no = $_GET['emp_no'];
 
    require_once('../includes/mysqli_connect.php');
    global $db_master;

    $output = '';

//echo "emp_no = $emp_no";

    $query = "SELECT 
      s.emp_no, 
      s.day, 
      es.shift_name,
      es.area,
      substr(es.start,12,15), 
      substr(es.stop,12,15)
    FROM 
      fannie.schedule as s 
      INNER JOIN fannie.emp_shifts as es
    WHERE s.emp_no = $emp_no
    AND s.shift_id = es.shift_id";

//echo $query . "<br />";
        
    $result = mysqli_query($db_master, $query) or die(mysqli_error($db_master));

    $output .= "<table class='emp_details'>";

    while ($row = mysqli_fetch_row($result)) {

        $output .= "<tr>";
        $output .= "<td>" .  $row[1] . "</td>";
        $output .= "<td>" .  $row[2] . "</td>";
        $output .= "<td>" .  $row[3] . "</td>";
        $output .= "<td>" .  $row[4] . "</td>";
        $output .= "<td>" .  $row[5] . "</td>";
        $output .= "</tr>";

    }

    $output .= "</table>";

    $var = mysqli_num_rows($result);

    if ($var == 0) $output = "<p>No shifts scheduled.</p>";
//echo $var . "<br />";
    echo $output;
?>
