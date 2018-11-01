
// Setup de console si inexistante
if (window.console === undefined) {
	function _console() {
		return {
			log : function(v) {
				
			}
		}
	}
	
	window.console = new _console();
}

var getScrollOffsets = function(w) {
	// Use the specified window or the current window if no argument 
	w = w || window;
	// This works for all browsers except IE versions 8 and before
	if (w.pageXOffset != null) return {x: w.pageXOffset, y:w.pageYOffset};
	// For IE (or any browser) in Standards mode
	var d = w.document;
	if (document.compatMode == "CSS1Compat")
		return {x:d.documentElement.scrollLeft, y:d.documentElement.scrollTop};

	// For browsers in Quirks mode
	return { x: d.body.scrollLeft, y: d.body.scrollTop };
}

var getInnerSize = function(w){
	// Use the specified window or the current window if no argument 
	w = w || window;

	if (w.innerWidth != undefined) {
		return { width : w.innerWidth, height : w.innerHeight };
	} else{
		var B = document.body,
			D = document.documentElement;
		return { width : Math.max(D.clientWidth, B.clientWidth),
			height : Math.max(D.clientHeight, B.clientHeight) };
	}
}

var currentBrowser = function() {
	
	$.returnVal = "";
	
	var browserUserAgent = navigator.userAgent;
	
	if (browserUserAgent.indexOf("Firefox") > -1) {
	
	    $.returnVal = { browser: "Firefox" };
	}
	
	else if (browserUserAgent.indexOf("Chrome") > -1) {
	
	    $.returnVal = { browser: "Chrome" };
	}
	
	else if (browserUserAgent.indexOf("Safari") > -1) {
	
	    $.returnVal = { browser: "Safari" };
	}
	
	else if (browserUserAgent.indexOf("MSIE") > -1) {
	
	    var splitUserAgent = browserUserAgent.split(";");
	
	    for (var val in splitUserAgent) {
	
	        if (splitUserAgent[val].match("MSIE")) {
	
	            var IEVersion = parseInt(splitUserAgent[val].substr(5, splitUserAgent[val].length));
	        }
	    }
	
	    $.returnVal = { browser: "IE", version: IEVersion };
	}
	
	else if (browserUserAgent.indexOf("Opera") > -1) {
	
	    $.returnVal = { browser: "Opera" };
	}
	
	else {
	    $.returnVal =
	     { browser: "other" };
	}
	
	return $.returnVal;
}


var getParameterByName = function(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
