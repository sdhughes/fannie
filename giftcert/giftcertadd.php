<?php # - Gift Certificate Module
$page_title = 'Fannie - Admin Module';
$header = 'Gift Certificate Manager';
include('../includes/header.html');
include('./includes/header.html');

require_once ('../includes/mysqli_connect.php');

if (isset($_POST['submitted'])) {
    mysqli_select_db($db_master, 'is4c_log');
    $errors = array();
    
    if (!isset($_POST['giftcertnum']) || !is_numeric($_POST['giftcertnum']) || $_POST['giftcertnum'] < 100) {
        $errors[] = 'The gift certificate number must be a number greater than 100.';
    } else {
        $GiftCertNum = $_POST['giftcertnum'];
        $query = "SELECT * FROM giftcertdetail WHERE GiftCertNum = $GiftCertNum";
        $result = mysqli_query($db_master, $query);
        if (mysqli_num_rows($result) != 0) {
            $errors[] = 'That gift certificate number is already in use.';
        }
    }
    
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
    
    if (!isset($_POST['value']) || $_POST['value'] > 9999 || strpos($_POST['value'], '$')) {
        $errors[] = 'The value of the gift certificate cannot exceed $9999.';
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
        $query = "INSERT INTO giftcertdetail VALUES ($GiftCertNum, '$buyer', '$user', $value, '$date1', '$date2')";
        $result = mysqli_query($db_master, $query);
        if ($result) { // success.
            echo 'Certificate Number ' . $GiftCertNum . ' successfully entered.';
            $_POST = array();
        } else { // Failure.
            echo '<p>Error. The gift certificate could not be added. </p>
            <p>Please try again and ensure that all fields are correctly filled in.</p>';
        }       
        
    } else {
        echo '<p>The following errors occurred: </p>';
        foreach ($errors as $msg) {
            echo '<p>- ' . $msg . '</p>';
        }
    }
    
            
}



// Always show the form.
echo '<link href="../style.css" rel="stylesheet" type="text/css">
  <script src="../src/CalendarControl.js" language="javascript"></script>
  <form name="details" action="giftcertadd.php" method="post">
  <p><b>Purchased By: </b><input type="text" size="30" maxlength="50" name="buyer"';
  
  if (isset($_POST['buyer'])) echo ' value="' . $_POST['buyer'] . '"';
  
  echo '></p>
  <p><b>Purchased For: </b><input type="text" size="30" maxlength="50" name="user"';
  
  if (isset($_POST['user'])) echo ' value="' . $_POST['user'] . '"';
  
  echo '></p>
  
  <p><b>Purchase Amount: </b>$<input type="text" size="7" maxlength="7" name="value"';
  
  if (isset($_POST['value'])) echo ' value="' . $_POST['value'] . '"';
  
  echo '></p>
  
  <p><b>Gift Certificate Number: </b><input type="text" size="5" maxlength="5" name="giftcertnum"';
  
  if (isset($_POST['giftcertnum'])) echo ' value="' . $_POST['giftcertnum'] . '"';
  
  echo '></p>
  
  <p><b>Issue Date: </b><input type=text size=10 name=date1 ';
  
  if (isset($_POST['date1'])) echo ' value="' . $_POST['date1'] . '" ';
  
  echo 'onfocus="showCalendarControl(this);">&nbsp;&nbsp;* (Issue date is not optional.)</p>
  
  <p><b>Expiration Date: </b><input type=text size=10 name=date2 ';
  
  if (isset($_POST['date2'])) echo ' value="' . $_POST['date2'] . '" ';
  
  echo 'onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
  
  <p>(If blank, expiration will default to a year after issue date.)</p>
  <input type="hidden" name="submitted" value="TRUE">
  <button type="submit" name="submit">Add Certificate</button>
  </form>';
  
include('./includes/footer.html');
include('../includes/footer.html');
?>
