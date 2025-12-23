<?php
if (!defined('ABSPATH')) exit;

add_action('add_meta_boxes', function () {
  add_meta_box('qf_question_box', 'Respostas (condicionais)', 'qf_question_box_html', 'qf_question', 'normal', 'high');
});

function qf_question_box_html($post) {
  wp_nonce_field('qf_question_save', 'qf_question_nonce');

  $answers = get_post_meta($post->ID, '_qf_answers', true);
  if (!is_array($answers)) $answers = [];

  $questions = qf_get_posts_options('qf_question');
  $results   = qf_get_posts_options('qf_result');

  echo '<p>Crie as respostas. Cada resposta pode levar para <strong>outra pergunta</strong> OU para um <strong>resultado final</strong>.</p>';

  echo '<table class="widefat" id="qf-answers-table">';
  echo '<thead><tr><th>Texto</th><th>Próxima Pergunta</th><th>Resultado</th><th></th></tr></thead>';
  echo '<tbody>';

  $i = 0;
  foreach ($answers as $row) {
    qf_answer_row($i, $row, $questions, $results);
    $i++;
  }

  // Linha modelo (oculta)
  echo '</tbody></table>';
  echo '<p><button type="button" class="button" id="qf-add-answer">+ Adicionar resposta</button></p>';

  // Template HTML sem JSON (só um bloco escondido)
  echo '<template id="qf-answer-template">';
  qf_answer_row('__INDEX__', ['text'=>'','next_question_id'=>0,'result_id'=>0], $questions, $results, true);
  echo '</template>';

  // JS inline simples (pode ir para arquivo depois)
  ?>
  <script>
  (function(){
    const tableBody = document.querySelector('#qf-answers-table tbody');
    const btn = document.getElementById('qf-add-answer');
    const tpl = document.getElementById('qf-answer-template');

    function reindex(){
      const rows = tableBody.querySelectorAll('tr');
      rows.forEach((tr, idx) => {
        tr.querySelectorAll('[data-name]').forEach(el => {
          const base = el.getAttribute('data-name'); // ex: qf_answers[__INDEX__][text]
          el.name = base.replace('__INDEX__', idx);
        });
      });
    }

    btn.addEventListener('click', () => {
      const clone = document.importNode(tpl.content, true);
      tableBody.appendChild(clone);
      reindex();
      bindRowLogic();
    });

    function bindRowLogic(){
      tableBody.querySelectorAll('tr').forEach(tr => {
        const nextSel = tr.querySelector('.qf-next');
        const resSel  = tr.querySelector('.qf-result');
        const delBtn  = tr.querySelector('.qf-del');

        if (delBtn && !delBtn.dataset.bound) {
          delBtn.dataset.bound = '1';
          delBtn.addEventListener('click', () => {
            tr.remove();
            reindex();
          });
        }

        // Condicional: se escolher próxima pergunta, zera resultado; se escolher resultado, zera próxima pergunta
        if (nextSel && !nextSel.dataset.bound) {
          nextSel.dataset.bound = '1';
          nextSel.addEventListener('change', () => {
            if (parseInt(nextSel.value || '0', 10) > 0) resSel.value = '0';
          });
        }
        if (resSel && !resSel.dataset.bound) {
          resSel.dataset.bound = '1';
          resSel.addEventListener('change', () => {
            if (parseInt(resSel.value || '0', 10) > 0) nextSel.value = '0';
          });
        }
      });
    }

    bindRowLogic();
  })();
  </script>
  <?php
}

function qf_answer_row($index, $row, $questions, $results, $is_template = false) {
  $text = esc_attr($row['text'] ?? '');
  $next = absint($row['next_question_id'] ?? 0);
  $res  = absint($row['result_id'] ?? 0);

  echo '<tr>';
  echo '<td style="width:40%">';
  printf(
    '<input type="text" class="widefat" value="%s" data-name="qf_answers[%s][text]" %s />',
    $text,
    esc_attr($index),
    $is_template ? '' : 'name="qf_answers[' . esc_attr($index) . '][text]"'
  );
  echo '</td>';

  echo '<td style="width:25%"><select class="widefat qf-next" data-name="qf_answers['.esc_attr($index).'][next_question_id]" '.($is_template?'':'name="qf_answers['.esc_attr($index).'][next_question_id]"').'>';
  echo '<option value="0">—</option>';
  foreach ($questions as $id => $title) {
    printf('<option value="%d" %s>%s</option>', $id, selected($next, $id, false), esc_html($title));
  }
  echo '</select></td>';

  echo '<td style="width:25%"><select class="widefat qf-result" data-name="qf_answers['.esc_attr($index).'][result_id]" '.($is_template?'':'name="qf_answers['.esc_attr($index).'][result_id]"').'>';
  echo '<option value="0">—</option>';
  foreach ($results as $id => $title) {
    printf('<option value="%d" %s>%s</option>', $id, selected($res, $id, false), esc_html($title));
  }
  echo '</select></td>';

  echo '<td style="width:10%"><button type="button" class="button qf-del">Remover</button></td>';
  echo '</tr>';
}

add_action('save_post_qf_question', function ($post_id) {
  if (!isset($_POST['qf_question_nonce']) || !wp_verify_nonce($_POST['qf_question_nonce'], 'qf_question_save')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;

  $raw = $_POST['qf_answers'] ?? [];
  $answers = qf_sanitize_answers($raw);
  update_post_meta($post_id, '_qf_answers', $answers);
});
