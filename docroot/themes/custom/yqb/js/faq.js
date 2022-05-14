(function ($) {
  $(document).ready(() => {

    // Trigger mobile faq menu
    var faqMenu = $('.categoriesFaq__menu');
    if(faqMenu) {
        $('.categoriesFaqTrigger__menu').click(function (e) {
            faqMenu.toggleClass('opened');
            if(faqMenu.hasClass('opened')){
                $('.categoriesFaqTrigger__menu').attr({
                    'aria-expanded': true,
                    'aria-selected': true
                });
                $('.categoriesFaqTrigger__menu').attr({
                    'aria-hidden': false
                });
            } else {
                $('.categoriesFaqTrigger__menu').attr({
                    'aria-expanded': false,
                    'aria-selected': false
                });
                $('.categoriesFaqTrigger__menu').attr({
                    'aria-hidden': true
                });
            }
            e.preventDefault();
        });
        $(document).mouseup(function(e) {
            var container = $('.categoriesFaq__wrapper-menu');
            if (!container.is(e.target) && container.has(e.target).length === 0) {
              console.log(e.target)
              faqMenu.removeClass('opened');
            }
        });
    }
  });
}(jQuery));
