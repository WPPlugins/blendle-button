<?php
require_once(__DIR__ . '/lib/helpers.php');

# Add a BlendleButton to the content if the Blendle Button is enabled
function blendle_button_the_content_filter($content) {
  global $post;

  if(!blendle_button_enabled($post)) {
    return $content;
  }

  $callback_url = add_query_arg(array('t' => time(), 'blendle_button' => '{cache_bust}'), get_permalink($post));
  $button_type = get_blendle_button_type_with_fallback();
  $provider_uid = get_blendle_button_provider_uid();
  $button_html = generate_button_html($button_type, $provider_uid, $callback_url);

  return '<div id="' . get_blendle_button_post_div_id($post) . '">' . $content . '</div>' . $button_html;
}

function generate_button_html($button_type, $provider_uid, $callback_url) {
  global $post;

  $attributes = 'data-type="' . $button_type . '" ' .
                'data-purchase-callback-url="' . $callback_url . '" ';

  if ($post) {
    $attributes .= 'data-item-selector="#' . get_blendle_button_post_div_id($post) . '" ' .
                   'data-item-jwt="' . blendle_button_generate_item_jwt($post) . '" ' .
                   'class="pwb-item"';
  } else {
    $attributes .= 'class="pwb-' . $button_type . '"';
  }

  return'<div' . $attributes . '></div>';
}

# Enqueue the Javascript URL for the Blendle Button Javascript SDK
function blendle_button_javascript() {
  $blendle_button_sdk = get_blendle_button_sdk();

  if (!$blendle_button_sdk) {
    return;
  }

  wp_enqueue_script('blendle_button_javascript', $blendle_button_sdk->clientURL(), false, null, true);
}

# Enqueue the Javascript URL for the Blendle Button Javascript SDK
function blendle_button_init_javascript() {
  wp_enqueue_script('blendle_button_init', plugins_url( '/js/blendle_button_init.js', __FILE__ ), false, null, true);

  wp_localize_script( 'blendle_button_init', 'blendleButtonInit', array(
    'provider_uid' => get_blendle_button_provider_uid(),
    'locale' => get_blendle_button_locale()['locale']
  ));
}

add_action('wp_enqueue_scripts', 'blendle_button_init_javascript');
add_action('wp_enqueue_scripts', 'blendle_button_javascript');


add_filter('the_content', 'blendle_button_the_content_filter');
