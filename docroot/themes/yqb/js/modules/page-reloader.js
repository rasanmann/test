var Drupal = Drupal || {};

var PageReloader = (function ($, Drupal, Bootstrap) {

    var self = {};

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Page Reloader constructed');

        // Initialize things
        
        if($('.view-departures').length || $('.view-arrivals').length){
            self.initPageReloader();
        }

        return self;
    };

    self.destruct = function () {
        console.log('Page Reloader destructed');

        // Clean up and uninitialize things

    };
    
    self.initPageReloader = function(){
        console.log('Page Reloader inited');
        
        var timer = 5*60; // 5 minutes
        
        setTimeout(function(){
            window.location.href = window.location.href;
        }, timer*1000);
    };

    
    // Return class
    return self.construct();
})(window.jQuery, window.Drupal, window.Drupal.bootstrap);