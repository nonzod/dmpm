(function ($, Drupal) {
  Drupal.behaviors.myPortalWeatherWidget = {

    attach: function (context) {

      /**
       * Helper function to trigger ajax commands upon a successful Ajax request.
       */
      function triggerCommands(data) {
        var ajaxObject = Drupal.ajax({
          url: "",
          base: false,
          element: false,
          progress: false
        });

        // Trigger any any ajax commands in the response.
        ajaxObject.success(data, "success");
      }

      // Search input widget location and init autocomplete plugin.
      $('input.weather-widget-location').each(function () {
        var $input = $(this);

        $input.autocomplete({
          delay: 500,
          minLength: 3,
          classes: {
            "ui-autocomplete": "myportal-weather-widget--location-autocomplete-results",
          },
          source: function (request, response) {
            const path = `myportal/weather/autocomplete/location?q=${request.term}`;
            $.ajax({
              type: "GET",
              url: Drupal.url(path),
              success: function (data) {
                response(data);
              }
            });
          },
          select: function (event, ui) {
            if (ui.item) {
              const path = `myportal/weather/ajax-widget?lname=${ui.item.label}`;
              // Use the value for retrieve the widget weather.
              $.ajax({
                type: "GET",
                url: Drupal.url(path),
                success: function (data) {
                  triggerCommands(data);
                }
              });
            }
          },
          open: function () {
            $(this).autocomplete('widget').css('z-index', 1100);
            return false;
          },
        });
        $input.autocomplete("instance")._resizeMenu = function () {
          var ul = this.menu.element;
          var container = this.element.parent().parent().parent();
          var padding = 30;
          ul.outerWidth(container.outerWidth() - padding);
          ul.outerHeight(container.outerHeight() - this.element.parent().outerHeight() - padding);
          ul.css('background-color', this.element.css('background-color'));
        };

      });
    }
  };
})(jQuery, Drupal, drupalSettings);
