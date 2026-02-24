<?php
if (!defined('ABSPATH'))
    exit;
global $wpdb;

$test_id = intval($_GET['test_id'] ?? 0);
$test = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}psytest_tests WHERE id = %d", $test_id
));

if (!$test) {
    echo '<div class="wrap"><div class="notice notice-error"><p>Tes tidak ditemukan.</p></div></div>';
    return;
}
?>

<div class="wrap mentalia-admin">
    <h1 class="mentalia-title">
        <span class="dashicons dashicons-list-view"></span>
        Kelola Soal: <?php echo esc_html($test->title); ?>
    </h1>

    <div class="mentalia-quick-links">
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-edit&test_id=' . $test->id); ?>" class="mentalia-quick-link">
            <span class="dashicons dashicons-edit"></span> Edit Tes
        </a>
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-categories&test_id=' . $test->id); ?>" class="mentalia-quick-link">
            <span class="dashicons dashicons-tag"></span> Kategori Hasil
        </a>
    </div>

    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Daftar Soal</h2>
            <button type="button" class="button button-primary mentalia-btn-primary" id="btn-add-question">
                <span class="dashicons dashicons-plus-alt2"></span> Tambah Soal
            </button>
        </div>

        <div id="questions-container" data-test-id="<?php echo esc_attr($test->id); ?>">
            <div class="mentalia-loading">
                <span class="spinner is-active"></span> Memuat soal...
            </div>
        </div>

        <div class="mentalia-form-actions" style="margin-top: 20px;">
            <button type="button" id="btn-save-questions" class="button button-primary mentalia-btn-primary mentalia-btn-save">
                <span class="dashicons dashicons-saved"></span> Simpan Semua Soal
            </button>
            <a href="<?php echo admin_url('admin.php?page=mentalia-psytest'); ?>" class="button mentalia-btn-secondary">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Question Template -->
<script type="text/template" id="tmpl-question-item">
<div class="mentalia-question-item" data-question-id="{{id}}">
    <div class="question-header">
        <span class="question-drag-handle dashicons dashicons-menu"></span>
        <span class="question-number">Soal #{{number}}</span>
        <span class="question-type-badge">{{type_label}}</span>
        <button type="button" class="mentalia-btn-small mentalia-btn-delete btn-remove-question" title="Hapus Soal">
            <span class="dashicons dashicons-trash"></span>
        </button>
        <button type="button" class="mentalia-btn-small btn-toggle-question" title="Buka/Tutup">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>
    </div>
    <div class="question-body">
        <div class="mentalia-form-grid">
            <div class="mentalia-form-group mentalia-full-width">
                <label>Teks Soal <span class="required">*</span></label>
                <textarea class="q-text" rows="2" placeholder="Tulis pertanyaan di sini...">{{question_text}}</textarea>
            </div>
            <div class="mentalia-form-group">
                <label>Tipe Soal</label>
                <select class="q-type">
                    <option value="multiple_choice">Pilihan Ganda</option>
                    <option value="likert">Skala Likert</option>
                    <option value="true_false">Benar/Salah</option>
                    <option value="essay">Essai</option>
                </select>
            </div>
            <div class="mentalia-form-group">
                <label class="mentalia-checkbox-label">
                    <input type="checkbox" class="q-required" value="1" {{required_checked}}>
                    Wajib dijawab
                </label>
            </div>
        </div>

        <!-- Likert Settings -->
        <div class="likert-settings" style="display:none;">
            <div class="mentalia-form-grid">
                <div class="mentalia-form-group">
                    <label>Nilai Min</label>
                    <input type="number" class="q-likert-min" value="{{likert_min}}" min="0">
                </div>
                <div class="mentalia-form-group">
                    <label>Label Min</label>
                    <input type="text" class="q-likert-min-label" value="{{likert_min_label}}">
                </div>
                <div class="mentalia-form-group">
                    <label>Nilai Max</label>
                    <input type="number" class="q-likert-max" value="{{likert_max}}" min="1">
                </div>
                <div class="mentalia-form-group">
                    <label>Label Max</label>
                    <input type="text" class="q-likert-max-label" value="{{likert_max_label}}">
                </div>
            </div>
        </div>

        <!-- Options Container (for multiple choice & true/false) -->
        <div class="options-container" style="display:none;">
            <label>Opsi Jawaban</label>
            <div class="options-list"></div>
            <button type="button" class="button btn-add-option">
                <span class="dashicons dashicons-plus"></span> Tambah Opsi
            </button>
        </div>
    </div>
</div>
</script>

<!-- Option Template -->
<script type="text/template" id="tmpl-option-item">
<div class="mentalia-option-item">
    <input type="text" class="opt-text" placeholder="Teks opsi..." value="{{option_text}}">
    <input type="number" class="opt-score" placeholder="Skor" value="{{score}}" title="Skor">
    <button type="button" class="mentalia-btn-small mentalia-btn-delete btn-remove-option" title="Hapus">
        <span class="dashicons dashicons-minus"></span>
    </button>
</div>
</script>
