jQuery(window).bind('beforeunload', function (e) {
  return Drupal.t('Are you sure you want to cancel the current transaction?')
});

window.addEventListener('beforeunload', function (e) {
  e.preventDefault();
  e.returnValue = Drupal.t('Are you sure you want to cancel the current transaction?');
});
