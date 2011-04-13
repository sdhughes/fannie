#!/bin/sh

# Add a loop scanning submission for 'Your weekly sales have been recorded'
# First truncate submission
echo '' > /pos/fannie/cron/submission

# Load the first grep
i=`grep 'Your weekly sales have been recorded!' /pos/fannie/cron/submission`

# Then the while loop

while [ -z "$i" ]
do
	# Sign in to NCGA, get cookie, start session...
	wget -O /pos/fannie/cron/login --post-data "edit[form_id]=user_login&edit[destination]=&edit[pass]=3th3rn3t&edit[name]=finance%40albertagrocery.coop" --save-cookies=/pos/fannie/cron/cookies.txt --keep-session-cookies http://ncga.coop/user/login?destination=member-area&ssl=off

	# Get sales data
	sales=`mysql -u root -plung*vIa is4c_log -e "SELECT SUM(total) AS '' FROM trans_2010 WHERE WEEK(datetime, 1) = (WEEK(curdate(), 1) - 1) AND emp_no <> 9999 AND trans_status <> 'X' AND trans_type IN ('I', 'D') AND department BETWEEN 1 AND 35 AND department NOT IN (17,18,19) AND trans_subtype <> 'MC'"`
	yesterday=`date -I --date='1 day ago'`

	validation=`wget --load-cookies=/pos/fannie/cron/cookies.txt http://ncga.coop/weeklysales -O - | sed -nr '/<input type="submit" name="op" value="Save"  class="form-submit" \/>/, /weeklysales/p' | sed -nr '/[a-fA-F0-9]{32}/p' | grep -Eo [a-fA-F0-9]{32}`

	echo $validation > /pos/fannie/cron/validation

	# Fill data into form.
	wget -O /pos/fannie/cron/submission --post-data "op=Save&edit[3716][nid]=3716&edit[3716][sundate]=$yesterday&edit[form_id]=weeklysales&edit[3716][sales]=$sales&edit[form_token]=$validation" --load-cookies=/pos/fannie/cron/cookies.txt http://ncga.coop/weeklysales

	# Refresh loop variable
	i=`grep 'Your weekly sales have been recorded!' /pos/fannie/cron/submission`
	
	# Wait a bit before looping
	sleep 60
	
	# Debugging...
	echo $i
done
