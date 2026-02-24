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
        <span class="dashicons dashicons-edit"></span>
        Edit Tes: <?php echo esc_html($test->title); ?>
    </h1>

    <div class="mentalia-quick-links">
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-questions&test_id=' . $test->id); ?>" class="mentalia-quick-link">
            <span class="dashicons dashicons-list-view"></span> Kelola Soal
        </a>
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-categories&test_id=' . $test->id); ?>" class="mentalia-quick-link">
            <span class="dashicons dashicons-tag"></span> Kategori Hasil
        </a>
    </div>

    <form id="mentalia-test-form" class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Informasi Tes</h2>
        </div>

        <div class="mentalia-form-grid">
            <div class="mentalia-form-group mentalia-full-width">
                <label for="test-title">Judul Tes <span class="required">*</span></label>
                <input type="text" id="test-title" name="title" value="<?php echo esc_attr($test->title); ?>" required>
            </div>

            <div class="mentalia-form-group mentalia-full-width">
                <label for="test-description">Deskripsi</label>
                <textarea id="test-description" name="description" rows="4"><?php echo esc_textarea($test->description); ?></textarea>
            </div>

            <div class="mentalia-form-group mentalia-full-width">
                <label for="test-instructions">Instruksi Pengerjaan</label>
                <textarea id="test-instructions" name="instructions" rows="3"><?php echo esc_textarea($test->instructions); ?></textarea>
            </div>

            <div class="mentalia-form-group">
                <label for="test-duration">Durasi (menit)</label>
                <input type="number" id="test-duration" name="duration_minutes" min="0" 
                       value="<?php echo esc_attr($test->duration_minutes); ?>">
                <span class="mentalia-help">Set 0 untuk tanpa batas waktu</span>
            </div>

            <div class="mentalia-form-group">
                <label for="test-status">Status</label>
                <select id="test-status" name="status">
                    <option value="draft" <?php selected($test->status, 'draft'); ?>>Draft</option>
                    <option value="published" <?php selected($test->status, 'published'); ?>>Published</option>
                </select>
            </div>

            <div class="mentalia-form-group">
                <label class="mentalia-checkbox-label">
                    <input type="checkbox" name="show_score" value="1" <?php checked($test->show_score, 1); ?>>
                    Tampilkan skor ke peserta
                </label>
            </div>

            <div class="mentalia-form-group">
                <label class="mentalia-checkbox-label">
                    <input type="checkbox" name="randomize_questions" value="1" <?php checked($test->randomize_questions, 1); ?>>
                    Acak urutan soal
                </label>
            </div>
        </div>

        <div class="mentalia-form-actions">
            <button type="submit" class="button button-primary mentalia-btn-primary mentalia-btn-save">
                <span class="dashicons dashicons-saved"></span> Update Tes
            </button>
            <a href="<?php echo admin_url('admin.php?page=mentalia-psytest'); ?>" class="button mentalia-btn-secondary">
                Kembali
            </a>
        </div>

        <input type="hidden" name="test_id" value="<?php echo esc_attr($test->id); ?>">
    </form>

    <div class="mentalia-card mentalia-shortcode-info">
        <h3>Shortcode</h3>
        <p>Gunakan shortcode berikut untuk menampilkan tes ini di halaman:</p>
        <code class="mentalia-shortcode-large" onclick="navigator.clipboard.writeText(this.textContent)">[mentalia_test id="<?php echo esc_attr($test->id); ?>"]</code>
        <p class="mentalia-help">Klik shortcode untuk menyalin</p>
    </div>
</div>
