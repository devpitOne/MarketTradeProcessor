'use strict';
$(document).ready(function () {
    $('.minimiser').click(function (e) {
        var nodeList = $(this).siblings();
        $(nodeList).slideToggle(99);
        //TODO: Use a proper selector, firstChild limits structure
        if ($(this.firstChild).attr("src").search("minus") > 0) {
            $(this.firstChild).attr("src", "/images/plus.png");
        }
        else
            $(this.firstChild).attr("src", "/images/minus.png");
    });

    $('#vmap').vectorMap({map: 'world_en'});

    var socket = io();
    socket.on("listResponse", function (msg) {
        //Table display
        $('#listDisplay').empty();
        var newRow = $('<tr>');
        for (var prop in msg[0]) {
            $(newRow).append($('<th>').text(AddSpaces(prop)));
        }
        $('#listDisplay').append(newRow);
        for (var iter = 0; iter < msg.length; iter += 1) {
            var newRow = $('<tr>');
            for (var prop in msg[iter]) {
                $(newRow).append($('<td>').text(msg[iter][prop]));
            }
            $('#listDisplay').append(newRow);
        }
    });
    socket.on("graphResponse", function (msg) {
        var labels = [];
        var data = [];
        for (var iter = 0; iter < msg.length; iter += 1) {
            labels.push(msg[iter].month);
            data.push(msg[iter].amounts);
        }
        //Chart display
        var data = {
            labels: labels,
            datasets: [
                {
                    label: "My First dataset",
                    fillColor: "rgba(0,220,220,0.5)",
                    strokeColor: "rgba(20,220,220,0.8)",
                    highlightFill: "rgba(20,220,220,0.75)",
                    highlightStroke: "rgba(0,220,220,1)",
                    data: data
                }
            ]
        };
        var options = {};
        var ctx = $("#graphDisplay").get(0).getContext("2d");
        var myNewChart = new Chart(ctx).Bar(data, options);
    });
    socket.on("mapResponse", function (msg) {        
        $('#vmap').unbind('labelShow.jqvmap');
        var countryList = msg;
        $('#vmap').bind('labelShow.jqvmap',
                function (event, label, code)
                {
                    if (countryList[code.toUpperCase()]) {
                        var originalLabel = label.text();
                        label.text(originalLabel + ": " + countryList[code.toUpperCase()]);
                    }
                }
        );
    });

    //Initial requests to get us loaded
    socket.emit("listRequest", "");
    socket.emit("graphRequest", "");
    socket.emit("mapRequest", "");
});

function AddSpaces(string) {
    return CapitalLetter(string.replace(/([a-z])([A-Z])/g, '$1 $2'));
}

function CapitalLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}
