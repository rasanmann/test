jQuery(document).ready(function($){

  if($('.block-views-blockslider-homepage-block-1 .swiper-container').length){
    console.log('init swiper v6');
    var homepageSwiper = new Swiper ('.block-views-blockslider-homepage-block-1 .swiper-container', {
        // Optional parameters
        direction: 'horizontal',
        loop: true,
        effect: 'fade',
        speed: 5000,
        autoplay: {
            delay: 5000,
        },
        // If we need pagination
        // pagination: {
        //   el: '.swiper-pagination',
        // //   type: 'bullets',
        //   renderBullet: function (index, className) {
        //     return '<span class="' + className + '">' + (index + 1) + '</span>';
        //   },
        //   clickable: true,
        // },

        pagination: '.swiper-pagination',
    
        // Navigation arrows
        // navigation: {
        //   nextEl: '.swiper-button-next',
        //   prevEl: '.swiper-button-prev',
        // },
    

      })
  }

});
