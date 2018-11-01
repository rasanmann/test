var Drupal = Drupal || {};

var About = (function ($, Drupal, Bootstrap) {

    var self = {};

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('About constructed');

        // Initialize things

        return self;
    };

    self.destruct = function () {
        console.log('About destructed');

        // Clean up and uninitialize things

    };

    /** -----------------------------
     * Public methods
     --------------------------------- */

    self.index = function () {
        console.log('Index page initialized');
    };

    self.governance = function () {
        console.log('Governance page initialized');

	    $('.trigger-toggle').on('click', onToggleClick);
    };

    /** -----------------------------
     * Events
     --------------------------------- */

    var onToggleClick = function (ev) {
	    ev.preventDefault();
	    var $this = $(this),
		    $parent = $this.parent(),
		    $content = $this.next('.content-toggle'),
		    offsetTop;

		
		$('.content-toggle').addClass('is-collapsed');
		
	    // Default scrolltop value
	    if ($content.hasClass('is-collapsed')) {
		    offsetTop = parseInt($content.offset().top);
			$content.removeClass('is-collapsed');
	    } else {
		    offsetTop = parseInt($parent.offset().top);
			$content.addClass('is-collapsed');
	    }

	    // Closing button in content & adjust scrolltop
	    if ($parent.hasClass('content-toggle')) {
		    $content = $parent;
		    offsetTop = parseInt($content.parent().offset().top);
	    }

	    // If content is not next to trigger button
		if ($content.length <= 0) {
			$parent = $this.closest('.embed-responsive');
			$content = $parent.next('.content-toggle');

			var thisPosY = parseInt($this.position().top),
				parentPosY = parseInt($parent.position().top),
				finalPosY = thisPosY + parentPosY + 30; // 30 = height of trigger button

			$content.css('top', finalPosY);

			// Update scrolltop value
			if ($content.hasClass('is-collapsed')) {
				offsetTop = parseInt($this.offset().top);
				$content.removeClass('is-collapsed');
			} else {
				offsetTop = parseInt($parent.offset().top);
				$content.addClass('is-collapsed');
			}
		}


	    // Aria state
	    if ($content.hasClass('is-collapsed')) {
		    $content.attr('aria-hidden', 'true');
	    } else {
		    $content.attr('aria-hidden', 'false');
	    }

	    // Detect viewport & scroll to content
	    if (!Modernizr.touchevents) {
		    $('html, body').animate({
			    scrollTop:offsetTop
		    }, 750);
	    } else {
		    $('html, body').scrollTop(offsetTop);
	    }
    };

    // Return class
    return self.construct();
});