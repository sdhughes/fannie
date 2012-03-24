<?php
ini_set('display_errors', 'on');
ini_set('error_reporting', E_ALL);

require_once('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_log');

if (isset($_POST['submitted'])) {
    
    foreach ($_POST['amount'] AS $dept => $amt) {
        // $dept is $deptID for budgetDetails
        
        foreach ($amt AS $date => $budget) {
            
            $checkQ = "SELECT id FROM is4c_op.budgetDetails WHERE period='$date' AND deptID=$dept";
            $checkR = mysqli_query($db_master, $checkQ);
            
            if (mysqli_num_rows($checkR) == 1) {
                list($id) = mysqli_fetch_row($checkR);
                
                $updateQ = sprintf("UPDATE is4c_op.budgetDetails SET amount=%s WHERE id=%u AND period='%s'", (is_numeric($budget) ? $budget : 0.00), $id, $date);
                $updateR = mysqli_query($db_master, $updateQ);
                
                if (!$updateR) printf('Query: %s, Error: %s', $updateQ, mysqli_error($db_master));
            } else {
                $insertQ = sprintf("INSERT INTO is4c_op.budgetDetails VALUES (NULL, %s, '%s', %s)", $dept, $date, (is_numeric($budget) ? $budget : 0.00));
                $insertR = mysqli_query($db_master, $insertQ);
                
                if (!$insertR || mysqli_affected_rows($db_master) != 1) printf('Query: %s, Error: %s', $insertQ, mysqli_error($db_master));
            }
        }
        
    }
}


echo '<body><html>';
    
printf('<form action="%s" method="POST"><table border="1"><tr><th>Department</th>', $_SERVER['PHP_SELF']);

$storeTotal = array();

for ($i=1; $i < 13; $i++) {
    printf('<th>%s</th>', date('F', strtotime("2010-$i-01")));
    $storeTotal[$i] = 0;
}

printf('<th>Total</th></tr>');

$deptQ = "SELECT dept_name, id FROM is4c_op.budgetNames ORDER BY id ASC";
$deptR = mysqli_query($db_master, $deptQ);

while (list ($dept, $id) = mysqli_fetch_row($deptR)) {
    printf('<tr><td>%s</td>', $dept);
    
    $total = 0;
    
    for ($i=1; $i < 13; $i++) {
        $date = "2010-$i-01";
        $detailQ = "SELECT amount, id FROM is4c_op.budgetDetails WHERE deptID = $id AND period='$date'";
        $detailR = mysqli_query($db_master, $detailQ);
        if (!$detailR) printf('Query: %s, Error: %s', $detailQ, mysqli_error($db_master));
        list($amount, $cellID) = mysqli_fetch_row($detailR);
        printf('<td><input type="text" name="amount[%s][%s]" value="%s" /></td>' . "\n", $id, $date, $amount);
        $total += $amount;
        $storeTotal[$i] += $amount;
    }
    
    printf('<td>%s</td></tr>', $total);
}

$endTotal = 0;
echo '<tr><td>Store Total</td>';
for ($i=1; $i < 13; $i++) {
    $date = "2010-$i-01";
    printf("<td>%s</td>", $storeTotal[$i]);
    $endTotal += $storeTotal[$i];
}

printf('<td>%s</td></tr>', $endTotal);


echo '</table><input type="hidden" name="submitted" value="TRUE" /><button name="submit">Submit</button></form>';



?>
