jQuery(document).ready(function() {
    jQuery('#mycarousel').jcarousel({
        vertical: true,
        start: 1,
        size: 12,
        scroll: 5,
        visible: 8,
        wrap: "circular"
    });
});

function showAction(itemType) {
    var elements = C('cheatingItemAction');
    for (var j=0, l=elements.length; j<l; j++) {
        var t = O(itemType + '-Act');
        if (t.id === elements[j].id) {
            elements[j].style.display = 'block';}
        else {elements[j].style.display = 'none';}
    }
}

function showDescription(itemType) {
    var elements = C('cheatingItemDesc');
    var t = O(itemType + '-Desc');
    for (var j=0, l=elements.length; j<l; j++) {
        if (t.id === elements[j].id) {
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

