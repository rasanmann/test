jQuery(window).bind('beforeunload', function (e) {
  if(jQuery(e.target.activeElement).is('button[type="submit"]')) return;
  return Drupal.t('Are you sure you want to cancel the current transaction?')
});

window.addEventListener('beforeunload', function (e) {
  if(jQuery(e.target.activeElement).is('button[type="submit"]')) return;
  e.preventDefault();
  e.returnValue = Drupal.t('Are you sure you want to cancel the current transaction?');
});
