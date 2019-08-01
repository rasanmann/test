(function (Drupal, $) {

  Drupal.behaviors.yqb_blocks_homepagetiles = {
    attach: function (context, settings) {
      $(context).find('input.tile-button').removeClass('is-disabled').removeAttr('disabled');
      $(context).find('input.tile-button:not(:disabled)').on('mousedown', function () {
        $(context).find('input.tile-button:not(:disabled)').addClass('is-disabled').attr('disabled', 'disabled');
      });
    }
  }

})(Drupal, jQuery);
