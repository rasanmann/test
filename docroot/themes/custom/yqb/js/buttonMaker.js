jQuery(document).ready(function($){

  function HrefToButtonConverter(href, text) {
    var link = $("a[href = '"+href+"']");
    link.text(text)
    link.css("background", "#0054ff")
    link.css("padding", "10px")
    link.css("border-radius", "10px")
    link.css("color", "white")
    link.css("text-decoration","none")
  }
  if(window.location.href.indexOf('/fr/') !== -1) {
    HrefToButtonConverter('https://outlook.office365.com/owa/calendar/BCA1@yqb.onmicrosoft.com/bookings/', 'PRENDRE RENDEZ-VOUS')
  }
});
