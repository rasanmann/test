var Console = (function() {
	
	var self = this;
	
	var init = function(ev) {
		if (window.console === undefined) {
			function _console() {
				return {
					log : function(v) {
						
					}
				}
			}
			
			window.console = new _console();
		}
	}
	
	/** ------------------------------
	 * Constructor
	 --------------------------------- */

	var construct = (function() {
		init()
	})();

	return self;
})();