<?php
require_once(__DIR__ . '/lib/helpers.php');

/**
 * Add a custom menu
 */
function blendle_button_admin_menu() {
  add_options_page(
    'Blendle Button Settings',
    'Blendle Button',
    'manage_options',
    'blendle_button',
    'blendle_button_options_page'
  );
}

function blendle_button_options_page() {
  if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

  echo '<div class="wrap"><h2>Blendle Button Settings</h2></div>';
  echo '<form action="options.php" method="post">';

  settings_fields('blendle_button_options_page');
  do_settings_sections('blendle_button_options_page');
  submit_button();

  echo '</form>';
}

add_action('admin_menu', 'blendle_button_admin_menu');

/**
 * Register production settings
 */
function blendle_button_register_general_settings() {
  add_settings_section(
    'blendle_button_general_configuration',
    'General',
    'blendle_button_options_page_configuration',
    'blendle_button_options_page'
  );

  add_settings_field(
    'blendle_button_provider_uid',
    'Provider UID',
    'blendle_button_provider_uid_callback',
    'blendle_button_options_page',
    'blendle_button_general_configuration'
  );

  register_setting('blendle_button_options_page', 'blendle_button_provider_uid');

  add_settings_field(
    'blendle_button_locale',
    'Language',
    'blendle_button_locale_callback',
    'blendle_button_options_page',
    'blendle_button_general_configuration'
  );

  register_setting('blendle_button_options_page', 'blendle_button_locale');

  add_settings_field(
    'blendle_button_provider_use_production',
    'Use production credentials',
    'blendle_button_provider_use_production_callback',
    'blendle_button_options_page',
    'blendle_button_general_configuration'
  );

  register_setting('blendle_button_options_page', 'blendle_button_provider_use_production');

  add_settings_field(
    'blendle_button_provider_button_type',
    'Type of Blendle Button',
    'blendle_button_provider_button_type_callback',
    'blendle_button_options_page',
    'blendle_button_general_configuration'
  );

  register_setting('blendle_button_options_page', 'blendle_button_provider_button_type');

}

add_action('admin_init', 'blendle_button_register_general_settings');

function blendle_button_provider_uid_callback() {
  echo '<input name="blendle_button_provider_uid" type="text" value="' . get_option('blendle_button_provider_uid') . '" />';
}

function blendle_button_locale_callback() {
  $current_value = get_blendle_button_locale();

  echo '<select name="blendle_button_locale" id="blendle_button_locale">';

  foreach (blendle_button_get_supported_locales() as $key => $value) {
    echo '<option type="radio" name="blendle_button_provider_button_type" value="' . $value['locale'] . '"' . ($current_value['locale'] == $value['locale'] ? ' selected' : '') . '>' . $value['title'] . '</option>';
  }

  echo '</select>';
}

function blendle_button_provider_use_production_callback() {
  echo '<input name="blendle_button_provider_use_production" type="checkbox" value="1" ' . checked(1, get_option('blendle_button_provider_use_production'), false) . ' />';
}

function blendle_button_provider_button_type_callback() {
  $savedButtonType = get_blendle_button_type_with_fallback();

  echo '<p><input type="radio" name="blendle_button_provider_button_type" value="item" '. checkedIfCurrentSelection($savedButtonType, "item") .' /> Sell items</p>
        <p><input type="radio" name="blendle_button_provider_button_type" value="subscription" '. checkedIfCurrentSelection($savedButtonType, "subscription") .' /> Sell subscriptions</p>
        <p><input type="radio" name="blendle_button_provider_button_type" value="combination" '. checkedIfCurrentSelection($savedButtonType, "combination") .' /> Sell both</p>';
}

function checkedIfCurrentSelection($savedButtonType, $radioValue) {
  return $savedButtonType === $radioValue ? 'checked' : null;
}

function blendle_button_options_page_configuration () {
  echo '<p>Required configuration for the Blendle Button to work.</p>';
}

function blendle_button_register_production_settings () {
  blendle_button_register_environment_settings(array( 'title'=>'Production', 'env'=>'production'));
}

function blendle_button_register_staging_settings () {
  blendle_button_register_environment_settings(array( 'title'=>'Staging', 'env'=>'staging'));
}

function blendle_button_token_setting_key ($env) {
  return 'blendle_button_provider_' . $env . '_token';
}

function blendle_button_key_setting_key ($env) {
  return 'blendle_button_provider_' . $env . '_key';
}

function blendle_button_configuration_key ($env) {
  return 'blendle_button_' . $env . '_configuration';
}

/**
 * Register production settings
 */
function blendle_button_register_environment_settings($args) {
  add_settings_section(
    blendle_button_configuration_key($args['env']),
    $args['title'],
    'blendle_button_options_page_configuration',
    'blendle_button_options_page'
  );

  $token_setting = blendle_button_token_setting_key($args['env']);

  add_settings_field(
    $token_setting,
    'API Secret',
    'blendle_button_input',
    'blendle_button_options_page',
    blendle_button_configuration_key($args['env']),
    array(
      'key' => $token_setting
    )
  );

  register_setting('blendle_button_options_page', $token_setting);

  $key_setting = blendle_button_key_setting_key($args['env']);

  add_settings_field(
    $key_setting,
    'Public Key',
    'blendle_button_gpg_textarea',
    'blendle_button_options_page',
    blendle_button_configuration_key($args['env']),
    array(
      'key' => $key_setting
    )
  );

  register_setting('blendle_button_options_page', $key_setting, 'replace_new_line');
}

function replace_new_line($value) {
  return str_replace('\n', "\n", $value);
}

function blendle_button_gpg_textarea($args) {
  echo '<textarea wrap="soft" cols="80" rows="15" name="' . $args['key'] . '">' . get_option($args['key']) . '</textarea>';
}

function blendle_button_input($args) {
  echo '<input size="37" name="' . $args['key'] . '" type="text" value="' . get_option($args['key']) . '" />';
}

add_action('admin_init', 'blendle_button_register_production_settings');
add_action('admin_init', 'blendle_button_register_staging_settings');
