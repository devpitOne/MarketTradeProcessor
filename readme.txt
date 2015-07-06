Licenses:
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

ReportServer.js is the Node.js server file. You'll need to run with node.

Usage:
The three interactive webpages are 
-AddUser.html expediates adding new user ids to the database
-TransactionTest.html which interacts with the POST endpoint 
-Reporting.html which is served by Node.js

The POST endpoint is at MTProcessor.php, there's referrer checking enforced. It can be turned off in the config.
If the userid given does not exist in the database it will return an error.

Nice-to-haves
Framework for html pages, allowing unified header. Possible in node.js?
SSL enforced. Not possible in my current environment.
Authentication. Web exposed POST endpoints will want verification like realex uses.
A config that allows comments. JSON is limited that way.

Default Message for reference
userId": "134256", 
"currencyFrom": "EUR", 
"currencyTo": "GBP", 
"amountSell": 1000, 
"amountBuy": 747.10, 
"rate": 0.7471, 
"timePlaced" : "24-JAN-15 10:27:44", 
"originatingCountry" : "FR"