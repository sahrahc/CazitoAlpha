jQuery(document).ready(function() {
    // cards carousel for tucker's table groove
        jQuery('.cardsCarousel').jcarousel({
	vertical: false,
	start: 1,
	size: 13,
	scroll: 7,
	visible: 8
    });
});

// global variables with namespace
var cazito = function() {
    var o = {};
    var globals = {};
    
    var setGlobalVar = function(name, value) {
	globals[name] = value;
    };
    
    var getGlobalVar = function(name) {
	if (globals.hasOwnProperty(name)) {
	    return globals[name];
	}
	else {
	    return null;
	}
    };
    
    o.setGlobalVar = setGlobalVar;
    o.getGlobalVar = getGlobalVar;
    return o;
}();

function showCardSelector() {
    $('#dialog-card-selector').dialog('open');
}

function addToSelected(card) {
    // TODO: make 'Add button' available
    $("#selectedCards").append("<img class='cImg' src='../../../images/PokerCard_" + card
	    + "_small.png' title='" + card + "' alt='" + card + "' />");
}

