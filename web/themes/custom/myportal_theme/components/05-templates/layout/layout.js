(function ($) {

  /**
   * Apply class to region complementary on small device
   */
  Drupal.behaviors.initSidebar = {
    attach: function (context, settings) {

      const REG_CONTENT = $('.region--content');
      const REG_COMPLEMENTARY = $('.region--complementary');
      const FACETS_HP = $('.region--complementary--facets');
      const MEN_TRIGGER_TAB = $('.men-trigger__tab');
      const COMPLEMENTARY_BLOCK = $('.region--complementary-blocklist');
      let offsetFacet = 0;
      let heightBlock = 0;
      if (COMPLEMENTARY_BLOCK.length) {
        offsetFacet = COMPLEMENTARY_BLOCK.offset().top;
        heightBlock = COMPLEMENTARY_BLOCK.innerHeight();
      }
      let positionFacet = (offsetFacet+heightBlock);
      let widthBlock = COMPLEMENTARY_BLOCK.innerWidth();

      let toolbarHeight = $('.region--toolbar').innerHeight();
      let headerHeight = $('.men-header--region').innerHeight();
      let menHeader = $('.men-header').innerHeight();
      let regionContentTop = $('.region--content-top').innerHeight();
      let toolbarAdminHeight = 0;
      if ($('#toolbar-bar').length) {
        toolbarAdminHeight = $('#toolbar-bar').innerHeight();
      }

      let topPositionComplementary = toolbarHeight + headerHeight + regionContentTop + toolbarAdminHeight;
      //Add class to REG_COMPLEMENTARY when trigger filters button
      MEN_TRIGGER_TAB.each(function(){
        $(this).once('initSidebar').on('click', function(){
          REG_COMPLEMENTARY.toggleClass('men-show__sidebar');
          REG_CONTENT.toggleClass('men-sidebar--is-show');

          $(this).parent('li').toggleClass('men-trigger__tab-full').siblings().toggleClass('men-trigger__tab-hide');
          $('.men-trigger__search-provider').removeClass('men-trigger__tab-full');
          $('.region--sidebar-second').removeClass('men-show__sidebar');
          $('.search--result-close').trigger('click');

        });
      });

      //Add badge if filters are applied
      let facetItem = REG_COMPLEMENTARY.find('.facets-checkbox');

      facetItem.each(function(){
        if($(this).prop('checked')) {
          REG_COMPLEMENTARY.addClass('men-filter__applied');
          return false;
        } else {
          REG_COMPLEMENTARY.removeClass('men-filter__applied');
        }
      });

      $(window).scroll(function() {
        let scroll = $(window).scrollTop();
        if ((scroll + regionContentTop + menHeader) >= positionFacet) {
          FACETS_HP.addClass("facets--fixed");
          // 104px = Top toolbar and navbar
          FACETS_HP.css({"top": topPositionComplementary, "width": widthBlock});
        } else {
          FACETS_HP.removeClass("facets--fixed");
          FACETS_HP.removeAttr("style");
        }
      });

    }

  };

  Drupal.behaviors.triggerProvider = {
    attach: function (context, settings) {

      const REG_CONTENT = $('.region--content');
      const REG_SIDEBAR_SECOND = $('.region--sidebar-second');
      const MEN_TRIGGER_PROVIDER = $('.men-trigger__search-provider button');

      //Add class to REG_COMPLEMENTARY when trigger filters button
      MEN_TRIGGER_PROVIDER.once('triggerProvider').on('click', function(){
        REG_SIDEBAR_SECOND.toggleClass('men-show__sidebar');
        REG_CONTENT.toggleClass('men-sidebar--is-show');

        $(this).parent('li').toggleClass('men-trigger__tab-full').siblings().toggleClass('men-trigger__tab-hide');
        $('.men-trigger__tab').parent('li').removeClass('men-trigger__tab-full');
        $('.region--complementary').removeClass('men-show__sidebar');
        $('.search--result-close').trigger('click');
      });
    }

  };

  /**
   * Back to top button functionality
   */
  Drupal.behaviors.backToTop = {
    attach: function (context, settings) {

      const BUTTON_TO_TOP = $('#men-scroll-top');
      const ROOT_ELEMENT = document.documentElement;

      function scrollToTop() {
        ROOT_ELEMENT.scrollTo({
          top: 0,
          behavior: "smooth"
        })
      }

      $(window).scroll(function() {
        var scroll = $(window).scrollTop();
        if (scroll >= 700) {
          BUTTON_TO_TOP.addClass("men-back_is-shown");
        } else {
          BUTTON_TO_TOP.removeClass("men-back_is-shown");
        }
      });

      BUTTON_TO_TOP.once('backToTop').on('click', function(){
        scrollToTop();
      });

    }

  };

  /**
   * New sidebar feature hide/show
   * and push main container
   * For now it handle only the page navigation (taxonomy)
   * we don't need in other pages
   */
  Drupal.behaviors.newSidebarPush = {
    attach: function (context, settings) {

      if ($('#block-taxonomylistblock').length) {
        let isThereSidebar = $('#block-taxonomylistblock').children().length;
        if (isThereSidebar > 0) {
          $('.region--complementary').addClass('men-complementary-is_full');
        }
      }

      $('.men-trigger__filter-full').once('SidebarPush').on('click', function(){
        $('.men-layout__full').toggleClass('men-complementary_open');
      })

    }
  };

  /**
   * Accordion for filter
   * on complementary region
   * on homepage
   */
  Drupal.behaviors.accordionFilter = {
    attach: function (context, settings) {

      let facetTitle = $('.men-filter-home').find('.men-block__title');

      facetTitle.each(function () {
        $(this).once('accordion_filter').on('click', function () {
          if ($(this).hasClass('facet-open')) {
            $(this).removeClass('facet-open');
            $(this).siblings('.js-facets-widget').removeClass('facet-open--content');
          } else {
            $(this).addClass('facet-open');
            $(this).siblings('.js-facets-widget').addClass('facet-open--content');
            $(this).parents('.block-facets').siblings().find('.men-block__title').removeClass('facet-open');
            $(this).parents('.block-facets').siblings().find('.js-facets-widget').removeClass('facet-open--content');
          }
        })
      })
    }
  };

  /**
   * Hide thumb when click on it and start video.
   */
  Drupal.behaviors.localThumbnailVideo = {
    attach: function (context, settings) {

      let videoThumb = $('.men-video--thumbnail');

      videoThumb.each(function () {
        $(this).once('video_click').on('click', function () {
          $(this).hide();
          $(this).siblings().find('video').get(0).play();
        });
      })
    }
  };

  /**
   * Region content top.
   */
  Drupal.behaviors.regionContentTop = {
    attach: function (context, settings) {
      let region__content_top = $('.region--content-top');

      if (region__content_top.length) {
        region__content_top.affix({
          offset: {
            top: function () {
              return (this.top = $('.region--hero').outerHeight(true) - region__content_top.outerHeight(true))
            }
          }
        });
        region__content_top.on('affixed.bs.affix', function () {
          $('.region--hero').css('margin-bottom', region__content_top.outerHeight(true));
          region__content_top.css('top', $('.men-header').outerHeight(true));
        });
        region__content_top.on('affixed-top.bs.affix', function () {
          $('.region--hero').css('margin-bottom', 0);
          region__content_top.css('top', 0);
        });
      }
    }
  };

})(jQuery);
