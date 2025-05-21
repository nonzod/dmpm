(function ($, Drupal) {
  Drupal.behaviors.myPortalGroupSelectorWidget = {
    attach: function (context, settings) {
      $('.group-item--selected', context).click(function () {
        let groupId = $(this).attr('data-group-id');
        let selector = 'input[value="' + groupId + '"][data-drupal-selector^="edit-groups-container-details-"]';
        if ($(selector, context).length === 0) {
          selector = 'input[value="' + groupId + '"][data-drupal-selector^="edit-field-application-access-container-details-"]';
        }
        $(selector, context).click();
      });

      $('.form-boolean--type-checkbox', context).once('myPortalGroupCheckbox').change(function () {
        let counter = $(this).parents('div.form-checkboxes').find('input[type="checkbox"]:checked').length;
        $(this).parents('.claro-details').find('span.counter-wrapper').text(counter);
      });

      $('span.counter-wrapper', context).each(function () {
        if ($(this).text() !== '0') {
          $(this).parent().css('display', 'inline-block');
        }
        else {
          $(this).parent().hide();
        }
      });
    }
  };
})(jQuery, Drupal);
