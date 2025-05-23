/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {
  var options = {
    keys: ["title"],
    threshold: 0.4
  };

  Drupal.behaviors.myaccess_filter = {
    attach: function attach(context) {
      var fuse = new Fuse(drupalSettings.myaccess.applications, options);
      $('#applications-grid__input').once('applications-grid__input').keyup(function () {
        var value = $('#applications-grid__input').val().trim();
        var tile = $('#applications-grid .applications__tile');
        var results = null;

        if (value === '') {
          tile.each(function () {
            $(this).show();
          });

          return;
        }

        results = fuse.search(value);

        tile.each(function () {
          $(this).hide();
        });

        results.forEach(function (el) {
          $('[data-application-id=' + el.item.id + ']').show();
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
