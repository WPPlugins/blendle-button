<?php
require_once __DIR__ . '/pay-with-blendle/vendor/autoload.php';

/**
 * Echos a notice in the Wordpress dashboard when
 * the plugin is active and no credentials are found
 */
function plugin_not_configured() {
  $credentials = get_blendle_button_public_key();
  $is_active   = is_plugin_active('blendle-button/blendle-button.php');

  if ($is_active && !$credentials) {
    $notice = '<div class="notice notice-warning is-dismissible">'.
              '<p>We noticed your <strong>Blendle Button</strong> credentials are missing, the button won\'t work without them. '.
              '<a href="'.admin_url('options-general.php?page=blendle_button').'">Add them now</a>?</p>'.
              '</div>';

    echo $notice;
  }
}

add_action('admin_notices', 'plugin_not_configured');

function validate_credentials() {
  $api_secret   = get_blendle_button_api_secret();
  $public_key   = get_blendle_button_public_key();
  $provider_uid = get_blendle_button_provider_uid();
  $is_active    = is_plugin_active('blendle-button/blendle-button.php');
  $screen       = get_current_screen();
  $nonce        = wp_create_nonce('blendle_button_credential_check');
  $domain       = blendle_button_use_production() ? "https://pay.blendle.com" : "https://pay.blendle.io";
  $url          = $domain."/api/provider/".$provider_uid."/check_credentials";

  if (!$is_active || $screen->id != "settings_page_blendle_button") {
    return;
  }

  JWT::$leeway = 900;

  $jwt = JWT::encode(array(
      'exp' => time() + 900,
      'sub' => 'check_credentials',
      'iss' => $provider_uid,
      'data' => array(
        'nonce' => $nonce
      )
    ), $api_secret, "HS256");

  $opts = array('http' =>
      array(
        'method'  => 'POST',
        'timeout' => 30,
        'header'  => ["Content-Type: application/jwt", "Accept: application/jwt"],
        'content' => $jwt
      )
    );

  try {
    $context = stream_context_create($opts);
    $result = @file_get_contents($url, false, $context);

    if($result) {
      $decoded = JWT::decode($result, $public_key, array("RS256"));
    }
  } catch (Exception $e) {
    // At this point we couldn't make a connection.
    return;
  }

  if ($decoded->data->nonce != $nonce) {
    $notice = '<div class="notice notice-error is-dismissible">'.
              '<p>We noticed your <strong>Blendle Button</strong> Provider UID, Public Key or API Secret is incorrect, the button won\'t work without them.</p>'.
              '<p>Please ensure you\'ve used exactly copied the ones provided in the <a href="http://portal.blendle.nl" target="_blank">Blendle Portal</a>, '.
              'or check the <a href="http://pay-docs.blendle.io/wordpress.html" target="_blank">Blendle Button Wordpress plugin documentation</a> for help</p>'.
              '</div>';

    echo $notice;
  }
}

add_action('admin_notices', 'validate_credentials');
