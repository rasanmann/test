(function ($, Drupal, Siema) {
    Drupal.behaviors.yqb_mosaique = {

        backdrop: null,
        caroussel: null,
        componentId: '#siema',
        selector: {
            backdrop: '.backdrop',
            caroussel: '#siema',
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

            $(document).on('click', this.selector.galleryTrigger, function (e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                self.createCloseEvent();
                self.caroussel.goTo($(self.selector.galleryTrigger).index(this));
                // on ne peut pas encore utiliser les propriétés backdrop et
                // carousselContainer à ce stade
                $(self.selector.backdrop).addClass('in');
                $(self.selector.carousselContainer).addClass('in');
            });
        },

        close: function () {
            if (this.backdrop && this.caroussel) {
                $(this.selector.backdrop).removeClass('in');
                $(this.selector.carousselContainer).removeClass('in');
                $(this.componentId + ' ' + this.selector.closeBtn).unbind('click');
                $(document.body).unbind('click');
            }
        },

        createCloseEvent: function () {
            var self = this;

            $(this.componentId).on('click', this.selector.closeBtn, function () {
                self.close();
            });

            $(document.body).one('click', function (e) {
                e.stopPropagation();
                self.close();
            });
        },

        createCarousselHTML: function () {
            var list = '';
            var items = this.getItems();

            for (var i = 0; i < items.length; i++) {
                list += '<div class="siema-item-container">' +
                    '<button class="close" name="close">' +
                    '<svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="times" class="svg-inline--fa fa-times fa-w-11" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 352 512"><path fill="currentColor" d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"></path></svg>' +
                    '</button>';
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
            var caroussel = '<div id="siema">' + list + '</div>';

            return '<div class="caroussel-outer">' +
                caroussel +
                controls +
                '</div>';
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

        isVideo: function (el) {
            return el.indexOf('.mp4') !== -1;
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
                var html = this.createCarousselHTML();
                var self = this;

                document.body.insertAdjacentHTML(
                    'beforeend',
                    html
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

                this.updateControlsStyle();
            }
        },

        updateControlsStyle: function () {
            if (this.caroussel.currentSlide === 0) {
                $(this.selector.prevBtn).addClass('disabled');
                $(this.selector.nextBtn).removeClass('disabled');
            }
            else if ((this.caroussel.currentSlide + 1) === this.caroussel.innerElements.length) {
                $(this.selector.prevBtn).removeClass('disabled');
                $(this.selector.nextBtn).addClass('disabled');
            }
            else {
                $(this.selector.prevBtn).removeClass('disabled');
                $(this.selector.nextBtn).removeClass('disabled');
            }

        }
    }
}(window.jQuery, window.Drupal, window.Siema));