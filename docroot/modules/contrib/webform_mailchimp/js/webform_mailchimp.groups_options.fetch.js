(function($, Drupal) {
  /**
   * Adds updated Mailchimp groups to edit form for a particular set of
   * predefined options. Adds additional form inputs if needed to accommodate
   * all groups.
   */
  Drupal.AjaxCommands.prototype.fetchGroupsOptions = function (ajax, response, status) {
    // Make sure we have enough inputs to add values to.
    var options = Object.entries(response.options);
    var num_options = options.length;
    var num_inputs = $('input').filter(function() {
      return this.id.match(/^edit\-options\-options\-items\-\d+\-value/);
    }).html("match").length;

    // Add inputs on form if we need them
    if (num_inputs <= num_options) {
      var num_to_add = num_options - num_inputs + 1;
      // Update select box with num rows to add on form
      $("[id^=edit-options-options-add-more-items]").val(num_to_add);
      // Trigger Add button on form
      $("[id^=edit-options-options-add-submit]").mousedown();
    }

    // First, we had to make sure we had enough inputs to add our options.
    // Since this had to happen before we update the inputs, we have to make a
    // separate Ajax call for the values update.

    // Add a progress throbber for the inputs update
    $('#webform-mailchimp-fetch-submit')
        .append('<div class="ajax-progress ajax-progress-throbber"><div class="throbber">&nbsp;</div><div class="message">Updating form...</div></div>');

    $.ajax({
      success: function(data) {
        for (var i = 0; i < options.length; i++) {
          $('input[name="options[options][items][' + i + '][value]"]').val(options[i][0]);
          $('input[name="options[options][items][' + i + '][text]"]').val(options[i][1]);
        }
        // For any remaining inputs, reset value to empty
        for (; i < num_inputs; i++) {
          $('input[name="options[options][items][' + i + '][value]"]').val('');
          $('input[name="options[options][items][' + i + '][text]"]').val('');
        }
        // Remove throbber
        $('.ajax-progress').remove();
      }
    });
  }

})(jQuery, Drupal);
