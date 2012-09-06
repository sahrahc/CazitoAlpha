/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function O(obj) {
    if (typeof obj == 'object') return obj;
    else return document.getElementById(obj);
}

function showAction(itemType) {
    var elements = document.getElementsByClassName('cheatingItemAction');
    for (var j=0, l=elements.length; j<l; j++) {
        var t = O(itemType + '-Act');
        if (t.id == elements[j].id) {
            elements[j].style.display = 'block';}
        else {elements[j].style.display = 'none';}
    }
}

function showDescription(itemType) {
    var elements = document.getElementsByClassName('cheatingItemDesc');
    var t = O(itemType + '-Desc');
    for (var j=0, l=elements.length; j<l; j++) {
        if (t.id == elements[j].id) {
            elements[j].style.display = 'block';}
        else {elements[j].style.display = 'none';}
    }
}

function hideDescription(itemType) {
    O(itemType + '-Desc').style.display = 'none';
}

function hideAction(itemType) {
    O(itemType + '-Act').style.display = 'none';
}

