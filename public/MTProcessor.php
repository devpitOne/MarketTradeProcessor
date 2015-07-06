<?php

//ini_set('display_errors', 1);

$config = json_decode(file_get_contents('../config.json'), true);
if ($config["domainRestricted"] == true) {
    if (array_key_exists('HTTP_REFERER', filter_input_array(INPUT_SERVER)) && strpos(filter_input(INPUT_SERVER, 'HTTP_REFERER'), $config["domain"])) {
        ProcessMessage($config);
    } else {
        exit;
    }
} else
    ProcessMessage($config);

//Passing the config is annoying but I don't like globals
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
        $insertStmt = $conn->prepare('INSERT INTO user (userId, username) VALUES (?, "TestUser")');
        $insertStmt->bind_param("i", $userid);
        $userid = $conn->real_escape_string(filter_input(INPUT_POST, "userId"));
        $success = $insertStmt->execute();        
        $conn->close();
        CompleteInsert($success, $insertStmt->error, "User already exists.");
    } else {
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
        //Should this go before or after the inesrt attempt? Before means failed transactions still throttle
        $userStmt = $conn->prepare("UPDATE user SET lastRequest = ?, throttleLimit = ? WHERE userID = ?");
        $dateNow = date('Y-m-d H:i:s');
        $userStmt->bind_param("sdi", $dateNow, $newMinuteThrottle, $userid);
        $userStmt->execute();
        if ($userStmt->execute() === TRUE) {
            
        } else {
            ReturnError("Throttling failed");
        }

        //Prepare request - There is no validation here. Also I'm paranoid about SQL injection
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
        $originatingCountry = $conn->real_escape_string(filter_input(INPUT_POST, "originatingCountry"));
        $success = $insertStmt->execute();        
        $conn->close();
        CompleteInsert($success, $insertStmt->error, "Transaction already processed.");
        
    }
}

function CompleteInsert($success, $error, $duplicateMsg){
    if ($success === TRUE) {
            ReturnSuccess("Transaction processed successfully");
        } else {            
            if (stripos($error, "Duplicate") > -1) {
                ReturnError($duplicateMsg);
            } else {
                ReturnError("I suffered an error trying to process your transaction. Please try again later.");
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
    echo json_encode($arr);
    exit;
}
