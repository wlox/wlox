WLOXE - Open Source Currency Exchange
=========
WLOXE is an open source alternative currency exchange, created to facilitate the exchange of alternative currencies (*alt-currencies*) for multiple fiat currencies. At this point, the only alt-currency supported is **Bitcoin**. It is not difficult, however, to adapt this project for the use of other alt-currencies.

The purpose of this document is to walk you throught the process of a typical setup of the app using a bitcoind server. We will also cover basic (i.e. logo) branding of the exchange.

To set up WLOXE, you will need the following:

Components of WLOXE
--------------
1. **Web Server:** Web server running a current version of PHP (a shared hosting or VPS account will work, but we strongly recommend a dedicated server).
2. A **MySQL server**.
3. A current version of the **bitcoind client** (available at https://en.bitcoin.it). This will be the "hot wallet". 
4. **Warm/Cold Wallet:** A Bitcoin wallet running on a seperate, secure computer.

It is recommended to keep the first three components on seperate servers to ensure maximum security. The fourth component should be on a secure computer that should only be connected to the internet when sending Bitcoin back to the hot wallet.

Once you have these four components, you can proceed to setting up the web application.

Web Application Folder Structure
---------------------
In order to set up the web app, clone this project on your **web server** using Git or download this project in a ZIP file and unzip it on your web server.

The WLOXE project is divided into six folders:

- /backstage2: The administrative back-end for the web app. Can be set up using its own configuration file. 
- /cfg: Contains the web app's main configuration file.
- /cron: Contains PHP files to be run by cron jobs.
- /htdocs: This should be the web folder (i.e. port 80 of your domain should point here).
- /lib: A set of libraries used by the web app.
- /shared2: A set of libraries used by both the web app and *backstage2*.

Again, /htdocs is the application's web folder. However, you will need web access to /backstage2 in order to access the application's back-end. A good way to do it would be by creating a virtual server to access backstage2/ through a different port (for example, as http://yourserver.com:12345).

Once we have set up web access to the appropriate folders, we can configure the web application using the main configuration file.

The Configuration File
------------
The main configuration file, cfg.php (found in the /cfg folder) is used to tell the app's front end where it can find all the different components of the application. It's also where you can specify your preferences for the way the app will function. The following parameters can be configured:

- **$CFG->baseurl:** The URL for the web app in the browser, such as http://www.yourdomain.com/. Make sure tu include the trailing slash at the end.
- **$CFG->dbhost:** The address of the database server.
- **$CFG->dbname:** The database name.
- **$CFG->dbuser:** The database username.
- **$CFG->dbpass:** The password for the database. Use something secure!
- **$CFG->dirroot:** The path to the /htdocs folder on the server, such as /var/www/htdocs/. Makesure to include the trailing slash.
- **$CFG->support_email:** Users will received automated support emails from this address.
- **$CFG->accounts_email:** Users will receive automated emails related to address features from this emails.
- **$CFG->exchange_name:** This is the name of your exchange on the front end app. This value will be used in the website's text and in automated emails sent by the system to users.
- **$CFG->bitcoin_username:** The username for the bitcoind server.
- **$CFG->bitcoin_accountname:** The accountname used by the bitcoind server (can be the same as username).
- **$CFG->bitcoin_passphrase:** This will be used as both the password for the bitcoind server as well as the passphrase to encrypt/decrypt the wallet file.
- **$CFG->bitcoin_host:** The hostname or ip address of the bitcoind server. Use "localhost" if it's on the same server as the web server.
- **$CFG->bitcoin_port:** The port at which the bitcoind server can be accessed (8332 by default).
- **$CFG->bitcoin_protocol:** The protocol for the bitcoind server (*http* by default).
- **$CFG->bitcoin_reserve_ratio:** The percentage, from zero to one, of Bitcoin that will be kept in the hot wallet (for example, 0.3 will cause WLOXE to keep 30% of the Bitcoin in the system in the hot wallet and send the rest to the warm/cold wallet).
- **$CFG->bitcoin_reserve_min:** The minimum amount of Bitcoin that must be received for WLOXE to send the reserve residual to the warm/cold wallet. A value of 1 = 1BTC received. The purpose of this variable is to reduce the amount of network fees incurred for moving Bitcoin between the hot and cold wallets.
- **$CFG->bitcoin_sending_fee:** The default fee to be used when sending Bitcoin (0.0001 by default). Specifying this value makes it easier to calculate how much Bitcoin will be sent when making transactions.
- **$CFG->authy_api_key:** By default, WLOXE uses Authy for two-factor authentication. This value is the API key that will be used by Authy to make requests. You can sign up for an API key at authy.com.

Setting Up The Back-End
-------------------
As mentioned above, WLOXE comes with its own back-end administrative program, *backstage2*, which is really a seperate project developed over a few years. For more information about this project check out [the backstage2 Github repository](https://github.com/mbassan/backstage2).
