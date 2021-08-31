"use strict";

Drupal.behaviors.changeLinkToButton = {


  attach: function (context, settings) {
    this.vefiryIfUrl(context);
    var _this = this;
    jQuery(document).on('pjax:success', function(){
      _this.vefiryIfUrl(context);
    });
  },


  vefiryIfUrl: function(context){
      var langCode = drupalSettings ? drupalSettings.path.currentLanguage : document.documentElement.lang;
      if(langCode == 'fr'){
        this.HrefToButtonConverter('https://outlook.office365.com/owa/calendar/BCA1@yqb.onmicrosoft.com/bookings/', 'PRENDRE RENDEZ-VOUS','href-to-button-converter no-after')
        this.HrefToButtonConverter('https://www.gtaa.org/pearsonawareness/index2.html', 'Commencer la formation'.toUpperCase(), 'no-after')
      }else{
        this.HrefToButtonConverter('https://outlook.office365.com/owa/calendar/BCA1@yqb.onmicrosoft.com/bookings/', 'MAKE AN APPOINTMENT','href-to-button-converter no-after')
        this.HrefToButtonConverter('https://www.gtaa.org/pearsonawareness/index2.html', 'Start training'.toUpperCase(), 'no-after')
      }
  },

  HrefToButtonConverter: function(href, text, css_class){
    var link = jQuery("a[href = '"+href+"']");
    if(css_class) {
      link.addClass(css_class);
    }
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
