(function ($) {

  /**
   * Init slick slider
   */
  Drupal.behaviors.initSlider = {
    attach: function (context, settings) {

      $(window).on('load', function () {
        initSlider();

        if($('body').hasClass('page-node-type-topic')) {
          var postTitle = "";
          var postCategory = "";

          if( $('header.page-title h1').length > 0 ) {
            postTitle = $('header.page-title h1').text().trim();
          }
          var attrPostCategory = $('article.parent-topic-container').attr('data-topic-type');
          if (typeof attrPostCategory !== 'undefined' && attrPostCategory !== false && attrPostCategory !== null) {
            postCategory = attrPostCategory;
          }

          if( postTitle!=="" && postCategory!=="" ) {
            gtag('event', 'post_interaction', {
              'post_action': 'impression',
              'post_category': postCategory,
              'post_title': postTitle,
              'interaction_type': ""
            });
          }
        }
      });

      // Run initialization sliders.
      initSlider();

      /**
       * Initialization all slider.
       */
      function initSlider() {

        // Slider in post content.
        $(context).find('.men-topic--activity .men-slider__in-content').once('initSliderImages').each(function () {
          var $this = $(this);
          if ($this.children().length > 0) {
            $this.slick({
              slidesToShow: 3,
              draggable: false,
              prevArrow: '<button type="button" class="slick-prev"><svg class="men-slider__icon"><use xlink:href="#icon-navigate_before"></use></svg></button>',
              nextArrow: '<button type="button" class="slick-next"><svg class="men-slider__icon"><use xlink:href="#icon-navigate_next"></use></svg></button>',
              responsive: [
                {
                  breakpoint: 1024,
                  settings: {
                    slidesToShow: 3
                  }
                },
                {
                  breakpoint: 960,
                  settings: {
                    slidesToShow: 2
                  }
                },
                {
                  breakpoint: 480,
                  settings: {
                    slidesToShow: 1
                  }
                }
              ]
            });
            $this.slickLightbox({
              src: 'href',
              itemSelector: '.slick-slide a'
            });
          }
        });

        // Slider in event content.
        $(context).find('.men-node--full .men-slider__in-content').once('initSliderEventFull').each(function () {
          var $this = $(this);
          if ($this.children().length > 0) {

            $this.slick({
              slidesToShow: 4,
              draggable: false,
              prevArrow: '<button type="button" class="slick-prev"><svg class="men-slider__icon"><use xlink:href="#icon-navigate_before"></use></svg></button>',
              nextArrow: '<button type="button" class="slick-next"><svg class="men-slider__icon"><use xlink:href="#icon-navigate_next"></use></svg></button>',
              responsive: [
                {
                  breakpoint: 1024,
                  settings: {
                    slidesToShow: 3
                  }
                },
                {
                  breakpoint: 960,
                  settings: {
                    slidesToShow: 2
                  }
                },
                {
                  breakpoint: 480,
                  settings: {
                    slidesToShow: 1
                  }
                }
              ]
            });
            $this.slickLightbox({
              src: 'href',
              itemSelector: '.slick-slide a'
            });
          }
        });

        // Slider in paragraphs.
        $(context).find('.paragraph--type--carousel').once('initSliderParagraph').each(function () {
          var $this = $(this);
          if ($this.children().length > 0) {

            $this.slick({
              slidesToShow: 3,
              draggable: false,
              centerMode: true,
              prevArrow: '<button type="button" class="slick-prev"><svg class="men-slider__icon"><use xlink:href="#icon-navigate_before"></use></svg></button>',
              nextArrow: '<button type="button" class="slick-next"><svg class="men-slider__icon"><use xlink:href="#icon-navigate_next"></use></svg></button>',
              responsive: [
                {
                  breakpoint: 1200,
                  settings: {
                    slidesToShow: 2
                  }
                },
                {
                  breakpoint: 900,
                  settings: {
                    slidesToShow: 2
                  }
                },
                {
                  breakpoint: 600,
                  settings: {
                    slidesToShow: 1
                  }
                },
                {
                  breakpoint: 450,
                  settings: {
                    slidesToShow: 1,
                    centerMode: false
                  }
                }
              ]
            });
            $this.slickLightbox();
          }
        });

        // Slider homepage.
        $(context).find('.slider__homepage').once('initSliderHomePage').each(function () {
          var $this = $(this);
          if ($this.children().length > 1) {

            $this.slick({
              arrows: false,
              autoplay: true,
              autoplaySpeed: 10000
            });
          }
        });
      }
    }
  };
})(jQuery);
