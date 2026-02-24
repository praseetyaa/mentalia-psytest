<?php
if (!defined('ABSPATH'))
    exit;
global $wpdb;

$tests = $wpdb->get_results(
    "SELECT id, title FROM {$wpdb->prefix}psytest_tests ORDER BY title ASC"
);
?>

<div class="wrap mentalia-admin">
    <h1 class="mentalia-title">
        <span class="dashicons dashicons-upload"></span>
        Import Data Tes Psikologi
    </h1>

    <!-- Step 1: Download Template -->
    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>
                <span class="dashicons dashicons-download" style="color:#764ba2;margin-right:6px;"></span>
                1. Download Template CSV
            </h2>
        </div>
        <p style="color:#757575;margin:0 0 16px;">
            Download template CSV terlebih dahulu, lalu isi dengan data soal yang ingin diimport.
            Template sudah berisi contoh data untuk berbagai tipe soal.
        </p>
        <button type="button" id="btn-download-template" class="button button-primary mentalia-btn-primary">
            <span class="dashicons dashicons-download"></span> Download Template CSV
        </button>
    </div>

    <!-- Step 2: Select Test & Upload -->
    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>
                <span class="dashicons dashicons-database-import" style="color:#764ba2;margin-right:6px;"></span>
                2. Import Data Soal
            </h2>
        </div>

        <div class="mentalia-form-grid">
            <div class="mentalia-form-group mentalia-full-width">
                <label for="import-test-id">Pilih Tes Tujuan <span class="required">*</span></label>
                <select id="import-test-id">
                    <option value="">— Pilih Tes —</option>
                    <?php foreach ($tests as $test): ?>
                        <option value="<?php echo esc_attr($test->id); ?>">
                            <?php echo esc_html($test->title); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
                <?php if (empty($tests)): ?>
                    <span class="mentalia-help" style="color:#c62828;">
                        Belum ada tes. <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-add'); ?>">Buat tes baru</a> terlebih dahulu.
                    </span>
                <?php
else: ?>
                    <span class="mentalia-help">Soal akan ditambahkan ke tes yang dipilih</span>
                <?php
endif; ?>
            </div>

            <div class="mentalia-form-group mentalia-full-width">
                <label>File CSV</label>
                <div id="import-dropzone" class="mentalia-dropzone">
                    <div class="dropzone-content">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <p class="dropzone-title">Drag & drop file CSV di sini</p>
                        <p class="dropzone-subtitle">atau klik untuk pilih file</p>
                    </div>
                    <input type="file" id="import-file" accept=".csv" style="display:none;">
                </div>
                <div id="import-file-info" class="mentalia-file-info" style="display:none;">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <span class="file-name"></span>
                    <span class="file-size"></span>
                    <button type="button" class="btn-remove-file mentalia-btn-small mentalia-btn-delete" title="Hapus file">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview -->
        <div id="import-preview" style="display:none;margin-top:20px;">
            <h3 style="margin:0 0 12px;font-size:15px;font-weight:600;">
                <span class="dashicons dashicons-visibility" style="color:#764ba2;"></span>
                Preview Data
            </h3>
            <div class="mentalia-preview-summary">
                <span class="mentalia-badge mentalia-badge-published" id="preview-question-count">0 soal</span>
                <span class="mentalia-badge mentalia-badge-category" id="preview-row-count">0 baris</span>
            </div>
            <div style="overflow-x:auto;margin-top:12px;">
                <table class="mentalia-table" id="import-preview-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Teks Soal</th>
                            <th>Tipe</th>
                            <th>Wajib</th>
                            <th>Opsi</th>
                            <th>Skor</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="mentalia-form-actions">
            <button type="button" id="btn-import-test" class="button button-primary mentalia-btn-primary mentalia-btn-save" disabled>
                <span class="dashicons dashicons-database-import"></span> Import Soal
            </button>
            <a href="<?php echo admin_url('admin.php?page=mentalia-psytest'); ?>" class="button mentalia-btn-secondary">
                Batal
            </a>
        </div>
    </div>

    <!-- Import Result -->
    <div id="import-result" class="mentalia-card" style="display:none;">
        <div class="mentalia-card-header">
            <h2>
                <span class="dashicons dashicons-yes-alt" style="color:#2e7d32;margin-right:6px;"></span>
                Hasil Import
            </h2>
        </div>
        <div id="import-result-content"></div>
    </div>
</div>
