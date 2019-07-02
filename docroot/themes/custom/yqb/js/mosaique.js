(function ($, Drupal, Isotope) {
    Drupal.behaviors.yqb_mosaique = {

        carousselSelector: '.siema',
        caroussel: null,

        attach: function (context, settings) {
            this.setupIsotope(context);
            this.setupCaroussel();
        },

        setupCaroussel: function () {
            if (document.querySelector(this.carousselSelector) === null) {
                var list = '';
                var items = this.getItems();

                for (var i = 0; i < items.length; i++) {
                    list += '<img src="' + items[i] + '"/>';
                }
                var controls = '<div class="controls">' +
                    '<button class="prev"><-</button>' +
                    '<button class="next">-></button>' +
                    '</div>';
                var caroussel = '<div class="siema">' + list + '</div>';

                document.body.insertAdjacentHTML(
                    'beforeend',
                    '<div class="caroussel-outer">' +
                    caroussel +
                    controls +
                    '</div>'
                );

                document.querySelector('.prev').addEventListener('click', () => this.caroussel.prev());
                document.querySelector('.next').addEventListener('click', () => this.caroussel.next());

                this.caroussel = new Siema({
                    selector: '.siema',
                    duration: 200,
                    easing: 'ease-out',
                    perPage: 1,
                    startIndex: 0,
                    draggable: true,
                    multipleDrag: true,
                    threshold: 20,
                    loop: false,
                    rtl: false
                });
            }
        },

        setupIsotope: function (context) {
            $('.field--name-field-mosaique-image', context).isotope({
                itemSelector: '.field--name-field-mosaique-image > .field--item',
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