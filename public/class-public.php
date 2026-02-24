<?php
if (!defined('ABSPATH')) {
    exit;
}

class Mentalia_PsyTest_Public
{

    public function __construct()
    {
        add_shortcode('mentalia_test', [$this, 'render_test_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_mentalia_submit_test', [$this, 'ajax_submit_test']);
        add_action('wp_ajax_nopriv_mentalia_submit_test', [$this, 'ajax_submit_test']);
    }

    public function enqueue_assets()
    {
        wp_enqueue_style(
            'mentalia-public-style',
            MENTALIA_PSYTEST_URL . 'public/css/public-style.css',
        [],
            MENTALIA_PSYTEST_VERSION
        );

        wp_enqueue_script(
            'mentalia-public-script',
            MENTALIA_PSYTEST_URL . 'public/js/public-script.js',
        ['jquery'],
            MENTALIA_PSYTEST_VERSION,
            true
        );

        wp_localize_script('mentalia-public-script', 'mentaliaPublic', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mentalia_psytest_public_nonce'),
        ]);
    }

    public function render_test_shortcode($atts)
    {
        $atts = shortcode_atts(['id' => 0], $atts, 'mentalia_test');
        $test_id = intval($atts['id']);

        if ($test_id <= 0) {
            return '<p class="mentalia-error">ID tes tidak valid.</p>';
        }

        global $wpdb;
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_tests WHERE id = %d AND status = 'published'",
            $test_id
        ));

        if (!$test) {
            return '<p class="mentalia-error">Tes tidak ditemukan atau belum dipublish.</p>';
        }

        $order = $test->randomize_questions ? 'RAND()' : 'question_order ASC';
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_questions WHERE test_id = %d ORDER BY $order",
            $test_id
        ));

        foreach ($questions as &$q) {
            if (in_array($q->question_type, ['multiple_choice', 'true_false'])) {
                $q->options = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}psytest_options WHERE question_id = %d ORDER BY option_order ASC",
                    $q->id
                ));
            }
        }

        ob_start();
        include MENTALIA_PSYTEST_PATH . 'public/views/test-form.php';
        return ob_get_clean();
    }

    public function ajax_submit_test()
    {
        check_ajax_referer('mentalia_psytest_public_nonce', 'nonce');

        $test_id = intval($_POST['test_id'] ?? 0);
        $answers = $_POST['answers'] ?? [];
        $guest_name = sanitize_text_field($_POST['guest_name'] ?? '');
        $guest_email = sanitize_email($_POST['guest_email'] ?? '');

        if ($test_id <= 0) {
            wp_send_json_error('ID tes tidak valid.');
        }

        global $wpdb;
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_tests WHERE id = %d",
            $test_id
        ));

        if (!$test) {
            wp_send_json_error('Tes tidak ditemukan.');
        }

        // Calculate score
        $score_data = Mentalia_PsyTest_Scoring::calculate_score($test_id, $answers);

        // Save result
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $result = Mentalia_PsyTest_Scoring::save_result(
            $test_id, $score_data, $user_id, $guest_name, $guest_email
        );

        // Build response
        $response = [
            'result_id' => $result['result_id'],
            'total_score' => $result['total_score'],
            'max_possible_score' => $result['max_possible_score'],
            'show_score' => (bool)$test->show_score,
        ];

        if ($result['category']) {
            $response['category'] = [
                'label' => $result['category']->label,
                'description' => $result['category']->description,
                'color' => $result['category']->color,
            ];
        }

        wp_send_json_success($response);
    }
}
