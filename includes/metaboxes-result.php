<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
  add_meta_box('qf_result_cta', 'CTA do Resultado', 'qf_result_cta_box', 'qf_result', 'normal', 'default');
});

function qf_result_cta_box($post) {
  wp_nonce_field('qf_result_save', 'qf_result_nonce');

  $cta_text = get_post_meta($post->ID, '_qf_cta_text', true);
  $cta_url  = get_post_meta($post->ID, '_qf_cta_url', true);

  echo '<p><label>Texto do botão</label><input class="widefat" name="qf_cta_text" value="'.esc_attr($cta_text).'"></p>';
  echo '<p><label>URL do botão</label><input class="widefat" name="qf_cta_url" value="'.esc_attr($cta_url).'"></p>';
}

add_action('save_post_qf_result', function ($post_id) {
  if (!isset($_POST['qf_result_nonce']) || !wp_verify_nonce($_POST['qf_result_nonce'], 'qf_result_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  update_post_meta($post_id, '_qf_cta_text', sanitize_text_field($_POST['qf_cta_text'] ?? ''));
  update_post_meta($post_id, '_qf_cta_url', esc_url_raw($_POST['qf_cta_url'] ?? ''));
});
