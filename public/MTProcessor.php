<?php

//ini_set('display_errors', 1);
error_reporting(E_ALL ^ E_WARNING); //Turn off when testing, keeps warnings from being spewed at the requestors
use ElephantIO\Client,
    ElephantIO\Engine\SocketIO\Version1X;
//TODO: Refactoring the framework should allow me to add an autoloader.
include 'ElephantIO\Client.php';
include 'ElephantIO\EngineInterface.php';
include 'ElephantIO\AbstractPayload.php';
include 'ElephantIO\Engine\SocketIO\Session.php';
include 'ElephantIO\Engine\AbstractSocketIO.php';
include 'ElephantIO\Engine\SocketIO\Version1X.php';
include 'ElephantIO\Payload\Encoder.php';
include 'ElephantIO\Payload\Decoder.php';
include 'ElephantIO\Exception\ServerConnectionFailureException.php';

$config = json_decode(file_get_contents('../config/config.json'), true);

if ($config["domainRestricted"] == true) {
    if (array_key_exists('HTTP_REFERER', filter_input_array(INPUT_SERVER)) && strpos(filter_input(INPUT_SERVER, 'HTTP_REFERER'), $config["domain"])) {
        ProcessMessage($config);
    } else {
        exit;
    }
} else
    ProcessMessage($config);

//The main process logic for messages. Passing the config is annoying but I don't like globals
function ProcessMessage($config) {
    //Create connection
    $servername = $config["sqlConn"]["host"] . ":" . $config["sqlConn"]["port"];
    $username = $config["sqlConn"]["user"];
    $password = $config["sqlConn"]["password"];
    $database = $config["sqlConn"]["database"];
    $conn = new mysqli($servername, $username, $password, $database);
    //Check connection
    if ($conn->connect_error) {
        ReturnError("I failed to connect. Please contact the admin.");
        die("Connection failed: " . $conn->connect_error);
    }
    if (filter_input(INPUT_POST, "type") && filter_input(INPUT_POST, "type") == "newUser") {
        if (filter_input(INPUT_POST, "userId") == null) {
            ReturnError("Invalid Request. All fields must be filled.");
        }
        $insertStmt = $conn->prepare('INSERT INTO user (userId, username) VALUES (?, "TestUser")');
        $insertStmt->bind_param("i", $userid);
        $userid = $conn->real_escape_string(filter_input(INPUT_POST, "userId"));
        $success = $insertStmt->execute();
        $conn->close();
        CompleteInsert($success, $insertStmt->error, "User already exists.");
    } else {
        //Basic validation. Improve to spare the sql server
        if (filter_input(INPUT_POST, "userId") == null || filter_input(INPUT_POST, "currencyFrom") == null || filter_input(INPUT_POST, "currencyTo") == null || filter_input(INPUT_POST, "amountSell") == null || filter_input(INPUT_POST, "amountBuy") == null || filter_input(INPUT_POST, "rate") == null || filter_input(INPUT_POST, "timePlaced") == null || filter_input(INPUT_POST, "originatingCountry") == null) {
            ReturnError("Invalid Request. All fields must be filled.");
        }
        $checkStmt = $conn->prepare("SELECT lastRequest, throttleLimit FROM user WHERE userID = ?");
        $minute = 60; //seconds
        $minuteLimit = $config["requestLimit"];
        $checkStmt->bind_param("i", $userid);
        $userid = $conn->real_escape_string(filter_input(INPUT_POST, "userId"));
        $checkStmt->execute();
        $checkStmt->bind_result($lastRequest, $throttleLimit);
        $checkStmt->store_result();
        $userFound = $checkStmt->num_rows;
        $checkStmt->fetch();
        if ($userFound != 1) { //There must be exactly one user matching the user id
            ReturnError("User not found. Please provide a valid user.");
        }
        $checkStmt->close();

        date_default_timezone_set('UTC');
        $timeNow = time();
        $lastApiDiff = $timeNow - strtotime($lastRequest);
        if (is_null($minuteLimit)) {
            $newMinuteThrottle = 0;
        } else {
            $newMinuteThrottle = $throttleLimit - $lastApiDiff;
            $newMinuteThrottle = $newMinuteThrottle > 0 ? $newMinuteThrottle : 0;
            $newMinuteThrottle += $minute / $minuteLimit;
            $minuteHitsRemaining = floor(( $minute - $newMinuteThrottle ) * $minuteLimit / $minute);
            $minuteHitsRemaining = $minuteHitsRemaining >= 0 ? $minuteHitsRemaining : 0;
        }

        if ($newMinuteThrottle > $minute) {
            $wait = ceil($newMinuteThrottle - $minute);
            usleep(250000);
            ReturnError('The one-minute API limit of ' . $minuteLimit
                    . ' requests has been exceeded. Please wait ' . $wait . ' seconds before attempting again.');
            return;
        }
        //Should this go before or after the insert attempt? Before means failed transactions still throttle
        $userStmt = $conn->prepare("UPDATE user SET lastRequest = ?, throttleLimit = ? WHERE userID = ?");
        $dateNow = date('Y-m-d H:i:s');
        $userStmt->bind_param("sdi", $dateNow, $newMinuteThrottle, $userid);
        $userStmt->execute();
        if ($userStmt->execute() === TRUE) {
            
        } else {
            ReturnError("Throttling failed");
        }

        //Prepare request - I'm paranoid about SQL injection
        $insertStmt = $conn->prepare("INSERT INTO transaction (userId, currencyFrom, currencyTo, amountSell, amountBuy, rate, timePlaced, originatingCountry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("issdddss", $userid, $currencyFrom, $currencyTo, $amountSell, $amountBuy, $rate, $timePlaced, $originatingCountry);
        $currencyFrom = $conn->real_escape_string(filter_input(INPUT_POST, "currencyFrom"));
        $currencyTo = $conn->real_escape_string(filter_input(INPUT_POST, "currencyTo"));
        $amountSell = $conn->real_escape_string(filter_input(INPUT_POST, "amountSell"));
        $amountBuy = $conn->real_escape_string(filter_input(INPUT_POST, "amountBuy"));
        $rate = $conn->real_escape_string(filter_input(INPUT_POST, "rate"));
        $timezone = new DateTimeZone('UTC');
        $datetime = DateTime::createFromFormat('d-M-y H:i:s', $conn->real_escape_string(filter_input(INPUT_POST, "timePlaced")), $timezone);
        if ($datetime == false) {
            $datetime = DateTime::createFromFormat('d-m-Y H:i:s', $conn->real_escape_string(filter_input(INPUT_POST, "timePlaced")), $timezone);
        }
        $timePlaced = date_format($datetime, 'Y-m-d H:i:s');
        if ($timePlaced == null) {
            ReturnError("Invalid timePlaced. It must be in the datetime format 24-JAN-15 10:27:44");
        }
        //No real requirements on validation. Normally all fields would need to be vetted. The SQL Server won't do it until foreign keyed.
        $originatingCountry = $conn->real_escape_string(filter_input(INPUT_POST, "originatingCountry"));
        $success = $insertStmt->execute();
        $conn->close();
        CompleteInsert($success, $insertStmt->error, "Transaction already processed.");
    }
}

function CompleteInsert($success, $error, $duplicateMsg) {
    if ($success === TRUE) {
        ReturnSuccess("Transaction processed successfully");
    } else {
        if (stripos($error, "Duplicate") > -1) {
            ReturnError($duplicateMsg);
        } else {
            ReturnError("I suffered an error trying to process your transaction. SQL Error: " + $error);
        }
    }
}

function ReturnError($errorMsg) {
    $arr[] = array("error" => $errorMsg);
    echo json_encode($arr);
    exit;
}

function ReturnSuccess($errorMsg) {
    $arr[] = array("success" => $errorMsg);
    try {
    $client = new Client(new Version1X('http://localhost:3000'));
    $client->initialize();
    $client->emit('update', ['foo' => 'bar']);
    $client->close();
    }
    catch (\Exception $e) {
        ReturnError("Transaction succeeded but unable to contact reporting server.");
    }
    echo json_encode($arr);    
    exit;
}
