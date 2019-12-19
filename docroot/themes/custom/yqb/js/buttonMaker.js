jQuery(document).ready(function($){

  function HrefToButtonConverter(href, text) {
    var link = $("a[href = '"+href+"']");
    link.text(text)
    link.css("background", "#0054ff")
    link.css("padding", "10px")
    link.css("border-radius", "10px")
    link.css("color", "white")
    link.css("text-decoration","none")

    link.on("mouseover", function(e){
      $(e.currentTarget).css({
        "background": "#6699ff",
        "cursor": "pointer"
      });
    }).on("mouseout", function(e){
      $(e.currentTarget).css({
        "background": "#0054ff",
        "cursor": "pointer"
      });
    });
  }



  if(window.location.href.indexOf('/fr/') !== -1) {
    HrefToButtonConverter('https://outlook.office365.com/owa/calendar/BCA1@yqb.onmicrosoft.com/bookings/', 'PRENDRE RENDEZ-VOUS')
  }
});
