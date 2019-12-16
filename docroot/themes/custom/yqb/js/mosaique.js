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
            medias: '.paragraph--type--mosaique .field--name-field-paragraph-media-mosaique .field--item img, ' +
                '.paragraph--type--mosaique .field--name-field-paragraph-media-mosaique .field--item video',
                // '.video-stream html5-main-video',
                // '.field--item iframe.media-oembed-content',
            nextBtn: '.next',
            prevBtn: '.prev'
        },

        arrAllIframes: [],

        attach: function (context, settings) {
          var _this = this;
          $(document, settings).on('pjax:success', function(){
            $('#siema').remove();
            _this.setupBackdrop();
            _this.setupCaroussel();
            _this.addListeners();
            _this.fullScreen();
          });
            this.setupBackdrop();
            this.setupCaroussel();
            this.addListeners();
            this.fullScreen();
            this.putLabelOnvideo();
            this.fixIframeHeight();
            // this.allIframesVideos();
            },



        addListeners: function () {
            var self = this;

          $(document).on('click', '.field--name-field-paragraph-media-mosaique > .field--item a', function(e) {
            e.preventDefault();
          });

            $(document).on('click', this.selector.galleryTrigger, function (e) {
                if($(this).find('.video-nb.full').length) return;
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                self.createCloseEvent();
                self.caroussel.goTo($(self.selector.galleryTrigger).index(this));
                // on ne peut pas encore utiliser les propriétés backdrop et
                // carousselContainer à ce stade
                self.updateControlsStyle();
                $(self.selector.backdrop).addClass('in');
                $(self.selector.carousselContainer).addClass('in');
            });
        },

        close: function () {
            if($('.field--name-field-paragraph-media-mosaique').find('.video-nb.full').length) return;
            if (this.backdrop && this.caroussel) {
                $(this.selector.backdrop).removeClass('in');
                $(this.selector.carousselContainer).removeClass('in');

                $(this.componentId + ' ' + this.selector.closeBtn).unbind('click');
                $(document.body).unbind('click');

                $(this.componentId).find('video').each(function () {
                    this.pause();
                    this.currentTime = '0.0';
                });
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

        isIframe: function(el){
          if(typeof(el) == "string" && (el.indexOf("oembed") != -1)){
            return true;
          }

        },

        createCarousselHTML: function () {
            var list = '';
            var items =   this.getItems();
            var videoId = 1;

            for (var i = 0; i < items.length; i++) {
                list += '<div class="siema-item-container">';



              if (items[i].video === true){
                list += '<p class="label-over-video-in-carousel">' + items[i].label + '</p>'
              }
              list +=
                    '<button class="close" name="close">' +
                    '<svg xmlns:x="http://ns.adobe.com/Extensibility/1.0/" xmlns:i="http://ns.adobe.com/AdobeIllustrator/10.0/" xmlns:graph="http://ns.adobe.com/Graphs/1.0/" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" x="0px" y="0px" viewBox="0 0 77.07 77.07" enable-background="new 0 0 77.07 77.07" xml:space="preserve"><g><g i:extraneous="self"><g><g><polygon points="0,7.071 7.071,0 77.07,69.998 69.999,77.07 0,7.071"></polygon></g><g><polygon points="0,69.998 69.999,0 77.07,7.071 7.071,77.07 0,69.998"></polygon></g></g></g></g></svg>' +
                    '</button>';

              if (items[i].video === true) {
                  list += '<div class="video-full-screen" data-id="video-nb-' + videoId + '"></div>' +
                      '<video id="carousel-video-nb-' + videoId + '" controls="controls">' +
                      '<source src="' + items[i].src + '" type="video/mp4">' +
                      '</video>';

                videoId++;
              } else if(this.isIframe(items[i])){
                list += '<iframe src="' + items[i] +   '" width=\"100%\" height=\"480\"></iframe>';
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
            var iframes = this.allIframesVideos();
            var intLastVideoIndex;


            //as the iframes are being put after the images we need to find the last video and insert them there
            for(i = 0; i < items.length; i++){
              if (items[i].nodeName !== 'VIDEO'){
                intLastVideoIndex = i;
                break
              }
           }
            // items = items.concat(iframes);
            items.splice(intLastVideoIndex, 0, ...iframes)


            items = items.concat(iframes);


          return items.map(function (el) {

              if (typeof(el) == "string" && (el.indexOf("oembed") != -1)){
                return el;
              }
              else if (el.nodeName && el.nodeName === 'IMG') {
                return el.src;
              }
              else if(el.nodeName && el.nodeName === 'VIDEO') {

                // var hook = $(el).closest( ".video-nb").siblings(".field--name-field-media-video-file").children();
                var label = $($(el).parent().parent().next(".field--name-field-text-over-video")[0]).find(".field--item").text();

                var objElement = {
                  'src' : el.querySelector('source').getAttribute('src'),
                  'label' : label,
                  'video' : true
                };

                // return el.querySelector('source').getAttribute('src');
                return objElement;
              }

          });


        },

        // Trigger fullScreen in the mosaique
        fullScreen: function () {
          $(".video-full-screen").on('click', function (e) {
            var videoId = $(this).attr('data-id');

            var videoCaroussel = $('#carousel-'+videoId)[0];
            var video = $('.'+videoId + ':eq( 0 ) video')[0];

            videoCaroussel.pause();
            video.currentTime = videoCaroussel.currentTime;

            var isFullScreen = false;

            $(video).on('fullscreenchange', function() {
              if(isFullScreen) {
                video.pause();
                videoCaroussel.currentTime = video.currentTime;
                $('.'+videoId + ':eq( 0 )').removeClass('full');
                $(video).off('fullscreenchange');
              }

              isFullScreen = !isFullScreen
            });

            $('.'+videoId + ':eq( 0 )').addClass('full');
            video.requestFullscreen();
          });
        },

        isVideo: function (el) {
          return typeof(el) === "object" ? true : false;
            // return el.indexOf('.mp4') !== -1;
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
                var html = this.createCarousselHTML(), self = this;

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
                    draggable: false,
                    multipleDrag: false,
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

        },

        putLabelOnvideo: function(){
          var query = $(".field--name-field-media-video-file");
          $.each(query,function(index, value){
            //find the video
            var video = $(value).find("video").first();

            //get the label
            var hook = $(value).next(".field--name-field-text-over-video")[0];
            var label = $(hook).children(".field--item").first().text();

            //append the label to the video container
            $(value).prepend(`<p style="position:absolute; color:white; padding: 5px;">${label}</p>`);

            //remove original label
            $(hook).children(".field--item").css({"visibility":"hidden"});
          });
        },

        //feature #100921
        fixIframeHeight: function(){
          $(window).on("resize load",function(){
            var videoHeight = $("video").height();
            $(".field--item iframe.media-oembed-content").css("height", videoHeight);

            // console.log($(".field--item iframe.media-oembed-content .video-stream .html5-main-video"));
          });

        },

      allIframesVideos: () => {
          var iframes = document.querySelectorAll("iframe");
          var arrIframes = [];


          Array.from(iframes).forEach(function (iframe){
            // arrIframes.push(iframe.contentDocument.querySelector("iframe"));
            // console.log(iframe.contentWindow.location.href);
            // arrIframes.push(iframe.cloneNode(true));
            arrIframes.push(iframe.contentWindow.location.href);
            // console.log(iframe.contentDocument.querySelector("video"));
            // this.iframeVideos.push($(iframe.contentDocument).find("video"));
          });
          return arrIframes;

      },




    };
}(window.jQuery, window.Drupal, window.Siema));
