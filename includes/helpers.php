<?php
if (!defined('ABSPATH')) exit;

function qf_get_posts_options($post_type) {
  $posts = get_posts([
    'post_type' => $post_type,
    'numberposts' => -1,
    'post_status' => ['publish','draft'],
    'orderby' => 'title',
    'order' => 'ASC',
  ]);

  $opts = [];
  foreach ($posts as $p) $opts[$p->ID] = $p->post_title ?: ('#' . $p->ID);
  return $opts;
}

function qf_sanitize_answers($raw) {
  $answers = [];
  if (!is_array($raw)) return $answers;

  foreach ($raw as $row) {
    $text = isset($row['text']) ? sanitize_text_field($row['text']) : '';
    if ($text === '') continue;

    $next_q = isset($row['next_question_id']) ? absint($row['next_question_id']) : 0;
    $result = isset($row['result_id']) ? absint($row['result_id']) : 0;

    // Regra: não pode ter os dois ao mesmo tempo. Se tiver, prioriza next_question
    if ($next_q > 0) $result = 0;

    $answers[] = [
      'text' => $text,
      'next_question_id' => $next_q,
      'result_id' => $result,
    ];
  }
  return $answers;
}
