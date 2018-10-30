var Drupal = Drupal || {};

var Schedule = (function ($, Drupal, Bootstrap) {

    var self = {};

    var $rows = null;
    var $filters = null;
    var $affix = null;
    var $regionContent = null;

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Schedule constructed');

        // Initialize things

        return self;
    };

    self.destruct = function () {
        console.log('Schedule destructed');

        // Clean up and uninitialize things

    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.index = function () {
        console.log('Index page initialized');

        $rows = $('.view-content .table tbody tr');

        $filters = $('input[name="airline"], input[name="origin"], input[name="destination"]');

        $filters.on('keyup change input propertychange', onRowsFilter);

        $affix = $('.region-sidebar-first');
        $regionContent = $('.region-content');

        $affix.affix({
            offset:{
                top:function () {
                    return (this.top = $regionContent.offset().top)
                },
                bottom:function () {
                    return (this.bottom = $(document).height() - ($regionContent.offset().top + $regionContent.height()))
                }
                /*
                top:($('#layout-header').outerHeight(true) + $('#layout-content > .heading').outerHeight(true) + $('#toolbar-item-administration-tray').outerHeight(true))
                ,
                bottom:function () {
                    return (this.bottom = $('.region-bottom').outerHeight(true) + $('.region-bottom-actions').outerHeight(true) + $('#layout-footer').outerHeight(true))
                }
                */
            }
        });

        $affix.on('affix.bs.affix affix-bottom.bs.affix', function(ev) {
            if ($(window).width() <= 900) return false;
            if ($regionContent.height() <= $affix.height()) return false;

            $(this).css({
                top:$('#toolbar-item-administration-tray').outerHeight(true) || 0,
                width:$(this).parent().width()
            });
        });

        $affix.on('affix-top.bs.affix', function(ev) {
            $(this).css({
                top:'auto',
                width:'auto'
            });
        });
    };

    /** -----------------------------
     * Events
     --------------------------------- */


    var onRowsFilter = function(ev) {
        // Remove styles
        $rows.attr('style', '');

        var filterSearch = {};

        $affix.affix('checkPosition');

        $filters.each(function() {
            var $input = $(this);
            var selector = null;

            switch($input.attr('name')) {
                case 'origin':
                case 'destination':
                    selector = 'view-view-table-column';
                    break;
                case 'airline':
                    selector = 'view-field-white-logo-table-column';
                    break;
            }

            if ($input.val() && selector) {
                filterSearch[$input.attr('name')] = {
                    selector:selector,
                    regexp:new RegExp(removeAccents($input.val()), 'gi'),
                };
            }
        });

        if (!$.isEmptyObject(filterSearch)) {
            $rows.each(function() {
                var $row = $(this);

                var passedAllFilters = null;

                for (var prop in filterSearch) {
                    var $cell = $row.find('td[headers="' + filterSearch[prop].selector + '"]');

                    if ($cell.length) {
                        var value = removeAccents($cell.text().trim() || (($cell.find('*:first').length) ? $cell.find('*:first').attr('alt').trim() : ''));
                        // console.log(filterSearch[prop].regexp);
                        if (!value || value.match(filterSearch[prop].regexp) && passedAllFilters !== false) {
                            passedAllFilters = true;
                        } else {
                            passedAllFilters = false;
                        }
                    }
                }

                if (passedAllFilters === true) {
                    $row.removeClass('hide');
                } else {
                    $row.addClass('hide');
                }
            });

            var lightColor = $rows.filter(':nth-of-type(odd):first').css('background-color');
            var darkColor = $rows.filter(':nth-of-type(even):first').css('background-color');

            $rows.filter(':visible:odd').css('background-color', darkColor);
            $rows.filter(':visible:even').css('background-color', lightColor);
        } else {
            $rows.removeClass('hide');
        }
        
        $('html,body').animate({scrollTop: $regionContent.offset().top}, 300);
    };

    // Return class
    return self.construct();
});