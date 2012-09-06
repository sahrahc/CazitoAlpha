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

$(function() {
    $( "#dialog:ui-dialog" ).dialog( "destroy" );
    $( "#dialog-modal" ).dialog({
        autoOpen:false,
        modal:true,
        buttons:  {
            "Practice Game": function() {
                startPracticeSession();
                $(this).dialog("close");
            },
            "Join a Table": function() {
                addUserToCasinoTable();
                $(this).dialog("close");
            }
        }
    });
});

