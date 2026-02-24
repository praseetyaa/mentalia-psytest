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
        <span class="dashicons dashicons-tag"></span>
        Kategori Hasil: <?php echo esc_html($test->title); ?>
    </h1>

    <div class="mentalia-quick-links">
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-edit&test_id=' . $test->id); ?>" class="mentalia-quick-link">
            <span class="dashicons dashicons-edit"></span> Edit Tes
        </a>
        <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-questions&test_id=' . $test->id); ?>" class="mentalia-quick-link">
            <span class="dashicons dashicons-list-view"></span> Kelola Soal
        </a>
    </div>

    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Kategori Interpretasi Hasil</h2>
            <button type="button" class="button button-primary mentalia-btn-primary" id="btn-add-category">
                <span class="dashicons dashicons-plus-alt2"></span> Tambah Kategori
            </button>
        </div>

        <p class="mentalia-help">
            Atur range skor dan interpretasinya. Misal: skor 0-10 = "Rendah", skor 11-20 = "Sedang", skor 21-30 = "Tinggi".
        </p>

        <div id="categories-container" data-test-id="<?php echo esc_attr($test->id); ?>">
            <div class="mentalia-loading">
                <span class="spinner is-active"></span> Memuat kategori...
            </div>
        </div>

        <div class="mentalia-form-actions" style="margin-top: 20px;">
            <button type="button" id="btn-save-categories" class="button button-primary mentalia-btn-primary mentalia-btn-save">
                <span class="dashicons dashicons-saved"></span> Simpan Kategori
            </button>
            <a href="<?php echo admin_url('admin.php?page=mentalia-psytest'); ?>" class="button mentalia-btn-secondary">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Category Template -->
<script type="text/template" id="tmpl-category-item">
<div class="mentalia-category-item" data-category-id="{{id}}">
    <div class="category-header">
        <span class="category-color-dot" style="background-color: {{color}};"></span>
        <span class="category-label-preview">{{label}}</span>
        <button type="button" class="mentalia-btn-small mentalia-btn-delete btn-remove-category" title="Hapus">
            <span class="dashicons dashicons-trash"></span>
        </button>
    </div>
    <div class="category-body">
        <div class="mentalia-form-grid">
            <div class="mentalia-form-group">
                <label>Label Kategori <span class="required">*</span></label>
                <input type="text" class="cat-label" value="{{label}}" placeholder="Contoh: Rendah, Sedang, Tinggi">
            </div>
            <div class="mentalia-form-group">
                <label>Warna</label>
                <input type="color" class="cat-color" value="{{color}}">
            </div>
            <div class="mentalia-form-group">
                <label>Skor Minimum</label>
                <input type="number" class="cat-min-score" value="{{min_score}}" min="0">
            </div>
            <div class="mentalia-form-group">
                <label>Skor Maksimum</label>
                <input type="number" class="cat-max-score" value="{{max_score}}" min="0">
            </div>
            <div class="mentalia-form-group mentalia-full-width">
                <label>Deskripsi/Interpretasi</label>
                <textarea class="cat-description" rows="3" placeholder="Penjelasan untuk kategori ini...">{{description}}</textarea>
            </div>
        </div>
    </div>
</div>
</script>
