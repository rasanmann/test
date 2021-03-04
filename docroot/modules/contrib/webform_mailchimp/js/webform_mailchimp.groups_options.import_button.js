(function($, Drupal) {

  // Adds a button to the Webform Options configuration form to open the
  // Webform Mailchimp import groups form
  Drupal.behaviors.addImportGroupsOptionsButton = {
    attach:function (context) {
      $(context).find('ul.action-links').append('<li><a href="/admin/structure/webform/config/options/import-webform-mailchimp-options" class="button button-action button--primary button--small" data-drupal-link-system-path="/admin/structure/webform/config/options/import-webform-mailchimp-options">Import Mailchimp Groups options</a></li>')
    }
  };

})(jQuery, Drupal);
