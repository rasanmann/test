(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.yqb_payments = {
    attach: function (context, settings) {
      $('button :submit').on('click', function(e){
        e.preventDefault();
        $(e.currentTarget).attr('disabled', true);
      });
    }
  };

})(jQuery, Drupal);
