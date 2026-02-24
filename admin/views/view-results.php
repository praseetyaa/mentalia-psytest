<?php
if (!defined('ABSPATH'))
    exit;
global $wpdb;

$filter_test = intval($_GET['filter_test'] ?? 0);
$paged = max(1, intval($_GET['paged'] ?? 1));
$per_page = 20;
$offset = ($paged - 1) * $per_page;

// Get all tests for filter
$all_tests = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}psytest_tests ORDER BY title ASC");

// Build query
$where = '';
if ($filter_test > 0) {
    $where = $wpdb->prepare(' WHERE r.test_id = %d', $filter_test);
}

$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}psytest_results r $where");
$total_pages = ceil($total / $per_page);

$results = $wpdb->get_results(
    "SELECT r.*, t.title as test_title 
     FROM {$wpdb->prefix}psytest_results r 
     LEFT JOIN {$wpdb->prefix}psytest_tests t ON r.test_id = t.id 
     $where 
     ORDER BY r.completed_at DESC 
     LIMIT $per_page OFFSET $offset"
);
?>

<div class="wrap mentalia-admin">
    <h1 class="mentalia-title">
        <span class="dashicons dashicons-chart-bar"></span>
        Hasil Tes Psikologi
    </h1>

    <div class="mentalia-card">
        <div class="mentalia-card-header">
            <h2>Semua Hasil (<?php echo esc_html($total); ?>)</h2>
            <div class="mentalia-header-actions">
                <form method="get" class="mentalia-filter-form">
                    <input type="hidden" name="page" value="mentalia-psytest-results">
                    <select name="filter_test" onchange="this.form.submit()">
                        <option value="0">Semua Tes</option>
                        <?php foreach ($all_tests as $t): ?>
                            <option value="<?php echo esc_attr($t->id); ?>" <?php selected($filter_test, $t->id); ?>>
                                <?php echo esc_html($t->title); ?>
                            </option>
                        <?php
endforeach; ?>
                    </select>
                </form>
                <button type="button" id="btn-export-csv" class="button mentalia-btn-secondary" data-filter-test="<?php echo esc_attr($filter_test); ?>">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </button>
            </div>
        </div>

        <?php if (empty($results)): ?>
            <div class="mentalia-empty-state">
                <span class="dashicons dashicons-chart-bar"></span>
                <h3>Belum ada hasil tes</h3>
                <p>Hasil akan muncul setelah ada peserta yang mengerjakan tes.</p>
            </div>
        <?php
else: ?>
            <table class="mentalia-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tes</th>
                        <th>Peserta</th>
                        <th>Skor</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $i => $r):
        $name = $r->user_id ? (get_userdata($r->user_id)->display_name ?? 'User #' . $r->user_id) : ($r->guest_name ?: 'Anonim');
?>
                        <tr>
                            <td><?php echo esc_html($offset + $i + 1); ?></td>
                            <td><?php echo esc_html($r->test_title); ?></td>
                            <td>
                                <strong><?php echo esc_html($name); ?></strong>
                                <?php if ($r->guest_email): ?>
                                    <br><small><?php echo esc_html($r->guest_email); ?></small>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($r->total_score); ?></strong>
                                <?php if ($r->max_possible_score > 0): ?>
                                    / <?php echo esc_html($r->max_possible_score); ?>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <?php if ($r->category_label): ?>
                                    <span class="mentalia-badge mentalia-badge-category"><?php echo esc_html($r->category_label); ?></span>
                                <?php
        else: ?>
                                    <span class="mentalia-badge mentalia-badge-draft">-</span>
                                <?php
        endif; ?>
                            </td>
                            <td><?php echo date_i18n('d M Y, H:i', strtotime($r->completed_at)); ?></td>
                            <td>
                                <div class="mentalia-actions">
                                    <a href="<?php echo admin_url('admin.php?page=mentalia-psytest-result-detail&result_id=' . $r->id); ?>" 
                                       class="mentalia-btn-small mentalia-btn-edit" title="Detail">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <button class="mentalia-btn-small mentalia-btn-delete btn-delete-result" 
                                            data-result-id="<?php echo esc_attr($r->id); ?>" title="Hapus">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
    endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="mentalia-pagination">
                    <?php
        $base_url = admin_url('admin.php?page=mentalia-psytest-results');
        if ($filter_test > 0)
            $base_url .= '&filter_test=' . $filter_test;

        for ($p = 1; $p <= $total_pages; $p++):
?>
                        <a href="<?php echo esc_url($base_url . '&paged=' . $p); ?>" 
                           class="mentalia-page-link <?php echo $p === $paged ? 'active' : ''; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php
        endfor; ?>
                </div>
            <?php
    endif; ?>
        <?php
endif; ?>
    </div>
</div>
