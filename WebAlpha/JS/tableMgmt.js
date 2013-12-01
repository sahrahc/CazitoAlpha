jQuery(document).ready(function() {
    jQuery('#mycarousel').jcarousel({
        vertical: true,
        start: 1,
        size: 13,
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
            Submit: function() {
                $(this).dialog("close");
                $( "#dialog-modal-follow-up" ).dialog("open");
            }
        }
    });
    $( "#dialog-modal-follow-up" ).dialog({
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

