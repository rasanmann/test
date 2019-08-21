(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.moneris = {
    attach: function (context, settings) {
      $('.moneris-frame', context).each(function () {
        var form = $(this).closest('form', context),
          input = $('input[data-frame-id=' + $(this).attr('id') + ']', context),
          frame = $(this),
          endpoint = settings.moneris.endpoint + '/HPPtoken/index.php';

        function handleFrameMessages(event) {
          if (event.origin !== settings.moneris.endpoint)
            return;

          var data = JSON.parse(event.data);
          if ($.inArray("001", data.responseCode) !== -1) {
            input.attr('value', data.dataKey);
            input.val(data.dataKey);
            console.log(input.val());
            form.unbind('submit').submit();
          } else {
            if (data.errorMessage) {
              $.each(data.responseCode, function (index, code) {
                var message;
                switch (code) {
                  case "940":
                    message = Drupal.t('Invalid profile id (on tokenization request)');
                    break;
                  case "941":
                    message = Drupal.t('Error generating token');
                    break;
                  case "942":
                    message = Drupal.t('Invalid Profile ID, or source URL');
                    break;
                  case "943":
                    message = Drupal.t('Card data is invalid (not numeric, fails mod10, we will remove spaces)');
                    break;
                  case "944":
                    message = Drupal.t('Invalid expiration date (mmyy, must be current month or in the future)');
                    break;
                  case "945":
                    message = Drupal.t('Invalid CVD data (not 3-4 digits)');
                    break;
                  default:
                    message = Drupal.t('Something went wrong, we were unable to process the transaction.');
                }
                input.before($('<div class="alert alert-danger alert-dismissible status-message moneris-error" role="alert"></div>').html(message));
                $(document).trigger('moneris.transaction_error');
              });
            }
          }
        }

        if (window.addEventListener) {
          window.addEventListener("message", handleFrameMessages, false);
        } else {
          if (window.attachEvent) {
            window.attachEvent("onmessage", handleFrameMessages);
          }
        }

        form.on('submit', function (e) {
          e.preventDefault();
          $('.moneris-error', context).remove();
          frame.get(0).contentWindow.postMessage('tokenize', endpoint);
          return false;
        })
      });
    }
  }

})(jQuery, Drupal);
