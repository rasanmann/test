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
        hostFaqCat = location.protocol + "//" + location.host + "/faq_ajax/category/get/"+lang+"/";

      $(document).on('click touchend', '#subCategoriesFaqMenu .subCategoriesFaq__link', function (e) {
        e.preventDefault();
        // Icon loading
        $loadingIcon = '<div class="ajax-progress ajax-progress-fullscreen"><span class="throbber"></span></div>';
        $('body').append($loadingIcon);
        let tid = $(this).data('tid');
        if (!tid) {
          $('.ajax-progress-fullscreen').remove();
          return false;
        }
        href = hostFaqCat + tid;
        $("#subCategoryFaqContent").load(href);
      })
    },
  }
})(jQuery);
