(function ($, Drupal, Isotope) {
  Drupal.behaviors.yqb_mosaique = {

    attach: function (context, settings) {
      $('.field--name-field-mosaique-image', context).isotope({
        itemSelector: '.field--name-field-mosaique-image > .field--item ',
        percentPosition: true,
        layoutMode: 'fitRows',
        masonry: {
          columnWidth: 50,
          gutter: 10
        }
      });
    }
  }
}(window.jQuery, window.Drupal, window.Isotope));