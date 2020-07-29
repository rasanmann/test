(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
    console.log('init swiper');
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


      if($('#block-homepagetilesblock-2 .swiper-container').length && $(window).width() < 600){ 
        var tilesSwiper = new Swiper ('#block-homepagetilesblock-2 .swiper-container', {
          direction: 'horizontal',
          loop: true,
          slidesPerView: 1,
          navigation: {
              nextEl: '#block-homepagetilesblock-2 .swiper-button-next',
              prevEl: '#block-homepagetilesblock-2 .swiper-button-prev',
            },
        })

      }
    }
  };
})(jQuery, Drupal);
