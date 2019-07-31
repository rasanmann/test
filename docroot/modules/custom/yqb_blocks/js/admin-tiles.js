(function (Drupal, $) {

  Drupal.behaviors.yqb_blocks_homepagetiles = {
    attach: function (context, settings) {
      $(context).find('#table-ajax-container').find('input.tile-button:not(:disabled)').removeClass('is-disabled').removeAttr('disabled');
    },
    detach: function (context, settings) {
      $(context).find('#table-ajax-container').find('input.tile-button').addClass('is-disabled').attr('disabled', 'disabled');
    }
  }

})(Drupal, jQuery);
