<?php
   $page_title = 'Fannie - Administration Module';
   $header = 'Admin Section Index';
   include ('../includes/header.html');

    //echo '<script type="text/javascript" language="javascript" src="../includes/javascript/ui/jquery.ui.core.js"></script>';
    //echo '<script type="text/javascript" src="../includes/javascript/ui/jquery.ui.sortable.js"></script>';
   echo '<script type="text/javascript" language="javascript">';
   include ('../includes/javascript/admin.js');
   echo '</script>';

///////////////// not yet //////////////////
function make_link($linkName) {
    
    

 echo '<div id="admin_menu_box">
    <div class="admin_menu_item">
        <a href="' . $linkname . '">Volunteer Hours</a>
        <div>Enter volunteer hours worked</div>
    </div>';



}

function createIndex() {

    $directory = "./";

    $links = new DirectoryIterator($directory);

    foreach ($links as $link) {
        if ((!$link->isDot()) && $link->isFile()) {
            
            $filename = $link->getPath() . DIRECTORY_SEPERATOR . $link->getFilename();
            make_link($filename);
        }
    }

}

//createIndex();
////////////////////////
echo '
<div id="admin_menu_box">
    <div class="admin_menu_item">
        <a href="/admin/volunteers.php">Volunteer Hours</a>
        <div>Enter volunteer hours worked</div>
    </div>

    <div class="admin_menu_item">
        <a href="/admin/subs.php">Substitute Hours</a>
        <div>
        Enter sub hours worked
        </div>
    </div>

    <div class="admin_menu_item">
        <a href="/admin/charges.php">Staff Charges</a>
        <div>
        View staff charge totals
        </div>
    </div>

    <div class="admin_menu_item">
        <a href="/admin/employees.php">Employee Management</a>
        <div>
        View and Edit Employee information
        </div>
    </div>

    <div class="admin_menu_item">
        <a href="/timesheet/payroll.php">Payroll Report</a>
        <div>
        Generate a Payroll Report For a pay period
        </div>
    </div>

    <div class="admin_menu_item">
        <a href="/admin/messages.php">Edit Register Messages</a>
        <div>
        Edit the Messages that Appear at the Registers
        </div>
    </div>
    <div class="admin_menu_item">
        <a href="/admin/OADays.php" >Owner Appreciation Days</a>
        <div>Add and Remove Owner Appreciation Days</div>
    </div>
    <div class="admin_menu_item">
        <a href="/admin/activitylog.php" >Activity Log</a>
        <div>View Register Activities</div>
    </div>
    <div class="admin_menu_item">
        <a href="../timesheet/admin.php" >Timesheet Admin</a>
        <div>Master the Sheet\'s of Time!</div>
    </div>

    <div class="admin_menu_item">
        <a href="/admin/front_page_admin.php">Edit Fannie Front Page</a>
        <div>
            Edit the Messages that Appear on Fannie\'s Front Page
        </div>
    </div>
    <div class="admin_menu_item">
        <a href="http://192.168.1.103/CoMET/">CoMET</a>
        <div>Access Co-op Membership Equity Tracking utility</div>
    </div>
</div>';
       include ('../includes/footer.html');
?>
