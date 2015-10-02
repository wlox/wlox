WLOX - Open Source Cryptocurrency Exchange  
=========
WLOX is an open source cryptocurrency exchange that supports multiple fiat currencies.

At this point, the exchange only supports one cryptocurrency at a time. We plan to adapt the project to a multiple-crypto-currency environment soon.

WLOX is configured to use Crypto Capital Corporation as the default banking services provider. For more information about them, please visit http://www.cryptocapital.co.

The purpose of this document is to walk you throught the process of a typical setup of the app using a bitcoind server.

**IF YOU INSTALLED WLOX PRIOR TO 14/01/2015, PLEASE READ THE FILE UPDATE_GUIDE_v1.04 BEFORE CONTINUING**

Getting Started
--------------
WLOX runs on the traditional PHP/MySQL/Apache setup.

**For a development environment**, you can simply clone the whole set of WLOX repositories by doing `git clone --recursive https://github.com/wlox/wlox.git`.

**For a production environment**, it is strongly recommended to distribute the different repositories across multiple servers. You can clone each sub-module independently by doing `https://github.com/wlox/wlox-[submodule].git`.


Project Structure
--------------
WLOX is structured as one main *git* repository with multiple sub-modules. As mentioned above, you can clone the whole project or each repository on it's own.

- **wlox** > The master repo. DB updates and documentation reside here.
- |-- **wlox-frontend** > The App's frontend.
- |-- **wlox-auth** > Handles user authentication.
- |-- **wlox-api** > Handles all requests for data made by the frontend.
- |-- **wlox-cron** > Contains all cron jobs.
- |-- **backstage2** > The CMS (back end) program.

Requirements
---------------
- **PHP/MySQL/Apache** (see section called *PHP Configuration* below).
- **bitcoind** server, running as daemon (see section called *bitcoind configuration* below).
- **Warm Wallet**: A secure Bitcoin wallet that is not connected to WLOX. WLOX will send a percentage of Bitcoin deposits to this address automatically. This is normally a half-way point between the Hot Wallet (bitcoind) and *cold storage*. See the **Warm Wallet** section below.
- **Cold Storage**: We recommend using some kind of *cold storage* for your Bitcoin reserves, such as a *hardware wallet* and an actual safe deposit box in a bank or other safe place.

Initializing the Database
-------------------
To set up the database, the first step is to create an empty database on your MySQL database server. Then, create a user with the following priviledges: SELECT, INSERT, UPDATE and DELETE.

You will find a file called *wlox.sql.gz* in the main project directory. Import this file into the database that you have created.

Setting up the Back-End (backstage2)
-------------------
As mentioned above, WLOX comes with its own administrative program, *backstage2*, which is really a [seperate project](https://github.com/mbassan/backstage2) developed over a few years.

You can clone **backstage2** doing `git clone https://github.com/wlox/backstage2.git`. After cloning, rename the file *cfg.php.example* to *cfg.php* and define the following variables: 

- **$CFG->dbhost:** The address of the database server.
- **$CFG->dbname:** The database name.
- **$CFG->dbuser:** The database username.
- **$CFG->dbpass:** The password for the database.

You can now log in using user/password admin/admin. You should obviously remove this user in a production setup.


Configuring WLOX to Run
-------------------------
Once you have managed to install and access **backstage2**, log in using admin/admin and go to 'Status'=>'App Configuration'. This the is the place where you will specify all of the application's settings from now on:

**Global App Settings**
- **Application Timezone:** The default timezone of WLOX (the frontend will be seen in each user's timezone).
- **BTC Miner's Fee:** The fee for sending Bitcoin, collected by the network. We recommend 0.001.
- **Currency Conversion Fee (%):** A number between 0 and 100 (decimals allowed). The fee collected by WLOX when a trade happens across currencies.
- **Exchange's Name:** For example 'MyExchange'. Will be used in place of [exchange_name] in site content.
- **Fiat Withdrawal Fee:** Not implemented. Leave at 0 for now, unless you want to develop this feature!
- **Min. Order Amnt. (USD):** The minimum order that can be placed. 
- **Min. Order Price (% under Mkt.):** A number between 0 and 100 (decimals allowed). Specifies how far under the last trade price a Bid order can be placed.
- **Password Hash Key: A salt Used by *mcrypt* for hashing user passwords in the DB.
- **Password Permitted Regex:** REGEX that specifies password character set.
- **Password Min. Chars.:** The minimum length for user passwords.
- **Enable Cross-Currency Trades:** Disable if you want each currency to have a seperate order book.
- **Notify when a new user registers:** By email.
- **Notify user fiat withdrawals:** By email.

**Email Settings**
- **Support Email:** The email address you will use for user support.
- **Contact Form Email:** The email address for the site's contact form.
- **Sender's Name:** System emails will be sent by this name.
- **SMTP Host:** Ex. *smtp.myserver.com*
- **SMTP Port:** Ex. *465*
- **SMTP Security Type:** Ex. *ssl*
- **SMTP Username:** For SMTP mail account.
- **SMTP Password:** For SMTP mail account.
- **SMTP Sender Email:** Can be the same as *support email* above.

**Bitcoin Server Settings**
- **Username:** The username for *bitcoind*.
- **Account Name:** The account name for *bitcoind* (optional, but makes things neater - can be same as username).
- **Passphrase:** Use something strong.
- **Host:** Use the server's IP if you are using multiple servers.
- **Port:** *8332* by default.
- **Protocol:** *http* by default.
- **Reserve Min. BTC (for Send to Warm Wal.):** *1* by default. The minimum hot wallet balance at which WLOX will send to the warm wallet.
- **Reserve Ratio (% in Hot Wallet):** A number from 0 to 100. The percentage of BTC reserves that will be kept in the hot wallet.
- **Warm Wallet BTC Addr.:** BE VERY CAREFUL TO INPUT THE RIGHT ADDRESS! Receiving address for the warm wallet.

**Third Party API Keys**
- **Authy API Key:** If you want to use Authy for 2FA (Google 2FA also supported, needs no API key).
- **Quandl API Key:** Optional. Quandl is a data service that you can use to get historical prices of Bitcoin to populate your price history before you begin generating your own.
- **Help Desk API Key:** If you use a third-party help desk. Accesible in the code as `$CFG->helpdesk_key`.

**Frontend Config**
- **Base URL:** For example *http://mysite.com/*.
- **Dir Root:** The web directory of the repo. For example */var/www/wlox/frontend/htdocs/*.

**API Config**
- **DB Debug On Fail:** Will halt script and output DB errors to PHP error stream.
- **Dir Root:** The web directory of the repo. For example */var/www/wlox/api/htdocs/*.

**Auth Config**
- **DB Debug On Fail:** Will halt script and output DB errors to PHP error stream.

**Cron Config**
- **DB Debug On Fail:** Will halt script and output DB errors to PHP error stream.
- **Dir Root:** The web directory of the repo. For example */var/www/wlox/cron/*.

**Backstage Config**
- **DB Debug On Fail:** Will halt script and output DB errors to PHP error stream.
- **Dir Root:** The web directory of the repo. For example */var/www/wlox/backstage2/*.

Setting up the API Server
---------------------
The API server provides a layer of security and abstraction between the frontend server and the database in order to prevent direct communication between these two components. 

Install by doing `git clone https://github.com/wlox/wlox-api.git` in the intended space.

When this is ready, rename cfg/cfg.php.example to cfg/cfg.php and set:

- **$CFG->dbhost:** The IP or host name of your database server.
- **$CFG->dname:** The name of the database on that server
- **$CFG->dbuser:** The database user.
- **$CFG->dbpass:** The database user's password.

Setting up the Auth Server
-------------------

The purpose of the Auth server is to allow users to initiate sessions and obtain a session key so that they can access protected methods on the API. 

Install by doing `git clone https://github.com/wlox/wlox-auth.git` in the intended space.

When this is ready, rename cfg.php.example to cfg.php and set:

- **$CFG->dbhost:** The IP or host name of your database server.
- **$CFG->dname:** The name of the database on that server
- **$CFG->dbuser:** The database user.
- **$CFG->dbpass:** The database user's password.


Setting up Cron Jobs
-------------------

**IMPORTANT: Should run on the same server as bitcoind daemon!**

The Cron Jobs necessary for WLOX to run are provided in this repository. 

Install by doing `git clone https://github.com/wlox/wlox-auth.git` in the intended space.

When this is ready, rename cfg.php.example to cfg.php and set:

- **$CFG->dbhost:** The IP or host name of your database server.
- **$CFG->dname:** The name of the database on that server
- **$CFG->dbuser:** The database user.
- **$CFG->dbpass:** The database user's password.

The next step is to set the right permissions so that these files can be run as cron jobs. This includes setting up the appropriate permissions for the /transactions directory so that the provided *receive.sh* file can create files in there.

When that is ready, we need to set up each file to be run by the server's cron tab. The cron jobs should be scheduled as follows:
- **daily_stats.php** - Should run at 0 min, 0 hrs (the very start) of every day.
- **get_stats.php** - Every 10 minutes.
- **maintenance.php** - Every 5 minutes.
- **monthly_stats.php** - 0 min, 0 hrs (the very start) of the first day of every month.
- **process_bitcoin.sh** - Every minute.


Setting up the Bitcoind Server
---------------------
Please install *bitcoind* from the *ppa:bitcoin/bitcoin* repository.

Then locate a file called *bitcoin.conf* (usually in /home/your_user/.bitcoin/ or /root/.bitcoin/) or create it in the appropriate location if it doesn't already exist. Copy the following directives into it:

```
rpcuser=Your user
rpcpassword=Your password
rpctimeout=30
rpcport=8332
walletnotify= path to the receive.sh file provided in wlox-cron (example path/to receive.sh %s)
```

You can also add `testnet=1` if you want to test out WLOX using Bitcoin testnet (which we obviously recommend for a development environment).

We recommend encrypting the wallet.dat file by running the following command in your terminal: ```>/path/to/bitcoind encryptwallet <passphrase>```. Make sure <passphrase> is the same as the password set in your *bitcoin.conf* file, as well as the *Bitcoin Passpharse* defined in backstage2 -> App Configuration -> Bitcoin Server Settings.


Setting up the Frontend
-------------------
The frontend server is intended to be the only part of the app which should be accesible to the user. 

Install by doing `git clone https://github.com/wlox/wlox-frontend.git` in the intended space.

The /htdocs folder provided in the package is intended to be the server's web directory.

When this is ready, rename cfg.php.example to cfg.php and set:

- **$CFG->api_url:** 'http://your.api.server/api.php';
- **$CFG->auth_login_url:** 'http://your.api.server/login.php';
- **$CFG->auth_verify_token_url:** 'http://your.api.server/verify_token.php';

Warm Wallet
-------------------
The "Warm Wallet" is simply a Bitcoin wallet running in a secure location. The cron job `receive_bitcoin.php` (provided in wlox-cron) will channel all Bitcoin above the *Reserve Ratio* specified in the backstage2 App Configuration to this address. You can do whatever you like with it once it arrives there (for example, you can transfer it to cold storage using a Piper wallet).

In order to load Bitcoin back into the Hot Wallet (when the amount needed for outgoing transfers exceeds the amount in the Hot Wallet), simply send Bitcoin from your Warm Wallet back to the dedicated Hot Wallet address. This can be found in the Back End under Registered Visitors -> Bitcoin Addresses (it will be the one with both "System" and "Hot Wallet" checked).

2-Factor Authentication (2FA)
-----------------------------
WLOX supports both Google Authenticator and Authy (www.authy.com) by default.


Getting Around the Back-End
----------------
The back-end is structure in the following manner:

**Admin Menu**: Can be found on the top right corner of the screen.
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

PHP Configuration
---------------------
Your php.ini should have the following settings:

**short_open_tag** = On

It should also have the following modules:

- curl
- gd
- mcrypt
- json
- mysql
- openssl
- pcre
