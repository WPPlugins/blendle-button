<?php
require_once(__DIR__ . '/lib/helpers.php');

# After saving a post, save the register checkbox status with the post meta data
function blendle_button_save_post_hook($id) {
  $post = get_post($id);

  setup_postdata($post);

  if (isset($_POST['blendle_button_enabled_nonce']) && wp_verify_nonce($_POST['blendle_button_enabled_nonce'], 'blendle_button' )) {
    update_post_meta($post->ID, 'blendle_button_enabled', (isset($_POST['blendle_button_enabled']) && $_POST['blendle_button_enabled'] ? 1 : 0));
  }
}

# Register the checkbox for the Blendle Button with the Wordpress metabox SDK
function blendle_button_add_meta_boxes() {
  add_meta_box('blendle_button_meta', 'Blendle Button', 'blendle_button_metaboxes_callback', 'post', 'side', 'high');
}

# Add a checkbox to posts to enable the Blendle Button for that post
function blendle_button_metaboxes_callback($post) {
  wp_nonce_field('blendle_button', 'blendle_button_enabled_nonce');

  echo '<p>';
  echo '<input type="checkbox" name="blendle_button_enabled" id="blendle_button_enabled" ' . (blendle_button_enabled($post) === 0 ? '' : 'checked') . '/>';
  echo '<label for="blendle_button_enabled">Enable Blendle Button?</label>';
  echo '</p>';
}

add_action('save_post', 'blendle_button_save_post_hook');
add_action('add_meta_boxes', 'blendle_button_add_meta_boxes');

# Init Blendle Button button tinyMCE plugin
function init_blendle_tinymce_plugin() {
  if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
    add_filter('mce_external_plugins', 'add_blendle_tinymce_button');
    add_filter('mce_buttons', 'register_blendle_tinymce_button', 0);
  }
}

# Register Blendle Button button with tinyMCE
function register_blendle_tinymce_button( $buttons ) {
  array_push($buttons, 'BlendleButton');
  return $buttons;
}

# Add Blendle Button button to tinyMCE
function add_blendle_tinymce_button( $plugin_array ) {
  $plugin_array['BlendleButton'] = plugins_url( '/js/pay_with_blendle-plugin.js', __FILE__ );
  return $plugin_array;
}

add_action( 'admin_init', 'init_blendle_tinymce_plugin' );
