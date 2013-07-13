constServiceUrl = "http://localhost//PokerService//PokerPlayerService.php";
/********************************************************************************************/
function WSClient() {

}

WSClient.call = function(method, obj, callback) {
    var param = "method=" + method + "&param=" + JSON.stringify(obj);

    $.ajax({
        type: "GET",
        url: constServiceUrl,
        data: param,
        contentType: "application/json; charset=utf-8",
        dataType: "json",
        success: function (req) {
            // already parsed
            // var rval = $.parseJSON(req);
            if (callback !== null) {
                callback(req);
            }
        },
        error: function (xhr) {
            alert(xhr.responseText);
        }
    });
};

