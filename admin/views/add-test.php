<?php
if (!defined('ABSPATH'))
    exit;
?>

<div class="wrap mentalia-admin">
    <h1 class="mentalia-title">
        <span class="dashicons dashicons-plus-alt2"></span>
        Tambah Tes Baru
    </h1>

    <form id="mentalia-test-form" class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Informasi Tes</h2>
        </div>

        <div class="mentalia-form-grid">
            <div class="mentalia-form-group mentalia-full-width">
                <label for="test-title">Judul Tes <span class="required">*</span></label>
                <input type="text" id="test-title" name="title" placeholder="Contoh: Tes Tingkat Kecemasan (GAD-7)" required>
            </div>

            <div class="mentalia-form-group mentalia-full-width">
                <label for="test-description">Deskripsi</label>
                <textarea id="test-description" name="description" rows="4" 
                          placeholder="Deskripsi singkat tentang tes ini..."></textarea>
            </div>

            <div class="mentalia-form-group mentalia-full-width">
                <label for="test-instructions">Instruksi Pengerjaan</label>
                <textarea id="test-instructions" name="instructions" rows="3" 
                          placeholder="Instruksi yang akan ditampilkan sebelum peserta memulai tes..."></textarea>
            </div>

            <div class="mentalia-form-group">
                <label for="test-duration">Durasi (menit)</label>
                <input type="number" id="test-duration" name="duration_minutes" min="0" value="0" 
                       placeholder="0 = tanpa batas waktu">
                <span class="mentalia-help">Set 0 untuk tanpa batas waktu</span>
            </div>

            <div class="mentalia-form-group">
                <label for="test-status">Status</label>
                <select id="test-status" name="status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>

            <div class="mentalia-form-group">
                <label class="mentalia-checkbox-label">
                    <input type="checkbox" name="show_score" value="1" checked>
                    Tampilkan skor ke peserta
                </label>
            </div>

            <div class="mentalia-form-group">
                <label class="mentalia-checkbox-label">
                    <input type="checkbox" name="randomize_questions" value="1">
                    Acak urutan soal
                </label>
            </div>
        </div>

        <div class="mentalia-form-actions">
            <button type="submit" class="button button-primary mentalia-btn-primary mentalia-btn-save">
                <span class="dashicons dashicons-saved"></span> Simpan Tes
            </button>
            <a href="<?php echo admin_url('admin.php?page=mentalia-psytest'); ?>" class="button mentalia-btn-secondary">
                Batal
            </a>
        </div>

        <input type="hidden" name="test_id" value="0">
    </form>
</div>
