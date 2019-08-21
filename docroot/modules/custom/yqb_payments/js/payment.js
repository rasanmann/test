(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.yqb_payments = {
    attach: function (context) {
      $(context).on('click', '.btn.is-clicked.is-loading', function (e) {
        e.preventDefault();
        return false;
      });

      $(context).on('click', 'form [type="submit"]', function () {
        if (!$(this).hasClass('is-clicked')) {
          $(this).addClass('is-clicked').addClass('is-loading');
          $(context).find('.payment-processing').stop(true).fadeIn().css('display', 'flex');
        }
      });

      $(document).on('moneris.transaction_error', function () {
        $(document).find('form [type="submit"]').removeClass('is-clicked').removeClass('is-loading');
        $(document).find('.payment-processing').stop(true).fadeOut().css('display', 'none');
      });

      $(context).on('submit', 'form', function () {
        $(this).find('form [type="submit"]').addClass('is-clicked').addClass('is-loading');
      });
    }
  };

})(jQuery, Drupal);
