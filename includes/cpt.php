<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
  register_post_type('qf_quiz', [
    'label' => 'Quizzes',
    'public' => false,
    'show_ui' => true,
    'menu_icon' => 'dashicons-forms',
    'supports' => ['title'],
  ]);

  register_post_type('qf_question', [
    'label' => 'Perguntas',
    'public' => false,
    'show_ui' => true,
    'menu_icon' => 'dashicons-editor-help',
    'supports' => ['title'],
  ]);

  register_post_type('qf_result', [
    'label' => 'Resultados',
    'public' => false,
    'show_ui' => true,
    'menu_icon' => 'dashicons-awards',
    'supports' => ['title','editor'],
  ]);
});
