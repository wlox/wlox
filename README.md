WLOXE - Open Source Currency Exchange
=========
WLOXE is an open source alternative currency exchange, created to facilitate the exchange of alternative currencies (*alt-currencies*) for multiple fiat currencies. At this point, the only alt-currency supported is **Bitcoin**. It is not difficult, however, to adapt this project for the use of other alt-currencies.

The purpose of this document is to walk you throught the process of a typical setup using Bitcoin. We will also cover basic (i.e. logo) branding of the exchange.

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
