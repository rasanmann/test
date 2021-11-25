(function ($) {
  $(document).on("ajaxSuccess", function() {
    $('.ajax-progress-fullscreen').remove();
  });

  Drupal.behaviors.get_faq_ajax = {

    attach: function () {
      this.addEvents();
    },

    addEvents: function () {
      let lang = $('html').attr('lang');
      let hostFaqCat = location.protocol + "//" + location.host +"/"+lang+"/faq_ajax/category/get/"+lang+"/";

      $(document).on('click touchend', '#subCategoriesFaqMenu .subCategoriesFaq__link', function (e) {
        e.preventDefault();
        let tid = $(this).data('tid')
            parentLi = $(this).parent('.subCategoryFaq__menu-item');

        if (!tid || parentLi.hasClass('active')) {
          return false;
        }
        // Icon loading
        $loadingIcon = '<div class="ajax-progress ajax-progress-fullscreen"><span class="throbber"></span></div>';
        $('#subCategoriesFaqMenu .subCategoryFaq__menu-item.active').removeClass('active')
        parentLi.addClass('active');
        $('body').append($loadingIcon);

        href = hostFaqCat + tid;
        $("#subCategoryFaqContent").load(href);
      })
    },
  }
})(jQuery);
