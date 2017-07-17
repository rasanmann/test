var Drupal = Drupal || {};

var Publications = (function ($, Drupal, Bootstrap) {

    var self = {};

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Publications constructed');

        // Initialize things

        return self;
    };

    self.destruct = function () {
        console.log('Publications destructed');

        // Clean up and uninitialize things

    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.index = function () {
        console.log('Index page initialized');

        var now = new Date();

        // Find which publications rows are older than 5 years
        $('table tr').each(function () {
            var $row = $(this);
            var created = new Date($row.data('created'));

            if (Math.floor((now.getTime() - created.getTime()) / (365 * 24 * 3600 * 1000)) > 5) {
                $row.hide();
            }
        });

        // Check if we need to put a "Show more" link under each table category
        $('table').each(function () {
            var $table = $(this);
            var $rows = $table.find('tr');

            if ($rows.filter(':visible').length === 0) {
                // Rows are all older than five years, revert and show everything
                $rows.show();
            } else if ($rows.filter(':hidden').length > 0) {
                // We have at least one hidden row, show it
                var source   = $('#more-template').html();
                var template = Handlebars.compile(source);
                var html = template({});

                var $more = $table.closest('.table-responsive').append(html);
                    $more.find('.more-link a').on('click', onMoreClick);
            }
        });
    };

    /** -----------------------------
     * Events
     --------------------------------- */

    /**
     * Triggered when the "Show more" button is clicked
     * @param ev
     */
    var onMoreClick = function(ev) {
        ev.preventDefault();

        $(this).closest('.table-responsive').find('table').find('tr').show();
        $(this).hide();
    };

    // Return class
    return self.construct();
});