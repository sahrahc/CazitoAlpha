/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function O(obj) {
    if (typeof obj == 'object') return obj;
    else return document.getElementById(obj);
}
function S(obj) {
    return O(obj).style;
}
function getSize(pixel) {
    return Number(pixel.substr(0, pixel.length - 2));
}

function enterTable(){
    $.cookies.set("tableValue", O('tableSizeId').value, {expires :1 , path: '/'})
    window.location = "PlayGame.php";
    return false;
}
