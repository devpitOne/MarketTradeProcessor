/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
//Order Dependent loads. Do not re-arrange
var express = require('express');
var app = express();
var http = require('http').Server(app);
var io = require('socket.io')(http);
var db = require('mysql');
var config = require("./config.json");

//TODO: Prepared statements with the mysql module. No dynamic queries yet.
var sqlConn = db.createConnection(config.sqlConn);
var listQuery = 'select transactionId, userId, currencyFrom, currencyTo, amountSell, amountBuy, rate, timePlaced, originatingCountry from transaction LIMIT 10';
var graphQuery = "select CONCAT(Month(timePlaced),'-',YEAR(timePlaced)) as 'month', count(1) as 'amounts' from transaction Group By CONCAT(Month(timePlaced),'-',YEAR(timePlaced))";
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
    //TODO: Callback functions are not DRY. Figure out how to pass both sources to callback method so it can be made into a seperate function
    socket.on('listRequest', function () {
        sqlConn.query(listQuery, function (err, rows, fields) {
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
    socket.on('graphRequest', function () {
        sqlConn.query(graphQuery, function (err, rows, fields) {
            var msgType = "graphResponse";
            if (!err) {
                io.emit(msgType, rows);
                //console.log('Result: ', queryResult);
            }
            else {
                io.emit(msgType, "error");
                console.log('Error: ', err);
            }
        });
    });
    socket.on('mapRequest', function () {
        sqlConn.query(mapQuery, function (err, rows, fields) {
            var msgType = "mapResponse";
            var result = {};
            for (var iter = 0; iter < rows.length; iter += 1) {
                result[rows[iter].originatingCountry] = rows[iter].amount;
            }
            if (!err) {
                io.emit(msgType, result);
                //console.log('Result: ', queryResult);
            }
            else {
                io.emit(msgType, "error");
                console.log('Error: ', err);
            }
        });
    });
    socket.on('disconnect', function () {
        console.log('user disconnected');
    });
});

//HTTP Listener
app.use("/", express.static(__dirname + "/public"));
app.get('/', function (req, res) {
    res.sendFile(__dirname + '/public/Reporting.html');
});
http.listen(3000, function () {
    console.log('listening on *:3000');
});
RealTimeEmit(); //Start the realtime broadcast

//Service Functions
function RealTimeEmit() {
    setTimeout(
            function () {
                sqlConn.query(listQuery, function (err, rows, fields) {                    
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
                sqlConn.query(graphQuery, function (err, rows, fields) {
                    var msgType = "graphResponse";
                    if (!err) {
                        io.emit(msgType, rows);
                        //console.log('Result: ', queryResult);
                    }
                    else {
                        io.emit(msgType, "error");
                        console.log('Error: ', err);
                    }
                });
                sqlConn.query(mapQuery, function (err, rows, fields) {
                    var msgType = "mapResponse";
                    var result = {};
                    for (var iter = 0; iter < rows.length; iter += 1) {
                        result[rows[iter].originatingCountry] = rows[iter].amount;
                    }
                    if (!err) {
                        io.emit(msgType, result);
                        //console.log('Result: ', queryResult);
                    }
                    else {
                        io.emit(msgType, "error");
                        console.log('Error: ', err);
                    }
                });
                console.log("Update Broadcast");
                RealTimeEmit();
            }
    , 10000);
}

