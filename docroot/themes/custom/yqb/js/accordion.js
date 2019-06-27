(function ($, Drupal) {
  Drupal.behaviors.yqb_accordion = {

    attach: function (context, settings) {
      $('.paragraph--type--tirroir .field--name-field-titre', context).on('click', function () {
        console.log(
            $(this).parent().find('.field--name-field-description')
        );
        $(this).parent().find('.field--name-field-description').slideToggle();
      });
    }
  }
})(jQuery, Drupal);