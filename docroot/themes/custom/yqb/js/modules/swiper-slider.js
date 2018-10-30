var Drupal = Drupal || {};

var SwiperSlider = (function ($, Drupal, Bootstrap) {

    var self = {};

    /** ------------------------------
     * Constructor/Destructor
     --------------------------------- */

    self.construct = function () {
        console.log('Swiper slider constructed');

        // Initialize things

        return self;
    };

    self.destruct = function () {
        console.log('Swiper slider destructed');

        // Clean up and uninitialize things

    };
    
    self.initWithThumbs = function(){
        initSwiperSliderWithThumbs();
    };


    var initSwiperSliderWithThumbs = function () {
        // https://github.com/nolimits4web/Swiper/issues/1209#issuecomment-164832557
        var galleryTop = new Swiper('.slider-gallery-top', {
            onSlideChangeEnd: function (swiper) {
                var activeIndex = swiper.activeIndex;
                $(galleryThumbs.slides).removeClass('is-selected');
                $(galleryThumbs.slides).eq(activeIndex).addClass('is-selected');
                galleryThumbs.slideTo(activeIndex, 500, false);
            }
        });

        galleryTop.once('sliderMove', function () {
            hideTitle();
        });

        var galleryThumbs = new Swiper('.slider-gallery-thumbs', {
            centeredSlides: false,
            spaceBetween: 20,
            slidesPerView: 'auto',
            touchRatio: 0.2,
            breakpoints: {
                768: {
                    centeredSlides: true,
                    slideToClickedSlide: true
                }
            },
            onClick: function (swiper, event) {
                var clicked = swiper.clickedIndex;
                if (clicked != undefined) {
                    swiper.activeIndex = clicked;
                    swiper.updateClasses();
                    $(swiper.slides).removeClass('is-selected');
                    $(swiper.clickedSlide).addClass('is-selected');
                    galleryTop.slideTo(clicked, 500, false);
                    hideTitle();
                }
            }
        });
    };

    var hideTitle = function () {
        $('.block-slider-gallery').addClass('is-fading');

        setTimeout(function () {
            $('.block-slider-gallery h1').addClass('is-display-none');
        }, 300);
    };


    // Return class
    return self.construct();
});