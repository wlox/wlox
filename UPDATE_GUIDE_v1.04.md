Update Guide For WLOX Version 1.04
============

In this update, all the $CFG settings that were stored in each repository's **cfg.php** file have now been moved to the back end. There will no longer be a need to update any cfg.php files after this update, as these will only be used to connect to the database. This update also significantly improves the performance of WLOX!

**ALL CONFIG VARIABLES ARE NOW IN BACKSTAGE2 UNDER 'STATUS'->'APP CONFIGURATION' AND NOWHERE ELSE**

Please follow this guide **in the exact order described here** so that everything will function correctly.

STEP 1
-------
Run the DB update file **update_v1.04.sql** (if you have not run previous updates, please run them before this).

STEP 2 - FRONTEND
-------------------
The following variables in the cfg.php file should copied to 'Status'->'App Configuration' as follows:<br/>

'Frontend Config' Section</br>
**$CFG->dirroot** => 'Dir Root' <br/>
**$CFG->baseurl** => 'Base URL'<br/>

'Global App Settings' Section</br>
**$CFG->orders_under_market_percent** => 'Min. Order Price (% under Mkt.)' (This should now be given as an integer, so 0.20 is now 20)<br/>
**$CFG->orders_min_usd** => 'Min. Order Amnt. (USD)'<br/>
**$CFG->bitcoin_sending_fee** => 'BTC Miner's Fee'<br/>
**$CFG->fiat_withdraw_fee** => 'Fiat Withdrawal Fee'<br/>
**$CFG->pass_regex** => 'Password Permitted Regex' (If you have never touched this value, set it to ``/[^0-9a-zA-Z!@#$%&*?\.\-\_]/``)<br/>

In addition to these, you should set the following:<br/> 
**Password Min. Chars.:** = The minimum password length.<br/> 
**Exchange's Name:** = Used in automated emails as well as the site's content as [exchange_name].<br/>
**Application Timezone:** = Any timezone name accepted by PHP.<br/> 

New optional values:<br/> 
**Notify when a new user registers:** = Self explanatory.<br/> 
**Notify user fiat withdrawals:** = Notifies when a user wants to withdraw fiat.<br/>

When you are finished, please check the new cfg.php.example to see what your cfg.php file should look like (it's now much more simple).

STEP 2 - API
-------------------
When you are finished, please check the new cfg.php.example to see what your cfg.php file should look like (it's now much more simple).<br/>

**$CFG->dirroot** => 'API Config'-> 'Dir Root' <br/>

'Email Settings' Section<br/>
$CFG->accounts_email => No longer used.<br/>
$CFG->support_email => 'Support Email'<br/>
$CFG->email_smtp_host => 'SMTP Host'<br/>
$CFG->email_smtp_port => 'SMTP Port'<br/>
$CFG->email_smtp_security => 'SMTP Security Type'<br/>
$CFG->email_smtp_username = 'SMTP Username'<br/>
$CFG->email_smtp_password = 'SMTP Password'<br/>
$CFG->email_smtp_send_from = 'SMTP Sender Email'<br/>

'Bitcoin Server Settings' Section<br/>
$CFG->bitcoin_username => 'Username'<br/>
$CFG->bitcoin_accountname => 'Account Name'<br/>
$CFG->bitcoin_passphrase => 'Passphrase'<br/>
$CFG->bitcoin_host => 'Host' (wlox-cron also uses this variable, so better use the IP)<br/>
$CFG->bitcoin_port => 'Port'<br/>
$CFG->bitcoin_protocol => 'Protocol'<br/>

'Third Party API Keys' Section<br/>
$CFG->authy_api_key => 'Authy API Key'<br/>

STEP 3 - CRON
---------------
When you are finished, please check the new cfg.php.example to see what your cfg.php file should look like (it's now much more simple).<br/>

'Third Party API Keys' Section<br/>
$CFG->quandl_api_key = 'Quandl API Key' (this is only for get_stats.php, which is only needed to populate the price history before you have any of your own).<br/>

'Bitcoin Server Settings' Section<br/>
$CFG->bitcoin_reserve_ratio = 'Reserve Ratio' (This should now be given as an integer, so 0.20 is now 20)<br/>
$CFG->bitcoin_reserve_min => 'Reserve Min. BTC'
$CFG->bitcoin_warm_wallet_address = 'Warm Wallet BTC Addr'

STEP 4 - AUTH
--------------
Refer to cfg.php.example for which values to delete.<br/>

STEP 5 - BACKSTAGE2
--------------------------
Refer to cfg.php.example for which values to delete.<br/>

LAST STEP
---------------
If you have completed the previous steps, you can now safely run ``git pull`` on your repositories.<br/>

We know this was difficult, but it will really make things easier in the future.

