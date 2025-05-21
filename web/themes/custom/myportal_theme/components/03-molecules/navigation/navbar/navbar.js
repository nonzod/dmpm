(function ($) {

  /**
   * Apply class to header on scroll and click
   */
  Drupal.behaviors.initNavbar = {
    attach: function (context, settings) {

      const MEN_HEADER = $(".men-header");

      // Add class to header after some scroll, so backgorund can be animated
      $(window).scroll(function() {    
        var scroll = $(window).scrollTop();
        if (scroll >= 50) {
          MEN_HEADER.addClass("men-header--fixed");
        } else if (scroll < 50) {
          MEN_HEADER.removeClass("men-header--fixed");
        }
      });

      //Add class to header region and main-content when menu is open
      const NAVBAR_TOGGLE = $('#men-navbar-toggle');
      const MAIN_CONTAINER = $('.main-container');
      const HEADER_REGION = $('.men-header--region');

      NAVBAR_TOGGLE.once('openMenu').on('click', function(){
        HEADER_REGION.toggleClass('men-header-open');
        MAIN_CONTAINER.toggleClass('men-nav_is-open');
        if (HEADER_REGION.hasClass('men-header-open_mega')) {
          HEADER_REGION.removeClass('men-header-open_mega');
          $('.navbar-nav--item.is-active').removeClass('is-active');
          $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
        }
      });

      MAIN_CONTAINER.once('restoreHeader').on('click', function () {
        if (HEADER_REGION.hasClass('men-header-open')) {
          HEADER_REGION.removeClass('men-header-open men-header-open_mega');
          MAIN_CONTAINER.removeClass('men-nav_is-open');
          $('.navbar-nav--item').removeClass('is-active');
          $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
        }
      })

      // Hide app when search is open
      $('.men-search_trigger').once('searchTrigger').on('click', function () {
        $('#applications-grid').remove();
        $('#applications-grid-wrapper').hide();
      })

    }

  };

})(jQuery);
