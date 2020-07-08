jQuery(document).ready(function($){

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
          800: {
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
            800: {
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

});
