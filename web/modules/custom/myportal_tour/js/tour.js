(function ($, Drupal, drupalSettings) {
    'use strict';
    Drupal.behaviors.Toursite = {
        attach: function (context, settings) {
            let nextLabel = Drupal.t('Next');
            let lastLabel = Drupal.t('End tour');

            let toolbarIcon = $('.toolbar-icon-help'),
            closeTooltip = $('.joyride-close-tip'),
            nextTooltip = $('.joyride-next-tip'),
            lastTooltip = nextTooltip.last();
            // Becuase we need a text to be translated
            nextTooltip.text(nextLabel);
            lastTooltip.text(lastLabel);

            if (typeof drupalSettings.tourid !== "undefined") {
                let tourId = drupalSettings.tourid,
                    tourIdCheck = localStorage.getItem("readTour" + tourId),
                    tourIdRead =  drupalSettings.tour.hasOwnProperty(tourId) ? drupalSettings.tour[tourId] : null;

                if (tourIdCheck !== "1" && (tourIdRead || drupalSettings.access)) {
                    if (toolbarIcon.length) {
                        localStorage.setItem("readTour" + tourId, "1");
                        window.location = document.location.href + '?tour=1';
                    }
                }
            }

            // For backend administration pages
            toolbarIcon.once("Tourlabel").on("click", function () {
                // Because we need to wait until the markup is created
                setTimeout(function () {
                    let nextTooltip = $('.joyride-next-tip'),
                        lastTooltip = nextTooltip.last();
                    // Becuase we need a text to be translated
                    nextTooltip.text(nextLabel);
                    lastTooltip.text(lastLabel);
                }, 100)
            });

            closeTooltip.once("Toursite").on("click", function() {
                window.history.replaceState(null, null, window.location.pathname);
            });
        }
    };
}(jQuery, Drupal, drupalSettings));