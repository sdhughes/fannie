<script type="text/javascript">
    function toggleTable(id, obj) {
        if (id == 'all') {
            div = document.getElementById(id);
            tables = div.getElementsByTagName("table");
            for (var b=0; b < tables.length; b++) {
                rows = tables[b].getElementsByTagName("tr");
                header = rows[0].getElementsByTagName("th");
                anchor = header[0].getElementsByTagName("a");
                
                if (obj.innerHTML == 'Expand All') {
                    for (var i=1; i < rows.length; i++) {
                        rows[i].style.display = 'table-row';
                    }
                    anchor[0].innerHTML = '-';
                } else {
                    for (var i=1; i < rows.length; i++) {
                        rows[i].style.display = 'none';
                    }
                    anchor[0].innerHTML = '+';
                }
                
            }
            
            if (obj.innerHTML == 'Expand All') {
                obj.innerHTML = 'Collapse All';
            } else {
                obj.innerHTML = 'Expand All';
            }
            
        } else {
            rows = document.getElementById(id).getElementsByTagName("tr");
    
            header = rows[0].getElementsByTagName("th");
    
            anchor = header[0].getElementsByTagName("a");
    
            
            if (anchor[0].innerHTML == '-') {
                for (var i=1; i < rows.length; i++) {
                    rows[i].style.display = 'none';
                }
                anchor[0].innerHTML = '+';
                
            } else if (anchor[0].innerHTML == '+') {
                for (var i=1; i < rows.length; i++) {
                    rows[i].style.display = 'table-row';
                }
                
                anchor[0].innerHTML = '-';
            }
        }
    }
</script>
<?php # admin subpage to view and edit timesheets.

require_once('/pos/fannie/includes/mysqli_connect.php');
mysqli_select_db($db_master, 'is4c_log');
mysqli_select_db($db_slave, 'is4c_log');


if ($_GET['function'] == 'edit' && isset($_GET['submitted']) && isset($_GET['emp_no']) && isset($_GET['periodID'])) {
    if (isset($_GET['id'])) {
        foreach ($_GET['id'] AS $key => $id) {
        
            $area = (int) $_GET['area'][$id];
            $date = $_GET['date'][$id];
            $timein = parseTime($_GET['time_in'][$id], $_GET['inmeridian'][$id]);
            $timeout = parseTime($_GET['time_out'][$id], $_GET['outmeridian'][$id]);
            
            if ($area != 0) {
                $query = "UPDATE is4c_log.timesheet
                    SET time_in='$date $timein', time_out='$date $timeout', area=$area
                    WHERE ID=$id";
            } else {
                $query = "UPDATE is4c_log.timesheet
                    SET time_in='2008-01-01 $timein'
                    WHERE ID=$id";
            }
            
            $result = mysqli_query($db_master, $query);
            
            if (!$result) {
                echo "<p>Query: $query</p>";
                echo "<p>MySQL Error: " . mysqli_error($db_master) . "</p>";
            }
                
        }
    }
}

if (isset($_GET['function']) && isset($_GET['emp_no']) && isset($_GET['periodID'])) {
    $emp_no = (int) $_GET['emp_no'];
    $periodID = (int) $_GET['periodID'];
    
    if ($emp_no == 0 || $periodID == 0) header("Location: {$_SERVER['PHP_SELF']}");
    
    if ($emp_no > 0) {
        $mainQ = "SELECT date, DATE_FORMAT(date, '%M %D'), ROUND(SUM(TIMESTAMPDIFF(MINUTE, time_in, time_out))/60, 2)
            FROM is4c_log.timesheet
            WHERE emp_no = $emp_no
                AND periodID = $periodID
            GROUP BY date";
        $mainR = mysqli_query($db_master, $mainQ);
        
        $nameQ = "SELECT firstname FROM is4c_op.employees WHERE emp_no=$emp_no";
        $nameR = mysqli_query($db_master, $nameQ);
        list($name) = mysqli_fetch_row($nameR);
        
        $periodQ = "SELECT date_format(periodStart, '%M %D, %Y'), date_format(periodEnd, '%M %D, %Y')
            FROM is4c_log.payperiods WHERE periodID=$periodID";
        $periodR = mysqli_query($db_master, $periodQ);
        $period = mysqli_fetch_row($periodR);
        
        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="get"><fieldset>
            <legend>Timesheet For ' . $name . ' from ' . $period[0] . ' to ' . $period[1] . '</legend>
            <input type="hidden" name="function" value="edit" /><a href="#" id="mainAnchor" onclick="toggleTable(\'all\', this)">Expand All</a><div id="all">';
        while ($mainRow = mysqli_fetch_row($mainR)) {
            echo '<table cellpadding="3" cellspacing="3" id="' . $mainRow[0] . '">
                <tr class="header">
                    <th align="left"><a href="#" onclick="toggleTable(\'' . $mainRow[0] . '\', this)">+</a></th>
                    <th align="left">' . $mainRow[1] . '</th>
                    <th><a href="' . $_SERVER['PHP_SELF'] . '?function=delete&date=' . $mainRow[0] . '&emp_no=' . $emp_no . '&periodID=' . $periodID . '">Delete</a></th>
                    <th align="right" colspan="2">' . $mainRow[2] . ' Hours</th>
                </tr>';
            $query = "SELECT CASE area WHEN 0 THEN TIME_FORMAT(time_in, '%H:%i') ELSE TIME_FORMAT(time_in, '%r') END,
                    CASE area WHEN 0 THEN time_out ELSE TIME_FORMAT(time_out, '%r') END,
                    area,
                    ID
                FROM is4c_log.timesheet
                WHERE emp_no = $emp_no
                AND area <> 13
                AND periodID = $periodID
                AND date = '$mainRow[0]'";
            $result = mysqli_query($db_master, $query);
            if (!$result) echo "<p>Error!</p><p>Query: $query</p><p>" . mysql_error() . "</p>";
            while ($row = mysqli_fetch_row($result)) {
                if ($row[2] == 0) {
                    $lunch = $row[0];
                    $lunchID = $row[3];
                    echo '<tr class="details" style="display:none">
                            <td>&nbsp;</td>
                            <td colspan="2" align="right">Lunch</td>
                            <td colspan="2" align="left">
                                <input type="hidden" name="date[' . $row[3] . ']" value="' . $mainRow[0] . '" />
                                <input type="hidden" name="area[' . $lunchID . ']" value="0" /><input type="hidden" name="id[' . $lunchID . ']" value="' . $lunchID . '" />
                        <select name="time_in[' . $lunchID . ']">
                            <option value="00:00:00"';
                        if ($lunch == '00:00') echo ' SELECTED';
                        echo '>None</option>
                                        <option value="00:15:00"';
                        if ($lunch == '00:15') echo ' SELECTED';
                        echo '>15 Minutes</option>
                                        <option value="00:30:00"';
                        if ($lunch == '00:30') echo ' SELECTED';
                        echo '>30 Minutes</option>
                                        <option value="00:45:00"';
                        if ($lunch == '00:45') echo ' SELECTED';
                        echo '>45 Minutes</option>
                                        <option value="01:00:00"';
                        if ($lunch == '01:00') echo ' SELECTED';
                        echo '>1 Hour</option>
                                        <option value="01:15:00"';
                        if ($lunch == '01:15') echo ' SELECTED';
                        echo '>1 Hour, 15 Minutes</option>
                                        <option value="01:30:00"';
                        if ($lunch == '01:30') echo ' SELECTED';
                        echo '>1 Hour, 30 Minutes</option>
                                        <option value="01:45:00"';
                        if ($lunch == '01:45') echo ' SELECTED';
                        echo '>1 Hour, 45 Minutes</option>
                                        <option value="02:00:00"';
                        if ($lunch == '02:00') echo ' SELECTED';
                        echo '>2 Hours</option>
                                </select></td></tr>';
                } else {
                    $in = substr($row[0], 9, 2);
                    $out = substr($row[1], 9, 2);
                    
                    $shiftQ = "SELECT * FROM is4c_log.shifts WHERE ShiftID NOT IN (0,13) ORDER BY ShiftID ASC";
                    $shiftR = mysqli_query($db_master, $shiftQ);
                    
                    echo '<tr class="details" style="display:none">
                            <td>
                                <input type="hidden" name="id[' . $row[3] . ']" value="' . $row[3] . '" />
                                <input type="hidden" name="date[' . $row[3] . ']" value="' . $mainRow[0] . '" /></td>
                            <td><input type="text" name="time_in[' . $row[3] . ']" size="5" maxlength="5" value="' . substr($row[0], 0, 5) . '" />
                                <select name="inmeridian[' . $row[3] . ']"><option value="AM"';
                    if ($in == 'AM') echo ' SELECTED';
                    echo '>AM</option><option value="PM"';
                    if ($in == 'PM') echo 'SELECTED';
                    echo '>PM</option></select>
                            </td>
                            <td><input type="text" name="time_out[' . $row[3] . ']" size="5" maxlength="5" value="' . substr($row[1], 0, 5) . '" />
                                <select name="outmeridian[' . $row[3] . ']"><option value="AM"';
                    if ($out == 'AM') echo ' SELECTED';
                    echo '>AM</option><option value="PM"';
                    if ($out == 'PM') echo 'SELECTED';
                    echo '>PM</option></select>
                            </td>
                            <td align="right"><select name="area[' . $row[3] . ']">';
                            
                while ($shiftrow = mysqli_fetch_row($shiftR)) {
                    echo "<option value=\"$shiftrow[1]\"";
                    if ($shiftrow[1] == $row[2]) {echo ' SELECTED';}
                    echo ">$shiftrow[0]</option>";
                }
                echo "</select>
                            </td>
                        </tr>";
                }
            }
            echo '</table>';
            
            
                
        }
        echo '</div>';
        
        $periodQ = "SELECT periodStart, periodEnd FROM is4c_log.payperiods WHERE periodID = $periodID";
        $periodR = mysqli_query($db_master, $periodQ);
        list($periodStart, $periodEnd) = mysqli_fetch_row($periodR);
        
        $weekoneQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
            FROM is4c_log.timesheet AS t
            INNER JOIN is4c_log.payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area <> 13
            AND t.date >= DATE(p.periodStart)
            AND t.date < DATE(date_add(p.periodStart, INTERVAL 7 day))";
        
        $weektwoQ = "SELECT ROUND(SUM(TIMESTAMPDIFF(MINUTE, t.time_in, t.time_out))/60, 2)
            FROM is4c_log.timesheet AS t
            INNER JOIN is4c_log.payperiods AS p
            ON (p.periodID = t.periodID)
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area <> 13
            AND t.date >= DATE(date_add(p.periodStart, INTERVAL 7 day)) AND t.date <= DATE(p.periodEnd)";
            
        $vacationQ = "SELECT ROUND(vacation, 2), ID
            FROM is4c_log.timesheet AS t
            WHERE t.emp_no = $emp_no
            AND t.periodID = $periodID
            AND t.area = 13";
            
        $houseChargeQ = "SELECT ROUND(SUM(d.total),2)
            FROM is4c_log.transarchive d
            INNER JOIN is4c_op.employees e ON (e.card_no = d.card_no)
            AND d.datetime BETWEEN '$periodStart' AND '$periodEnd'
            AND d.staff IN (1,2)
            AND d.trans_subtype = 'MI'
            AND e.emp_no = $emp_no
            AND d.emp_no <> 9999 AND d.trans_status <> 'X'";
            
        $WageQ = "SELECT pay_rate FROM is4c_op.employees WHERE emp_no = $emp_no";
        
        $weekoneR = mysqli_query($db_master, $weekoneQ);
        $weektwoR = mysqli_query($db_master, $weektwoQ);
        $vacationR = mysqli_query($db_master, $vacationQ);
        $houseChargeR = mysqli_query($db_master, $houseChargeQ);
        $WageR = mysqli_query($db_master, $WageQ);
        
        list($weekone) = mysqli_fetch_row($weekoneR);
        if (is_null($weekone)) $weekone = 0;
        list($weektwo) = mysqli_fetch_row($weektwoR);
        if (is_null($weektwo)) $weektwo = 0;
        
        if (mysqli_num_rows($vacationR) != 0) {
            list($vacation, $vacationID) = mysqli_fetch_row($vacationR);
        } elseif (!isset($vacation) || is_null($vacation)) {
            $vacation = 0;
            $vacationID = 'insert';
        } else {
            $vacation = 0;
            $vacationID = 'insert';
        }
        
        list($houseCharge) = mysqli_fetch_row($houseChargeR);
        $houseCharge *= -1;
        if (is_null($houseCharge)) $houseCharge = 0;
        list($Wage) = mysqli_fetch_row($WageR);
        if (is_null($Wage)) $Wage = 0;
        
        
        
        
            
            echo "
            <p>Total hours in this pay period: " . number_format($weekone + $weektwo, 2) . "</p>
            <table cellpadding='5'><tr><td>Week One: ";
            if ($weekone > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo number_format($weekone, 2) . $font . "</td>";
            echo "<td>Gross Wages (before taxes): $" . number_format($Wage * ($weekone + $weektwo + $vacation), 2) . "</td></tr>";
            echo "<tr><td>Week Two: ";
            if ($weektwo > 40) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo number_format($weektwo, 2) . $font . "</td>";
            echo "<td>Amount House Charged: $" . number_format($houseCharge, 2) . "</td></tr>";
            echo "<tr><td>Vacation Hours: ";
            if ($vacation > 0) {echo '<font color="red">'; $font = '</font>';} else {$font = NULL;}
            echo number_format($vacation, 2) . $font;
        
        echo '
            </td></tr></table><input type="hidden" name="submitted" value="true" />
            <input type="hidden" name="emp_no" value="' . $_GET['emp_no'] . '" />
            <input type="hidden" name="periodID" value="' . $_GET['periodID'] . '" />
            <button type="submit">Change This Stuff!</button>
            </fieldset></form>';
    }
}

function roundTime($number) {
    // This function takes a two digit precision number and rounds it to the nearest quarter.
    
    $roundhour = explode('.', number_format($number, 2));
                                  
    if ($roundhour[1] < 13) {$roundhour[1] = 00;}
    elseif ($roundhour[1] >= 13 && $roundhour[1] < 37) {$roundhour[1] = 25;}
    elseif ($roundhour[1] >= 37 && $roundhour[1] < 63) {$roundhour[1] = 50;}
    elseif ($roundhour[1] >= 63 && $roundhour[1] < 87) {$roundhour[1] = 75;}
    elseif ($roundhour[1] >= 87) {$roundhour[1] = 00; $roundhour[0]++;}
                
    return number_format($roundhour[0] . '.' . $roundhour[1], 2);
}

function parseTime($time, $mer) {
    $hour = array();
    if (strlen($time) == 2 && is_numeric($time)) {
            $time = $time . ':00';
    } elseif (strlen($time) == 4 && is_numeric($time)) {
            $time = substr($time, 0, 2) . ':' . substr($time, 2, 2);
    } elseif (strlen($time) == 3 && is_numeric($time)) {
            $time = substr($time, 0, 1) . ':' . substr($time, 1, 2);
    }
    
    $in = explode(':', $time);
        
    if (($mer == 'PM') && ($in[0] < 12)) {
        $in[0] = $in[0] + 12;
    } elseif (($mer == 'AM') && ($in[0] == 12)) {
        $in[0] = 0;
    }
    
    return $in[0] . ':' . $in[1] . ':00';
}

?>