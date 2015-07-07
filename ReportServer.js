//Order Dependent loads. Do not re-arrange
var express = require('express');
var app = express();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var db = require('mysql');
var config = require("./config/config.json");

//TODO: Prepared statements with the mysql module. No dynamic queries yet.
var pool = db.createPool(config.sqlConn);
var sqlConn = db.createConnection(config.sqlConn);
var listQuery = 'select transactionId, userId, currencyFrom, currencyTo, amountSell, amountBuy, rate, timePlaced, originatingCountry from transaction Order By timePlaced desc LIMIT 10';
var graphQuery = "select CONCAT(currencyFrom,'/',currencyTo) as 'month',COUNT(1) as 'amounts' FROM `mtp`.`transaction` GROUP BY currencyFrom, currencyTo";
var mapQuery = "SELECT originatingCountry, COUNT(1) as 'amount' FROM transaction GROUP BY originatingCountry";
var queryCallback = function (err, rows, fields) {
    if (!err)
        //console.log('Result: ', rows);
        return rows;
    else {
        return "error";
        console.log(err);
    }
};

//io Handling
io.on('connection', function (socket) {
    console.log('user connected');
    
    socket.on('listRequest', function(){ListQuery()});
    socket.on('graphRequest', function () {GraphQuery()});
    socket.on('mapRequest', function () {MapQuery()});
    socket.on('disconnect', function () {
        console.log('user disconnected');
    });
});

//HTTP Listener
app.use("/", express.static(__dirname + "/public"));
app.get('/', function (req, res) {
    res.sendFile(__dirname + '/public/Reporting.html');
});
//Inseucre and likely to conflict with htaccess. Deployment testing needed
http.listen(config.nodePort, function () {
    console.log('listening on *:' + config.nodePort);
});
RealTimeEmit(); //Start the realtime broadcast

//Service Functions
function RealTimeEmit() {
    setTimeout(
            function () {
                ListQuery();
                GraphQuery();
                MapQuery();
                console.log("Update Broadcast");
                RealTimeEmit();
            }
    , 10000);
}
//TODO: These functions are not DRY. Figure out how to pass in the msgTypes and queries
function ListQuery() {
    pool.getConnection(function (err, connection) {
            if (err) {
                connection.release();
                res.json({"code": 100, "status": "Error in connection database"});
                return;
            }
            connection.query(listQuery, function (err, rows, fields) {
                connection.release();
                if (!err) {
                    var msgType = "listResponse";
                    io.emit(msgType, rows);
                    //console.log('Result: ', queryResult);
                }
                else {
                    io.emit(msgType, "error");
                    console.log('Error: ', err);
                }
            });
        });
}
function GraphQuery() {
    pool.getConnection(function (err, connection) {
            if (err) {
                connection.release();
                res.json({"code": 100, "status": "Error in connection database"});
                return;
            }
            connection.query(graphQuery, function (err, rows, fields) {
                connection.release();
                if (!err) {
                    var msgType = "graphResponse";
                    io.emit(msgType, rows);
                    //console.log('Result: ', queryResult);
                }
                else {
                    io.emit(msgType, "error");
                    console.log('Error: ', err);
                }
            });
        });
}
function MapQuery() {
    pool.getConnection(function (err, connection) {
            if (err) {
                connection.release();
                res.json({"code": 100, "status": "Error in connection database"});
                return;
            }
            connection.query(mapQuery, function (err, rows, fields) {
                connection.release();
                if (!err) {
                    var msgType = "mapResponse";
                    var result = {};
                    for (var iter = 0; iter < rows.length; iter += 1) {
                        result[rows[iter].originatingCountry] = rows[iter].amount;
                    }
                    io.emit(msgType, result);
                    //console.log('Result: ', result);
                }
                else {
                    io.emit(msgType, "error");
                    console.log('Error: ', err);
                }
            });
        });
}

