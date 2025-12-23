<?php
if (!defined('ABSPATH')) exit;

add_shortcode('quiz_funil', function ($atts) {
  $atts = shortcode_atts(['id' => 0], $atts);
  $quiz_id = absint($atts['id']);
  if (!$quiz_id) return '<div>Quiz inválido.</div>';

  wp_enqueue_style('qf-css');
  wp_enqueue_script('qf-js');

  ob_start();
  ?>
  <div class="qf-wrap" data-quiz-id="<?php echo esc_attr($quiz_id); ?>">
    <div class="qf-card">
      <div class="qf-body">
        <div class="qf-loading">Carregando…</div>
      </div>
    </div>
  </div>
  <?php
  return ob_get_clean();
});
