<?php
$page_title = 'Fannie - lab';
$header = 'Graphing some datar';
include('../includes/header.html');
echo "<script type='text/javascript' src='../includes/javascript/flot/jquery.flot.js'></script>";
echo
<<<EOS
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.core.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.theme.css" />
<link rel="STYLESHEET" type="text/css" href="../includes/javascript/ui.datepicker.css" />
<!-- a script ref to jquery.js was removed bc it was double included from the header  -->
<script type="text/javascript" src="../includes/javascript/datepicker/date.js"></script>
<script type="text/javascript" src="../includes/javascript/ui.datepicker.js"></script>
<script type="text/javascript" src="../includes/javascript/ui.core.js"></script>
<script type="text/javascript">
    Date.format = 'yyyy-mm-dd';
    $(function(){
        $('.datepick').datepicker({startDate:'2007-08-01', endDate: (new Date()).asString(), clickInput: true, dateFormat: 'yy-mm-dd', changeYear: true, changeMonth: true, duration: 0 });
    });

$(document).ready(function() {
    $('#selectAll').click(function() {
    if ($(this).text() == 'All Departments') {
        $('input.deptCheck').attr('checked', true);
        $(this).text('Clear Selections');
    } else {
        $('input.deptCheck').attr('checked', false);
        $(this).text('All Departments');
    }
    });

    $('.deptCheck').click(function() {
    $('#selectAll').text('Clear Selections');
    });
});

    var options = {
                    lines: { show: true },
                    points: { show: true, hoverable:true },
                    grid: { hoverable: true, clickable: true },
                    dataType: "json"
                };
		$.plot($('#graphMe'),[[0, 12], [7, 12], [7, 2.5], [12, 2.5]], options);

</script>
EOS;


echo "<script type='text/javascript' src='../includes/javascript/graph.js'></script>";
//echo "<script type='text/javascript'>alert('fuckme');</script>"; 

echo "Enter UPC:<input type='text' id='upc' class='' value='' >";
echo "Start Date:<input type='text' id='date1' class='datepick' >";
echo "End Date:<input type='text' id='date2' class='datepick' >";
echo "<input type='submit' name='submit' value='graph' />";
echo "<div id='graphMe' style='width:600px;height:300px;' ></div>";

include('../includes/footer.html');
?>
