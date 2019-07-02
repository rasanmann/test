(function ($, Drupal, Isotope, Siema) {
    Drupal.behaviors.yqb_mosaique = {

        backdrop: null,
        caroussel: null,
        carousselContainer: null,
        selector: {
            backdrop: '.backdrop',
            caroussel: '.siema',
            carousselContainer: '.caroussel-outer',
            galleryTrigger: '.field--light-gallery',
            nextBtn: '.next',
            prevBtn: '.prev'
        },

        attach: function (context, settings) {
            this.setupIsotope(context);
            this.setupBackdrop();
            this.setupCaroussel();
            this.addListeners();
        },

        addListeners: function () {
            var self = this;

            $(document).on('click', this.selector.galleryTrigger, function (e) {
                self.createCloseEvent();
                self.caroussel.goTo($(self.selector.galleryTrigger).index(this));
                // on ne peut pas encore utiliser les propriétés backdrop et
                // carousselContainer à ce stade
                document.querySelector(self.selector.backdrop).classList.add('in');
                document.querySelector(self.selector.carousselContainer).classList.add('in');
            });
        },

        createCloseEvent: function () {
            var self = this;

            $(document.body).one('click', function (e) {
                e.stopPropagation();

                self.close();
            });
        },

        getItems: function () {
            var items = Array.prototype.slice.call(document.querySelectorAll('.field--light-gallery > img'));

            return items.map(function (el) {
                return el.src;
            });
        },

        setupBackdrop: function () {
            if (document.querySelector(this.selector.backdrop) === null) {
                document.body.insertAdjacentHTML(
                    'beforeend',
                    '<div class="backdrop"></div>'
                );
                this.backdrop = document.querySelector(this.selector.backdrop);
            }
        },

        setupCaroussel: function () {
            if (document.querySelector(this.selector.caroussel) === null) {
                var self = this;
                var list = '';
                var items = this.getItems();

                for (var i = 0; i < items.length; i++) {
                    list += '<img src="' + items[i] + '"/>';
                }
                var controls = '<div class="controls">' +
                    '<button class="prev"><span style="background-image: none;" class="icon icon-left-arrow-2"><svg id="Calque_1" xmlns="http://www.w3.org/2000/svg" width="126.9" height="68.1" viewBox="0 0 126.9 68.1"><style>.st0{fill:#05f}</style><path class="st0" d="M125.8 6.1l-59.9 61c-1.4 1.4-3.6 1.4-5 0L1 6.1C-.4 4.7-.4 2.4 1 1S4.6-.4 6 1l57.4 58.4L120.8 1c1.4-1.4 3.6-1.4 5 0 .7.7 1 1.6 1 2.5.1 1.1-.3 2-1 2.6z"></path></svg></span></button>' +
                    '<button class="next"><span style="background-image: none;" class="icon icon-right-arrow-2"><svg id="Calque_1" xmlns="http://www.w3.org/2000/svg" width="126.9" height="68.1" viewBox="0 0 126.9 68.1"><style>.st0{fill:#05f}</style><path class="st0" d="M125.8 6.1l-59.9 61c-1.4 1.4-3.6 1.4-5 0L1 6.1C-.4 4.7-.4 2.4 1 1S4.6-.4 6 1l57.4 58.4L120.8 1c1.4-1.4 3.6-1.4 5 0 .7.7 1 1.6 1 2.5.1 1.1-.3 2-1 2.6z"></path></svg></span></button>' +
                    '<button class="close">x</button>' +
                    '</div>';
                var caroussel = '<div class="siema">' + list + '</div>';

                document.body.insertAdjacentHTML(
                    'beforeend',
                    '<div class="caroussel-outer">' +
                    caroussel +
                    controls +
                    '</div>'
                );

                this.caroussel = new Siema({
                    selector: self.selector.caroussel,
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

                document.querySelector(this.selector.prevBtn).addEventListener('click', function (e) {
                    e.stopPropagation();
                    self.caroussel.prev();
                });
                document.querySelector(this.selector.nextBtn).addEventListener('click', function (e) {
                    e.stopPropagation();
                    self.caroussel.next();
                });
                document.querySelector(this.selector.caroussel).addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                this.carousselContainer = document.querySelector(this.selector.carousselContainer);
            }
        },

        close: function () {
            if (this.backdrop && this.caroussel) {
                this.backdrop.classList.remove('in');
                this.carousselContainer.classList.remove('in');
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