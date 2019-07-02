(function ($, Drupal, Isotope, Siema) {
    Drupal.behaviors.yqb_mosaique = {

        caroussel: null,
        selector: {
            backdrop: '.backdrop',
            caroussel: '.siema',
            carousselContainer: '.caroussel-outer',
            galleryTrigger: '.field--light-gallery'
        },

        attach: function (context, settings) {
            this.setupIsotope(context);
            this.setupCaroussel();
            this.addListeners();
        },

        addListeners: function() {
            var self = this;

            $(document).on('click', this.selector.galleryTrigger, function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.setupBackdrop();
                document.querySelector(self.selector.carousselContainer).classList.add('in');
            });
        },

        getItems: function() {
            var items = Array.prototype.slice.call(document.querySelectorAll('.field--light-gallery > img'));

            return items.map(function(el) {
                return el.src;
            });
        },

        setupBackdrop: function() {
            if (document.querySelector(this.selector.backdrop) === null) {
                document.body.insertAdjacentHTML(
                    'beforeend',
                    '<div class="backdrop"></div>'
                );
            }
            document.querySelector(this.selector.backdrop).classList.add('in');
        },

        setupCaroussel: function () {
            if (document.querySelector(this.selector.caroussel) === null) {
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
}(window.jQuery, window.Drupal, window.Isotope, window.Siema));