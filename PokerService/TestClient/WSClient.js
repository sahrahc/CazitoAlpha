/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function WSClient() {

}

WSClient.call = function(urlParam, method, obj, callback) {
    var param = "method=" + method + "&param=" + JSON.stringify(obj);
    // Constructor function
    function GameSession(data) {
    this.data = data;
    }

    $.ajax({
    type: "GET",
    url: urlParam,
    data: param,
    contentType: "application/json; charset=utf-8",
    dataType: "json",
    success: function (req) {
        // already parsed
        // var rval = $.parseJSON(req);
        // callback(rval);
        callback(req);
    // return new GameSession(rval);
    },
    error: function (xhr) {
        alert(xhr.responseText);
        return;
    }
    });
}


function runTest() {
    WSClient.call("http://localhost//PokerService//PokerPlayerService.php", "addUserToGamingSession", 
        {playerId:-1,isPractice:0}, 
        addSession_callback);   
}

function addSession_callback(r) {
    alert('The gameSessionId is ' + r.gameSessionId + " and the gameInstanceId = " 
    + r.gameInstanceId + " and the assigned playerId is " + r.assignedPlayerId);
}
