(function ($) {

  /**
   * Use jquery expander library to post teaser
   */
  Drupal.behaviors.expandTeaser = {
    attach: function (context, settings) {
      var bodyText = $('.men-topic--activity').find('.body-text');
      var strings = {
        readMore: Drupal.t('Read more'),
        readLess: Drupal.t('Read less')
      };
      $(bodyText).expander({
        slicePoint: 500,
        expandEffect: 'fadeIn',
        expandSpeed: 350,
        collapseEffect: 'fadeOut',
        collapseSpeed: 350,
        expandText: strings.readMore,
        userCollapseText: strings.readLess
      });
    }

  };
})(jQuery);
