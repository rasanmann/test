var Drupal = Drupal || {};

var Home = (function ($, Drupal, Bootstrap) {

    var self = {};

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Home constructed');

        // Initialize things

        return self;
    };

    self.destruct = function () {
        console.log('Home destructed');

        // Clean up and uninitialize things

    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.index = function () {
        console.log('Index page initialized');

        $('a.trigger-nav').on('click', App.findAndTriggerHeaderLink);
    };

    /** -----------------------------
     * Events
     --------------------------------- */

    // Return class
    return self.construct();
});