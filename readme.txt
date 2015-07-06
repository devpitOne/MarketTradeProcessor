Readme

Licenses
Chart.js - http://www.chartjs.org/ Copyright 2015 Nick Downie - Released under the MIT license
jquery - http://jquery.com/
jqvmap - http://jqvmap.com/

Requirements:
Node.js
PHP 5.6
MySQL
The SeleniumTests require Selenium http://docs.seleniumhq.org/

Installation:
Is likely to be fraught with worry as all installations are. Do not be overly concerned. Any failures are on my part, not yours.

The DB Creation Script is under DB_Script. Run that first.

The web config is the config.json file under the root. Alter it to set your DB Connection. 

Deploy the project to your web server or run with an IDE. There's a htaccess for apache to keep people in the public folder.

ReportServer.js is the Node.js file. You'll need to run with node.

The two interactive webpages are TransactionTest.html which interacts with the POST endpoint and Reporting.html which is served by Node.js
The POST endpoint is at MTProcessor.php, the only security right now is checking the referrer, which can be easily removed or spoofed.


Build Log


Default Message for reference
userId": "134256", 
"currencyFrom": "EUR", 
"currencyTo": "GBP", 
"amountSell": 1000, 
"amountBuy": 747.10, 
"rate": 0.7471, 
"timePlaced" : "24-JAN-15 10:27:44", 
"originatingCountry" : "FR"