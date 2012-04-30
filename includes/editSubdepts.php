   <?php

    foreach ($_POST as $key => $val) ${$key} = $val;

//things we need: sd_id, sd_name, dept
    require_once('../includes/mysqli_connect.php');
    require_once('../includes/common.php');
    global $db_master;



    $query = "SELECT subdept_no,subdept_name,dept_ID FROM subdepts WHERE subdept_no = $subdept_id";


    mysqli_select_db($db_master,'is4c_op') or die("db select error". mysqli_error($db_master));
    $result = mysqli_query($db_master,$query) or die("update subdept query error ($db_master, $query):<br />" . mysqli_error($db_master));

    $row = mysqli_fetch_row($result);    


    echo "<div id='edit_inputs' class='sd_col'>";
          //  echo "<input type='text' id='new_subdept_id' name='new_subdept_id' value='" . $row[0] . "' />";
            echo "<span>" . $row[0] . "</span>";
            echo "<input type='text' id='new_subdept_name'  name='new_subdept_name' value='" . $row[1] . "' />";
     //       dept_selector('new_subdept_dept',$row[2]);
            echo "<input type='submit' id='' name='submit' value='submit' />";
    echo "</div>";

?>
