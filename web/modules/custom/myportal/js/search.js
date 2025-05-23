/**
 * DO NOT EDIT THIS FILE.
 * See the following change record for more information,
 * https://www.drupal.org/node/2815083
 * @preserve
 **/

window.MyPortalSearch = {};

(function ($, Drupal) {
  Drupal.behaviors.MyPortalSearch = {
    attach: function attach(context) {
      var $body = $('body'),
          $navbar = $('.navbar-collapse'),
          $searchTabList = $('.search--tab-list'),
          $searchTabListActive = $('.search--tab-list').find('a.active'),
          $searchClose = $('.search--result-close'),
          $regionContent = $('.region--content'),
          $searchCloseMobile = $('.search--tab-bottom'),
          pathname = window.location.pathname;
          query_search = getParameterByName('fulltext_search');

      if ((typeof query_search !== 'undefined' && null !== query_search) || /\/search/i.test(pathname)) {
        $('#block-exposedformsearch-sitepage-1-2').show();
      }

      $body.once('myp-search-wrapper').each(function () {
        $body.append('<div id="myp-search-wrapper"><div id="js-search-grid__close" class="search-grid__close"><svg class="icon icon-close"><use xlink:href="#icon-close"></use></svg><div class="sr-only">Close</div></div></div>');
      });


      // Open list of sources on mobile
      $searchTabListActive.once('showListSource').on('click', function () {
        $searchTabList.toggleClass('search-show-sources');
      })

      // Hide advanced filter on click Close
      $('.btn-filter-close').once('HideFilter').on('click', function (e) {
        e.preventDefault();
        $('.filter-wrapper-inner').hide();
        $('.btn-filter-search-show').show();
      })

      // Show filters when clicking on button show filter
      $('.btn-filter-search-show').once('ShowFilter').on('click', function (e) {
        e.preventDefault();
        $('.filter-wrapper-inner').show();
        $(this).hide();
      })

      // Hide clear button when click on it
      $('.btn-filter-clear').once('HideClearButton').on('click', function () {
        $(this).hide();
      })

      function searchClose($button) {
        $button.once('HideResultsGsuite').on('click', function () {
          $($button).hide();
          $('.search--result-item').hide();
          $regionContent.removeClass('men-provider--open');
          $('.search--tab-list').find('.active').removeClass('active');
          $('body').removeClass('disable-scroll-nooverlay');
        });
      }
      searchClose($searchClose);
      searchClose($searchCloseMobile);

      function handleMenus() {
        // Handle menus if open.
        $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
        $('.navbar-nav--item').removeClass('is-active');
        $('.men-header--region').removeClass('men-header-open men-header-open_mega');
        $('.main-container').removeClass('men-nav_is-open');
        if ($navbar.hasClass("in")) {
          $navbar.collapse('hide');
        }
      }

      function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
          results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
      }

      //Close search modal
      $('#js-search-grid__close', context).once('search__close').click(function () {
        $('.myportal-search-form').remove();
        $('#myp-search-wrapper').hide();
        $body.removeClass('disable-scroll');
        $('body:not(.path-search)').find('#block-exposedformsearch-sitepage-1-2').hide();
      });

      $('.use-ajax-fullscreen', context).once('use-ajax-fullscreen').on('click', function () {
        $body.addClass('disable-scroll');
        handleMenus();
      });

      $('.account__search_site .use-ajax-fullscreen', context).once('use-ajax-fullscreen-search').on('click', function () {
        $('#applications-grid-wrapper').hide();
      });

      Drupal.ajax.BindFullscreenAjaxLinks(document.body);
    }
  };

  Drupal.ajax.BindFullscreenAjaxLinks = function (element) {
    $(element).find('.use-ajax-fullscreen').once('ajax').each(function (i, ajaxLink) {
      var $linkElement = $(ajaxLink);

      var elementSettings = {
        progress: { type: 'fullscreen' },
        dialogType: $linkElement.data('dialog-type'),
        dialog: $linkElement.data('dialog-options'),
        dialogRenderer: $linkElement.data('dialog-renderer'),
        base: $linkElement.attr('id'),
        element: ajaxLink
      };
      var href = $linkElement.attr('href');

      if (href) {
        elementSettings.url = href;
        elementSettings.event = 'click';
      }
      Drupal.ajax(elementSettings);
    });
  };
})(jQuery, Drupal);
