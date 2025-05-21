(function ($) {

  Drupal.behaviors.searcFunctions = {
    attach: function (context, settings) {
      $('.men-block__title', context).off().on("click", function () {
        $(this).parent('div').toggleClass("opened-facet");
        $(this).siblings('ul').slideToggle();
      });
    }
  };

})(jQuery);
