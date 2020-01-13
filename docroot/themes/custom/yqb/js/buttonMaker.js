"use strict";

Drupal.behaviors.changeLinkToButton = {


  attach: function (context, settings) {
    this.vefiryIfUrl(context);
    var _this = this;
    jQuery(document, settings).on('pjax:success', function(){
      _this.vefiryIfUrl(context);
    });
  },


  vefiryIfUrl: function(context){
    console.log(context);
    if (window.location.href.indexOf('/fr/') !== -1) {
      this.HrefToButtonConverter('https://outlook.office365.com/owa/calendar/BCA1@yqb.onmicrosoft.com/bookings/', 'PRENDRE RENDEZ-VOUS')
    }

    //for button gtaa on page cizr
    if (window.location.href.indexOf('/fr/') !== -1) {
      this.HrefToButtonConverter('https://www.gtaa.org/pearsonawareness/index2.html', 'Commencer la formation'.toUpperCase())
    }
  },

  HrefToButtonConverter: function(href, text){
    var link = jQuery("a[href = '"+href+"']");
    link.text(text);
    link.css("background", "#0054ff");
    link.css("padding", "10px");
    link.css("border-radius", "10px");
    link.css("color", "white");
    link.css("text-decoration","none");

    link.on("mouseover", function(e){
      jQuery(e.currentTarget).css({
        "background": "#6699ff",
        "cursor": "pointer"
      });
    }).on("mouseout", function(e){
      jQuery(e.currentTarget).css({
        "background": "#0054ff",
        "cursor": "pointer"
      });
    });
  }

};
