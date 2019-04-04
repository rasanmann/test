var onloadCallback = function () {
  grecaptcha.render('recaptcha_element', {
    'sitekey': drupalSettings.yqb_bills.recaptcha.sitekey,
    'callback': 'submitPayment'
  });
};

var submitPayment = function (token) {

};
