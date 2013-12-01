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
    for (j=1, l=document.getElementsByClassName('cheatingItemAction').length; j<=l; j++) {
        var t = O('tabs-' + j + '-act');
        if (j == curIndex) {
            t.style.display = 'block';}
        else {t.style.display = 'none';}
    }
}

function showDescription(curIndex) {
    for (j=1, l=document.getElementsByClassName('cheatingItemDesc').length; j<=l; j++) {
        var t = O('tabs-' + j);
        if (j == curIndex) {
            t.style.display = 'block';}
        else {t.style.display = 'none';}
    }
}

function hideDescription(curIndex) {
    O('tabs-' + curIndex).style.display = 'none';
}

function hideAction(curIndex) {
    O('tabs-' + curIndex + '-act').style.display = 'none';
}
