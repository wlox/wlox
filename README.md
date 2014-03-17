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

Setting Up Web Application
---------------------
In order to set up the web app, clone this project on your **web server** using Git or download this project in a ZIP file and unzip it on your web server.

The WLOXE project is divided into six folders:

- /backstage2: The administrative back-end for the web app. 
- /cfg: Contains the web app's **configuration file**.
- /cron: Contains PHP files to be run by cron jobs.
- /htdocs: This should be the web folder (i.e. port 80 of your domain should point here).
- /lib: A set of libraries used by the web app.
- /shared2: A set of libraries used by both the web app and *backstage2*.
