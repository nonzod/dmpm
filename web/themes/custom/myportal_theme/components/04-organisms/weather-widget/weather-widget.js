(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.myPortalThemeWeatherWidget = {
    attach: function (context, settings) {

      // Active dropdown widget in region toolbar.
      $('.weather-widget').once('myPortalThemeWeatherWidget').each(function () {
        dropdownClick($(this));
      });

      /**
       * Dropdown weather widget.
       *
       * @param element
       */
      function dropdownClick(element) {
        element.click(function (event) {
          if (event.target.matches('.weather-widget-location')
            || event.target.matches('.weather-widget-location--label')
            || event.target.closest("a")) {
            return;
          }

          element.toggleClass("open");
        });
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
