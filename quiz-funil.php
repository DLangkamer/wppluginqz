<?php
/**
 * Plugin Name: Quiz Funil (Etapas + Condicional)
 * Description: Quiz em etapas com lógica condicional, repetidores nativos e shortcode.
 * Version: 1.0.0
 * Author: Você
 */

if (!defined('ABSPATH')) exit;

define('QF_PATH', plugin_dir_path(__FILE__));
define('QF_URL', plugin_dir_url(__FILE__));
define('QF_VER', '1.0.0');

require_once QF_PATH . 'includes/helpers.php';
require_once QF_PATH . 'includes/cpt.php';
require_once QF_PATH . 'includes/admin.php';
require_once QF_PATH . 'includes/metaboxes-question.php';
require_once QF_PATH . 'includes/metaboxes-result.php';
require_once QF_PATH . 'includes/rest.php';
require_once QF_PATH . 'public/shortcode.php';

add_action('wp_enqueue_scripts', function () {
  wp_register_style('qf-css', QF_URL . 'assets/css/quiz-funil.css', [], QF_VER);
  wp_register_script('qf-js', QF_URL . 'assets/js/quiz-funil.js', ['wp-api-fetch'], QF_VER, true);

  wp_localize_script('qf-js', 'QF', [
    'restUrl' => esc_url_raw(rest_url('qf/v1')),
    'nonce'   => wp_create_nonce('wp_rest'),
  ]);
});
