/**
 * @file
 * JavaScript for autologout.
 */

(function ($, Drupal, cookies) {

  'use strict';

  /**
   * Used to lower the cpu burden for activity tracking on browser events.
   */
  function debounce(f) {
    var timeout;
    return function () {
      var savedContext = this;
      var savedArguments = arguments;
      var finalRun = function () {
        timeout = null;
        f.apply(savedContext, savedArguments);
      };

      if (!timeout) {
        f.apply(savedContext, savedArguments);
      }
      clearTimeout(timeout);
      timeout = setTimeout(finalRun, 500);
    };
  }

  /**
   * Attaches the batch behavior for autologout.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.myportal_autologout = {
    attach: function (context, settings) {
      if (context !== document) {
        return;
      }

      var localSettings;

      // Prevent settings being overridden by ajax callbacks by cloning it.
      localSettings = jQuery.extend(true, {}, settings.myportal_autologout);

      var login_time = cookies.get("Drupal.visitor.myportal_autologout.login");
      var time_passed = Math.floor(Date.now()) - (login_time * 1000);

      // Variables.
      var t;

      // Activity is a boolean used to detect a user has
      // interacted with the page.
      var activity;

      // Timer to keep track of activity resets.
      var activityResetTimer;

      // Add timer element to prevent detach of all behaviours.
      var timerMarkup = $('<div id="timer"></div>').hide();
      $('body').append(timerMarkup);

      if (localSettings.refresh_only) {

        // On pages where user shouldn't be logged out, don't set the timer.
        t = setTimeout(keepAlive, localSettings.timeout - 30);

      } else if (time_passed >= settings.myportal_autologout.delay) {

        // Delay expired, start count down for auto-login.
        start();

      } else {

        // Apply delay to start count for timeout.
        setTimeout(start, settings.myportal_autologout.delay - time_passed);
      }

      /**
       * Start countdown system!.
       */
      function start() {

        // Set no activity to start with.
        activity = false;

        // Bind formUpdated events to preventAutoLogout event.
        $('body').bind('formUpdated', debounce(function (event) {
          $(event.target).trigger('preventAutologout');
        }));

        // Bind mousemove events to preventAutoLogout event.
        $('body').bind('mousemove', debounce(function (event) {
          $(event.target).trigger('preventAutologout');
        }));

        // Support for CKEditor.
        if (typeof CKEDITOR !== 'undefined') {
          CKEDITOR.on('instanceCreated', function (e) {
            e.editor.on('contentDom', function () {
              e.editor.document.on('keyup', debounce(function (event) {
                // Keyup event in ckeditor should prevent autologout.
                $(e.editor.element.$).trigger('preventAutologout');
              }));
            });
          });
        }

        $('body').bind('preventAutologout', function (event) {

          // When the preventAutologout event fires, we set activity to true.
          activity = true;

          // Clear timer if one exists.
          clearTimeout(activityResetTimer);

          // Set a timer that goes off and resets this activity indicator after
          // a minute, otherwise sessions never timeout.
          activityResetTimer = setTimeout(function () {
            activity = false;
          }, 60000);
        });

        // On pages where the user should be logged out, set the timer to check and log them out.
        t = setTimeout(init, localSettings.timeout);
      }

      /**
       * Init function to check activity.
       */
      function init() {

        if (activity) {
          // The user has been active on the page.
          activity = false;
          refresh();
        } else {
          // The user hasn't been active on the page.
          // Check how much time is left.
          Drupal.Ajax['autologout.getTimeLeft'].autologoutGetTimeLeft(function (time) {
            if (time > 0) {
              t = setTimeout(init, time);
            } else {
              // Logout user.
              logout();
            }
          });
        }
      }

      /**
       * Logout function.
       */
      function logout() {
        $.ajax({
          url: drupalSettings.path.baseUrl + "autologout_ajax_logout",
          type: "POST",
          beforeSend: function (xhr) {
            xhr.setRequestHeader('X-Requested-With', {
              toString: function () {
                return '';
              }
            });
          },
          success: function () {
            window.location = localSettings.redirect_url;
          },
          error: function (XMLHttpRequest, textStatus) {
            if (XMLHttpRequest.status === 403 || XMLHttpRequest.status === 404) {
              window.location = localSettings.redirect_url;
            }
          }
        });
      }

      /**
       * Get the remaining time.
       *
       * Use the Drupal ajax library to handle get time remaining events
       * because if using the JS Timer, the return will update it.
       *
       * @param function callback(time)
       *   The function to run when ajax is successful. The time parameter
       *   is the time remaining for the current user in ms.
       */
      Drupal.Ajax.prototype.autologoutGetTimeLeft = function (callback) {
        var ajax = this;

        if (ajax.ajaxing) {
          return false;
        }
        ajax.options.submit = {
          uactive: activity
        };
        ajax.options.success = function (response, status) {
          if (typeof response == 'string') {
            response = $.parseJSON(response);
          }
          if (typeof response[0].command === 'string' && response[0].command === 'alert') {
            // In the event of an error, we can assume user has been logged out.
            window.location = localSettings.redirect_url;
          }

          callback(response[1].settings.time);

          response[0].data = '<div id="timer" style="display: none;">' + response[0].data + '</div>';

          // Let Drupal.ajax handle the JSON response.
          return ajax.success(response, status);
        };

        try {
          $.ajax(ajax.options);
        } catch (e) {
          ajax.ajaxing = false;
        }
      };

      Drupal.Ajax['autologout.getTimeLeft'] = Drupal.ajax({
        base: null,
        element: document.body,
        // See \Drupal\myportal_autologout\Controller\AutologoutController::ajaxGetRemainingTime.
        url: drupalSettings.path.baseUrl + 'autologout_ajax_get_time_left',
        submit: {
          uactive: activity
        },
        event: 'autologout.getTimeLeft',
        error: function (XMLHttpRequest, textStatus) {
          // Disable error reporting to the screen.
        },
      });

      /**
       * Handle refresh event.
       *
       * Use the Drupal ajax library to handle refresh events because if using
       * the JS Timer, the return will update it.
       *
       * @param function timerFunction
       *   The function to tell the timer to run after its been restarted.
       */
      Drupal.Ajax.prototype.autologoutRefresh = function (timerfunction) {
        var ajax = this;

        if (ajax.ajaxing) {
          return false;
        }

        ajax.options.success = function (response, status) {
          if (typeof response === 'string') {
            response = $.parseJSON(response);
          }
          if (typeof response[0].command === 'string' && response[0].command === 'alert') {
            // In the event of an error, we can assume the user has been logged out.
            window.location = localSettings.redirect_url;
          }

          t = setTimeout(timerfunction, localSettings.timeout);
          activity = false;

          // Wrap response data in timer markup to prevent detach of all behaviors.
          response[0].data = '<div id="timer" style="display: none;">' + response[0].data + '</div>';

          // Let Drupal.ajax handle the JSON response.
          return ajax.success(response, status);
        };

        try {
          $.ajax(ajax.options);
        } catch (e) {
          ajax.ajaxing = false;
        }
      };

      Drupal.Ajax['autologout.refresh'] = Drupal.ajax({
        base: null,
        element: document.body,
        // See \Drupal\myportal_autologout\Controller\AutologoutController::ajaxSetLast.
        url: drupalSettings.path.baseUrl + 'autologout_ajax_set_last',
        event: 'autologout.refresh',
        error: function (XMLHttpRequest, textStatus) {
          // Disable error reporting to the screen.
        }
      });

      function keepAlive() {
        Drupal.Ajax['autologout.refresh'].autologoutRefresh(keepAlive);
      }

      function refresh() {
        Drupal.Ajax['autologout.refresh'].autologoutRefresh(init);
      }

    }
  }
})(jQuery, Drupal, window.Cookies);
