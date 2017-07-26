<?php
require_once(__DIR__ . '/lib/helpers.php');

# Don't show unacquired content when the Blendle Button is enabled
function blendle_button_shortcode($attr, $content = '') {
  global $post;

  if(blendle_button_post_is_acquired($post)) {
    return $content;
  } else {
    return '';
  }
}

add_shortcode('blendlebutton', 'blendle_button_shortcode');
