<?php
echo 'now: ' . strtotime("now"), "<br />";
echo 'sept 10 2000: ' . strtotime("10 September 2000"), "<br />";
echo '+ 1 day: ' . strtotime("+1 day"),  "<br />";
echo '+ 1 week: ' . strtotime("+1 week"), "<br />";
echo '+ 1 week 2 days 4 hours 2 secs: ' . strtotime("+1 week 2 days 4 hours 2 seconds"),"<br />";
echo 'next Thurs: '. strtotime("next Thursday"), "<br />";
echo 'last Monday: ' . strtotime("last Monday"), "<br />";

$date = date("Y-m-d");// current date
echo "<br />today: " . $date;
$date = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime($date)) . " +1 day")); 
echo "<br />+1 day: ";
echo $date;

$date = strtotime(date("Y-m-d", strtotime($date)) . " +1 week");
//$date = strtotime(date("Y-m-d", strtotime($date)) . " +2 week");
//$date = strtotime(date("Y-m-d", strtotime($date)) . " +1 month");
//$date = strtotime(date("Y-m-d", strtotime($date)) . " +30 days");

echo "<br />+1 week: " . date("Y-m-s H:i:s",$date) . "<br />";

$timestamp = strtotime('2011-11-11 11:11:11');    

$time_togo = date("Y-m-d H:i:s", strtotime("+22 Year", $timestamp));
echo $timestamp . " and " . $time_togo;
?>
