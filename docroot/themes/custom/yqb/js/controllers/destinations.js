var Drupal = Drupal || {};

var Destinations = (function ($, Drupal, Bootstrap) {

    var self = {
        AMERICA_REGION_ID:"57475"
    };

    var didResize = false;
    var delayedEventsInterval = null;

    var $container = null;
    var $map = null;
    var $list = null;

    var maps = {};
    var markers = {};
    var paths = [];

    var yqbPosition = null;
    var yqbMarker = null;

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Destinations constructed');

		$(window).on('resize', onWindowResize);
		$(window).on('delayedresize', onWindowDelayedResize).trigger('delayedresize');
		delayedEventsInterval = setInterval(onDelayedEvents, 250);

        return self;
    };

    self.destruct = function () {
        console.log('Destinations destructed');

        $(window).off('resize', onWindowResize);

        // Clean up and uninitialize things
    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.index = function () {
        console.log('Destinations page initialized');

        $container = $('.content-wrapper');

        $container.on('change', '.drawer-datepicker', onDateClick);
        $container.on('change', '.panel-datepicker', onDateClick);
        $container.on('click', '.drawer-bottom-close', onReturnToCalendar);
        $container.on('click', '.accordion-destination', function(ev) {
            var $this = $(this);
            var $content = $this.closest('.panel-heading').siblings('.panel-collapse');

            if(!$content.find('.xdsoft_datetimepicker').length){
                initCalendar($content.find('[data-toggle="datepicker-planner-list"]'), buildDatesArray($this.closest('.panel-destination').data().schedule));
            }
        });

        var $selector = $('.trigger-map');
            $selector.on('click', onSelectorClick);

        $map = $('#map-tiles');
        $list = $('#map-accordion');

        $map.find('.map-tile-overlay').on('click', onMapOverlayClick);

        // YQB coordinates
        yqbPosition = {lat: 46.7907719, lng:-71.3907577};

        App.loadGoogleMaps(initializeMap);
    };

    /** -----------------------------
     * Private methods
     --------------------------------- */

    var initializeMap = function() {
        console.log('initializeMap');

        // Add labels capabilities to Google Maps markers
        google.maps.Marker.prototype.setLabel = function(label){
            this.label = new MarkerLabel({
                map: this.map,
                marker: this,
                text: label.text,
                alignment:label.position || 'MC'
            });
            this.label.bindTo('position', this, 'position');
        };

        var MarkerLabel = function(options) {
            this.setValues(options);
            this.span = document.createElement('span');
            this.span.className = 'map-marker-label map-marker-label-' + options.alignment.toLowerCase();
        };

        MarkerLabel.prototype = $.extend(new google.maps.OverlayView(), {
            onAdd: function() {
                this.getPanes().overlayImage.appendChild(this.span);
                var self = this;
                this.listeners = [
                    google.maps.event.addListener(this, 'position_changed', function() { self.draw(); })
                ];

                // Make label clickable and trigger marker click
                this.getPanes().overlayMouseTarget.appendChild(this.span);
                google.maps.event.addDomListener(this.span, 'click',        function() { google.maps.event.trigger(self.marker, 'click'); });
                google.maps.event.addDomListener(this.span, 'mouseover',    function() { google.maps.event.trigger(self.marker, 'mouseover'); });
                google.maps.event.addDomListener(this.span, 'mouseout',     function() { google.maps.event.trigger(self.marker, 'mouseout'); });
            },
            draw: function() {
                var text = String(this.get('text'));
                var position = this.getProjection().fromLatLngToDivPixel(this.get('position'));
                this.span.innerHTML = text;

                this.span.style.left = position.x + 'px';
                this.span.style.top = position.y + 'px';
            }
        });

        google.maps.Marker.prototype.setPulse = function(pulse) {
            this.label = new MarkerPulse({
                text:'',
                map: this.map,
                marker: this
            });
            this.label.bindTo('position', this, 'position');
        };

        var MarkerPulse = function(options) {
            this.setValues(options);
            this.span = document.createElement('span');
            this.span.className = 'map-pulse';
        };

        MarkerPulse.prototype = $.extend(new google.maps.OverlayView(), {
            onAdd: function() {
                this.getPanes().overlayImage.appendChild(this.span);
                var self = this;
                this.listeners = [
                    google.maps.event.addListener(this, 'position_changed', function() { self.draw(); })
                ];
            },
            draw: function() {
                var position = this.getProjection().fromLatLngToDivPixel(this.get('position'));
                this.span.style.left = position.x + 'px';
                this.span.style.top = position.y + 'px';
            }
        });

        $('.map-tile .map-tile-container').each(function() {
            var $mapContainer = $(this);
            var $mapTile = $mapContainer.closest('.map-tile');

            var regionId = $mapTile.data('region-id');

            // Create map inside tile
            maps[regionId] = new google.maps.Map($mapContainer.get(0), {
                center:yqbPosition,
                maxZoom:5,
                disableDoubleClickZoom: true,
                draggable:false,
                scrollwheel:false,
                disableDefaultUI:true
            });

            // Find corresponding drawer in list mode
            var $panel = $('.panel-region[id$="' + regionId + '"]');

            var regionTitle = $panel.find('.panel-title h2:first').html();
            var regionColor = $mapTile.data('color') || "#90d7f5";
            var overlayColor = $mapTile.data('overlay') || regionColor;

            $mapTile.find('.map-tile-header, .map-tile-overlay-title').html(regionTitle);
            $mapTile.css('background-color', overlayColor);

            // Style map accordingly
            maps[regionId].setOptions({styles: [
                { "stylers": [ { "color": "#FFFFFF" } ] },
                { "featureType": "water", "stylers": [ { "color": regionColor } ] },
                { "elementType": "labels", "stylers": [ { "color": "#000000" }, { "visibility": "off" } ] },
                {
                    featureType: "administrative",
                    elementType: "geometry",
                    stylers: [
                        { visibility: "off" }
                    ]
                }
            ]});

            if ($panel.length) {
                // Add markers
                showTileMarkers($panel, maps[regionId], regionId);
            }

            if (regionId == self.AMERICA_REGION_ID) {
                // Center marker
                yqbMarker = new google.maps.Marker({
                    position:yqbPosition,
                    map:maps[regionId],
                    visible:false,
                    pulse:'Québec',
                    /*
                    icon:{
                        url:window.location.origin + '/themes/custom/yqb/img/maps/marker-yqb.png',
                        size: new google.maps.Size(46, 46),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(11, 11),
                        scaledSize: new google.maps.Size(23, 23)
                    }
                    */
                });
            }
        });
    };

    var onMapOverlayClick = function(ev) {
        ev.preventDefault();

        $container.removeClass('has-drawer');
        $('.drawer-content').remove();

        erasePaths();

        var $tileOverlay = $(this);

        var regionId = $tileOverlay.attr('href').replace(/#/, '');

        // Remove previous active tile
        $tileOverlay.closest('.map-tiles').find('.map-tile').removeClass('map-tile-active');

        // Add class to current active tile
        $tileOverlay.closest('.map-tile').addClass('map-tile-active');

        // Hide all markers
        $.each(markers, function(index, regionMarkers) {
            $.each(regionMarkers, function(index, marker) {
                marker.setVisible(false);
            });
        });

        // Show only markers from region
        $.each(markers[regionId], function(index, marker) {
            marker.setVisible(true);
        });
    };

    /**
     * Erases all paths from the map
     */
    var erasePaths = function() {
        $.each(paths, function(index, path) {
            path.setMap(null);
        });

        paths = [];
    };

    /**
     * Parses all rows
     */
    var showTileMarkers = function($panel, tileMap, regionId) {
        var $rows = $panel.find('.panel-destination');

        markers[regionId] = [];

        // Scan through table to fetch data
        $rows.each(function() {
            markers[regionId].push(parseRow($(this), tileMap, regionId));
        });

        fitBounds(tileMap, markers[regionId]);
    };

    /**
     * Parses a given row of the table list and stores it in an object
     */
    var parseRow = function($row, map, regionId) {
        var $rowData = $row.find('.panel-raw-data .views-row:first-child');

        var id =                parseInt($rowData.find('.views-field-nid .field-content').text().trim());
        var alias =             $rowData.find('.views-field-title-1 .field-content').text().trim();
        var city =              $rowData.find('.views-field-title .field-content').text().trim();
        var image =             $rowData.find('.views-field-uri .field-content').text().trim();
        var iata =              $rowData.find('.views-field-field-iata .field-content').text().trim();
        var frequency =         $rowData.find('.views-field-field-frequency .field-content').text().trim();
        var latitude =          parseFloat($rowData.find('.views-field-field-latitude .field-content').text().trim());
        var longitude =         parseFloat($rowData.find('.views-field-field-longitude .field-content').text().trim());
        var annual =            (parseInt($rowData.find('.views-field-field-destination-category .field-content').text().trim()) === 15);
        var labelPosition =     $rowData.find('.views-field-field-label-position .field-content').text().trim();
        var schedule =          [];

        var destination = {
            lat:latitude,
            lng:longitude
        };

        // For IE-10
        if (!window.location.origin) {
            window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '');
        }

        // Seasonal by default
        var iconUrl = window.location.origin + '/themes/custom/yqb/img/maps/marker-season-sprite.png';

        if(annual === true) {
            iconUrl = window.location.origin + '/themes/custom/yqb/img/maps/marker-year-sprite.png';
        }

        // Build schedule
        $row.find('.panel-raw-data .views-row').each(function() {
            var $item = $(this);
            var item = {
                airlineName:$item.find('.views-field-field-airline .field-content').text().trim(),
                airlineLogo:$item.find('.views-field-uri-1 .field-content').text().trim(),
                airlineLink:$item.find('.views-field-field-website .field-content').text().trim(),
                everyday:parseInt($item.find('.views-field-field-everyday .field-content').text().trim()) ? true : false,
                stops:[],
                schedule:[]
            };

            var stops = $item.find('.views-field-field-stops .field-content').text().trim();

            if (stops) {
                item.stops = stops.split(',');
                item.stops = $.map(item.stops, $.trim);
            }

            if (item.everyday === false) {
                $item.find('.views-field-field-schedule time').each(function() {
                    item.schedule.push($(this).text().trim());
                });
            }

            // Item should have at least one airline and some schedule
            if (item.airlineName && (item.everyday || item.schedule.length)) {
                schedule.push(item);
            }
        });

        var rowData = {
            id: id,
            regionId: regionId,
            alias: alias,
            image: image,
            city: city,
            labelPosition: labelPosition,
            frequency: frequency,
            iata: iata,
            schedule: schedule
        };

        // Store row data for future use
        $row.data(rowData);

        var icon= {
            url: iconUrl,
            size: new google.maps.Size(30, 40),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(7, 35),
            scaledSize: new google.maps.Size(60, 40)
        };

        // Add destination marker
        var marker = new google.maps.Marker({
            position: destination,
            visible: false,
            map: map,
            label:{
                text:rowData.city,
                position:rowData.labelPosition
            },
            data: rowData,
            icon: icon
        });

        marker.addListener('click', onMarkerClick);
        marker.addListener('mouseover', onMarkerOver);
        marker.addListener('mouseout', onMarkerOut);

        return marker;
    };

    /**
     * Zooms and pans map around given markers
     * @param boundsMarkers
     */
    var fitBounds = function(map, boundsMarkers) {
        // Make map zoom to markers
        var bounds = new google.maps.LatLngBounds();

        // Start by YQB position
        // bounds.extend(yqbMarker.getPosition());

        // Add all airports bounds
        $.each(boundsMarkers, function(index, marker) {
            bounds.extend(marker.getPosition());
        });

        // Fit defined bounds
        try {
            map.fitBounds(bounds);
        }catch(err){

        }
    };

    /**
     * Initializes a calendar inside a given container with given dates
     * @param $context
     * @param dates
     */
    var initCalendar = function($context, dates) {
        dates = dates || [];

        var everyday = false;

        $.each(dates, function(i, date) {
            if (date.everyday) {
                everyday = true;
            }
        });

        var changeGoogleFlightsLink = function(date, $i) {
            var $link = $i.parent().find('.google-flights-link');
            var href = $link.data('href') || $link.attr('href');

            $link.data('href', href);

            $link.attr('href', prepareBookingUrl(href, moment(date).format('Y-MM-DD')));
        };

        $context.datetimepicker({
            inline: true,
            format: 'Y-m-d',
            useCurrent: false,
            defaultDate: false,
            timepicker: false,
            scrollMonth: false,
            timepickerScrollbar: false,
            defaultSelect: false,
            minDate: new Date(),
            maxDate: moment().add(12, 'months').toDate(),
            onGenerate:function(date, $i) {
                $i.parent().addClass('drawer-right-generated');
            },
            onShow:changeGoogleFlightsLink,
            onSelectDate:changeGoogleFlightsLink,
            beforeShowDay: function(date) {
                if (everyday) {
                    return [true, 'xdsoft_highlighted_default'];
                } else {
                    var dateString = date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);

                    if ($.inArray(dateString, dates) !== -1) {
                        return [true, 'xdsoft_highlighted_default'];
                    }
                }

                return [true, 'xdsoft_disabled'];
            }
        });
    };

    /**
     * Return a flattened array of dates for datepicker initialization
     * @param schedules
     * @returns {Array.<*>}
     */
    var buildDatesArray = function(schedules) {
        return [].concat.apply([], schedules.map(function(item) {
            return (item.everyday) ? { everyday:true} : item.schedule;
        }));
    };

    /**
     * Inserts data into a pre-formatted URL
     * Ex : https://www.google.ca/flights/#search;f=YQB;t={{airport}};d={{departure}};tt=o
     * @param url
     * @param date
     * @param iata
     * @returns {*}
     */
    var prepareBookingUrl = function(url, date, iata) {
        // For most URLs
        url = url.replace(/\[\[airport\]\]/g, iata);
        url = url.replace(/\[\[departure\]\]/g, moment(date).format('Y-MM-DD'));

        // For Transat
        url = url.replace(/\[\[departure_day\]\]/g, moment(date).format('D'));
        url = url.replace(/\[\[departure_month\]\]/g, moment(date).format('M'));
        url = url.replace(/\[\[departure_year\]\]/g, moment(date).format('Y'));

        return url;
    };

    /**
     * Returns a list of carriers and search links for a given date and destination
     * @param date
     * @param destinationAlias
     */
    var searchAirlines = function(date, destinationAlias) {
        var airlines = [];
        var airlineNames = [];

        var formattedDate = moment(date).format('Y-MM-DD');

        var $airlinesSchedules = $('#destination-' + destinationAlias + ' .panel-raw-data .views-row');

        $airlinesSchedules.each(function() {
            var $carrierSchedule = $(this);

            var everyday = (parseInt($carrierSchedule.find('.views-field-field-everyday .field-content').text().trim())) ? true : false;

            if (everyday || $carrierSchedule.find('.views-field-field-schedule time:contains("' + formattedDate + '")').length) {
                // Carrier serves this date, push it
                var iata = $carrierSchedule.find('.views-field-field-iata .field-content').text().trim();
                var url = $carrierSchedule.find('.views-field-field-website .field-content').text().trim();
                    url = prepareBookingUrl(url, date, iata);
                    var name = $carrierSchedule.find('.views-field-field-airline .field-content').text().trim(),
                      sameExists = false;

                    for(var i = 0; i < airlineNames.length; i++) {
                      if(airlineNames[i] === name) {
                        sameExists = true;
                        break;
                      }
                    }

                    if(!sameExists) {
                      airlineNames.push(name);
                      airlines.push({
                        airlineName: name,
                        airlineLogo: $carrierSchedule.find('.views-field-uri-2 .field-content').text().trim(),
                        airlineLink: url
                      });
                    }
            }
        });

        return airlines;
    };

    var addPath = function(map, startPosition, endPosition) {
        var path = new google.maps.Polyline({
            path: [
                startPosition,
                endPosition
            ],
            geodesic: true,
            strokeColor: '#0054FF',
            strokeOpacity: 1.0,
            strokeWeight: 3,
            map: map
        });

        paths.push(path);
    };

    var drawRoute = function(destinationMarker) {
        // Only draw routes on main America tile
        if (destinationMarker.data.regionId != self.AMERICA_REGION_ID) {
            return false;
        }

        // Check if we have stops
        if (destinationMarker.data.schedule.length && destinationMarker.data.schedule[0].stops.length) {
            // First stop from destination
            var startPosition = destinationMarker.getPosition();

            $.each(destinationMarker.data.schedule[0].stops, function(index, stop) {
                $.each(markers[destinationMarker.data.regionId], function(index, marker) {
                    if (stop === marker.data.city) {
                        addPath(marker.map, startPosition, marker.getPosition());

                        startPosition = marker.getPosition();
                    }
                });
            });

            // Final stop to YQB
            addPath(destinationMarker.map, startPosition, yqbPosition);
        } else  {
            addPath(destinationMarker.map, yqbPosition, destinationMarker.getPosition());
        }
    };

    /** -----------------------------
     * Events
     --------------------------------- */

    var onDelayedEvents = function() {
        if (didResize) {
            $(window).trigger('delayedresize');
            didResize = false;
        }
    };

    var onWindowResize = function(ev) {
        didResize = true;
    };

    var onWindowDelayedResize = function(ev) {
        for (var regionId in maps) {
            fitBounds(maps[regionId], markers[regionId]);
        }
    };

    /**
     * Triggered when the selector has been click (map or list mode)
     * @param ev
     */
    var onSelectorClick = function(ev) {
        ev.preventDefault();
        var $this = $(this);

        var currentMapClasses = $map.attr('class');
        var currentListClasses = $list.attr('class');
        var $mapLegend = $('.map-legend');

        $('.trigger-map').removeClass('is-active');

        $this.addClass('is-active');

        if ($this.attr('href') === '#list') {
            $map.removeClass(currentMapClasses).addClass('map-hidden' + " " + currentMapClasses);
            $mapLegend.addClass('hidden');
            $list.removeClass('hidden');
        } else {
            $map.removeClass('map-hidden');
            $mapLegend.removeClass('hidden');
            $list.removeClass(currentListClasses).addClass('hidden' + " " + currentListClasses);
        }
    };

    /**
     * Triggered when a date is clicked in one of the datepickers
     * @param ev
     */
    var onDateClick = function(ev) {
        var $this = $(this);
        var date = $this.datetimepicker('getValue');
        var dateFormatted =  moment(date).format('LL');
        var $parent =  $this.parents('.drawer-action');
            $parent.addClass('subcontent-open');

        // console.log(date);
        // console.log(dateFormatted);

        var $container = $this.closest('.drawer-body, .panel-body');

        // Clean up before appending next one
        $('.drawer-bottom, .panel-content-right-wrapper').remove();

        // Fetch destination alias from ID
        var destinationAlias = $container.closest('.panel-collapse, .drawer-content').attr('id').match(/(?:destination-collapse-|drawer-content-)(.*)/).pop();

        // Fetch airlines serving selected date
        var airlines = searchAirlines(date, destinationAlias);

        // Only display content if we have airlines
        if (airlines.length) {
            var templateId;

            if ($container.find('.drawer-right').length) {
                templateId = '#drawer-airlines-template';
            } else {
                templateId = '#panel-airlines-template';
            }

            var source   = $(templateId).html();
            var template = Handlebars.compile(source);
            var html = template({
                airlines:airlines
            });

            $container.append(html);

            var $dateSelect = $parent.find('.date-select');
            $dateSelect.html($dateSelect.data('pattern').replace('%s', dateFormatted));

            // drawer  scrollviews
            $('.drawer-bottom').find('.airlines').perfectScrollbar({
                suppressScrollX: true,
                wheelPropagation: false,
                swipePropagation: false
            });
        }

		if ($(document).width() < 630) {
            var $parentDest = $parent.parents('.panel-destination');
            var pos = ($parentDest.offset().top);

            if (!Modernizr.touchevents) {
                $('html, body').animate({scrollTop: pos}, 1000);
            } else {
                $('body, html').scrollTop(pos);
            }
        }
    };

    /**
     * Triggered when the back is clicked in the right panel
     * @param ev
     */
    var onReturnToCalendar = function(ev) {
        ev.preventDefault();
        var $this = $(this);
        $this.parents('.drawer-action').removeClass('subcontent-open');
    };

    /**
     * Triggered when the close button is clicked in the right panel
     * @param ev
     */
    var onDrawerClose = function(ev) {
        ev.preventDefault();

        $container.removeClass('has-drawer');
        $('.drawer-content').remove();

        erasePaths();

        setTimeout(function() { fitBounds(markers); }, 300);
    };

    /**
     * Triggered the cursor is over a marker
     * @param ev
     */
    var onMarkerOut = function(ev) {
        var icon = this.icon;
            icon.origin = new google.maps.Point(0, 0);

        this.setIcon(icon);

        if ($('.drawer-content').length === 0) {
            // Erase all paths
            erasePaths();
        }
    };

    /**
     * Triggered the cursor is over a marker
     * @param ev
     */
    var onMarkerOver = function(ev) {
        var icon = this.icon;
            icon.origin = new google.maps.Point(30, 0);

        this.setIcon(icon);

        if ($('.drawer-content').length === 0) {
            // Erase all paths
            erasePaths();

            drawRoute(this);
        }
    };

    /**
     * Triggered when a marker on the map is clicked
     * Or when a map-extra is clicked
     * @param ev
     */
    var onMarkerClick = function(ev) {
        var data = this.data;

        $('.drawer-content').remove();

        var source   = $('#drawer-template').html();
        var template = Handlebars.compile(source);
        var html = template(data);

        var $drawer = $container.find('.drawer').append(html);

        // Scroll to drawer
        $('html, body').animate({
            scrollTop:$drawer.offset().top - $('#toolbar-administration #toolbar-bar').height() * 2
        });

        // Add unique ID
        $drawer.find('.drawer-content').attr('id', 'drawer-content-' + data.alias);

        // Drawer close button
        $drawer.find('.drawer-close').on('click', onDrawerClose);

        $container.addClass('has-drawer');

        setTimeout(function() {
            // Grunt icon refresh
            grunticon(["/themes/custom/yqb/dist/output/icons.data.svg.css", "/themes/custom/yqb/dist/output/icons.data.png.css", "/themes/custom/yqb/dist/output/icons.fallback.css"], grunticon.svgLoadedCallback);

            // Initialize date picker
            initCalendar($drawer.find('[data-toggle="datepicker-planner-map"]'), buildDatesArray(data.schedule));
        }, 400);

        // Erase all paths
        erasePaths();

        drawRoute(this);
    };

    // Return class
    return self.construct();
});
