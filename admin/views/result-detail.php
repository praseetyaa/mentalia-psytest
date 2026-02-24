<?php
if (!defined('ABSPATH'))
    exit;
global $wpdb;

$result_id = intval($_GET['result_id'] ?? 0);
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT r.*, t.title as test_title, t.show_score 
     FROM {$wpdb->prefix}psytest_results r 
     LEFT JOIN {$wpdb->prefix}psytest_tests t ON r.test_id = t.id 
     WHERE r.id = %d", $result_id
));

if (!$result) {
    echo '<div class="wrap"><div class="notice notice-error"><p>Hasil tidak ditemukan.</p></div></div>';
    return;
}

$answers = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, q.question_text, q.question_type, o.option_text 
     FROM {$wpdb->prefix}psytest_answers a 
     LEFT JOIN {$wpdb->prefix}psytest_questions q ON a.question_id = q.id 
     LEFT JOIN {$wpdb->prefix}psytest_options o ON a.option_id = o.id 
     WHERE a.result_id = %d 
     ORDER BY q.question_order ASC", $result_id
));

$name = $result->user_id
    ? (get_userdata($result->user_id)->display_name ?? 'User #' . $result->user_id)
    : ($result->guest_name ?: 'Anonim');

$category = null;
if ($result->category_id) {
    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}psytest_categories WHERE id = %d", $result->category_id
    ));
}
?>

<div class="wrap mentalia-admin">
    <h1 class="mentalia-title">
        <span class="dashicons dashicons-visibility"></span>
        Detail Hasil Tes
    </h1>

    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2><?php echo esc_html($result->test_title); ?></h2>
        </div>

        <div class="mentalia-result-summary">
            <div class="mentalia-result-info-grid">
                <div class="result-info-item">
                    <span class="label">Peserta:</span>
                    <span class="value"><?php echo esc_html($name); ?></span>
                </div>
                <?php if ($result->guest_email): ?>
                <div class="result-info-item">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo esc_html($result->guest_email); ?></span>
                </div>
                <?php
endif; ?>
                <div class="result-info-item">
                    <span class="label">Tanggal:</span>
                    <span class="value"><?php echo date_i18n('d M Y, H:i:s', strtotime($result->completed_at)); ?></span>
                </div>
                <div class="result-info-item">
                    <span class="label">IP Address:</span>
                    <span class="value"><?php echo esc_html($result->ip_address); ?></span>
                </div>
            </div>

            <div class="mentalia-score-display">
                <div class="score-circle" <?php if ($category): ?>style="border-color: <?php echo esc_attr($category->color); ?>;"<?php
endif; ?>>
                    <span class="score-value"><?php echo esc_html($result->total_score); ?></span>
                    <?php if ($result->max_possible_score > 0): ?>
                        <span class="score-max">/ <?php echo esc_html($result->max_possible_score); ?></span>
                    <?php
endif; ?>
                </div>
                <?php if ($result->category_label): ?>
                    <div class="score-category" <?php if ($category): ?>style="color: <?php echo esc_attr($category->color); ?>;"<?php
    endif; ?>>
                        <?php echo esc_html($result->category_label); ?>
                    </div>
                <?php
endif; ?>
                <?php if ($category && $category->description): ?>
                    <div class="score-interpretation">
                        <?php echo wp_kses_post($category->description); ?>
                    </div>
                <?php
endif; ?>
            </div>
        </div>
    </div>

    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Detail Jawaban</h2>
        </div>

        <table class="mentalia-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pertanyaan</th>
                    <th>Jawaban</th>
                    <th>Skor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($answers as $i => $a): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo esc_html($a->question_text); ?></td>
                        <td>
                            <?php
    if ($a->question_type === 'essay') {
        echo esc_html($a->answer_text);
    }
    elseif ($a->option_text) {
        echo esc_html($a->option_text);
    }
    elseif ($a->answer_text) {
        echo esc_html($a->answer_text);
    }
    else {
        echo '<em>Tidak dijawab</em>';
    }
?>
                        </td>
                        <td><?php echo esc_html($a->score); ?></td>
                    </tr>
                <?php
endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mentalia-form-actions">
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-results'); ?>" class="button mentalia-btn-secondary">
            ← Kembali ke Daftar Hasil
        </a>
    </div>
</div>
