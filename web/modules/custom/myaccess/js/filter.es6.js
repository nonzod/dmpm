/**
 * @file
 * Defines Javascript behaviors for the myaccess module.
 */

(function ($, Drupal, drupalSettings) {
  const options = {
    keys: [
      "title",
      "description"
    ],
    threshold: 0.4
  };

  const fuse = new Fuse(drupalSettings.myaccess.applications, options);

  Drupal.behaviors.myaccess_filter = {
    attach: (context) => {
      $('#applications-grid__input').once('applications-grid__input').keyup(function () {
        const value = $('#applications-grid__input').val();
        const tile = $('#applications-grid .applications__tile');

        // If search key is empty show all results and return.
        if (value === '') {
          tile.each(function() {
            $(this).show();
          });

          return;
        }

        // Perform search.
        const results = fuse.search(value);


        // Hide all tiles.
        tile.each(function() {
          $(this).hide();
        });

        // Show only tiles that matches.
        results.forEach(function(el) {
          $('[data-application-id=' + el.item.id + ']').show();
        });

      })

    },
  };

}(jQuery, Drupal, drupalSettings));
