//constServiceUrl = "//192.168.1.70//PokerService//PokerPlayerService.php";
constServiceUrl = "https://cazito.net//PokerService//PokerPlayerService.php";
/********************************************************************************************/
function WSClient() {

}

WSClient.call = function(method, obj, callback) {
    var param = "method=" + method + "&param=" + JSON.stringify(obj);

    $.ajax({
	type: "GET",
	url: constServiceUrl,
	data: param,
	//dataType: "json",
	//contentType: "application/json;charset=utf-8",
	//contentType: "application/x-www-form-urlencoded;charset=utf-8",
        success: function(respRaw) {
	    // already parsed
	    // var rval = $.parseJSON(req);
	    if (callback !== null) {
	        var resp = $.parseJSON(respRaw.substring(2));
		callback(resp);
	    }
	},
	error: function(resp, textStatus, errorThrown) {
	    alert(resp.responseText);
	},
    });
};

