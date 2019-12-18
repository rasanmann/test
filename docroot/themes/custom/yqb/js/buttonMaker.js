jQuery(document).ready(function($){


  console.log('hello world');
  if(window.location.href.indexOf('/fr/') !== -1) {
    var link = $("a[href = 'https://outlook.office365.com/owa/calendar/BCA1@yqb.onmicrosoft.com/bookings/']");
    link.text("PRENDRE RENDEZ-VOUS")
  }
  // link.css("background", "#0054ff")
  // link.css("paddind", "15px")
  // link.css("border-radius", "10px")
  // link.css("color", "white")
  console.log(window.location.href.indexOf('/fr/'));


});
