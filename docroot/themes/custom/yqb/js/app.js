/**
 * @file
 * Theme hooks for the Drupal Bootstrap base theme.
 */
var App = (function ($, Drupal, Bootstrap) {

    /*-----------------------------------------------------------------------------------------------------------
     MAIN APP
     -----------------------------------------------------------------------------------------------------------*/

    return (function () {

        var self = {},
            timeSwipers = {},
            $document,
            $body,
            $html,
            $window,
            $content,
            $footer,
            $header,
            didResize = false,
            didScroll = false,
            delayedEventsFrequency = 250,
            delayedEventsInterval = null;

        var timeElapsed = 0;

        /** ------------------------------
         * Constructor
         --------------------------------- */

        self.construct = function () {
            $(document).ready(onAppReady);
            $(window).bind('load', onAppLoaded);

            return self;
        };

        /** ------------------------------
         * App event handlers
         --------------------------------- */

        var onAppReady = function (ev) {
            console.log('DOM ready');

            // Global elements
            $document = $(document);
            $window = $(window);
            $html = $('html');
            $body = $('body');
            $content = $('#layout-content');
            $header = $('#layout-header');
            $footer = $('#layout-footer');

            // Window events
            $window.on('resize', onWindowResize);
            $window.on('delayedresize', onWindowDelayedResize).trigger('delayedresize');
            delayedEventsInterval = setInterval(onDelayedEvents, delayedEventsFrequency);

            // Simple resolution detection
            self.isMobileResolution = Modernizr.mq('(max-width: 767px)');
            $html.addClass((self.isMobileResolution) ? 'window-is-mobile' : 'window-is-not-mobile');

            // Set custom class for input types
            if (Modernizr.inputtypes.time) {
                $html.addClass('inputtypes-time');
            }

            // Set moment locale
            moment.locale($html.attr('lang'));

            var $search = $('#block-formulairederecherche-2');
                $search.on('click', 'button', onClickSearch);
                $search.on('click', '.btn-close', onCloseSearch);

            // Init nav
            var $menus = $(".menu");
                $menus.on('click', 'a.dropdown-toggle', onMenuOpen);
                $menus.on('click', 'a.dropdown-back', onMenuBack);
                $menus.on('click', 'a.dropdown-close', onMenuClose);

            if (Modernizr.touchevents) {
                // FastClick
                // Attach FastClick to body
                FastClick.attach(document.body);
            } else if (!Modernizr.touchevents) {
                // Header dropdowns scrollviews
                $menus.find('.dropdown-menu').perfectScrollbar({
                    suppressScrollX: true,
                    wheelPropagation: false,
                    swipePropagation: false
                });
            }

            // Footer links
            $footer.find('a').on('click', self.findAndTriggerHeaderLink);

            // Breadcrumb links
            $document.on('click', '.breadcrumb a', self.findAndTriggerHeaderLink);

            // Breadcrumb links
            $document.on('focus blur', '.form-control', toggleFormControlsFocus);

            // Form events
            $document.on('submit', 'form', onFormSubmit);
            $document.on('click', 'form [type="submit"]', onFormButtonClick);

            // Colorize background colors on dropdowns
            colorizeHeaderDropdowns();

            manageDismissableBlocks();

            // Tracking events
            $document.on('mousedown', 'a[data-event-category][data-event-action], button[data-event-category][data-event-action]', onTrackEventClick);

            // PJAX support
            if ($.support.pjax) {
                // Prepare NProgress bar
                NProgress.configure({parent: '#layout-header'});

                // PJAX normal request
                $document.pjax('a:not([target="_blank"], [target="_self"], [target="_top"], [href$="/edit"], [href$="/delete"], [href*="/admin/"], [href="/fr/affaires/paiement-de-facture"], [href="/en/business/pay-bills"], [href="/fr/a-propos/travaux-de-construction"], [href="/a-propos/travaux-de-construction"], [href="/en/about/construction-work"])', '#layout-content', {
                    timeout: 20 * 1000,
                    fragment: '#layout-content',
                    xhr: pjaxXHR
                });


                // PJAX events
                $document.on('pjax:send', onPjaxSend);
                $document.on('submit', 'form[data-pjax]', onPjaxSubmit);
                $document.on('pjax:success', onPjaxSuccess);
                $document.on('pjax:popstate', onPjaxPopState);
                $document.on('pjax:timeout', onPjaxTimeout);
                $document.on('pjax:error', onPjaxError);
                $document.on('pjax:complete', onPageReady);
            }

            // First load, manually trigger page ready
            onPageReady();
        };

        var onAppLoaded = function (ev) {
            console.log('Window loaded');

            $html.addClass('is-loaded');
        };

        var onPageReady = function (ev, xhr, textStatus, options) {
            console.log('Page ready');

            // If event has been triggered by pushState
            if (ev && xhr) {
                // Remove loading class
                $body.removeClass('is-loading');
                $html.addClass('is-loaded');

                // Remove loading bar
                NProgress.done();

                // Drupal behaviors
                window.drupalSettings = JSON.parse(xhr.getResponseHeader('x-drupal-settings'));

                if (Drupal.behaviors.contextual) {
                    // Add edit buttons everywhere
                    Drupal.behaviors.contextual.attach($content.get(0));
                }

                if (Drupal.behaviors.activeLinks) {
                    // Add active links class
                    refreshScript('active-link');
                    Drupal.behaviors.activeLinks.attach($content.get(0));
                }

                if (Drupal.behaviors.autocomplete) {
                    // Add autocomplete
                    Drupal.behaviors.autocomplete.attach($content.get(0));
                }

                if (Drupal.behaviors.states) {
                    // Add states
                    Drupal.behaviors.states.attach($content.get(0));
                }

                if (Drupal.behaviors.addToAny) {
                    // Add states
                    Drupal.behaviors.addToAny.attach($content.get(0));
                }

                // Grunt icon refresh
                grunticon(["/themes/custom/yqb/dist/output/icons.data.svg.css", "/themes/custom/yqb/dist/output/icons.data.png.css", "/themes/custom/yqb/dist/output/icons.fallback.css"], grunticon.svgLoadedCallback);

                // Reload AddToAny
                if(typeof a2a !== 'undefined'){
                    a2a.init('feed');
                }

                if (typeof ga !== 'undefined') {
                    // Inform Google Analytics of the change
                    ga('send', 'pageview', location.pathname + location.search);
                }

                if (typeof FB !== 'undefined') {
                    // Parse Facebook in target
                    FB.XFBML.parse(ev.target);
                }

                if (typeof twttr !== 'undefined') {
                    // Parse Twitter in target
                    twttr.widgets.load(ev.target);
                }

                if (typeof IN !== 'undefined') {
                    // Parse LinkedIn in target
                    IN.parse(ev.target);
                }

                if (typeof gapi !== 'undefined') {
                    gapi.plusone.render("gplus-widget-div");
                }
            }

            // Lazy load images
            $('img[data-src]').unveil();

            // Switches
            $('input[data-toggle="switch"]').bootstrapSwitch({
                onSwitchChange: onSwitchChange
            });

            // Tooltips
            $content.find('[data-toggle="tooltip"]').tooltip();

            // Initialize date pickers on devices that don't support time inputs
            if (Modernizr.inputtypes.time === false || self.isMobileResolution === false) {
                initializeDatePickers();
            }

            initializeParkingBooker();
            initializeReminders();
            initializeDropdownMenu();
            initializeGallery();
            setMenuActiveClass();
            initSliderHomePage();
            initSliderTilesHomePage();
            Router.init();
        };

        /** ------------------------------
         * PJAX Events
         --------------------------------- */

        /**
         * XHR object for PJAX
         * @returns {XMLHttpRequest}
         */
        var pjaxXHR = function () {
            // TODO : realistic progress

            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (ev) {
                if (ev.lengthComputable) {
                    var percentComplete = ev.loaded / ev.total;
                    //Do something with upload progress here
                }
            }, false);

            xhr.addEventListener('progress', function (ev) {
                if (ev.lengthComputable) {
                    var percentComplete = ev.loaded / ev.total;
                    //Do something with download progress
                }
            }, false);

            return xhr;
        };

        /**
         * Fired when a pjax request is triggered
         * @param ev
         */
        var onPjaxSend = function (ev) {
            console.log('PJAX : send');

            // Add loading class
            $body.addClass('is-loading');

            // Add loading bar
            NProgress.start();

            if (Drupal.behaviors.activeLinks) {
                // Remove active links class
                Drupal.behaviors.activeLinks.detach($content.get(0), null, 'unload');
            }

            // Close menus
            $('.dropdown.open').removeClass('open');

            $document.on('click', onClickOutside);
            $document.off('keydown', onEscPress);
        };

        /**
         * Fire when a form is submitted through pjax
         * @param ev
         */
        var onPjaxSubmit = function (ev) {
            console.log('PJAX : submit');
            $.pjax.submit(ev, '#layout-content');
        };

        /**
         * Fired when a pjax request is successful
         * @param ev
         * @param data
         * @param status
         * @param xhr
         * @param options
         */
        var onPjaxSuccess = function (ev, data, status, xhr, options) {
            // Page title
            var title = data.match(/<title>(.*?)<\/title>/i)[1];

            $('title').html(title);

            // Body attributes
            var bodyAttributes = data.match(/<body(.*?)>/i)[0];

            var bodyId = bodyAttributes.match(/id="(.*?)"/i);
                bodyId = (bodyId) ? bodyId[1] : '';
            var bodyClass = bodyAttributes.match(/class="(.*?)"/i);
                bodyClass = (bodyClass) ? bodyClass[1] : '';

            // Change body classes
            var adminClasses = $body.attr('class').split(' ').filter(function (value) {
                return value.match(/toolbar/)
            }).join(' ');

            $body.attr('id', bodyId);
            $body.attr('class', bodyClass + ' ' + adminClasses);

            // Language link
            var languageLink = data.match(/<a(.*?)language-link(.*?)>/i)[0];
            var languageHref = languageLink.match(/href="(.*?)"/i)[1];

            $header.find('.language-link').attr('href', languageHref);
        };

        /**
         * Fired when the back button is triggered
         * @param ev
         */
        var onPjaxPopState = function (ev) {
            console.log('PJAX : popstate');
            $.pjax.reload('#layout-content', {
                timeout: 10 * 1000,
                fragment: '#layout-content'
            });
        };

        /**
         * Fired when pjax times out (see pjax settings)
         * @param ev
         */
        var onPjaxTimeout = function (ev) {
            console.log('PJAX : timeout');
        };

        /**
         * Fired when the request page returns a HTTP code other than 200
         * @param ev
         * @param xhr
         * @param textStatus
         * @param error
         * @param options
         */
        var onPjaxError = function (ev, xhr, textStatus, error, options) {
            console.log('PJAX : error');
            console.log(textStatus, error);
        };

        /** ------------------------------
         * Events
         --------------------------------- */

        /**
         * Validates parking booker values
         * @param ev
         */
        var onParkingBookerSubmit = function(ev) {
            //ev.preventDefault();

            var $form = $(this).find('.form-block-container:visible:first');
                $form.find('.has-error').removeClass('has-error');

            var isMobile = $form.hasClass('is-mobile');

            // Fetch either one value or two values from form
            var $arrival = $form.find('input[name="arrival_date"]' + ((isMobile) ? ', input[name="arrival_time"]' : ''));
            var $departure = $form.find('input[name="departure_date"]' + ((isMobile) ? ', input[name="departure_time"]' : ''));

            var arrivalValue = $arrival.map(function() { return this.value; }).get().join(' ');
            var departureValue = $departure.map(function() { return this.value; }).get().join(' ');

            var arrival = new Date(arrivalValue);
            var departure = new Date(departureValue);

            if (arrival.getTime() < moment().add(24, 'hours').toDate().getTime()) {
                $arrival.each(function() {
                    $(this).parent().closest('.form-group').addClass('has-error');
                });

                ev.preventDefault();
                ev.stopImmediatePropagation();
            }

            if (departure.getTime() <= arrival.getTime()) {
                $departure.each(function() {
                    $(this).parent().closest('.form-group').addClass('has-error');
                });

                ev.preventDefault();
                ev.stopImmediatePropagation();
            }
        };

        var onSwitchChange = function (ev, state) {
            var selector = (state) ? '.switch-on' : '.switch-off';

            $(this).closest('.switch').find(selector).trigger('click');
        };

        /**
         * Manages active classes in header element
         */
        var setMenuActiveClass = function () {
            // Remove all active classes
            $header.find('a').removeClass('is-active');

            // Parse path from window location
            var path = window.location.pathname.replace(/\/(\?(.*))?$/, '');

            // If path isn't empty (front page)
            if (path) {
                // Remove last part of URL if too long
                var segments = path.split('/').filter(function (segment) {
                    return (segment);
                });

                if (segments.length >= 3) {
                    path = '/' + segments.slice(0, -1).join('/');
                }

                if (segments.length > 1) {
                    // Find corresponding link and farthest <li> from link
                    var $element = $header.find('a[href^="' + path + '"]:first').parents('li').last();

                    // If found, add active class to closest <a>
                    if ($element.length) {
                        $element.find('a:first').addClass('is-active');
                    }
                }
            }
        };

        var onClickSearch = function (ev) {
            var $this = $(this);
            var $parent = $this.closest('#block-formulairederecherche-2');
            if (!$parent.hasClass('is-open')) {
                ev.preventDefault();

                $parent.addClass('is-open');

                var focusTimer = setTimeout(function () {
                    clearTimeout(focusTimer);
                    $parent.find('input').focus();
                }, 500);
            }
        };

        var onCloseSearch = function (ev) {
            ev.preventDefault();
            var $this = $(this);
            var $parent = $this.closest('#block-formulairederecherche-2');
            $parent.removeClass('is-open');

        };

        var onFormButtonClick = function (ev, $target) {
            var $this = $target || $(this);
                $this.addClass('is-clicked');
        };

        var onFormSubmit = function (ev) {
            var $this = $(this).closest('form');

            if (!$this.hasClass('search-block-form')) {
                var $btn =  $this.find('.btn.is-clicked');
                $btn.addClass('is-loading');
            }
        };

        var onMenuOpen = function (ev) {
            console.log('onMenuOpen');

            ev.preventDefault();
            ev.stopImmediatePropagation();

            var $this = $(this),
                $li = $this.parent(),
                $submenu = $this.next('.dropdown-menu');

            if ($submenu.length) {
                $li.parents('.ps-container:eq(0)').animate({scrollTop: 0}, 100);

                $li.parents('.dropdown-menu').addClass('no-overflow');

                $li.addClass('open');

                $document.on('click', onClickOutside);
                $document.on('keydown', onEscPress);

                $submenu.find('.dropdown-menu-header:eq(0)').focus();
                // If item has a parent menu, but parent is not opened
                if ($this.closest('.dropdown-menu').length && !$li.parent().closest('.open').length) {
                    // Force opening parent menu
                    $this.closest('.dropdown-menu').prev('a.dropdown-toggle').trigger('click');
                }
            }
        };

        var onMenuBack = function (ev) {
            ev.preventDefault();

            var $this = $(this),
                $li = $this.parents('.dropdown:eq(0)');
            $li.addClass('is-closing');
            $li.parents('.dropdown-menu:eq(0)').removeClass('no-overflow');
            var classTimer = setTimeout(function () {
                clearTimeout(classTimer);
                $li.removeClass('is-closing');
            }, 1000);
            $li.removeClass('open');
        };

        var onMenuClose = function (ev) {
            ev.preventDefault();

            var $this = $(this),
                $li = $this.parents('.dropdown');

            $('.dropdown-menu').removeClass('no-overflow');
            $li.removeClass('open');

            $document.off('click', onClickOutside);
            $document.off('keydown', onEscPress);
        };

        var onClickOutside = function (ev) {
            var $container = $('.dropdown.open:first');
            if (!$container.is(ev.target) && $container.has(ev.target).length === 0) {
                $container.removeClass('open');

                $('.dropdown-menu').removeClass('no-overflow');
                $document.off('click', onClickOutside);
                $document.off('keydown', onEscPress);
            }
        };

        var onEscPress = function (ev) {
            var $container = $('.dropdown.open:first');

            if (ev.keyCode == 27) {
                $container.removeClass('open');

                $('.dropdown-menu').removeClass('no-overflow');
                $document.off('click', onClickOutside);
                $document.off('keydown', onEscPress);
            }
        };

        var onTrackEventClick = function (ev) {
            var category = $(this).data('event-category') || '',
                action = $(this).data('event-action') || '',
                label = $(this).data('event-label') || '';

            try {
                ga('send', 'event', category, action, label);
            } catch (err) {
                console.log('Error tracking google analytics : ' + err);
            }

            try {
                mixpanel.track(
                    category,
                    {"Comportement": action, "Label": label}
                );
            } catch (err) {
                console.log('Error tracking mixpanel : ' + err);
            }
        };

        /** ------------------------------
         * Public methods
         --------------------------------- */

        self.loadGoogleMaps = function (callback) {
            callback = callback || function () {
                    console.log('Google Maps loaded')
                };

            if (window.google && window.google.maps) {
                callback();
            } else {
                $.getScript('https://maps.googleapis.com/maps/api/js?key=AIzaSyAcp2QT1Yb_U2XjCQiCf3seDhEdpM06-OA', function (data, textStatus, jqxhr) {
                    console.log('Google Maps loaded');
                    callback();
                });
            }
        };

        self.findImgHeight = function () {
            $('.block-image-content').each(function (i, el) {
                var $this = $(this);
                var fileUrl = $this.data('file');
                var $parent = $this.closest('.col-table.h-full');

                $parent.css('background-image', 'url(' + fileUrl + ')');
            });
        };

        self.findAndTriggerHeaderLink = function (ev) {
            if (ev && this) {
                // Clean up path
                var path = $(this).attr('href').replace(/\/$/, '');

                if (path == '/fr' || path == '/en') return true;

                var $element = $header.find('a[href^="' + path + '"]:first');

                if ($element.length) {
                    ev.preventDefault();
                    ev.stopImmediatePropagation();

                    $element.trigger('click');
                }
            }
        };

        /** ------------------------------
         * Private methods
         --------------------------------- */
        var manageDismissableBlocks = function() {
            $('.block-dismissable').each(function() {
                var $block = $(this);

                if (getCookie($block.attr('id'))) {
                    $block.hide();
                    return true;
                }

                // Attach link events
                $block.find('a').on('click', function(ev) {
                    ev.preventDefault();

                    $(this).closest('.block-dismissable').remove();

                    // Set cookie
                    createCookie($(this).closest('.block-dismissable').attr('id'), 'clicked', 7);
                });

                // Manage show after ticker
                if ($block.data('show-after')) {
                    setTimeout(function() {
                        $block.removeAttr('data-show-after');
                    }, parseInt($block.data('show-after')) * 1000);
                }
            });
        };

        var createCookie = function(name, value, days) {
            var expires;
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toGMTString();
            }
            else {
                expires = "";
            }
            document.cookie = name + "=" + value + expires + "; path=/";
        };

        function getCookie(c_name) {
            if (document.cookie.length > 0) {
                c_start = document.cookie.indexOf(c_name + "=");
                if (c_start != -1) {
                    c_start = c_start + c_name.length + 1;
                    c_end = document.cookie.indexOf(";", c_start);
                    if (c_end == -1) {
                        c_end = document.cookie.length;
                    }
                    return unescape(document.cookie.substring(c_start, c_end));
                }
            }
            return "";
        };

        var colorizeHeaderDropdowns = function () {
            $('#layout-header').find(' .dropdown-menu').each(function () {
                $(this).css('background-color', $(this).find('> li:last a').css('background-color'));
            });
        };

        var initSliderHomePage = function() {
            if($('.block-views-blockslider-homepage-block-1 .swiper-container').length){
                var homepageSwiper = new Swiper ('.block-views-blockslider-homepage-block-1 .swiper-container', {
                    direction: 'horizontal',
                    loop: true,
                    effect: 'fade',
                    speed: 5000,
                    autoplay: {
                        delay: 5000,
                    },
                    simulateTouch: false,
                    paginationClickable: true,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                      },
                  })
              }
        
              if($('#block-views-block-commerces-block-boutique .swiper-container').length){
                var boutiquesSwiper = new Swiper ('#block-views-block-commerces-block-boutique .swiper-container', {
                    direction: 'horizontal',
                    loop: true,    
                    slidesPerView: 4,
                    navigation: {
                      nextEl: '#block-views-block-commerces-block-boutique .swiper-button-next',
                      prevEl: '#block-views-block-commerces-block-boutique .swiper-button-prev',
                    },
                    breakpoints: {
                      320: {
                          slidesPerView: 1,
                          spaceBetween: 10
                      },
                      768: {
                          slidesPerView: 2,
                          spaceBetween: 20
                      },
                      1082: {
                        slidesPerView: 3,
                        spaceBetween: 20
                      },
                      1300: {
                          slidesPerView: 4,
                          spaceBetween: 30
                      }
                  }
                  })
              }
        
              if($('#block-views-block-commerces-block-restaurant .swiper-container').length){
                var restaurantSwiper = new Swiper ('#block-views-block-commerces-block-restaurant .swiper-container', {
                    direction: 'horizontal',
                    loop: true,
                    slidesPerView: 4,
                    navigation: {
                        nextEl: '#block-views-block-commerces-block-restaurant .swiper-button-next',
                        prevEl: '#block-views-block-commerces-block-restaurant .swiper-button-prev',
                      },
                    breakpoints: {
                        320: {
                            slidesPerView: 1,
                            spaceBetween: 10
                        },
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 20
                        },
                        1082: {
                          slidesPerView: 3,
                          spaceBetween: 20
                        },
                        1300: {
                            slidesPerView: 4,
                            spaceBetween: 30
                        }
                    }
                  })
              }
        }

       
        var initSliderTilesHomePage = function () {
            
            if($('#block-homepagetilesblock-2 .swiper-container').length){

                if( !$('#block-homepagetilesblock-2 .swiper-container').hasClass('swiper-container-initialized') 
                    && $(window).width() < 585 ){ 

                    var tilesSwiper = new Swiper ('#block-homepagetilesblock-2 .swiper-container', {
                    direction: 'horizontal',
                    loop: true,
                    slidesPerView: 1,
                    on: {
                        resize: function () {
                            var swiper = this;
                            if($(window).width() > 585 && swiper){
                                // To prevent event attach on undefined after resize like updateSize
                                setTimeout(function(){
                                    swiper.destroy(true, true);
                                    console.log('swiper tile destroyed');
                                }, 100);
                            }
                        },
                        init: function() {
                            console.log('swiper tile initialized');
                        }
                      },
                    navigation: {
                        nextEl: '#block-homepagetilesblock-2 .swiper-button-next',
                        prevEl: '#block-homepagetilesblock-2 .swiper-button-prev',
                        },
                    })

                
                }
            } 
        }

        var initializeReminders = function() {
            var $reminders = $('.block-reminders');

            if (Modernizr.inputtypes.time && self.isMobileResolution) {
                // Disable all desktop required inputs to prevent form validation blocking
                $reminders.find('.is-desktop :input').attr('disabled', true);
            } else {
                // Disable all mobile required inputs to prevent form validation blocking
                $reminders.find('.is-mobile :input').attr('disabled', true);
            }

            $reminders.find('input[name="flight_number"]').on('blur keyup change click', function() {
                var $input = $(this);
                var flightNumber = $input.val().replace(/\D/g, '');
                var $form = $(this).closest('form');
                var $airlineInput = $form.find('select[name="flight_airline"]');
                var $typeInput = $form.find('select[name="flight_type"]');

                if (!flightNumber || flightNumber.length < 3) {
                    $airlineInput.val('');
                    $typeInput.val('');
                    return true;
                }

                var data = $input.data('autocomplete');

                var foundIcao = [];
                var foundType = [];

                for(var type in data) {
                    $.each(data[type], function(i, flight) {
                        if (flightNumber === flight.flightNumber) {
                            foundIcao.push(flight.icao);
                            foundType.push(type);
                        }
                    });
                }

                foundIcao = $.unique(foundIcao);
                foundType = $.unique(foundType);

                if (foundIcao.length === 1) {
                    $airlineInput.val(foundIcao[0]);
                } else {
                    $airlineInput.val('');
                }

                if (foundType.length === 1) {
                    $typeInput.val(foundType[0]);
                } else {
                    $typeInput.val('');
                }
            });
        };

        var initializeParkingBooker = function() {
            var $parkingBooker = $('.block-parking-booker');
                $parkingBooker.on('submit', onParkingBookerSubmit);

            if (Modernizr.inputtypes.time && self.isMobileResolution) {
                // Disable all desktop required inputs to prevent form validation blocking
                $parkingBooker.find('.is-desktop :input').attr('disabled', true);
            } else {
                // Disable all mobile required inputs to prevent form validation blocking
                $parkingBooker.find('.is-mobile :input').attr('disabled', true);

                // At least 24 hours in advance
                var parkingMinDate = moment().add(24, 'hours').toDate();

                // Increments of 30 minutes
                var steps = 30;

                var dateTimePickerOptions = {
                    format: 'Y-m-d H:i',
                    defaultTime:moment().add((steps - moment().minute()) % steps, 'minutes').format('H:mm'),
                    minDate: parkingMinDate,
                    step: steps,
                    scrollMonth: false,
                    scrollInput: false,
                    showApplyButton: false,
                    todayButton: false,
                    lazyInit: true,
                    timepickerScrollbar: false,
                    closeOnTimeSelect: true,
                    closeOnWithoutClick: false,
                    closeOnInputClick: false
                };

                // We may have multiple forms in same page
                $parkingBooker.each(function() {
                    var $parkingDatepickerStart = $(this).find('.datetimepicker-start');
                    var $parkingDatepickerEnd = $(this).find('.datetimepicker-end');

                    $parkingDatepickerStart.datetimepicker($.extend(dateTimePickerOptions, {
                        onShow: function (currentTime, $input) {
                            var $end = $input.closest('.form-block-container').find('.datetimepicker-end');
                                $end.datetimepicker('hide');

                            $input.closest('.form-group').removeClass('has-error');

                            this.setOptions({
                                maxDate: $end.val() ? moment($end.val()).toDate() : false
                            });
                        }
                    }));

                    $parkingDatepickerEnd.datetimepicker($.extend(dateTimePickerOptions, {
                        onShow: function (currentTime, $input) {
                            var $start = $input.closest('.form-block-container').find('.datetimepicker-start');
                                $start.datetimepicker('hide');

                            $input.closest('.form-group').removeClass('has-error');

                            this.setOptions({
                                minDate: $start.val() ? moment($start.val()).toDate() : parkingMinDate
                            });
                        }
                    }));
                });
            }
        };

        var initializeDropdownMenu = function () {
            // Scans for each sub menu
            $('.sub-menu').each(function () {
                // If sub-menu contains an active link, open menu
                if ($(this).find('a.is-active').length) {
                    $(this).addClass('sub-menu-start-active').addClass('sub-menu-active');
                }

                // Add click event to submenu
                $(this).find('> a').on('click', function (ev) {
                    ev.preventDefault();
                    var $this = $(this);
                    var $parent = $this.parent();
                    if ($parent.hasClass('sub-menu-active')) {
                        $parent.removeClass('sub-menu-active');
                        $this.siblings('.nav').slideUp();
                    }
                    else {
                        $parent.addClass('sub-menu-active');
                        $this.siblings('.nav').slideDown();
                    }
                });
            });
        };

        var initializeDatePickers = function () {
            $.datetimepicker.setLocale($html.attr('lang'));

            // Default datepicker
            $('[data-toggle="datepicker"]').each(function () {
                $(this).datetimepicker({
                    format: 'Y-m-d',
                    minDate: new Date(),
                    step: 30,
                    scrollMonth: false,
                    scrollInput: false,
                    showApplyButton: false,
                    closeOnTimeSelect: false,
                    inline: false,
                    lazyInit: true,
                    timepicker: false,
                    timepickerScrollbar: false,
                    closeOnWithoutClick: true,
                    closeOnInputClick: true,
                    todayButton: false,
                });
            });

            // Default datetimepicker
            $('[data-toggle="datetimepicker"]').each(function () {
                $(this).datetimepicker({
                    format: 'Y-m-d H:i',
                    minDate: parkingMinDate,
                    step: 30,
                    scrollMonth: false,
                    scrollInput: false,
                    showApplyButton: false,
                    todayButton: false,
                    lazyInit: true,
                    timepickerScrollbar: false,
                    closeOnTimeSelect: true,
                    closeOnWithoutClick: false,
                    closeOnInputClick: false,
                });
            });
        };

        var initializeGallery = function(){
            $('.field--name-field-gallery').each( function() {
                var $gallery = $(this),
                getItems = function() {
                    var items = [];
                    $gallery.find('a').each(function() {
                        var $href   = $(this).attr('href');

                        var item = {
                            src : $href,
                            w   : 0,
                            h   : 0
                        };

                        items.push(item);
                    });
                    return items;
                };

                var items = getItems();

                var $pswp = $('.pswp')[0];
                $gallery.on('click', '.field--item:not(.field)', function(event) {
                    event.preventDefault();

                    var $index = $(this).index();
                    var options = {
                        index: $index,
                        bgOpacity: 0.7,
                        showHideOpacity: true
                    };

                    // Initialize PhotoSwipe
                    var gallery = new PhotoSwipe($pswp, PhotoSwipeUI_Default, items, options);

                    gallery.listen('gettingData', function(index, item) {
                        if (item.w < 1 || item.h < 1) { // unknown size
                            var img = new Image();
                            img.onload = function() { // will get size after load
                                item.w = this.width; // set image width
                                item.h = this.height; // set image height
                                gallery.invalidateCurrItems(); // reinit Items
                                gallery.updateSize(true); // reinit Items
                            };
                            img.src = item.src; // let's download image
                        }
                    });

                    gallery.init();
                });

            });
        };

        var refreshScript = function (match) {
            var $script = $('script[src*="' + match + '"]');
            if ($script.length === 1) {
                var src = $script.attr('src');
                $script.remove();
                $('<script>').attr('src', src).appendTo('body');
            } else {
                console.log('No or multiple scripts found with same src.');
            }
        };

        var toggleFormControlsFocus = function (ev) {
            var $label = $(this).prevAll('label:first');

            // Textareas are wrapper in a div, dig deeper
            if (!$label.length) {
                $label = $(this).parent().prevAll('label:first');
            }

            if ($label.length) {
                if (ev.type === 'focusin') {
                    // Do something on focus
                    $label.addClass('control-label-focus');
                } else {
                    // Do something on blur
                    $label.removeClass('control-label-focus');
                }
            }
        };


        /** ------------------------------
         * Window events
         --------------------------------- */
        var onDelayedEvents = function() {
            if (didResize) {
                $(window).trigger('delayedresize');
                didResize = false;
            }
        };

        var onWindowResize = function(ev) {
            didResize = true;
            initSliderTilesHomePage();
        };

        var onWindowDelayedResize = function(ev) {
            // Close all menus
            $document.trigger('click', { target:$body });
        };

        return self.construct();
    })();

})(window.jQuery, window.Drupal, window.Drupal.bootstrap);

/**************************************************************************
 * Translations Handlebar
 * This sections translates all handlebar static texts
 * **************************************************************************/


Handlebars.registerHelper('I18n',
    function (str) {
        return ((I18n != undefined && I18n.locale != 'fr') ? I18n.t(str) : str);
    }
);

I18n.defaultLocale = 'fr';
I18n.locale = window.jQuery('html').attr('lang');
I18n.currentLocale();

I18n.translations = {};

I18n.translations['en'] = {
    "Plus de publications": "See more",
    "Réserver maintenant": "Reserve now",
    "Vol direct disponible à cette date": "Direct flight available on this date",
    "Cette destination est accessible à l’année via correspondance, cliquez ci-dessous pour connaître les itinéraires possibles et les dates de départ": "This destination is accessible year-round via connecting flights; click below to see possible itineraries and dates of departure",
    "Recherchez un vol sur Google Flight": "Find a flight on Google Flight",
    "Retour au calendrier": "Back to calendar",
    "Vols directs du %s": "%s direct flights",
    "Liaison assurée par": "Connection provided by",
    "Voir les détails sur le site du transporteur": "See details on the airline’s website"
};
