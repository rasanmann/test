function onloadCallback() {
  grecaptcha.render('recaptcha_element', {
    'sitekey': drupalSettings.yqb_payments.recaptcha.sitekey,
    'callback': 'submitPayment'
  });
}

function submitPayment(token) {
  jQuery('.recaptcha-submit').unbind('click').removeAttr('disabled');
}

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.yqb_payments = {
    attach: function (context, settings) {
      $('.recaptcha-submit', context).attr('disabled', 'disabled').on('click', function (e) {
        e.preventDefault();
        return false;
      });
    }
  };
})(jQuery, Drupal);
