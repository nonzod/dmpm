/**
 * @file
 * Defines Javascript behaviors for the myaccess module.
 */

window.MyAccess = { };

(function ($, Drupal) {
  Drupal.behaviors.myaccess = {
    attach: (context) => {
      const body = $('body');
      const navbar = $('.navbar-collapse');

      body.once('applications-grid-wrapper').each(() => {
          body.append('<div id="applications-grid-wrapper"></div>')
      });

      $('#applications-favorite-wrapper').once('applications-favorite-wrapper').each(() => {
        MyAccess.loadFavorites.execute();
      });

      $('#js-applications-grid__close', context)
        .once('applications-grid__close')
        .click(function() {
          MyAccess.loadFavorites.execute();
          $('#applications-grid').remove();
          $('#applications-grid-wrapper').hide();
          body.removeClass('disable-scroll')
        });

      function handleMenus() {
        //Handle menus if open
        $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
        $('.navbar-nav--item').removeClass('is-active');
        $('.men-header--region').removeClass('men-header-open men-header-open_mega');
        $('.main-container').removeClass('men-nav_is-open');
        if (navbar.hasClass('in')) {
          navbar.collapse('hide');
        }
      }

      $('.use-ajax-fullscreen', context).once('use-ajax-fullscreen').on('click', function () {
        body.addClass('disable-scroll');
        handleMenus();
        $('#myp-search-wrapper').hide();
      });

      Drupal.ajax.BindFullscreenAjaxLinks(document.body);
    },
  };

  /**
   *
   */
  MyAccess.loadFavorites = Drupal.ajax({
    url: Drupal.url('myaccess/applications-favorite'),
    progress: {type: 'none'},
  });

  /**
   *
   * @param element
   * @constructor
   */
  Drupal.ajax.BindFullscreenAjaxLinks = element => {
    // Bind Ajax behaviors to all items showing the class.
    $(element)
      .find('.use-ajax-fullscreen')
      .once('ajax')
      .each((i, ajaxLink) => {
        const $linkElement = $(ajaxLink);

        const elementSettings = {
          progress: {type: 'fullscreen'},
          dialogType: $linkElement.data('dialog-type'),
          dialog: $linkElement.data('dialog-options'),
          dialogRenderer: $linkElement.data('dialog-renderer'),
          base: $linkElement.attr('id'),
          element: ajaxLink,
        };
        const href = $linkElement.attr('href');
        /**
         * For anchor tags, these will go to the target of the anchor rather
         * than the usual location.
         */
        if (href) {
          elementSettings.url = href;
          elementSettings.event = 'click';
        }
        Drupal.ajax(elementSettings);
      });
  };
}(jQuery, Drupal));
