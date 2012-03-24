<?php
//an example of using the php date functions//

$date = date_create('2000-01-01');
date_add($date, date_interval_create_from_date_string('10 days'));
echo date_format($date, 'Y-m-d');

?>
