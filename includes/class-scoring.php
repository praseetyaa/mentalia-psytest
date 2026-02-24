<?php
if (!defined('ABSPATH')) {
    exit;
}

class Mentalia_PsyTest_Scoring
{

    /**
     * Calculate the total score from submitted answers
     */
    public static function calculate_score($test_id, $answers)
    {
        global $wpdb;
        $total_score = 0;
        $max_possible = 0;
        $scored_answers = [];

        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_questions WHERE test_id = %d ORDER BY question_order ASC",
            $test_id
        ));

        foreach ($questions as $question) {
            $qid = $question->id;
            $answer_value = isset($answers[$qid]) ? $answers[$qid] : null;
            $score = 0;

            switch ($question->question_type) {
                case 'multiple_choice':
                case 'true_false':
                    // Get max score for this question
                    $max_q_score = (int)$wpdb->get_var($wpdb->prepare(
                        "SELECT MAX(score) FROM {$wpdb->prefix}psytest_options WHERE question_id = %d",
                        $qid
                    ));
                    $max_possible += $max_q_score;

                    if ($answer_value) {
                        $option = $wpdb->get_row($wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}psytest_options WHERE id = %d AND question_id = %d",
                            intval($answer_value), $qid
                        ));
                        if ($option) {
                            $score = (int)$option->score;
                        }
                    }

                    $scored_answers[] = [
                        'question_id' => $qid,
                        'option_id' => $answer_value ? intval($answer_value) : null,
                        'answer_text' => null,
                        'score' => $score,
                    ];
                    break;

                case 'likert':
                    $max_possible += (int)$question->likert_max;
                    $score = $answer_value ? intval($answer_value) : 0;

                    $scored_answers[] = [
                        'question_id' => $qid,
                        'option_id' => null,
                        'answer_text' => (string)$score,
                        'score' => $score,
                    ];
                    break;

                case 'essay':
                    // Essay is not automatically scored
                    $scored_answers[] = [
                        'question_id' => $qid,
                        'option_id' => null,
                        'answer_text' => sanitize_textarea_field($answer_value ?? ''),
                        'score' => 0,
                    ];
                    break;
            }

            $total_score += $score;
        }

        return [
            'total_score' => $total_score,
            'max_possible_score' => $max_possible,
            'answers' => $scored_answers,
        ];
    }

    /**
     * Get the result category based on score
     */
    public static function get_category($test_id, $total_score)
    {
        global $wpdb;

        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}psytest_categories 
             WHERE test_id = %d AND min_score <= %d AND max_score >= %d 
             ORDER BY category_order ASC LIMIT 1",
            $test_id, $total_score, $total_score
        ));

        return $category;
    }

    /**
     * Save test result to database
     */
    public static function save_result($test_id, $score_data, $user_id = 0, $guest_name = '', $guest_email = '')
    {
        global $wpdb;

        $category = self::get_category($test_id, $score_data['total_score']);

        // Insert result
        $wpdb->insert(
            $wpdb->prefix . 'psytest_results',
        [
            'test_id' => $test_id,
            'user_id' => $user_id,
            'guest_name' => $guest_name,
            'guest_email' => $guest_email,
            'total_score' => $score_data['total_score'],
            'max_possible_score' => $score_data['max_possible_score'],
            'category_id' => $category ? $category->id : null,
            'category_label' => $category ? $category->label : '',
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
        ],
        ['%d', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s']
        );

        $result_id = $wpdb->insert_id;

        // Insert individual answers
        foreach ($score_data['answers'] as $answer) {
            $wpdb->insert(
                $wpdb->prefix . 'psytest_answers',
            [
                'result_id' => $result_id,
                'question_id' => $answer['question_id'],
                'option_id' => $answer['option_id'],
                'answer_text' => $answer['answer_text'],
                'score' => $answer['score'],
            ],
            ['%d', '%d', '%d', '%s', '%d']
            );
        }

        return [
            'result_id' => $result_id,
            'total_score' => $score_data['total_score'],
            'max_possible_score' => $score_data['max_possible_score'],
            'category' => $category,
        ];
    }
}
