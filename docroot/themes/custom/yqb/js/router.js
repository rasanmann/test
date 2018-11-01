var Drupal = Drupal || {};

var Router = (function ($, Drupal, Bootstrap) {

    var self = {};

    var routes = [
        {filter: 'body.path-frontpage', cls: 'Home', method: 'index'},
        {filter: 'body.view-page-departures, body.view-page-arrivals', cls: 'Schedule', method: 'index'},
        {filter: 'body.view-page-destinations', cls: 'Destinations', method: 'index'},
        {filter: 'body.view-page-publications', cls: 'Publications', method: 'index'},
        {filter: 'body[id*="gouvernance"], body[id*="governance"]', cls: 'About', method: 'governance'},
        {filter: 'body.view-page-check_in', cls: 'Pages', method: 'checkIn'},
        {filter: 'body[id*="histoire"]', cls: 'Pages', method: 'history'},
        {filter: 'body[id*="quebec"]', cls: 'Pages', method: 'visit'},
        {filter: 'body[id*="app-mobile"]', cls: 'Pages', method: 'appMobile'},
        {filter: 'body[id*="mission-vision"]', cls: 'Pages', method: 'mission'},
        {filter: 'body[id*="vestiaire"], body[id*="coat-check"], body[id*="service-demballage"], body[id*="wrap-baggage"]', cls: 'Pages', method: 'storage'},
        {filter: 'body[id*="facture"], body[id*="bill"], body[id*="stationnement"], body[id*="parking"]', cls: 'Payments', method: 'index'},
        {filter: '.slider-gallery-thumbs', cls: 'SwiperSlider', method: 'initWithThumbs'}
    ];

    self.currentPage = null;
    self.currentController = null;

    /** ------------------------------
     * Constructor
     --------------------------------- */

    self.construct = function () {
        return self;
    };

    /** ------------------------------
     * Public methods
     --------------------------------- */

    self.init = function () {
        if (self.currentController && typeof self.currentController['destruct'] != 'undefined') {
            self.currentController.destruct();
            self.currentController = null;
        }

        // Loop through routes to call initializer
        for (var i = 0; i < routes.length; i++) {
            var route = routes[i];

            if ($(route.filter).length) {
                // Validate if classes and methods exist
                if (typeof window[route.cls] != 'undefined') {
                    // Create new page
                    self.currentController = new window[route.cls]($, Drupal, Bootstrap);

                    if (typeof self.currentController[route.method] != 'undefined') {
                        // Call specific method
                        self.currentController[route.method]();
                    } else {
                        console.log('Method ' + route.method + ' not found in Class ' + route.cls);
                    }

                    // No need to check further
                    //break;
                } else {
                    console.log('Class ' + route.cls + ' not found');
                }
            }
        }
    };

    return self.construct();
})(window.jQuery, window.Drupal, window.Drupal.bootstrap);