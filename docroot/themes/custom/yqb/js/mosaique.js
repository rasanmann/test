(function ($, Drupal, Isotope) {
  Drupal.behaviors.yqb_mosaique = {

    modalSelector: '#mosaique-modal',

    attach: function (context, settings) {
      this.modal = document.querySelector(this.modalSelector);
      this.setupIsotope(context);
      this.setupModal();
    },

    addListeners: function() {
      $(document.body).on('click', '.field--name-field-mosaique-image > .field--item', function() {

      });
    },

    setupIsotope: function(context) {
      $('.field--name-field-mosaique-image', context).isotope({
        itemSelector: '.field--name-field-mosaique-image > .field--item',
        percentPosition: true,
        layoutMode: 'fitRows',
        masonry: {
          columnWidth: 50,
          gutter: 10
        }
      });
    },

    setupModal: function() {
      this.addListeners();
    }


  }
}(window.jQuery, window.Drupal, window.Isotope));