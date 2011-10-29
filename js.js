$(function(){

    var dialog = new ModalDialog ("#missing");

    $('#missing').hide().css({
        position: 'absolute',
        fontSize: 'smaller',
        width: '50%',
        backgroundColor: 'white',
        border: "solid 4px #666666",
        borderRadius: '2em',
        "-moz-border-radius": "2em",
        "-webkit-border-radius": "2em",
        padding: "0.5em"
    });
    $('#missing_link').click(function(){
        dialog.show();
        return false;
    });

});


// Simple modal dialog. Requires jQuery. Treats IE 6 as special, and uses an iframe layer for it to inhibit
// bleed-through of things like select elements.
// M. A. Sridhar, April 3, 2008
function ModalDialog (boxSpec) {
    // Configuration constants
    var OVERLAY_ELEMENT_ID = "s--modalbox-overlay";
    var OVERLAY_Z_INDEX    = 20;
    var OVERLAY_OPACITY    = 0.8;
    var _boxSpec = boxSpec;
    var self = this;
    function getViewport() {
        return {
            x: window.pageXOffset || document.documentElement && document.documentElement.scrollLeft || document.body.scrollLeft,
            y: window.pageYOffset || document.documentElement && document.documentElement.scrollTop || document.body.scrollTop,
            w: window.innerWidth  || document.documentElement && document.documentElement.clientWidth || document.body.clientWidth,
            h: window.innerHeight || document.documentElement && document.documentElement.clientHeight || document.body.clientHeight
        };
    };
    var styleStr = "top: 0; left: 0; z-index: " + OVERLAY_Z_INDEX + "; display: none;background-color: #000;";
    var vp = getViewport();
    var overlayHTML = null;
    var sizeSpec = "width: 100%; height: 100%;";
    if (jQuery.browser.msie && (jQuery.browser.version < 7)) {
        sizeSpec = "width:" + (vp.x + vp.w) + "px;height:" +  Math.max(vp.y + vp.h,jQuery(document.body).height()) + "px;";
        styleStr += "filter: alpha(opacity=" + ( (100*OVERLAY_OPACITY)) + ");position: absolute;" + sizeSpec;
    } else {
        styleStr += "position:fixed; opacity: " + OVERLAY_OPACITY + ";" + sizeSpec;
    }
    overlayHTML = "<div src='javascript:false' id='" + OVERLAY_ELEMENT_ID + "' style='" + styleStr + "'></div>";
    jQuery("body").append (overlayHTML);
    var overlayElt = jQuery("#" + OVERLAY_ELEMENT_ID);

    this.show =  function () {
        var vp = getViewport();
        overlayElt.css ({display: "block"});
        var box = $(_boxSpec);
        box.css ({display: "block", zIndex: OVERLAY_Z_INDEX+1, top: (vp.y + (vp.h - box.height())/2) + "px",  left: (vp.x + (vp.w - box.width())/2) + "px"});
        box.html( box.html() + '<img src="/close.png" alt="Close">' );
        $(document).keyup(function(e) {
            self.hide();
            return false;
        });
        box.find('img').click(function() {
            self.hide();
        });
    };

    this.hide = function () {
        overlayElt.css ({display: "none"});
        $(_boxSpec).css ({display: "none"});
        $(document).unbind('keyup');
    };
};



