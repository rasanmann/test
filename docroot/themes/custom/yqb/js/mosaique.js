(function ($, Drupal, Siema) {
    Drupal.behaviors.yqb_mosaique = {

        backdrop: null,
        caroussel: null,
        carousselContainer: null,
        selector: {
            backdrop: '.backdrop',
            caroussel: '.siema',
            carousselContainer: '.caroussel-outer',
            closeBtn: 'button.close',
            galleryTrigger: '.field--name-field-paragraph-media-mosaique > .field--item',
            medias: '.paragraph--type--mosaique .contextual-region',
            nextBtn: '.next',
            prevBtn: '.prev'
        },

        attach: function (context, settings) {
            this.setupBackdrop();
            this.setupCaroussel();
            this.addListeners();
        },

        addListeners: function () {
            var self = this;

            $(document).on('click', this.selector.galleryTrigger, function () {
                self.createCloseEvent();
                self.caroussel.goTo($(self.selector.galleryTrigger).index(this));
                // on ne peut pas encore utiliser les propriétés backdrop et
                // carousselContainer à ce stade
                document.querySelector(self.selector.backdrop).classList.add('in');
                document.querySelector(self.selector.carousselContainer).classList.add('in');
            });
        },

        close: function () {
            if (this.backdrop && this.caroussel) {
                this.backdrop.classList.remove('in');
                this.carousselContainer.classList.remove('in');
            }
        },

        createCloseEvent: function () {
            var self = this;

            $('button.close').one(function (e) {
                self.close();
            });

            $(document.body).one('click', function (e) {
                e.stopPropagation();

                self.close();
            });


        },

        getItems: function () {
            var items = Array.prototype.slice.call(document.querySelectorAll(this.selector.medias));

            return items.map(function (el) {
                if (el.querySelector('img') !== null) {
                    return el.querySelector('img').src;
                }
                else if (el.querySelector('video') !== null) {
                    return el.querySelector('source').getAttribute('src');
                }
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
                    list += '<div class="siema-item-container">' +
                        '<button class="close" name="close">X</button>';
                    if (this.isVideo(items[i])) {
                        list += '<video controls="controls">' +
                            '<source src="' + items[i] + '" type="video/mp4">' +
                            '</video>'
                    }
                    else {
                        list += '<img src="' + items[i] + '"/>';
                    }
                    list += '</div>';
                }
                var controls = '<div class="controls">' +
                    '<button class="prev"><span style="background-image: none;" class="icon icon-left-arrow-2"><svg id="Calque_1" xmlns="http://www.w3.org/2000/svg" width="126.9" height="68.1" viewBox="0 0 126.9 68.1"><style>.st0{fill:#05f}</style><path class="st0" d="M125.8 6.1l-59.9 61c-1.4 1.4-3.6 1.4-5 0L1 6.1C-.4 4.7-.4 2.4 1 1S4.6-.4 6 1l57.4 58.4L120.8 1c1.4-1.4 3.6-1.4 5 0 .7.7 1 1.6 1 2.5.1 1.1-.3 2-1 2.6z"></path></svg></span></button>' +
                    '<button class="next"><span style="background-image: none;" class="icon icon-right-arrow-2"><svg id="Calque_1" xmlns="http://www.w3.org/2000/svg" width="126.9" height="68.1" viewBox="0 0 126.9 68.1"><style>.st0{fill:#05f}</style><path class="st0" d="M125.8 6.1l-59.9 61c-1.4 1.4-3.6 1.4-5 0L1 6.1C-.4 4.7-.4 2.4 1 1S4.6-.4 6 1l57.4 58.4L120.8 1c1.4-1.4 3.6-1.4 5 0 .7.7 1 1.6 1 2.5.1 1.1-.3 2-1 2.6z"></path></svg></span></button>' +
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
                    self.updateControlsStyle();
                });
                document.querySelector(this.selector.nextBtn).addEventListener('click', function (e) {
                    e.stopPropagation();
                    self.caroussel.next();
                    self.updateControlsStyle();
                });
                document.querySelector(this.selector.caroussel).addEventListener('click', function (e) {
                    e.stopPropagation();
                });

                this.carousselContainer = document.querySelector(this.selector.carousselContainer);
            }
        },

        isVideo: function (el) {
            return el.indexOf('.mp4') !== -1;
        },

        updateControlsStyle: function() {
            if (this.caroussel.currentSlide === 0) {
                $(this.selector.prevBtn).css('display', 'none');
                $(this.selector.nextBtn).css('display', 'block');
            } else if ((this.caroussel.currentSlide + 1) === this.caroussel.innerElements.length) {
                $(this.selector.prevBtn).css('display', 'block');
                $(this.selector.nextBtn).css('display', 'none');
            } else {
                $(this.selector.prevBtn).css('display', 'block');
                $(this.selector.nextBtn).css('display', 'block');
            }

        }
    }
}(window.jQuery, window.Drupal, window.Siema));