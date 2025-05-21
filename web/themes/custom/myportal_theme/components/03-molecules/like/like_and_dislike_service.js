/**
 * @file
 * Like and dislike icons behavior.
 * Questo file è identico a quello in socialbase
 * Abbiamo cambiato solo il selettore dell'elemento che contiene i count dei like
 */
(function ($, Drupal) {

  'use strict';

  window.likeAndDislikeService = window.likeAndDislikeService || (function() {
    function likeAndDislikeService() {}
    likeAndDislikeService.vote = function(entity_id, entity_type, tag) {
      $.ajax({
        type: "POST",
        url: drupalSettings.path.baseUrl + 'like_and_dislike/' + entity_type + '/' + tag + '/' + entity_id,
        success: function(response) {
          // Expected response is a json object where likes is the new number
          // of likes, dislikes is the new number of dislikes, message_type is
          // the type of message to display ("status" or "warning") and message
          // is the message to display.

          if( typeof response.operation.like  !== "undefined" && response.operation.like !== null ) {
            var postAction = response.operation.like ? 'like' : 'dislike';
            var interactionType = postAction === 'like' ? 'add' : 'remove';
            var postTitle = $('.title-topic-label').children('span').eq(0).text().trim();
            var postCategory = $('.parent-topic-container').data('topic-type').trim();
            gtag('event', 'post_interaction', {
              'post_action': 'like',
              'post_category': postCategory,
              'post_title': postTitle,
              'interaction_type': interactionType
            });
          }

          ['like', 'dislike'].forEach(function (iconType) {
            var selector = '#' + iconType + '-container-' + entity_type + '-' + entity_id;
            var $aTag = $(selector + ' a');
            if ($aTag.length == 0) {
              return;
            }
            response.operation[iconType] ? $aTag.addClass('voted') : $aTag.removeClass('voted');
            $(selector + ' .count').text(response[iconType + 's']);
            // Update text of like
            var likeText = Drupal.formatPlural(response.likes, "like", "likes");
            $(selector + ' .count').siblings('.like-text').html(likeText);
          });
          // Display a message whether the vote was registered or an error
          // happened.
          // @todo - this will work only for case when theme has messages in
          // highlighted region.
          $('.region.region-highlighted').html("<div class='messages__wrapper layout-container'><div class='messages messages--" + response.message_type + " role='contentinfo'>" + response.message + "</div></div>");
        }
      });
    };
    return likeAndDislikeService;
  })();

})(jQuery, Drupal);
