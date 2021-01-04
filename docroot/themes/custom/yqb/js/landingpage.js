(function ($) {
    $(document).ready(() => {
      
      // To manage tag subscription in MailChimp
      if($('#campaign_tag_landing_page').length){
        $('input[name=campaign_tag]').val($('#campaign_tag_landing_page').val());
      }

      // Set Primary and secondary color on button
      if($('.block-child').length){
            $( ".block-child" ).each(function( index ) {
                if($(this).find('.button--primary').length){
                    $(this).find('.button--primary').css({'color' : $(this).find('.button--primary').css('color') , 'background-color' : $(this).find('.button--primary').attr('data-secondary')})
                }
                if($(this).find('iframe').length){ 
                  var iframe = $(this).find('iframe');
                  iframe.wrap( "<div class='embed-responsive embed-responsive-16by9'></div>");
                }
            });
        }
    });
  }(jQuery));