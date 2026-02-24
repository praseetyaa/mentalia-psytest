<?php
if (!defined('ABSPATH'))
    exit;
global $wpdb;

$tests = $wpdb->get_results(
    "SELECT t.*, 
        (SELECT COUNT(*) FROM {$wpdb->prefix}psytest_questions WHERE test_id = t.id) as question_count,
        (SELECT COUNT(*) FROM {$wpdb->prefix}psytest_results WHERE test_id = t.id) as result_count
     FROM {$wpdb->prefix}psytest_tests t 
     ORDER BY t.created_at DESC"
);

$total_tests = count($tests);
$total_results = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}psytest_results");
$published_tests = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}psytest_tests WHERE status = 'published'");
?>

<div class="wrap mentalia-admin">
    <h1 class="mentalia-title">
        <span class="dashicons dashicons-heart"></span>
        Mentalia PsyTest — Dashboard
    </h1>

    <div class="mentalia-stats-grid">
        <div class="mentalia-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <div class="stat-info">
                <h3><?php echo esc_html($total_tests); ?></h3>
                <p>Total Tes</p>
            </div>
        </div>
        <div class="mentalia-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-info">
                <h3><?php echo esc_html($published_tests); ?></h3>
                <p>Tes Published</p>
            </div>
        </div>
        <div class="mentalia-stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-info">
                <h3><?php echo esc_html($total_results); ?></h3>
                <p>Total Hasil</p>
            </div>
        </div>
    </div>

    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Daftar Tes Psikologi</h2>
            <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-add'); ?>" class="button button-primary mentalia-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span> Tambah Tes Baru
            </a>
        </div>

        <?php if (empty($tests)): ?>
            <div class="mentalia-empty-state">
                <span class="dashicons dashicons-clipboard"></span>
                <h3>Belum ada tes psikologi</h3>
                <p>Mulai buat tes psikologi pertama Anda!</p>
                <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-add'); ?>" class="button button-primary">Buat Tes Baru</a>
            </div>
        <?php
else: ?>
            <table class="mentalia-table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Soal</th>
                        <th>Peserta</th>
                        <th>Durasi</th>
                        <th>Shortcode</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tests as $test): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($test->title); ?></strong>
                                <div class="mentalia-row-date">
                                    <?php echo date_i18n('d M Y, H:i', strtotime($test->created_at)); ?>
                                </div>
                            </td>
                            <td>
                                <span class="mentalia-badge mentalia-badge-<?php echo esc_attr($test->status); ?>">
                                    <?php echo $test->status === 'published' ? 'Published' : 'Draft'; ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($test->question_count); ?> soal</td>
                            <td><?php echo esc_html($test->result_count); ?> peserta</td>
                            <td><?php echo $test->duration_minutes > 0 ? esc_html($test->duration_minutes) . ' menit' : '-'; ?></td>
                            <td>
                                <code class="mentalia-shortcode" title="Klik untuk copy" onclick="navigator.clipboard.writeText(this.textContent)">[mentalia_test id="<?php echo esc_attr($test->id); ?>"]</code>
                            </td>
                            <td>
                                <div class="mentalia-actions">
                                    <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-edit&test_id=' . $test->id); ?>" 
                                       class="mentalia-btn-small mentalia-btn-edit" title="Edit">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-questions&test_id=' . $test->id); ?>" 
                                       class="mentalia-btn-small mentalia-btn-questions" title="Kelola Soal">
                                        <span class="dashicons dashicons-list-view"></span>
                                    </a>
                                    <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-categories&test_id=' . $test->id); ?>" 
                                       class="mentalia-btn-small mentalia-btn-categories" title="Kategori Hasil">
                                        <span class="dashicons dashicons-tag"></span>
                                    </a>
                                    <button class="mentalia-btn-small mentalia-btn-delete" 
                                            data-test-id="<?php echo esc_attr($test->id); ?>" 
                                            title="Hapus">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
    endforeach; ?>
                </tbody>
            </table>
        <?php
endif; ?>
    </div>
</div>
