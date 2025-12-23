<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
  add_meta_box('qf_quiz_settings', 'Configurações do Quiz', 'qf_quiz_settings_box', 'qf_quiz', 'normal', 'high');
});

function qf_quiz_settings_box($post) {
  wp_nonce_field('qf_quiz_save', 'qf_quiz_nonce');

  $start_question_id = absint(get_post_meta($post->ID, '_qf_start_question_id', true));
  $questions = qf_get_posts_options('qf_question');

  echo '<p><strong>Pergunta inicial:</strong></p>';
  echo '<select name="qf_start_question_id" style="width:100%">';
  echo '<option value="0">— Selecione —</option>';
  foreach ($questions as $id => $title) {
    printf('<option value="%d" %s>%s</option>', $id, selected($start_question_id, $id, false), esc_html($title));
  }
  echo '</select>';

  echo '<p style="margin-top:12px;color:#666">Dica: você vai usar o shortcode <code>[quiz_funil id="' . esc_attr($post->ID) . '"]</code> na página.</p>';
}

add_action('save_post_qf_quiz', function ($post_id) {
  if (!isset($_POST['qf_quiz_nonce']) || !wp_verify_nonce($_POST['qf_quiz_nonce'], 'qf_quiz_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  $start = isset($_POST['qf_start_question_id']) ? absint($_POST['qf_start_question_id']) : 0;
  update_post_meta($post_id, '_qf_start_question_id', $start);
});
