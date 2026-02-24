<?php
if (!defined('ABSPATH'))
    exit;
$total_questions = count($questions);
?>

<div class="mentalia-test-wrapper" data-test-id="<?php echo esc_attr($test->id); ?>" data-total="<?php echo $total_questions; ?>">
    
    <!-- Test Intro -->
    <div class="mentalia-test-intro" id="test-intro-<?php echo esc_attr($test->id); ?>">
        <div class="mentalia-test-header">
            <h2 class="mentalia-test-title"><?php echo esc_html($test->title); ?></h2>
            <?php if ($test->description): ?>
                <p class="mentalia-test-desc"><?php echo wp_kses_post($test->description); ?></p>
            <?php
endif; ?>
        </div>

        <div class="mentalia-test-meta">
            <?php if ($total_questions > 0): ?>
                <span class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <?php echo $total_questions; ?> Soal
                </span>
            <?php
endif; ?>
            <?php if ($test->duration_minutes > 0): ?>
                <span class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo esc_html($test->duration_minutes); ?> Menit
                </span>
            <?php
endif; ?>
        </div>

        <?php if ($test->instructions): ?>
            <div class="mentalia-instructions">
                <h4>Instruksi</h4>
                <div><?php echo wp_kses_post($test->instructions); ?></div>
            </div>
        <?php
endif; ?>

        <?php if (!is_user_logged_in()): ?>
            <div class="mentalia-guest-info">
                <h4>Informasi Peserta</h4>
                <div class="mentalia-form-row">
                    <input type="text" class="mentalia-input guest-name" placeholder="Nama Anda (opsional)">
                    <input type="email" class="mentalia-input guest-email" placeholder="Email (opsional)">
                </div>
            </div>
        <?php
endif; ?>

        <button type="button" class="mentalia-btn mentalia-btn-start">
            Mulai Tes
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
    </div>

    <!-- Test Questions -->
    <div class="mentalia-test-questions" style="display:none;" id="test-questions-<?php echo esc_attr($test->id); ?>">
        
        <!-- Progress Bar -->
        <div class="mentalia-progress">
            <div class="mentalia-progress-bar" style="width: 0%;">
                <span class="mentalia-progress-text">0 / <?php echo $total_questions; ?></span>
            </div>
        </div>

        <?php if ($test->duration_minutes > 0): ?>
            <div class="mentalia-timer" data-minutes="<?php echo esc_attr($test->duration_minutes); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span class="timer-display">--:--</span>
            </div>
        <?php
endif; ?>

        <form class="mentalia-test-form">
            <?php foreach ($questions as $index => $q): ?>
                <div class="mentalia-question" 
                     data-index="<?php echo $index; ?>" 
                     data-question-id="<?php echo esc_attr($q->id); ?>"
                     data-required="<?php echo esc_attr($q->is_required); ?>"
                     style="<?php echo $index > 0 ? 'display:none;' : ''; ?>">
                    
                    <div class="question-counter">
                        Soal <?php echo $index + 1; ?> dari <?php echo $total_questions; ?>
                    </div>

                    <h3 class="question-text"><?php echo wp_kses_post($q->question_text); ?></h3>

                    <div class="question-answer">
                        <?php if ($q->question_type === 'multiple_choice'): ?>
                            <div class="mentalia-options">
                                <?php foreach ($q->options as $opt): ?>
                                    <label class="mentalia-option">
                                        <input type="radio" name="answer_<?php echo esc_attr($q->id); ?>" value="<?php echo esc_attr($opt->id); ?>">
                                        <span class="option-label"><?php echo esc_html($opt->option_text); ?></span>
                                    </label>
                                <?php
        endforeach; ?>
                            </div>

                        <?php
    elseif ($q->question_type === 'true_false'): ?>
                            <div class="mentalia-options mentalia-options-tf">
                                <?php foreach ($q->options as $opt): ?>
                                    <label class="mentalia-option">
                                        <input type="radio" name="answer_<?php echo esc_attr($q->id); ?>" value="<?php echo esc_attr($opt->id); ?>">
                                        <span class="option-label"><?php echo esc_html($opt->option_text); ?></span>
                                    </label>
                                <?php
        endforeach; ?>
                            </div>

                        <?php
    elseif ($q->question_type === 'likert'): ?>
                            <div class="mentalia-likert">
                                <div class="likert-labels">
                                    <span><?php echo esc_html($q->likert_min_label); ?></span>
                                    <span><?php echo esc_html($q->likert_max_label); ?></span>
                                </div>
                                <div class="likert-scale">
                                    <?php for ($s = intval($q->likert_min); $s <= intval($q->likert_max); $s++): ?>
                                        <label class="likert-item">
                                            <input type="radio" name="answer_<?php echo esc_attr($q->id); ?>" value="<?php echo $s; ?>">
                                            <span class="likert-circle"><?php echo $s; ?></span>
                                        </label>
                                    <?php
        endfor; ?>
                                </div>
                            </div>

                        <?php
    elseif ($q->question_type === 'essay'): ?>
                            <textarea class="mentalia-textarea" name="answer_<?php echo esc_attr($q->id); ?>" 
                                      rows="4" placeholder="Tulis jawaban Anda di sini..."></textarea>
                        <?php
    endif; ?>
                    </div>
                </div>
            <?php
endforeach; ?>

            <!-- Navigation -->
            <div class="mentalia-nav">
                <button type="button" class="mentalia-btn mentalia-btn-prev" style="display:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Sebelumnya
                </button>
                <button type="button" class="mentalia-btn mentalia-btn-next">
                    Selanjutnya
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
                <button type="button" class="mentalia-btn mentalia-btn-submit" style="display:none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Selesai & Kirim
                </button>
            </div>

            <input type="hidden" name="test_id" value="<?php echo esc_attr($test->id); ?>">
        </form>
    </div>

    <!-- Result Display -->
    <div class="mentalia-test-result" style="display:none;" id="test-result-<?php echo esc_attr($test->id); ?>">
        <!-- Filled dynamically by JS -->
    </div>
</div>
