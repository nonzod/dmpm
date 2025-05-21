(function ($) {

  /**
   * Attach marquee effect.
   */
  Drupal.behaviors.marquee = {
    attach: function (context, settings) {

      $('.marquee').once('marquee').marquee({
        direction: 'left',
        duplicated: true,
        speed: 30,
        pauseOnHover: true,
      });

    }
  };

})(jQuery);
