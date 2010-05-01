#!/bin/sh

# Sign in to NCGA, get cookie, start session...
wget -O /dev/null --post-data "edit[form_id]=user_login&edit[destination]=&edit[pass]=3th3rn3t&edit[name]=finance%40albertagrocery.coop" --save-cookies=cookies.txt --keep-session-cookies http://ncga.coop/user/login?destination=member-area&ssl=off

# Get sales data
sales=`mysql -u root -plung*vIa is4c_log -e "SELECT ROUND(SUM(total), 0) AS '' FROM trans_2010 WHERE WEEK(datetime, 1) = (WEEK(curdate(), 1) - 1) AND emp_no <> 9999 AND trans_status <> 'X' AND trans_type IN ('I', 'D') AND department BETWEEN 1 AND 35 AND department NOT IN (17,18,19) AND trans_subtype <> 'MC'"`
yesterday=`date -I --date='1 day ago'`

validation=`wget --load-cookies=cookies.txt http://ncga.coop/weeklysales -O - | sed -nr '/<input type="submit" name="op" value="Save"  class="form-submit" \/>/, /weeklysales/p' | sed -nr '/[a-fA-F0-9]{32}/p' | grep -Eo [a-fA-F0-9]{32}`

# Fill data into form.
wget --post-data "op=Save&edit[3716][nid]=3716&edit[3716][sundate]=$yesterday&edit[form_id]=weeklysales&edit[3716][sales]=$sales&edit[form_token]=$validation" --load-cookies=cookies.txt http://ncga.coop/weeklysales

# BAM - A Smart Query...