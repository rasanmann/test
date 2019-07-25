(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.yqb_payments = {
    attach: function (context, settings) {
      $('button.is-loading').on('click', function(e){
        e.preventDefault();
        return false;
      });
    }
  };
})(jQuery, Drupal);
