<?php
if (!defined('ABSPATH')) {
    exit;
}

class Mentalia_PsyTest_Admin
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_mentalia_save_test', [$this, 'ajax_save_test']);
        add_action('wp_ajax_mentalia_delete_test', [$this, 'ajax_delete_test']);
        add_action('wp_ajax_mentalia_save_questions', [$this, 'ajax_save_questions']);
        add_action('wp_ajax_mentalia_load_questions', [$this, 'ajax_load_questions']);
        add_action('wp_ajax_mentalia_save_categories', [$this, 'ajax_save_categories']);
        add_action('wp_ajax_mentalia_load_categories', [$this, 'ajax_load_categories']);
        add_action('wp_ajax_mentalia_delete_result', [$this, 'ajax_delete_result']);
        add_action('wp_ajax_mentalia_export_results', [$this, 'ajax_export_results']);
        add_action('wp_ajax_mentalia_download_import_template', [$this, 'ajax_download_import_template']);
        add_action('wp_ajax_mentalia_import_test', [$this, 'ajax_import_test']);
    }

    public function add_menus()
    {
        add_menu_page(
            'Mentalia PsyTest',
            'PsyTest',
            'manage_options',
            'mentalia-psytest',
        [$this, 'page_dashboard'],
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'mentalia-psytest',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'mentalia-psytest',
        [$this, 'page_dashboard']
        );

        add_submenu_page(
            'mentalia-psytest',
            'Tambah Tes',
            'Tambah Tes',
            'manage_options',
            'mentalia-psytest-add',
        [$this, 'page_add_test']
        );

        add_submenu_page(
            'mentalia-psytest',
            'Import Tes',
            'Import Tes',
            'manage_options',
            'mentalia-psytest-import',
        [$this, 'page_import_test']
        );

        add_submenu_page(
            'mentalia-psytest',
            'Hasil Tes',
            'Hasil Tes',
            'manage_options',
            'mentalia-psytest-results',
        [$this, 'page_results']
        );

        // Hidden pages (no menu item)
        add_submenu_page(
            null,
            'Edit Tes',
            'Edit Tes',
            'manage_options',
            'mentalia-psytest-edit',
        [$this, 'page_edit_test']
        );

        add_submenu_page(
            null,
            'Kelola Soal',
            'Kelola Soal',
            'manage_options',
            'mentalia-psytest-questions',
        [$this, 'page_manage_questions']
        );

        add_submenu_page(
            null,
            'Kelola Kategori',
            'Kelola Kategori',
            'manage_options',
            'mentalia-psytest-categories',
        [$this, 'page_manage_categories']
        );

        add_submenu_page(
            null,
            'Detail Hasil',
            'Detail Hasil',
            'manage_options',
            'mentalia-psytest-result-detail',
        [$this, 'page_result_detail']
        );
    }

    public function enqueue_assets($hook)
    {
        if (strpos($hook, 'mentalia-psytest') === false) {
            return;
        }

        wp_enqueue_style(
            'mentalia-admin-style',
            MENTALIA_PSYTEST_URL . 'admin/css/admin-style.css',
        [],
            MENTALIA_PSYTEST_VERSION
        );

        wp_enqueue_script(
            'mentalia-admin-script',
            MENTALIA_PSYTEST_URL . 'admin/js/admin-script.js',
        ['jquery', 'jquery-ui-sortable'],
            MENTALIA_PSYTEST_VERSION,
            true
        );

        wp_localize_script('mentalia-admin-script', 'mentaliaAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mentalia_psytest_nonce'),
        ]);
    }

    // ========== PAGE RENDERERS ==========

    public function page_dashboard()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/dashboard.php';
    }

    public function page_add_test()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/add-test.php';
    }

    public function page_edit_test()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/edit-test.php';
    }

    public function page_manage_questions()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/manage-questions.php';
    }

    public function page_manage_categories()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/manage-categories.php';
    }

    public function page_results()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/view-results.php';
    }

    public function page_result_detail()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/result-detail.php';
    }

    public function page_import_test()
    {
        require_once MENTALIA_PSYTEST_PATH . 'admin/views/import-test.php';
    }

    // ========== AJAX HANDLERS ==========

    public function ajax_save_test()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'psytest_tests';

        $data = [
            'title' => sanitize_text_field($_POST['title'] ?? ''),
            'description' => wp_kses_post($_POST['description'] ?? ''),
            'instructions' => wp_kses_post($_POST['instructions'] ?? ''),
            'duration_minutes' => intval($_POST['duration_minutes'] ?? 0),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft'),
            'show_score' => intval($_POST['show_score'] ?? 1),
            'randomize_questions' => intval($_POST['randomize_questions'] ?? 0),
        ];

        $test_id = intval($_POST['test_id'] ?? 0);

        if ($test_id > 0) {
            $wpdb->update($table, $data, ['id' => $test_id]);
        }
        else {
            $wpdb->insert($table, $data);
            $test_id = $wpdb->insert_id;
        }

        wp_send_json_success([
            'test_id' => $test_id,
            'message' => 'Tes berhasil disimpan!'
        ]);
    }

    public function ajax_delete_test()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $test_id = intval($_POST['test_id'] ?? 0);

        if ($test_id <= 0) {
            wp_send_json_error('Invalid test ID');
        }

        // Delete related data
        $question_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}psytest_questions WHERE test_id = %d", $test_id
        ));

        if (!empty($question_ids)) {
            $placeholders = implode(',', array_fill(0, count($question_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}psytest_options WHERE question_id IN ($placeholders)",
                ...$question_ids
            ));
        }

        $wpdb->delete($wpdb->prefix . 'psytest_questions', ['test_id' => $test_id]);
        $wpdb->delete($wpdb->prefix . 'psytest_categories', ['test_id' => $test_id]);

        // Delete results and answers
        $result_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}psytest_results WHERE test_id = %d", $test_id
        ));
        if (!empty($result_ids)) {
            $placeholders = implode(',', array_fill(0, count($result_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}psytest_answers WHERE result_id IN ($placeholders)",
                ...$result_ids
            ));
        }
        $wpdb->delete($wpdb->prefix . 'psytest_results', ['test_id' => $test_id]);

        // Delete the test
        $wpdb->delete($wpdb->prefix . 'psytest_tests', ['id' => $test_id]);

        wp_send_json_success(['message' => 'Tes berhasil dihapus!']);
    }

    public function ajax_save_questions()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $test_id = intval($_POST['test_id'] ?? 0);
        $questions = json_decode(stripslashes($_POST['questions'] ?? '[]'), true);

        if ($test_id <= 0 || !is_array($questions)) {
            wp_send_json_error('Invalid data');
        }

        // Get existing question IDs
        $existing_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}psytest_questions WHERE test_id = %d", $test_id
        ));

        $submitted_ids = [];

        foreach ($questions as $order => $q) {
            $q_data = [
                'test_id' => $test_id,
                'question_text' => wp_kses_post($q['question_text'] ?? ''),
                'question_type' => sanitize_text_field($q['question_type'] ?? 'multiple_choice'),
                'question_order' => $order,
                'is_required' => intval($q['is_required'] ?? 1),
                'likert_min' => intval($q['likert_min'] ?? 1),
                'likert_max' => intval($q['likert_max'] ?? 5),
                'likert_min_label' => sanitize_text_field($q['likert_min_label'] ?? 'Sangat Tidak Setuju'),
                'likert_max_label' => sanitize_text_field($q['likert_max_label'] ?? 'Sangat Setuju'),
            ];

            $q_id = intval($q['id'] ?? 0);

            if ($q_id > 0 && in_array($q_id, $existing_ids)) {
                $wpdb->update($wpdb->prefix . 'psytest_questions', $q_data, ['id' => $q_id]);
                $submitted_ids[] = $q_id;
            }
            else {
                $wpdb->insert($wpdb->prefix . 'psytest_questions', $q_data);
                $q_id = $wpdb->insert_id;
                $submitted_ids[] = $q_id;
            }

            // Save options for this question (if applicable)
            if (in_array($q_data['question_type'], ['multiple_choice', 'true_false']) && isset($q['options'])) {
                // Delete existing options
                $wpdb->delete($wpdb->prefix . 'psytest_options', ['question_id' => $q_id]);

                foreach ($q['options'] as $opt_order => $opt) {
                    $wpdb->insert($wpdb->prefix . 'psytest_options', [
                        'question_id' => $q_id,
                        'option_text' => sanitize_text_field($opt['option_text'] ?? ''),
                        'score' => intval($opt['score'] ?? 0),
                        'option_order' => $opt_order,
                    ]);
                }
            }
        }

        // Delete removed questions
        $to_delete = array_diff($existing_ids, $submitted_ids);
        foreach ($to_delete as $del_id) {
            $wpdb->delete($wpdb->prefix . 'psytest_options', ['question_id' => $del_id]);
            $wpdb->delete($wpdb->prefix . 'psytest_questions', ['id' => $del_id]);
        }

        wp_send_json_success(['message' => 'Soal berhasil disimpan!']);
    }

    public function ajax_load_questions()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        global $wpdb;
        $test_id = intval($_POST['test_id'] ?? 0);

        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_questions WHERE test_id = %d ORDER BY question_order ASC",
            $test_id
        ));

        foreach ($questions as &$q) {
            $q->options = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}psytest_options WHERE question_id = %d ORDER BY option_order ASC",
                $q->id
            ));
        }

        wp_send_json_success(['questions' => $questions]);
    }

    public function ajax_save_categories()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $test_id = intval($_POST['test_id'] ?? 0);
        $categories = json_decode(stripslashes($_POST['categories'] ?? '[]'), true);

        if ($test_id <= 0 || !is_array($categories)) {
            wp_send_json_error('Invalid data');
        }

        // Delete existing categories
        $wpdb->delete($wpdb->prefix . 'psytest_categories', ['test_id' => $test_id]);

        foreach ($categories as $order => $cat) {
            $wpdb->insert($wpdb->prefix . 'psytest_categories', [
                'test_id' => $test_id,
                'label' => sanitize_text_field($cat['label'] ?? ''),
                'min_score' => intval($cat['min_score'] ?? 0),
                'max_score' => intval($cat['max_score'] ?? 0),
                'description' => wp_kses_post($cat['description'] ?? ''),
                'color' => sanitize_hex_color($cat['color'] ?? '#4CAF50'),
                'category_order' => $order,
            ]);
        }

        wp_send_json_success(['message' => 'Kategori berhasil disimpan!']);
    }

    public function ajax_load_categories()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        global $wpdb;
        $test_id = intval($_POST['test_id'] ?? 0);

        $categories = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_categories WHERE test_id = %d ORDER BY category_order ASC",
            $test_id
        ));

        wp_send_json_success(['categories' => $categories]);
    }

    public function ajax_delete_result()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $result_id = intval($_POST['result_id'] ?? 0);

        $wpdb->delete($wpdb->prefix . 'psytest_answers', ['result_id' => $result_id]);
        $wpdb->delete($wpdb->prefix . 'psytest_results', ['id' => $result_id]);

        wp_send_json_success(['message' => 'Hasil berhasil dihapus!']);
    }

    public function ajax_export_results()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $test_id = intval($_POST['test_id'] ?? 0);

        $where = $test_id > 0 ? $wpdb->prepare(' WHERE r.test_id = %d', $test_id) : '';

        $results = $wpdb->get_results(
            "SELECT r.*, t.title as test_title 
             FROM {$wpdb->prefix}psytest_results r 
             LEFT JOIN {$wpdb->prefix}psytest_tests t ON r.test_id = t.id
             $where
             ORDER BY r.completed_at DESC"
        );

        $csv_data = [];
        $csv_data[] = ['ID', 'Tes', 'Nama', 'Email', 'Skor', 'Skor Maks', 'Kategori', 'Tanggal'];

        foreach ($results as $r) {
            $name = $r->user_id ? get_userdata($r->user_id)->display_name ?? 'User #' . $r->user_id : $r->guest_name;
            $csv_data[] = [
                $r->id,
                $r->test_title,
                $name,
                $r->guest_email,
                $r->total_score,
                $r->max_possible_score,
                $r->category_label,
                $r->completed_at,
            ];
        }

        wp_send_json_success(['csv' => $csv_data]);
    }

    public function ajax_download_import_template()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $headers = ['question_text', 'question_type', 'is_required', 'option_text', 'option_score', 'likert_min', 'likert_max', 'likert_min_label', 'likert_max_label'];

        $examples = [
            ['Apakah Anda sering merasa cemas?', 'multiple_choice', '1', 'Tidak pernah', '0', '', '', '', ''],
            ['Apakah Anda sering merasa cemas?', 'multiple_choice', '1', 'Kadang-kadang', '1', '', '', '', ''],
            ['Apakah Anda sering merasa cemas?', 'multiple_choice', '1', 'Sering', '2', '', '', '', ''],
            ['Apakah Anda sering merasa cemas?', 'multiple_choice', '1', 'Selalu', '3', '', '', '', ''],
            ['Saya merasa nyaman dengan diri sendiri', 'likert', '1', '', '', '1', '5', 'Sangat Tidak Setuju', 'Sangat Setuju'],
            ['Saya mampu mengelola stres dengan baik', 'likert', '1', '', '', '1', '5', 'Sangat Tidak Setuju', 'Sangat Setuju'],
            ['Air mendidih pada suhu 100 derajat Celcius', 'true_false', '1', 'Benar', '1', '', '', '', ''],
            ['Air mendidih pada suhu 100 derajat Celcius', 'true_false', '1', 'Salah', '0', '', '', '', ''],
            ['Ceritakan pengalaman Anda mengatasi stres', 'essay', '0', '', '', '', '', '', ''],
        ];

        $csv_data = [];
        $csv_data[] = $headers;
        foreach ($examples as $row) {
            $csv_data[] = $row;
        }

        wp_send_json_success(['csv' => $csv_data, 'filename' => 'template-import-soal.csv']);
    }

    public function ajax_import_test()
    {
        check_ajax_referer('mentalia_psytest_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $test_id = intval($_POST['test_id'] ?? 0);
        if ($test_id <= 0) {
            wp_send_json_error('Pilih tes tujuan terlebih dahulu');
        }

        global $wpdb;

        // Verify test exists
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}psytest_tests WHERE id = %d", $test_id
        ));
        if (!$test) {
            wp_send_json_error('Tes tidak ditemukan');
        }

        $csv_data = json_decode(stripslashes($_POST['csv_data'] ?? '[]'), true);
        if (!is_array($csv_data) || count($csv_data) < 1) {
            wp_send_json_error('Data CSV kosong atau tidak valid');
        }

        // Get max question_order for existing questions
        $max_order = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(MAX(question_order), -1) FROM {$wpdb->prefix}psytest_questions WHERE test_id = %d",
            $test_id
        ));

        // Group rows by question_text
        $grouped = [];
        foreach ($csv_data as $row) {
            $q_text = trim($row['question_text'] ?? '');
            if (empty($q_text))
                continue;
            if (!isset($grouped[$q_text])) {
                $grouped[$q_text] = [
                    'question_text' => $q_text,
                    'question_type' => sanitize_text_field($row['question_type'] ?? 'multiple_choice'),
                    'is_required' => intval($row['is_required'] ?? 1),
                    'likert_min' => intval($row['likert_min'] ?? 1),
                    'likert_max' => intval($row['likert_max'] ?? 5),
                    'likert_min_label' => sanitize_text_field($row['likert_min_label'] ?? 'Sangat Tidak Setuju'),
                    'likert_max_label' => sanitize_text_field($row['likert_max_label'] ?? 'Sangat Setuju'),
                    'options' => [],
                ];
            }
            $opt_text = trim($row['option_text'] ?? '');
            if (!empty($opt_text)) {
                $grouped[$q_text]['options'][] = [
                    'option_text' => sanitize_text_field($opt_text),
                    'score' => intval($row['option_score'] ?? 0),
                ];
            }
        }

        $imported_questions = 0;
        $imported_options = 0;

        foreach ($grouped as $q) {
            $max_order++;
            $wpdb->insert($wpdb->prefix . 'psytest_questions', [
                'test_id' => $test_id,
                'question_text' => wp_kses_post($q['question_text']),
                'question_type' => $q['question_type'],
                'question_order' => $max_order,
                'is_required' => $q['is_required'],
                'likert_min' => $q['likert_min'],
                'likert_max' => $q['likert_max'],
                'likert_min_label' => $q['likert_min_label'],
                'likert_max_label' => $q['likert_max_label'],
            ]);
            $q_id = $wpdb->insert_id;
            $imported_questions++;

            foreach ($q['options'] as $opt_order => $opt) {
                $wpdb->insert($wpdb->prefix . 'psytest_options', [
                    'question_id' => $q_id,
                    'option_text' => $opt['option_text'],
                    'score' => $opt['score'],
                    'option_order' => $opt_order,
                ]);
                $imported_options++;
            }
        }

        wp_send_json_success([
            'message' => sprintf('Berhasil import %d soal dan %d opsi jawaban!', $imported_questions, $imported_options),
            'imported_questions' => $imported_questions,
            'imported_options' => $imported_options,
        ]);
    }
}
