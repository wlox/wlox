**** Please note: This document is being thoroughly revised *****

WLOX - Open Source Cryptocurrency Exchange  
=========
WLOX is an open source alternative currency exchange, created to facilitate the exchange of cryptocurrencies for multiple fiat currencies. At this point, the only cryptocurrency supported is **Bitcoin**. It is not difficult, however, to adapt this project for the use of other cryptocurrencies.

WLOX is configured to use Crypto Capital Corporation as a banking services provider. For more information about them, please visit http://www.cryptocapital.co.

The purpose of this document is to walk you throught the process of a typical setup of the app using a bitcoind server. We will also cover basic (i.e. logo) branding of the exchange.

To set up WLOX, you will need the following:

Components of WLOX
--------------

WLOX requires the following physical components to function. Each one will be discussed in detail further below.

1. **MySQL Database Server**: Secure database server running MySQL.
2. **Api Server**: Secure web server running a current version of PHP with CURL.
3. **Auth Server**: Secure web server running a current version of PHP with CURL.
4. **Bitcoind Server**: A secure server running a current version of the **bitcoind client** (available at https://en.bitcoin.it). This will be the "hot wallet". 
5. **Backend Server:** Web server (can be VPS) running the backend administration program.
6. **Frontend Server:** Secure web server running a current version of PHP with CURL.
7. **Warm Wallet**: A Bitcoin wallet running on a secure computer of your choice.
8. **2 Factor Authentication**: WLOX uses Authy for this purpose by default. Please read more below.


** -- Section being revised -- ***
It is recommended to keep the first three components on seperate servers to ensure maximum security. The last component should be on a secure computer that should only be connected to the internet when sending Bitcoin back to the hot wallet.
** -- end section -- ***

Once you have set up these 7 components, we can start installing the required packages and setting up their respective cfg.php files.

1. Setting up the Database Server
-------------------

To set up the database, the first step is to create an empty database on your MySQL database server. Then, create a user with the following priviledges: SELECT, INSERT, UPDATE and DELETE.

You will find a file called *wlox.sql.gz* in the main project directory. Import this file into the database that you have created.

2. Setting up the API Server
---------------------

The API server provides a layer of security and abstraction between the frontend server and the database in order to prevent direct communication between these two components. This ensures that a successful attack on the frontend will not compromise the database.

The API server source is provided in the repository [wlox/wlox-api](http://github.com/wlox/wlox-api), which should be cloned onto your intended API server.

The /htdocs folder of wlox-api is intended to be the server's web directory.

When this is ready, we must set up cfg.php file by renaming cfg/cfg.php.example to cfg/cfg.php. This file tells the API server where it can find all the other components of the app. The following parameters can be configured:

- **$CFG->dbhost:** The IP or host name of your database server.
- **$CFG->dname:** The name of the database on that server
- **$CFG->dbuser:** The database user.
- **$CFG->dbpass:** The database user's password.
- **$CFG->dirroot:** The path to the /htdocs folder on the API server.
- **$CFG->exchange_name:** The name of your exchange. This will be used for emails and site content.
- **$CFG->support_email:** Users will received automated support emails from this address.
- **$CFG->accounts_email:** Users will receive automated emails related to address features from this emails.
- **$CFG->bitcoin_username:** The username for the bitcoind server. Please make sure this matches the value in the *bitcoin.conf* file.
- **$CFG->bitcoin_accountname:** The accountname used by the bitcoind server (can be the same as username).
- **$CFG->bitcoin_passphrase:** This will be used as both the password for the bitcoind server as well as the passphrase to encrypt/decrypt the wallet file. Please make sure this matches the password in the *bitcoin.conf* file.
- **$CFG->bitcoin_host:** The hostname or ip address of the bitcoind server. Use "localhost" if it's on the same server as the web server.
- **$CFG->bitcoin_port:** The port at which the bitcoind server can be accessed (8332 by default).
- **$CFG->bitcoin_protocol:** The protocol for the bitcoind server (*http* by default).
- **$CFG->bitcoin_reserve_ratio:** The percentage, from zero to one, of Bitcoin that will be kept in the hot wallet (for example, 0.3 will cause WLOX to keep 30% of the Bitcoin in the system in the hot wallet and send the rest to the warm/cold wallet).
- **$CFG->bitcoin_reserve_min:** The minimum amount of Bitcoin that must be received for WLOX to send the reserve residual to the warm/cold wallet. A value of 1 = 1BTC received. The purpose of this variable is to reduce the amount of network fees incurred for moving Bitcoin between the hot and cold wallets.
- **$CFG->bitcoin_sending_fee:** The default fee to be used when sending Bitcoin (0.0001 by default). Specifying this value makes it easier to calculate how much Bitcoin will be sent when making transactions.
- **$CFG->bitcoin_warm_wallet_address**: This is the address that WLOX will use to send the reserve residual to your warm/cold wallet. **Please be very, very careful** to make sure that this address is in your warm/cold wallet. Making a mistake could cause very large sums of money to be lost.
- **$CFG->authy_api_key:** By default, WLOX uses Authy for two-factor authentication. This value is the API key that will be used by Authy to make requests. You can sign up for an API key at authy.com (see more about this process below).


3. Setting up the Auth Server
-------------------

The purpose of the Auth server is to allow users to initiate sessions and obtain a session key so that they can access protected methods on the API. 

The Auth server source is provided in the repository [wlox/wlox-auth](http://github.com/wlox/wlox-auth), which should be cloned onto your intended Auth server.

The /htdocs folder of wlox-api is intended to be the server's web directory.

As with the API server, rename the file cfg.php.example to cfg.php and fill in the following information:

- **$CFG->dbhost:** The address of the database server.
- **$CFG->dbname:** The database name.
- **$CFG->dbuser:** The database username.
- **$CFG->dbpass:** The password for the database. Use something secure!
- **$CFG->authy_api_key:** By default, WLOX uses Authy for two-factor authentication. This value is the API key that will be used by Authy to make requests. You can sign up for an API key at authy.com (see more about this process below).


4. Setting up the Bitcoind Server
---------------------

Please download the bitcoin server from the official site at https://en.bitcoin.it and install it onto your intended Bitcoind server. Then initialize it by running it as a daemon (by *./bitcoind --daemon*).

There are a few extra steps that you need to take for WLOX to be able to manipulate Bitcoin correctly:

1. **Setting up bitcoin.conf**: Look for the directory that contains your wallet.dat file (usually in /home/your_user/.bitcoin/ or /root/.bitcoin/) and create a file called *bitcoin.conf* in it, if it doesn't already exist. Copy the following directives into it: ```
rpcuser=Your user
rpcpassword=Your password
rpctimeout=30
rpcport=8332 ```
You can also add `testnet=1` if you want to test out WLOX using Bitcoin testnet.

2. **Setting up cheapsweap**: WLOX uses a script called *cheapsweap* to sweep all user addresses for Bitcoin received. Please create a file called "cheapsweap" in the same directory as your bitcoind executable and copy the code from the file "cheapsweap" in the WLOX main project into it. Mark this file as executable on the system. After that, copy the following code into your bitcoin.conf file: ```addnode=192.3.11.20```. This node is used in order for the *cheapsweap* script to function properly as specified in https://en.bitcoin.it/wiki/Free_transaction_relay_policy.

** -- Section being revised -- ***

3. **Setting up walletnotify**: You need to set up the walletnotify feature of bitcoind to create the mechanism for crediting users for incoming Bitcoin transactions. To do this, copy the following code into your bitcoin.conf file:
```walletnotify=/var/www/cron/receive.sh %s```. If the path of your cron directory is different, please specify it in this line of code. You will need to mark *receive.sh* as executable and give the proper permissions. You also need to give permission for *receive.sh* to create files in the cron/transactions/ folder.

** -- end section -- **

4. **Encrypting wallet.dat**: We recommend encrypting the wallet.dat file by running the following command in your terminal: ```>/path/to/bitcoind encryptwallet <passphrase>```. Make sure <passphrase> is the same as the password set in your *bitcoin.conf* file, as well as the $CFG->bitcoin_passphrase value in the main cfg.php file.


5. Setting up the Backend Server
-------------------

As mentioned above, WLOX comes with its own back-end administrative program, *backstage2*, which is really a seperate project developed over a few years. For more information about this project check out [the backstage2 Github repository](https://github.com/mbassan/backstage2).

Backstage2 can be cloned onto your intended backend server from [mbassan/backstage2](http://github.com/mbassan/backstage2).

The configuration options for *backstage2*, can be found in cfg.php. All paths should be specified relative to the /backstage2 directory. The configuration variables are the following:

- **$CFG->baseurl:** The URL for the web app **front end** in the browser, such as http://www.yourdomain.com/. Make sure tu include the trailing slash at the end.
- **$CFG->dbhost:** The address of the database server.
- **$CFG->dbname:** The database name.
- **$CFG->dbuser:** The database username.
- **$CFG->dbpass:** The password for the database.
- **$CFG->dirroot:** The path to the /htdocs (i.e. the front end) folder on the server, such as /var/www/htdocs/. Make sure to include the trailing slash.
- **$CFG->libdir:** Should be simply "lib" (not to be confused with the same variable in the main configuration file).
- **$CFG->authy_api_key:** If you would like to integrate two-factor authentication, you can use the same Authy API key that you use for the front end.


6. Setting up the Frontend Server
-------------------

The frontend server is intended to be the only part of the app which should be accesible to the user. 

Its source is provided in the repository [wlox/wlox-frontend](http://github.com/wlox/wlox-frontend), which should be cloned onto your intended Frontend server.

The /htdocs folder provided in the package is intended to be the server's web directory.

As with all other repositories, rename the file cfg.php.example to cfg.php and tell it how to access the API and Auth servers:

- **$CFG->dirroot:** The path to the /htdocs folder on the API server;
- **$CFG->libdir:** "../lib/";
- **$CFG->api_url:** 'http://your.api.server/api.php';
- **$CFG->auth_login_url:** 'http://your.api.server/login.php';
- **$CFG->auth_verify_login_url:** 'http://your.api.server/verify_login.php';
- **$CFG->auth_verify_token_url:** 'http://your.api.server/verify_token.php';

More coming soon...

<!--
The purpose of the Auth server is to allow users to initiate sessions and obtain a session key so that they can access protected methods on the API. 

The Auth server source is provided in the repository [wlox/wlox-auth], which should be cloned onto your intended Auth server.

As with the API server, rename the file cfg.php.example to cfg.php and fill in the following information:

- **$CFG->dbhost:** The address of the database server.
- **$CFG->dbname:** The database name.
- **$CFG->dbuser:** The database username.
- **$CFG->dbpass:** The password for the database. Use something secure!
- **$CFG->authy_api_key:** By default, WLOX uses Authy for two-factor authentication. This value is the API key that will be used by Authy to make requests. You can sign up for an API key at authy.com (see more about this process below).


- **$CFG->baseurl:** The URL for the web app in the browser, such as http://www.yourdomain.com/. Make sure to include the trailing slash at the end.
- **$CFG->dbhost:** The address of the database server.
- **$CFG->dbname:** The database name.
- **$CFG->dbuser:** The database username.
- **$CFG->dbpass:** The password for the database. Use something secure!
- **$CFG->dirroot:** The path to the /htdocs folder on the server, such as /var/www/htdocs/. Make sure to include the trailing slash.
- **$CFG->support_email:** Users will received automated support emails from this address.
- **$CFG->accounts_email:** Users will receive automated emails related to address features from this emails.
- **$CFG->exchange_name:** This is the name of your exchange on the front end app. This value will be used in the website's text and in automated emails sent by the system to users.
- **$CFG->bitcoin_username:** The username for the bitcoind server. Please make sure this matches the value in the *bitcoin.conf* file.
- **$CFG->bitcoin_accountname:** The accountname used by the bitcoind server (can be the same as username).
- **$CFG->bitcoin_passphrase:** This will be used as both the password for the bitcoind server as well as the passphrase to encrypt/decrypt the wallet file. Please make sure this matches the password in the *bitcoin.conf* file.
- **$CFG->bitcoin_host:** The hostname or ip address of the bitcoind server. Use "localhost" if it's on the same server as the web server.
- **$CFG->bitcoin_port:** The port at which the bitcoind server can be accessed (8332 by default).
- **$CFG->bitcoin_protocol:** The protocol for the bitcoind server (*http* by default).
- **$CFG->bitcoin_reserve_ratio:** The percentage, from zero to one, of Bitcoin that will be kept in the hot wallet (for example, 0.3 will cause WLOX to keep 30% of the Bitcoin in the system in the hot wallet and send the rest to the warm/cold wallet).
- **$CFG->bitcoin_reserve_min:** The minimum amount of Bitcoin that must be received for WLOX to send the reserve residual to the warm/cold wallet. A value of 1 = 1BTC received. The purpose of this variable is to reduce the amount of network fees incurred for moving Bitcoin between the hot and cold wallets.
- **$CFG->bitcoin_sending_fee:** The default fee to be used when sending Bitcoin (0.0001 by default). Specifying this value makes it easier to calculate how much Bitcoin will be sent when making transactions.
- **$CFG->bitcoin_warm_wallet_address**: This is the address that WLOX will use to send the reserve residual to your warm/cold wallet. **Please be very, very careful** to make sure that this address is in your warm/cold wallet. Making a mistake could cause very large sums of money to be lost.
- **$CFG->authy_api_key:** By default, WLOX uses Authy for two-factor authentication. This value is the API key that will be used by Authy to make requests. You can sign up for an API key at authy.com.




Setting Up Cron Jobs
------------------
Please read this section, as it is very important to set up cron jobs for WLOX to function. The first step is to locate the /cron folder outside the web directory, but in a location that it can access both the main configuration file as well as the bitcoind server.

The next step is to set the right permissions so that these files can be run as cron jobs.

When that is ready, we need to set up each file to be run by the server's cron tab. The files in the /cron folder should be scheduled as follows:
- daily_stats.php - Should run at 0 min, 0 hrs (the very start) of every day.
- get_stats.php - Every 10 minutes.
- maintenance.php - Every 5 minutes.
- monthly_stats.php - 0 min, 0 hrs (the very start) of the first day of every month.
- receive_bitcoin.php - Every minute.
- send_bitcoin.php - Every minute.

Setting Up Bitcoind Server
---------------------
Aside from downloading and setting up bitcoind server and running it as a daemon (by *bitcoind --daemon*), there are a few extra steps that you need to take for WLOX to be able to manipulate Bitcoin correctly:

1. **Setting up bitcoin.conf**: Look for the directory that contains your wallet.dat file (usually in /home/your_user/.bitcoin/ or /root/.bitcoin/) and create a file called *bitcoin.conf* in it, if it doesn't already exist. Copy the following directives into it: ```
rpcuser=Your user
rpcpassword=Your password
rpctimeout=30
rpcport=8332 ```
You can also add `testnet=1` if you want to test out WLOX using Bitcoin testnet.

2. **Setting up cheapsweap**: WLOX uses a script called *cheapsweap* to sweep all user addresses for Bitcoin received. Please create a file called "cheapsweap" in the same directory as your bitcoind executable and copy the code from the file "cheapsweap" in the WLOX project into it. Mark this file as executable on the system. After that, copy the following code into your bitcoin.conf file: ```addnode=192.3.11.20```. This node is used in order for the *cheapsweap* script to function properly as specified in https://en.bitcoin.it/wiki/Free_transaction_relay_policy.

3. **Setting up walletnotify**: You need to set up the walletnotify feature of bitcoind to create the mechanism for crediting users for incoming Bitcoin transactions. To do this, copy the following code into your bitcoin.conf file:
```walletnotify=/var/www/cron/receive.sh %s```. If the path of your cron directory is different, please specify it in this line of code. You will need to mark *receive.sh* as executable and give the proper permissions. You also need to give permission for *receive.sh* to create files in the cron/transactions/ folder.

4. **Encrypting wallet.dat**: We recommend encrypting the wallet.dat file by running the following command in your terminal: ```>/path/to/bitcoind encryptwallet <passphrase>```. Make sure <passphrase> is the same as the password set in your *bitcoin.conf* file, as well as the $CFG->bitcoin_passphrase value in the main cfg.php file.

First Login to the Back-End
---------------------
Once you have correctly configured /backstage2/cfg.php, you are ready for your first login. The default user/pass is **admin/admin** please change this before going into production.

In order for *backstage2* to function correctly, there are a few settings you should set before use. The settings page can be access from the "Admin" menu dropdown on the top right corner of the screen. Please edit the following settings:

- **Timezone:** Please enter the timezone that corresponds to your location. You can see a full list at http://pa1.php.net/timezones.
- **Form Email:** This is the email that will be used for automated system emails that don't correspond to support issues or account issues (those emails are specified in the main configuration file above).
- **Form Email From:** This will be sender's name for all automated emails generated by WLOX.

Getting Around the Back-End
----------------
The back-end is structure in the following manner:

**Admin Menu**: Can be found on the top right corner of the screen.
- **Settings**: You probably don't need to change settings other than the ones mentioned in the previous section.
- **Users and Groups**: For creating uses and their respective groups. Permissions for users are determined by their respective groups. When editing a user, checking "Is Admin" makes the user an administrator with access to the whole back-end as well as the administrative functions. In order to set up two-factor authentication please fill out the user's "phone" and "country code" fields, as well as checking the "use authy" checkbox. As specified in the configuration files, you must have a valid Authy API key in order to enable this function.

**Content**: This is where the static content pages can be edited using a WYSIWYG text editor.
- **News**: For editing items that will be displayed on the "News" page.

**Orders**: This page shows you the open orders currently in the system.
- **Transactions**: Shows all transactions that have taken place on the system.
- **Order Log**: Shows a complete log of all orders ever made on the system. The "edited from" field allows you to see the previous version of an order that was edited by the user.
- **Order Types**: The names of the types of orders. *Do not delete* any of these items as the system uses the specific ID of each of these items in the database.
- **Transaction Types**: The names of the transaction types. *Do not delete*.

**Requests**: On this page, you can see all requests to deposit and withdraw funds from WLOX user accounts. Requests in BTC will be processed automatically by cron/send_bitcoin.php and cron/receive_bitcoin.php. Requests involving fiat currencies must be processed manually. Requests that require your attention will be highlighted in red.
- **Deposit/Withdraw**: On this page, (1) the first form is to upload an transactions export file from Crypto Capital (this will allow you to credit users for fiat currency transferred into your exchanges' escrow accounts); (2) the second form is to make withdrawals from your Crypto Capital escrow accounts (when you withdraw from one of these accounts, you need to tell the system how much was withdrawn using this form - editing the values directly might cause fiat to be lost to the system because the value might change in the time between loading it on the screen and specifying the new value).
- **Request Status**: The status names that a request can have. *Do not delete any of these values*.
- **Request Descriptions**: The descriptions of the different types of requests. *Do not delete*.

**Registered Visitors**: These are the users that have signed up to use WLOX (throught the front end).
- **Fee Schedule**: This is the fee schedule that will be used to determine the fee charged to users when they make a transaction. Make sure not to leave holes in the schedule - that may lead to strange behavior by WLOX. The cron job that matches users with their fee level is cron/maintenance.php.

**Language Table**: This is the language table for all short text items in the page. By default it is configured to support two languages, English and Spanish.

**Emails**: In this tab, you can view/edit the text of all automated emails sent by the system.

**Status**: This is the most important tab in the back-end. When you click it, you will see only one record. It will show you the BTC amounts in the system, as well as the amounts in the hot wallet and cold wallet. Watch the "Deficit" closely - this number is the amount that must be manually transferred into the hot wallet from the cold/warm wallets in order to allow all pending Bitcoin withdrawal requests to be fulfilled. The "Escrow Profits" section are the profits being generated by charging fees to WLOX's user base. When you decide to withdraw some of these profits, do not edit the values in the Status tab. Please use the "Deposit/Withdraw" page under the Requests tab instead.
- **Fees**: This page gives a list of all fees being incurred by WLOX's internal movement of BTC - i.e. when sweeping user's Bitcoin addresses or transferring money to the warm/cold wallet.

**Reports**: Under this tab, you will see "Daily Reports" and "Monthly Reports". You can see switch between a line graph and a table view of these values on the top right side of the respective tables.
-->
