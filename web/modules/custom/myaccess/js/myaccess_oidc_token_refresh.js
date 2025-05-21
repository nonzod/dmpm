(function ($, Drupal, drupalSettings) {
  var refresh_token_path = '/myaccess/oidc-token-refresh',
    refreshToken = function () {
      $.ajax({
        type: 'POST',
        url: refresh_token_path,
        contentType: "application/json",
        dataType: 'json',
        success: function (data) {
          if (typeof data.status != "undefined") {
            if (data.status === "ok") {
              switch (data.message) {
                case 'token_refreshed':
                  break;
                default:
                  break;
              }
              var next_delay = Math.min(data.refresh_expires_in, refresh_time_interval);
              setNextRefresh(next_delay);
            } else if (data.status === "ko") {
              switch (data.message) {
                case 'expired_token':
                  location = '/user/logout';
                  break;
                case 'error_refreshing_token':
                  setNextRefresh(30, 1);
                  break;
                default:
                  break;
              }
            }
          } else {
            setNextRefresh(30);
          }
        },
        error: function (xhr, status, error) {
          setNextRefresh(30, 1);
        },
      });
    },
    setNextRefresh = function (expires_in, increment_errors = 0) {
      refresh_errors += increment_errors;
      // if (refresh_errors > 10) { location = '/user/logout'; }
      setTimeout(refreshToken, expires_in * 1000);
    },
    refresh_time_interval = drupalSettings.myaccess.oidc_token_refresh_settings.refresh_time_interval,
    refresh_errors = 0;

  refreshToken();

})(jQuery, Drupal, drupalSettings);
