'use strict';
$(document).ready(function () {
    $('.minimiser').click(function (e) {
        var nodeList = $(this).siblings();
        $(nodeList).slideToggle(99);
        //TODO: Use a proper selector, firstChild limits structure
        if ($(this.firstChild).attr("src").search("minus") > 0) {
            $(this.firstChild).attr("src", "/public/images/plus.png");
        }
        else
            $(this.firstChild).attr("src", "/public/images/minus.png");
    });

    $('input').change(function () {
        $(this).removeClass("invalid");
    });
    $('.Hide').click(function () {
        $(this).slideUp();
    });
    $('#defaultTransaction').click(function (e) {
        SubmitDefault();
    });
    $('#submitForm').click(function (e) {
        e.preventDefault();
        $('#ErrorText').hide();
        $('#SuccessText').hide();
        ValidateTime();
        return false;
    });
    $('#userSubmit').click(function (e) {
        e.preventDefault();
        $('#ErrorText').hide();
        $('#SuccessText').hide();
        SubmitUser();
        return false;
    });
});

function LessThanTen(value) {
    return (value < 10 ? '0' : '') + value;
}

function Submit(data) {
    if (data === undefined) {
        var finalDate;
        if (/^\d{2}-\w+-\d{2}\W\d{2}:\d{2}:\d{2}$/.test($('#timePlaced').val()) === false) {
            var datetime = new Date($('#timePlaced').val());
            finalDate = LessThanTen(datetime.getDate()) + "-" + LessThanTen(datetime.getMonth() + 1) + "-" + datetime.getFullYear() + " " + LessThanTen(datetime.getHours()) + ":" + LessThanTen(datetime.getMinutes()) + ":" + LessThanTen(datetime.getSeconds());
        } else
            finalDate = $('#timePlaced').val();
        data = {"userId": $('#userid').val(), "currencyFrom": $('#currencyFrom').val(), "currencyTo": $('#currencyTo').val(), "amountSell": $('#amountSell').val(), "amountBuy": $('#amountBuy').val(), "rate": $('#rate').val(), "timePlaced": finalDate, "originatingCountry": $('#originatingCountry').val()};
    }
    $.ajax({
        type: "POST",
        url: "MTProcessor.php",
        data: data,
        datatype: "json",
        success: function (response) {
            var reply;
            try {
                reply = $.parseJSON(response);
            }
            catch (e) {
                reply = response;
                WriteError("The server gave me a broken response. Please contact the admin. Error:" + e.message);
            }
            reply = reply[0];
            if (reply["error"]) {
                WriteError(reply["error"]);
            }
            if (reply["success"]) {
                WriteSuccess(reply["success"]);
            }


        },
        fail: function (response) {
            WriteError("The server gave me a broken response. Please contact the admin.");
        }
    });
}

function SubmitDefault() {
    Submit({"userId": "134256", "currencyFrom": "EUR", "currencyTo": "GBP", "amountSell": 1000, "amountBuy": 747.10, "rate": 0.7471, "timePlaced": "24-JAN-15 10:27:44", "originatingCountry": "FR"});
}

function SubmitUser() {
    var data = {
        userId: $('#userUser').val(),
        type: "newUser"
    };
    if (Validate()) {
        Submit(data);
    }
}

function Validate() {
    if ($('input:invalid').length > 0) {
        $('input:invalid').addClass("invalid");
        WriteError("Sorry but some of your entries are invalid above.");
        return false;
    }
    else return true;
}

function ValidateTime() {
    if (isNaN(Date.parse($('#timePlaced').val()))) {
        var datetime = $('#timePlaced').val();
        if (/^\d{2}-\w+-\d{2}\W\d{2}:\d{2}:\d{2}$/.test($('#timePlaced').val()) === false) {
            $('#timePlaced').addClass("invalid");
            WriteError("Sorry, but your date time must be in the exact format of 24-JAN-15 10:27:44 or just a valid date eg. 2015-01-01 in order to be processed.");
            return;
        }
    }
    if (Validate()) {
        Submit();
    }
}

function WriteError(text) {
    if (text === undefined) {
        text = "There was an error. Please contact the admin.";
    }
    $('#ErrorText').text(text);
    $('#ErrorText').slideDown();
    $('#SuccessText').hide();
}

function WriteSuccess(text) {
    if (text === undefined) {
        text = "Transaction Successful.";
    }
    $('#SuccessText').text(text);
    $('#SuccessText').slideDown();
    $('#ErrorText').hide();
}