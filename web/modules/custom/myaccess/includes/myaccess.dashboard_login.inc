<?php
use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_preprocess_html.
 *
 * Used for: CR1 - Dashboard
 * Used for: CR21 - Timeout
 *
 * @param array $variables
 */
function myaccess_preprocess_html(&$variables) {
  $variables['hrms_data_gtm'] = array();
  //Verifico se la variabile tempstore è settata (quindi l'utente ha appena fatto la login)
  $tempstoreJustLogged = \Drupal::service('tempstore.private')->get('myaccess');
  if( isset($tempstoreJustLogged) ) {
    $tmpIslogged = $tempstoreJustLogged->get('msgJustLogged');
    if ($tmpIslogged) {
      //Chiamo le API per farmi restituire le Legal Entitities e i Country dell'utente, per poterle passare all'html per l'evento GTM
      $user = \Drupal::currentUser();
      $apiClient = \Drupal::service('myaccess.hmrs_api_client');
      $userData = $apiClient->getUserData($user->getEmail(), true);
      /*$apiClient = \Drupal::service('myaccess.hmrs_csv_client');
      $userData = $apiClient->getUserData("eansaldi@menarini.it", true);*/

      if( isset($userData) ) {
        $records = $userData->getRecords();
        $legalEntity = '';
        $country = '';
        if( !empty($records) ) {
          foreach ($records as $record) {
            if ($record['scope'] === 'legal_entity') {
              $recordValue = $record['name'];
              if( !empty($recordValue) && ($recordValue ==="not set" || $recordValue ==="not_set") ) {
                $recordValue = "";
              }
              $recordValue = trim($recordValue);
              $legalEntity .= empty($legalEntity) ? $recordValue : ", " . $recordValue;
            }
            if ($record['scope'] === 'country') {
              $recordValue = $record['name'];
              if( !empty($recordValue) && ($recordValue ==="not set" || $recordValue ==="not_set") ) {
                $recordValue = "";
              }
              $recordValue = trim($recordValue);
              $country .= empty($country) ? $recordValue : ", " . $recordValue;
            }
          }
        }

        $variables['hrms_data_gtm']['user_legal_entity'] = $legalEntity;
        $variables['hrms_data_gtm']['user_country'] = $country;
      }
      //Cancello la variabile tempstore della login perché ho terminato le operazioni di tracciamento evento sulla login
      $tempstoreJustLogged->delete('msgJustLogged');
    }
  }

  // OIDC Token refresh settings
  $mod_config = \Drupal::config('myaccess.oidc_token_refresh_settings');
  if($mod_config->get('refresh_mode') !== 'disabled') {
    $oidc_settings = [
      'refresh_mode' => $mod_config->get('refresh_mode'),
      'refresh_time_interval' => intval($mod_config->get('refresh_time_interval')) * 60
    ];
    $variables['#attached']['drupalSettings']['myaccess']['oidc_token_refresh_settings'] = $oidc_settings;
    $variables['#attached']['library'][] = 'myaccess/oidc_token_refresh';
  }
}
