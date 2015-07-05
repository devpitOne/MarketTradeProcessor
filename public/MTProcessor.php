<?php

//ini_set('display_errors', 1);
if (array_key_exists('HTTP_REFERER', $_SERVER) && (strpos($_SERVER['HTTP_REFERER'], 'the-devpit.net') || strpos($_SERVER['HTTP_REFERER'], 'localhost'))) {
    //TODO: POST has been deprecated, refactor 
    $test = preg_grep("/^\d+$/", str_split($_POST["userId"]), PREG_GREP_INVERT);
    $servername = "127.0.0.1:2433";
    $username = "WebLink";
    $password = "starwolf_2spookY";
// Create connection
    $conn = new mysqli($servername, $username, $password);
// Check connection
    if ($conn->connect_error) {
        ReturnError("I failed to connect. Please contact the admin");
        die("Connection failed: " . $conn->connect_error);
    }
    $minute = 60;
    $minute_limit = 10; //request limit. Refactor to config
    $checkStmt = $conn->prepare("SELECT lastRequest, throttleLimit FROM mtp.user WHERE userID = ?");
    $checkStmt->bind_param("i", $userid);
    $userid = $conn->real_escape_string($_POST["userId"]);
    $checkStmt->execute();
    $checkStmt->bind_result($lastRequest, $throttleLimit);
    $checkStmt->fetch();
    $checkStmt->close();
    
    date_default_timezone_set('UTC');
    $timeNow = time();    
    $last_api_diff = $timeNow - strtotime($lastRequest);
    if (is_null($minute_limit)) {
        $new_minute_throttle = 0;
    } else {
        $new_minute_throttle = $throttleLimit - $last_api_diff;
        $new_minute_throttle = $new_minute_throttle > 0 ? $new_minute_throttle: 0;
        $new_minute_throttle += $minute / $minute_limit;
        $minute_hits_remaining = floor(( $minute - $new_minute_throttle ) * $minute_limit / $minute);
        $minute_hits_remaining = $minute_hits_remaining >= 0 ? $minute_hits_remaining : 0;
    }

    if ($new_minute_throttle > $minute) {
        $wait = ceil($new_minute_throttle - $minute);
        usleep(250000);
        ReturnError( 'The one-minute API limit of ' . $minute_limit
        . ' requests has been exceeded. Please wait ' . $wait . ' seconds before attempting again.' );
        return;
    }
    //Should this go before or after the inesrt attempt? Before means failed transactions still throttle
    $userStmt = $conn->prepare("UPDATE mtp.user SET lastRequest = ?, throttleLimit = ? WHERE userID = ?");        
    $dateNow = date('Y-m-d H:i:s');
    $userStmt->bind_param("sdi", $dateNow, $new_minute_throttle, $userid);
    $userStmt->execute();
    if ($userStmt->execute() === TRUE) {
        
    }
    else { 
        ReturnError("Throttling failed"); 
        return;//Should probably stop prcessing since if we can't throttle we're dead.          
    }

    //Prepare request - There is no validation here yet
    $insertStmt = $conn->prepare("INSERT INTO mtp.transaction (userId, currencyFrom, currencyTo, amountSell, amountBuy, rate, timePlaced, originatingCountry) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("issdddss", $userid, $currencyFrom, $currencyTo, $amountSell, $amountBuy, $rate, $timePlaced, $originatingCountry);
    $currencyFrom = $conn->real_escape_string($_POST["currencyFrom"]);
    $currencyTo = $conn->real_escape_string($_POST["currencyTo"]);
    $amountSell = $conn->real_escape_string($_POST["amountSell"]);
    $amountBuy = $conn->real_escape_string($_POST["amountBuy"]);
    $rate = $conn->real_escape_string($_POST["rate"]);    
    $timezone = new DateTimeZone('UTC');
    $datetime = DateTime::createFromFormat('d-M-y H:i:s', $conn->real_escape_string($_POST["timePlaced"]), $timezone);
    if ($datetime==false){
        $datetime = DateTime::createFromFormat('d-m-Y H:i:s', $conn->real_escape_string($_POST["timePlaced"]), $timezone);
    }
    $timePlaced = date_format($datetime, 'Y-m-d H:i:s');
    $originatingCountry = $conn->real_escape_string($_POST["originatingCountry"]);

    if ($insertStmt->execute() === TRUE) {
        ReturnSuccess("Transaction processed successfully");
    } else {
        if (stripos($conn->error, "Duplicate") > -1) {
            ReturnError("Transaction already processed.");
        } else {
            ReturnError("I suffered an error trying to process your transaction. Please try again later.");
        }
    }
    $insertStmt->close();
    $conn->close();
} else
    echo "<html><body>You are not authorized to access this functionality.</body></html>";

function ReturnError($errorMsg) {
    $arr[] = array("error" => $errorMsg);
    echo json_encode($arr);
}

function ReturnSuccess($errorMsg) {
    $arr[] = array("success" => $errorMsg);
    echo json_encode($arr);
}

?>
