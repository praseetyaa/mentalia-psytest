<?php
if (!defined('ABSPATH')) {
    exit;
}

class Mentalia_PsyTest_Database
{

    public function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tests table
        $table_tests = $wpdb->prefix . 'psytest_tests';
        $sql_tests = "CREATE TABLE $table_tests (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description longtext,
            instructions longtext,
            cover_image varchar(500),
            duration_minutes int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'draft',
            show_score tinyint(1) DEFAULT 1,
            randomize_questions tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Questions table
        $table_questions = $wpdb->prefix . 'psytest_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id bigint(20) UNSIGNED NOT NULL,
            question_text longtext NOT NULL,
            question_type varchar(30) NOT NULL DEFAULT 'multiple_choice',
            question_order int(11) DEFAULT 0,
            is_required tinyint(1) DEFAULT 1,
            likert_min int(11) DEFAULT 1,
            likert_max int(11) DEFAULT 5,
            likert_min_label varchar(100) DEFAULT 'Sangat Tidak Setuju',
            likert_max_label varchar(100) DEFAULT 'Sangat Setuju',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $charset_collate;";

        // Options table (for multiple choice & true/false)
        $table_options = $wpdb->prefix . 'psytest_options';
        $sql_options = "CREATE TABLE $table_options (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question_id bigint(20) UNSIGNED NOT NULL,
            option_text varchar(500) NOT NULL,
            score int(11) DEFAULT 0,
            option_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY question_id (question_id)
        ) $charset_collate;";

        // Categories table (score ranges => interpretations)
        $table_categories = $wpdb->prefix . 'psytest_categories';
        $sql_categories = "CREATE TABLE $table_categories (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id bigint(20) UNSIGNED NOT NULL,
            label varchar(100) NOT NULL,
            min_score int(11) NOT NULL,
            max_score int(11) NOT NULL,
            description longtext,
            color varchar(20) DEFAULT '#4CAF50',
            category_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY test_id (test_id)
        ) $charset_collate;";

        // Results table
        $table_results = $wpdb->prefix . 'psytest_results';
        $sql_results = "CREATE TABLE $table_results (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT 0,
            guest_name varchar(200),
            guest_email varchar(200),
            total_score int(11) DEFAULT 0,
            max_possible_score int(11) DEFAULT 0,
            category_id bigint(20) UNSIGNED DEFAULT NULL,
            category_label varchar(100),
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Answers table
        $table_answers = $wpdb->prefix . 'psytest_answers';
        $sql_answers = "CREATE TABLE $table_answers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            result_id bigint(20) UNSIGNED NOT NULL,
            question_id bigint(20) UNSIGNED NOT NULL,
            option_id bigint(20) UNSIGNED DEFAULT NULL,
            answer_text longtext,
            score int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY result_id (result_id),
            KEY question_id (question_id)
        ) $charset_collate;";

        dbDelta($sql_tests);
        dbDelta($sql_questions);
        dbDelta($sql_options);
        dbDelta($sql_categories);
        dbDelta($sql_results);
        dbDelta($sql_answers);

        update_option('mentalia_psytest_db_version', MENTALIA_PSYTEST_VERSION);
    }

    public static function drop_tables()
    {
        global $wpdb;
        $tables = [
            $wpdb->prefix . 'psytest_answers',
            $wpdb->prefix . 'psytest_results',
            $wpdb->prefix . 'psytest_categories',
            $wpdb->prefix . 'psytest_options',
            $wpdb->prefix . 'psytest_questions',
            $wpdb->prefix . 'psytest_tests',
        ];
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        delete_option('mentalia_psytest_db_version');
    }
}
