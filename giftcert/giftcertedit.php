<?php # - Gift Certificate Module
$page_title = 'Fannie - Admin Module';
$header = 'Gift Certificate Editor';
include('../includes/header.html');
include('./includes/header.html');

require_once ('../includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_log');

if (isset($_GET['num'])) {
    $num = $_GET['num'];
} elseif (isset($_POST['num'])) {
    $num = $_POST['num'];
} else {
    echo '<p class="error">This page has been accessed in error.</p>';
    include('./includes/footer.html');
    include ('../includes/footer.html');
    exit();
}

if (isset($_POST['submitted'])) {

    $errors = array();
    
    if (!isset($_POST['user']) || strlen($_POST['user']) < 3) {
        $errors[] = 'The recipient must be longer than 2 characters.';
    } else {
        $user = escape_data ($_POST['user']);
    }
    
    if (!isset($_POST['buyer']) || strlen($_POST['buyer']) < 3) {
        $errors[] = 'The purchaser must be longer than 2 characters.';
    } else {
        $buyer = escape_data ($_POST['buyer']);
    }
    
    if (!isset($_POST['value']) || $_POST['value'] > 300 || strpos($_POST['value'], '$')) {
        $errors[] = 'The value of the gift certificate cannot exceed $300.';
    } else {
        $value = escape_data ($_POST['value']);
    }
    
    $date1pieces = explode('-', $_POST['date1']);
    $date2pieces = explode('-', $_POST['date2']);
    
    if (!isset($_POST['date1']) || (!checkdate($date1pieces[1], $date1pieces[2], $date1pieces[0]))) {
        $errors[] = 'The issue date was invalid.';
    } else {
        $date1 = escape_data ($_POST['date1']);
    }
    
    if (empty($_POST['date2'])) {
        $date2 = $date1pieces[0]+1 . '-' . $date1pieces[1] . '-' . $date1pieces[2];
    } elseif (is_numeric($date2pieces[0]) && is_numeric($date2pieces[1]) && is_numeric($date2pieces[2])) {
        if (!checkdate($date2pieces[1], $date2pieces[2], $date2pieces[0])) {
            $errors[] = 'The expiration date was invalid.';
        } else {
            $date2 = escape_data ($_POST['date2']);
        }
    } else {
        $errors[] = 'The expiration date was invalid.';
    }
    
    if (empty($errors)) {
        $query = "UPDATE giftcertdetail SET buyer='$buyer', user='$user', value=$value, issued='$date1', expire='$date2' WHERE GiftCertNum=$num";
        $result = mysqli_query($db_master, $query);
        if ($result) { // success.
            echo 'Certificate Number ' . $GiftCertNum . ' successfully edited.';
            $_POST = array();
        } else { // Failure.
            echo '<p>Error. The gift certificate could not be edited. </p>
            <p>Please try again and ensure that all fields are correctly filled in.</p>';
        }       
        
    } else {
        echo '<p>The following errors occurred: </p>';
        foreach ($errors as $msg) {
            echo '<p>- ' . $msg . '</p>';
        }
    }
    
            
}

$query = "SELECT * FROM giftcertdetail WHERE GiftCertNum=$num";
$result = mysqli_query($db_master, $query);

if (mysqli_num_rows($result) != 1) {
    echo '<p class="error">This page has been accessed in error.</p>';
    include('./includes/footer.html');
    include ('../includes/footer.html');
    exit();
}

$row = mysqli_fetch_row($result);

// Always show the form.
echo '<link href="../style.css" rel="stylesheet" type="text/css">
  <script src="../src/CalendarControl.js" language="javascript"></script>
  <form name="details" action="giftcertedit.php" method="post">
  <p><b>Purchased By: </b><input type="text" size="30" maxlength="50" name="buyer" value="' . $row[1] . '"></p>
  <p><b>Purchased For: </b><input type="text" size="30" maxlength="50" name="user" value="' . $row[2] . '"></p>
  <p><b>Purchase Amount: </b>$<input type="text" size="7" maxlength="7" name="value" value="' . $row[3] . '"></p>
  <p><b>Gift Certificate Number: ' . $row[0] . '</b><input type="hidden" name="num" value="' . $row[0] . '"></p>
  <p><b>Issue Date: </b><input type="text" size="10" name="date1" value="' . $row[4] . '"onfocus="showCalendarControl(this);">&nbsp;&nbsp;* (Issue date is not optional.)</p>
  <p><b>Expiration Date: </b><input type="text" size="10" name="date2" value="' . $row[5] . '"onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>    
  <p>(If blank, expiration will default to a year after issue date.)</p>
  <input type="hidden" name="submitted" value="TRUE">
  <button type="submit" name="submit">Edit Certificate</button>
  </form>';

include('./includes/footer.html');
include('../includes/footer.html');
?>