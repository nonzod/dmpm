(function ($) {

  /**
   * Toggle accordion
   */
  Drupal.behaviors.toggleAccordion = {
    attach: function (context, settings) {
      let accordionTrigger = $('.men-accordion--trigger');
      let accordionContent = $('.men-accordion--content');

      if (accordionTrigger.length) {
        accordionTrigger.once('accordionTrig').each(function () {
          $(this).on('click', function () {
            $(this).next(accordionContent).slideToggle();
            $(this).toggleClass('men-accordion_open');
          })
        })
      }
    }

  };
})(jQuery);
