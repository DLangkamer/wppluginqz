<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
  register_rest_route('qf/v1', '/start', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => 'qf_rest_start',
  ]);

  register_rest_route('qf/v1', '/answer', [
    'methods' => 'POST',
    'permission_callback' => '__return_true',
    'callback' => 'qf_rest_answer',
  ]);
});

function qf_rest_start(WP_REST_Request $req) {
  $quiz_id = absint($req->get_param('quiz_id'));
  if (!$quiz_id) return new WP_REST_Response(['error' => 'quiz_id inválido'], 400);

  $start_q = absint(get_post_meta($quiz_id, '_qf_start_question_id', true));
  if (!$start_q) return new WP_REST_Response(['error' => 'Quiz sem pergunta inicial'], 400);

  $token = wp_generate_uuid4();
  set_transient('qf_' . $token, [
    'quiz_id' => $quiz_id,
    'answers' => [],
    'current_question_id' => $start_q,
  ], HOUR_IN_SECONDS);

  return new WP_REST_Response([
    'token' => $token,
    'node'  => qf_build_question_payload($start_q),
  ]);
}

function qf_rest_answer(WP_REST_Request $req) {
  $token = sanitize_text_field($req->get_param('token'));
  $answer_index = absint($req->get_param('answer_index'));

  $state = get_transient('qf_' . $token);
  if (!$state) return new WP_REST_Response(['error' => 'Sessão expirada'], 400);

  $qid = absint($state['current_question_id']);
  $answers = get_post_meta($qid, '_qf_answers', true);
  if (!is_array($answers) || !isset($answers[$answer_index])) {
    return new WP_REST_Response(['error' => 'Resposta inválida'], 400);
  }

  $picked = $answers[$answer_index];
  $state['answers'][] = [
    'question_id' => $qid,
    'answer_index' => $answer_index,
    'answer_text' => $picked['text'] ?? '',
  ];

  $next_q = absint($picked['next_question_id'] ?? 0);
  $result = absint($picked['result_id'] ?? 0);

  if ($next_q > 0) {
    $state['current_question_id'] = $next_q;
    set_transient('qf_' . $token, $state, HOUR_IN_SECONDS);
    return new WP_REST_Response(['type' => 'question', 'node' => qf_build_question_payload($next_q)]);
  }

  if ($result > 0) {
    // finaliza
    delete_transient('qf_' . $token);
    return new WP_REST_Response(['type' => 'result', 'node' => qf_build_result_payload($result)]);
  }

  // Se não tiver próximo nem resultado, encerra "vazio"
  delete_transient('qf_' . $token);
  return new WP_REST_Response(['type' => 'result', 'node' => [
    'title' => 'Fim',
    'content' => 'Obrigado! Seu quiz foi concluído.',
    'cta_text' => '',
    'cta_url' => '',
  ]]);
}

function qf_build_question_payload($question_id) {
  $post = get_post($question_id);
  if (!$post) return null;

  $answers = get_post_meta($question_id, '_qf_answers', true);
  if (!is_array($answers)) $answers = [];

  $outAnswers = [];
  foreach ($answers as $a) {
    $outAnswers[] = ['text' => $a['text'] ?? ''];
  }

  return [
    'id' => $question_id,
    'title' => $post->post_title,
    'answers' => $outAnswers,
  ];
}

function qf_build_result_payload($result_id) {
  $post = get_post($result_id);
  if (!$post) return null;

  return [
    'id' => $result_id,
    'title' => $post->post_title,
    'content' => apply_filters('the_content', $post->post_content),
    'cta_text' => get_post_meta($result_id, '_qf_cta_text', true),
    'cta_url'  => get_post_meta($result_id, '_qf_cta_url', true),
  ];
}
