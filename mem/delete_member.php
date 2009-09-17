<?php
$page_title='Fannie - Membership Module';
$header='Delete a Member';
include('../includes/header.html');
include('./includes/header.html');

require_once ('../includes/mysqli_connect.php'); // Connect to the DB.
mysqli_select_db($db_master, 'is4c_op');

if (!isset($_POST['submitted'])) {
    if ( !isset($_POST['id']) && !isset($_POST['cardno']) ) {
        echo '<br /><h3>Page error.</h3>
        <p class="error">This page has been accessed in error.</p><p><br /><br /></p>';
        
    } elseif ( isset($_POST['id']) || isset($_POST['cardno']) ) {

        if ( isset($_POST['id']) && is_numeric($_POST['id']) ) {
            echo '<h3>Delete a member.</h3><br />';
            $id = $_POST['id'];
            $query = "SELECT firstname, lastname, cardno FROM custdata WHERE id=$id";
            $result = mysqli_query($db_master, $query);
            $row = mysqli_fetch_row($result);
            echo "<h3><b>$row[1], $row[0]<b> - $row[2]</h3>";
            echo '<form action="delete_member.php" method="post">
                    <input type="radio" value="yes" name="confirm">Yes
                    <input type="radio" value="no" name="confirm" CHECKED>No
                    <p>Are you sure you want to <i>permanently</i> delete this member?</p>
                    <input type="hidden" name="id" value="' . $id . '">
                    <input type="hidden" name="cn" value="' . $row[2] . '">
                    <input type="hidden" name="submitted" value="TRUE">
                    <input type="submit" name="submit" value="submit">
                    </form>';
        } elseif ( isset($_POST['cardno']) && is_numeric($_POST['cardno']) ) {
            echo '<h3>Delete a household.</h3>';
            $cardno = $_POST['cardno'];
            $query = "SELECT firstname, lastname FROM custdata WHERE cardno=$cardno";
            $result = mysqli_query($db_master, $query);
            echo "<p>Household Number: $cardno</p>";
            while ($row = mysqli_fetch_row($result)) {
                echo "<h3><b>$row[1], $row[0]<b></h3>";
            }
            echo '<form action="delete_member.php" method="post">
            <input type="radio" value="yes" name="confirm">Yes
            <input type="radio" value="no" name="confirm" CHECKED>No
            <p>Are you sure you want to <i>permanently</i> delete this household?</p>
            <input type="hidden" name="cardno" value="' . $cardno . '">
            <input type="hidden" name="submitted" value="TRUE">
            <input type="submit" name="submit" value="submit">
            </form>';
        }
    }
    
} elseif (isset($_POST['submitted'])) {
    if ( !isset($_POST['id']) && !isset($_POST['cardno']) ) {
        echo '<br /><h3>Page error.</h3>
        <p class="error">This page has been accessed in error.</p><p><br /><br /></p>';
        
    } elseif ( isset($_POST['id']) || isset($_POST['cardno']) ) {

        if ( isset($_POST['id']) && is_numeric($_POST['id']) ) {
            $id = $_POST['id'];
            $cn = $_POST['cn'];
            if ($_POST['confirm'] == 'no') {
                echo '<h3>The member has <i>not</i> been deleted.<h3><p><br /><br /></p>';
            } elseif ($_POST['confirm'] == 'yes') {

                $query = "DELETE FROM custdata WHERE id = $id";
                $result = mysqli_query($db_master, $query);
                if ($result) {
                    echo '<h3>Member number ' . $cn . ' has been deleted.</h3><p><br /><br /></p>';
                } else {
                    echo '<h3>The member could not be deleted due to a system error.</h3><p><br /><br /></p>';
                }
            }
        } elseif ( isset($_POST['cardno']) && is_numeric($_POST['cardno']) ) {
            $cardno = $_POST['cardno'];
            if ($_POST['confirm'] == 'no') {
                echo '<h3>The household has <i>not</i> been deleted.<h3><p><br /><br /></p>';
            } elseif ($_POST['confirm'] == 'yes') {

                $query = "DELETE FROM custdata WHERE cardno = $cardno";
                $result = mysqli_query($db_master, $query);
                if ($result) {
                    echo '<h3>Household number ' . $cardno . ' has been deleted.</h3><p><br /><br /></p>';
                } else {
                    echo '<h3>The household could not be deleted due to a system error.</h3><p><br /><br /></p>';
                }
            }
        }
    }
}

include('./includes/footer.html');
include('../includes/footer.html');
?>