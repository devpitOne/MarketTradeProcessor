Licenses:
Chart.js - http://www.chartjs.org/ Copyright 2015 Nick Downie - Released under the MIT license
jquery - http://jquery.com/
jqvmap - http://jqvmap.com/
ElephantIO - http://elephant.io/ - Released under the MIT License

Requirements:
Node.js
PHP 5.6 - openssl and mysqli extensions
MySQL
The SeleniumTests require Selenium http://docs.seleniumhq.org/

Updates:
As I go along the Db will receive updates. In the interests of compatibility I'll provide a new DB Creation script each time but also an Update script for those who want to keep their DB and incorporate the changes.

If you are installing for the first time ignore any Update scripts, all you need is in Db_Creation.sql

0.1.1 Currency Update
currency_Update1 is available, adding foreign key references to transactions. This will ensure currencies come from the approved list.

0.1.2 True Real Time
With a little help from elephantIO the MTProcessor now issues update commands to Node Server. When a transaction is processed the report page will automatically update.
I've deprecated the update cycle, it will be removed in future updates.

Installation:
Is likely to have problems as installations do. Do not be overly concerned. Any failures are on my part, not yours.

The DB Creation Script is under DB_Script. Run that first against your mysql instance.

The web config is the config.json file under the root. Alter it to set your DB Connection. 

Deploy the project's main, config and public folder to your web server or run with an IDE. There's a htaccess for apache to keep people out of the config folder. Use appropriate controls if not using apache.

ReportServer.js is the Node.js server file. You'll need to run with node. 

Usage:
The three interactive webpages are 
-AddUser.html to expedite adding new user ids to the database
-TransactionTest.html which interacts with the POST endpoint 
-Reporting.html which is served by Node.js. It has three fairly static displays. The List shows the most recent transactions, the graph charts transfers by currency pair and the map can be hovered over to see how many transfers by country

To access reporting.html start ReportServer.js with node and browse to port 3000 eg. localhost:3000 locally

The POST endpoint is at MTProcessor.php, there's referrer checking enforced. It can be turned off in the config. Don't rely on this too much as referrers can be spoofed.
If the userid given does not exist in the database it will return an error.

Nice-to-haves
Framework for html pages, allowing unified header. Possible in node.js?
SSL enforced. Not possible in my current environment due to broken certs.
Authentication. Web exposed POST endpoints will want verification like realex uses.
A config that allows comments. JSON is limited that way.
Foreign Keyed database with dedicated reporting tables. It would take the load off the queries.
Unit Test Battery. Transaction Test is too high level currently.

Default Message for reference
userId": "134256", 
"currencyFrom": "EUR", 
"currencyTo": "GBP", 
"amountSell": 1000, 
"amountBuy": 747.10, 
"rate": 0.7471, 
"timePlaced" : "24-JAN-15 10:27:44", 
"originatingCountry" : "FR"