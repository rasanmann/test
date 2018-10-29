/**
 * @file
 * Javascript for Field Example.
 */

/**
 * Provides a calendar widget.
 */
(function ($) {
  // Adds a date if we don't have it yet, else remove it
  function addOrRemoveDate(dates, date) {
    var index = jQuery.inArray(date, dates);

    if (index >= 0) {
      return removeDate(dates, index);
    } else {
      return addDate(dates, date);
    }
  }

  function addDate(dates, date) {
    if (jQuery.inArray(date, dates) < 0) {
      dates.push(date);
    }

    return dates;
  }

  function removeDate(dates, index) {
    dates.splice(index, 1);

    return dates;
  }

  Drupal.behaviors.datetime_multiselect_calendar = {
    attach: function () {
      $('.field--widget-datetime-multiselect').each(function() {
        var $widget = $(this);

        var calendarContainerClass = 'datetime-multiselect-calendar-container';

        // Check if calendar has already been created
        if ($widget.find('.' + calendarContainerClass).length) {
          return true;
        }

        $widget.find('tbody, .field-add-more-submit').hide();

        var $sourceInput = $widget.find('input[type="date"]').attr('type', 'text');
        var $container = $sourceInput.closest('.field--type-datetime');

        // Get values
        var values = $sourceInput.map(function() { return this.value }).get();

        // Remove empty strings
        values = values.filter(function(value) {
          return (value);
        });

        // Remove duplicates
        values = values.filter(function(value) {
          var seen = {};
            return function(element, index, array) {
            return !(element in seen) && (seen[element] = 1);
          };
        });

        // Empty all inputs
        $sourceInput.val('');

        // Store all values in first input
        $sourceInput.filter(':first').val(values.join(';'));

        var $calendarContainer = $('<div class="' + calendarContainerClass + '"></div>');
            $calendarContainer.appendTo($widget);

        $calendarContainer.data('input', $sourceInput.filter(':first'));

        $calendarContainer.datepicker({
          dateFormat:'yy-mm-dd', // YYYY-MM-DD format
          numberOfMonths:3,
          onSelect: function (dateText, inst) {
            var $calendar = $(this);

            var values = $calendar.data('input').val().split(';').filter(function(value) { return (value); });

            var dates = addOrRemoveDate(values, dateText);

            $calendar.data('input').val(dates.join(';'));
          },
          beforeShowDay: function (date) {
            // Fired for each date in calendar, determines how to display things

            var $calendar = $(this);

            var formattedDate = $.datepicker.formatDate($calendar.datepicker('option', 'dateFormat'), date);

            var values = $calendar.data('input').val().split(';').filter(function(value) { return (value); });

            var gotDate = $.inArray(formattedDate, values);

            if (gotDate >= 0) {
              // Add value
              return [true, 'ui-state-error', 'Select date'];
            } else {
              // Remove value
              return [true, ''];
            }
          }
        });
      });
    },
    attachHeaderEvents:function(context) {
      context.find('thead th').on('click', function() {
        var index = $(this).parent().index();
        console.log('th', index);
        $(this).closest('table').find('tbody tr').each(function() {
          console.log($(this).find('td:eq(' + index + ') a').text());
          $(this).find('td:eq(' + index + ') a').trigger('click');
        });
      });
    }
  };
})(jQuery);
