var Drupal = Drupal || {};

var Payments = (function ($, Drupal, Bootstrap) {

  var self = {};

  var $form = null;

  /** ------------------------------
   * Constructor/Destructor
   --------------------------------- */

  self.construct = function () {
    console.log('Payments constructed');

    // Initialize things
    $form = $('#layout-content').find('form');

    return self;
  };

  self.destruct = function () {
    console.log('Payments destructed');

    // Clean up and uninitialize things
    $(window).off('message');
    $form.off('submit');
  };

  /** -----------------------------
   * Public methods
   --------------------------------- */

  self.index = function () {
    console.log('Payments page initialized');

    $(window).on('message', onFrameMessage);
    $form.on('submit', onFormSubmit);

    // TODO : move to module
    $form.find('.btn-product-id').on('click', onProductBooking);

    $('form[target="results"]').on('submit', onResultsClick);
  };

  /** -----------------------------
   * Events
   --------------------------------- */

  var resetReCaptcha = function () {
    if ($('#recaptcha_element').length > 0) {
      if (grecaptcha) {
        grecaptcha.reset();
      }
    }
  };

  var validateReCaptcha = function () {
    var validated = true;

    if ($('#recaptcha_element').length > 0) {
      validated = false;
      if (grecaptcha && grecaptcha.hasOwnProperty('getResponse')) {
        if (grecaptcha.getResponse() == "") {
          $('.recaptcha-error').remove();
          $form.find('#recaptcha_element').append('<p class="recaptcha-error">' + Drupal.t('The reCAPTCHA field is required.') + '</p>');
          validated = false;
        }
        else {
          validated = true;
        }
      }
    }

    return validated;
  };


  var onResultsClick = function (ev) {
    var $form = $(this);

    if (!validateReCaptcha()) {
      ev.preventDefault();
      return false;
    }

    window.open($form.attr('action'), $form.attr('target'), 'scrollbars=1,resizable=1,width=740,height=690');

    // Weird bug with chrome
    setTimeout(function () {
      resetReCaptcha();
      $form.get(0).reset();
    }, 500);

    $form.find('button').removeClass('is-clicked is-loading');
  };

  // TODO : move to module
  var onProductBooking = function (ev) {
    console.log('onProductBooking');

    ev.preventDefault();

    var $form = $(this).closest('form');
    var $button = $form.find('.form-actions .btn[type="submit"]');

    $form.find('input[name="product_id"]').attr('disabled', true);

    $(this).parent().closest('.form-group').find('input[name="product_id"]').attr('disabled', false);

    // Trigger real submit button
    $button.trigger('click', [$(this)]);
  };

  var onFormSubmit = function (ev) {
    if ($form.data('submitting')) {
      ev.preventDefault();
      return false;
    }

    if (!validateReCaptcha()) {
      ev.preventDefault();
      return false;
    }

    var $moneris = $form.find('.moneris-frame');
    $moneris.prevAll('.alert').remove();

    if ($moneris.length) {
      $form.data('submitting', true);
      $form.find('button[type="submit"],input[type="submit"]').attr('disabled', 'disabled');
      var contentWindow = $moneris.get(0).contentWindow;
      contentWindow.postMessage('tokenize', $moneris.attr('src').replace(/\?(.*)/, ''));

      ev.preventDefault();
    }
  };

  var onFrameMessage = function (ev) {
    console.log('onFrameMessage');

    if (ev.originalEvent.data === 'recaptcha-setup') return;

    var respData = {};
    try{
      respData = JSON.parse(ev.originalEvent.data);
    }catch(e){
      return;
    }

    if (respData.dataKey) {
      // Great success

      // Insert token into form
      $form.find('input[name="data_key"]').val(respData.dataKey);

      // Remove event handler
      $form.off('submit', onFormSubmit);

      // Re-submit form
      $form.submit();
    }
    else {
      // Error
      $form.data('submitting', false);
      resetReCaptcha();
      var $alert = $('<div></div>');
      $alert.addClass('alert alert-danger');
      $alert.text(respData.errorMessage);

      $alert.insertBefore($form.find('#moneris_frame'));
      $form.find('button[type="submit"],input[type="submit"]').removeClass('is-clicked is-loading').removeAttr('disabled');
    }
  };

  // Return class
  return self.construct();
});
