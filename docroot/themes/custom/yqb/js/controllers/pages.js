var Drupal = Drupal || {};

var Pages = (function ($, Drupal, Bootstrap) {

    var self = {};

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Pages constructed');

        // Initialize things

        return self;
    };

    self.destruct = function () {
        console.log('Pages destructed');

        // Clean up and uninitialize things

    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.visit = function () {
        console.log('Visit page initialized');

	    // Responsive imgs IE
	    App.findImgHeight();
    };

	self.mission = function () {
		console.log('Mission/Vision page initialized');

		// Responsive imgs IE
		App.findImgHeight();
	};
	self.checkIn = function () {
		console.log('Check-In page initialized');
		// L'app avait finalement besoin de target blank
		// if (window.location.search.indexOf('webview=1') > -1) {
		//
		// 	$('.view-tiles').find('a').each(function() {
		// 		var $this = $(this);
		// 		$this.removeAttr('target');
		//
		// 	});
		// 	$('.view-tiles a[rel="blank"]').click(function (e) {
		// 		$('.region-content').append('<div class="loading-extern-link"></div>')
	     //    });
		// }

	};

	self.appMobile = function () {
		console.log('appMobile page initialized');

		// L'app avait finalement besoin de target blank
        // $('.region-content a[rel="blank"]').click(function (e) {
			// $('.region-content').append('<div class="loading-extern-link"></div>')
        // });
		$('.nav-tabs a.special').click(function (e) {
			e.preventDefault()
			$(this).tab('show');
			var $this = $(this);
			var tab = $this.attr('href');
			if(!$this.hasClass('is-init')){
				var pz = PinchZoomer.init($(tab).find('img'));
				$this.addClass('is-init');
			}
        });

		initSlideSwipe();

		if($('#map').length !=0){
			App.loadGoogleMaps(initializeMap);
		}

		var $btnHelp = $('#btn-call-help');
		if($btnHelp.length && getParameterByName('caller_id') != null){
			var callerId = getParameterByName('caller_id');

			$btnHelp.attr('href', $btnHelp.attr('href') + ',' + callerId);
		}
	};

	self.storage = function(){
		console.log('Coat check / storage page initialized');

		// Add th to td data attribute for responsive table
		$('article.full ').find('table').each(function() {
			var $table = $(this),
				$th = $table.find('tr:eq(1)');

			$table.find('td').each(function () {
				var $this = $(this),
				index = $this.index();

				$this.attr('data-label', $th.find('td:eq('+index+')').text());
			})
		});
	};

	self.vip = function(){
		initSwiperSliderWithThumbs();
	};

    /** -----------------------------
     * Events
     --------------------------------- */

	/** ------------------------------
	 * Private
	 --------------------------------- */


	var initSlideSwipe = function () {
		$('body').find('.swiper-container').each(function () {
			var $swiper = $(this);
			$swiper.find('ul').addClass('swiper-wrapper');
			$swiper.find('li').each(function () {
				var $this = $(this);
				$this.addClass('swiper-slide');

			});

			var swiper = new Swiper($swiper, {
				centeredSlides: false,
				spaceBetween: 20,
				slidesPerView: "auto",
				watchSlidesProgress: true,
				watchSlidesVisibility: true,
				loop: false,
				onInit: function (swiper, event) {
					$swiper.addClass('is-init');
				},
				onTransitionEnd: function(swiper){
					var $lastSlide = $(swiper.container).find('li.swiper-slide:last-child');
					if(swiper.virtualSize + swiper.translate === swiper.width){
						$lastSlide.addClass('is-virtual-active');
						$lastSlide.prev().addClass('is-virtual-inactive');
					}else{
						if($lastSlide.hasClass('is-virtual-active')){
							$lastSlide.removeClass('is-virtual-active');
							$lastSlide.prev().removeClass('is-virtual-inactive');
						}
					}
				}
			});
		});
	};

	var initializeMap = function () {
		console.log('initializeMap');

		var adress = new google.maps.LatLng(46.7907719, -71.3907577);
		var mapOptions = {
			center: adress,
			zoom: 12,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			streetViewControl: false,
			panControl: false,
			zoomControl: true,
			mapTypeControl: false,
			styles: [
			    {
			        "featureType": "administrative",
			        "elementType": "labels.text.fill",
			        "stylers": [
			            {
			                "color": "#444444"
			            }
			        ]
			    },
			    {
			        "featureType": "landscape",
			        "elementType": "all",
			        "stylers": [
			            {
			                "color": "#f2f2f2"
			            }
			        ]
			    },
			    {
			        "featureType": "poi",
			        "elementType": "all",
			        "stylers": [
			            {
			                "visibility": "off"
			            }
			        ]
			    },
			    {
			        "featureType": "road",
			        "elementType": "all",
			        "stylers": [
			            {
			                "saturation": -100
			            },
			            {
			                "lightness": 45
			            }
			        ]
			    },
			    {
			        "featureType": "road.highway",
			        "elementType": "all",
			        "stylers": [
			            {
			                "visibility": "simplified"
			            }
			        ]
			    },
			    {
			        "featureType": "road.highway",
			        "elementType": "geometry.fill",
			        "stylers": [
			            {
			                "color": "#0054ff"
			            },
			            {
			                "visibility": "simplified"
			            }
			        ]
			    },
			    {
			        "featureType": "road.highway",
			        "elementType": "geometry.stroke",
			        "stylers": [
			            {
			                "visibility": "simplified"
			            }
			        ]
			    },
			    {
			        "featureType": "road.highway",
			        "elementType": "labels.text.fill",
			        "stylers": [
			            {
			                "color": "#ffffff"
			            }
			        ]
			    },
			    {
			        "featureType": "road.arterial",
			        "elementType": "labels.icon",
			        "stylers": [
			            {
			                "visibility": "off"
			            }
			        ]
			    },
			    {
			        "featureType": "transit",
			        "elementType": "all",
			        "stylers": [
			            {
			                "visibility": "off"
			            }
			        ]
			    },
			    {
			        "featureType": "water",
			        "elementType": "all",
			        "stylers": [
			            {
			                "color": "#0054ff"
			            },
			            {
			                "visibility": "on"
			            }
			        ]
			    },
			    {
			        "featureType": "water",
			        "elementType": "geometry.fill",
			        "stylers": [
			            {
			                "color": "#89d2f7"
			            }
			        ]
			    }
			]
		};

		var mapElement = document.getElementById('map');
		var map = new google.maps.Map(mapElement, mapOptions);
		var marker = new google.maps.Marker({
			position: adress,
			icon: window.location.origin + '/themes/custom/yqb/img/maps/marker-app.svg'
		});
		marker.setMap(map);
	};

    // Return class
    return self.construct();
});
