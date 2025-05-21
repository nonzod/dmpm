/**
 * EDIT THIS FILE. (this file is newer than the es6 version)
 * See the following change record for more information,
 * https://www.drupal.org/node/2815083
 * @preserve
 **/

window.MyAccess = {};

(function ($, Drupal) {
  Drupal.behaviors.myaccess = {
    attach: function attach(context) {
      var body = $('body');
      var navbar = $('.navbar-collapse');

      body.once('applications-grid-wrapper').each(function () {
        body.append('<div id="applications-grid-wrapper"></div>');
      });

      $('#applications-favorite-wrapper').once('applications-favorite-wrapper').each(function () {
        MyAccess.loadFavorites.execute();
      });

      $('.applications-group-wrap').sortable({
        update: function () {
          const applicationIds = $(this).sortable('toArray', {attribute: 'data-application-id'});
          MyAccess.sortFavoriteApplications(applicationIds);
        }
      });

      $('#js-applications-grid__close', context).once('applications-grid__close').click(function () {
        MyAccess.loadFavorites.execute();
        $('#applications-grid').remove();
        $('#applications-grid-wrapper').hide();
        body.removeClass('disable-scroll');
      });

      // formfill applications
      $('.applications__tile a[data-auth-type]', context).once('applications-grid__close').on('click', function (e) {
        is_formfill = $(this).is('[data-auth-type="formfill"]');
        is_saml_mya = $(this).is('[data-auth-type="saml"]') && $(this).is('[data-url-mya="1"]');
        not_has_password = typeof drupalSettings.myaccess.hasPassword === 'undefined' || drupalSettings.myaccess.hasPassword === false;
        if ((is_formfill || is_saml_mya) && not_has_password) {
          e.preventDefault();
          var urlAppToOpen = $(this).attr('href');
          $.ajax({
            url: Drupal.url('pw-session-form'),
            type: 'POST',
            success: function (html) {
              const $body = $('body');
              $body.append(html);
              $body.addClass('disable-scroll');
              const $popover_wrapper = $('#password-session-form-wrapper'),
                $popover = $('#password-session-form-popover'),
                $input = $('#password-session--input'),
                $form = $('#password-session-form'),
                $button = $('#password-session-form .form-submit');
              setTimeout(function () {
                $popover.addClass('open');
              }, 100);
              $('svg.icon-close', $popover).on('click', function () {
                $popover_wrapper.remove();
                $body.removeClass('disable-scroll');
              });
              $input.on('input', function () {
                $button.prop('disabled', $(this).val().length < 3);
              });
              $button.on('click', function (e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass("loading-throbber");
                $.ajax({
                  url: Drupal.url('pw-session-save'),
                  type: 'POST',
                  data: {'pwd': $input.val()},
                  dataType: 'json',
                  success: function (data) {
                    if (data.status === 'ok') {
                      $('.feedback-ok', $popover_wrapper).show(0);
                      $form.hide(0);
                      drupalSettings.myaccess.hasPassword = true;
                      window.open(urlAppToOpen);
                    } else {
                      if ($input.next('.form-item--error-message').length === 0) {
                        $input.after('<div class="form-item--error-message alert alert-danger alert-sm alert-dismissible form-control-radius">' + Drupal.t('Invalid password') + '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button></div>');
                      }
                      drupalSettings.myaccess.hasPassword = false;
                    }

                    //$('#password-session-form-wrapper').remove();
                    //$body.removeClass('disable-scroll');
                  },
                  complete: function (data) {
                    $this.removeClass("loading-throbber");
                  }
                });
              });
            }
          });
        } else {
        }
      });

      function handleMenus() {
        $('#megamenu-wrapper').empty().removeClass('megamenu_visible');
        $('.navbar-nav--item').removeClass('is-active');
        $('.men-header--region').removeClass('men-header-open men-header-open_mega');
        $('.main-container').removeClass('men-nav_is-open');
        if (navbar.hasClass('in')) {
          navbar.collapse('hide');
        }
      }

      $('.use-ajax-fullscreen', context).once('use-ajax-fullscreen').on('click', function () {
        body.addClass('disable-scroll');
        handleMenus();
        $('#myp-search-wrapper').hide();
      });

      Drupal.ajax.BindFullscreenAjaxLinks(document.body);

      function get_hostname(url) {
        var m = url.match(/^(?:https?:\/\/)?(?:[^@\n]+@)?([^:\/\n\?\=]+)/im);
        return m ? m[1] : null;
      }

      $('.applications__tile_linkwrap a', context).once('applications__tile_linkwrap').on('click', function () {
        var appUrl = $(this).attr('href');
        if (appUrl.indexOf("myaccess/open") < 0) {
          var appName = $(this).parent('.applications__tile_linkwrap').siblings('.applications__title').text();
          var appDomain = get_hostname(appUrl);
          gtag('event', 'click', {
            'app_name': appName,
            'link_domain': appDomain,
            'link_url': appUrl
          });
        }
      });
    }
  };

  MyAccess.loadFavorites = Drupal.ajax({
    url: Drupal.url('myaccess/applications-favorite'),
    progress: {type: 'none'}
  });

  MyAccess.sortFavoriteApplications = function (applicationIds) {
    $.ajax({
      url: Drupal.url('myaccess/applications-favorite-sort'),
      type: 'POST',
      data: {'application_ids[]': applicationIds},
      dataType: 'json',
    });
  }

  Drupal.ajax.BindFullscreenAjaxLinks = function (element) {
    $(element).find('.use-ajax-fullscreen').once('ajax').each(function (i, ajaxLink) {
      var $linkElement = $(ajaxLink);

      var elementSettings = {
        progress: {type: 'fullscreen'},
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
