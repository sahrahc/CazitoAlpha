/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function O(obj) {
    if (typeof obj == 'object') return obj;
    else return document.getElementById(obj);
}

function cheatClubMarker(){
    alert("You clicked on Young Quick Draw CHarlie's Club Thumb!");
}

function showAction(curIndex) {
    for (j=1, l=O('cheatingItemTab').children.length; j<=l; j++) {
        var t = O('tabs-' + j + '-act');
        if (j == curIndex) {
            t.style.display = 'block';}
        else {t.style.display = 'none';}
    }
}

function showDescription(curIndex) {
    for (j=1, l=O('cheatingItemDescription').children.length; j<=l; j++) {
        var t = O('tabs-' + j);
        if (j == curIndex) {
            t.style.display = 'block';}
        else {t.style.display = 'none';}
    }
}
