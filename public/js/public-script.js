(function ($) {
    'use strict';

    // ===== Test Navigation & Submission =====
    $('.mentalia-test-wrapper').each(function () {
        var $wrapper = $(this);
        var testId = $wrapper.data('test-id');
        var totalQuestions = parseInt($wrapper.data('total'));
        var currentIndex = 0;
        var timerInterval = null;

        var $intro = $wrapper.find('.mentalia-test-intro');
        var $questions = $wrapper.find('.mentalia-test-questions');
        var $result = $wrapper.find('.mentalia-test-result');
        var $form = $wrapper.find('.mentalia-test-form');
        var $progressBar = $wrapper.find('.mentalia-progress-bar');
        var $progressText = $wrapper.find('.mentalia-progress-text');
        var $btnPrev = $wrapper.find('.mentalia-btn-prev');
        var $btnNext = $wrapper.find('.mentalia-btn-next');
        var $btnSubmit = $wrapper.find('.mentalia-btn-submit');

        // Start test
        $wrapper.find('.mentalia-btn-start').on('click', function () {
            $intro.fadeOut(300, function () {
                $questions.fadeIn(300);
                updateProgress();
                startTimer();
            });
        });

        // Option selection highlight
        $wrapper.on('change', '.mentalia-option input[type="radio"]', function () {
            var $optionGroup = $(this).closest('.mentalia-options');
            $optionGroup.find('.mentalia-option').removeClass('selected');
            $(this).closest('.mentalia-option').addClass('selected');

            // Remove error state
            $(this).closest('.mentalia-question').removeClass('has-error');
        });

        // Textarea removes error
        $wrapper.on('input', '.mentalia-textarea', function () {
            $(this).closest('.mentalia-question').removeClass('has-error');
        });

        // Navigation
        $btnNext.on('click', function () {
            if (!validateCurrentQuestion()) return;
            if (currentIndex < totalQuestions - 1) {
                navigateTo(currentIndex + 1);
            }
        });

        $btnPrev.on('click', function () {
            if (currentIndex > 0) {
                navigateTo(currentIndex - 1);
            }
        });

        function navigateTo(index) {
            var $current = $wrapper.find('.mentalia-question[data-index="' + currentIndex + '"]');
            var $next = $wrapper.find('.mentalia-question[data-index="' + index + '"]');

            $current.fadeOut(200, function () {
                $next.fadeIn(300);
            });

            currentIndex = index;
            updateProgress();
            updateNavButtons();

            // Scroll to top of test
            $('html, body').animate({ scrollTop: $wrapper.offset().top - 20 }, 300);
        }

        function updateProgress() {
            var answered = currentIndex + 1;
            var percent = (answered / totalQuestions) * 100;
            $progressBar.css('width', percent + '%');
            $progressText.text(answered + ' / ' + totalQuestions);
        }

        function updateNavButtons() {
            $btnPrev.toggle(currentIndex > 0);

            if (currentIndex >= totalQuestions - 1) {
                $btnNext.hide();
                $btnSubmit.show();
            } else {
                $btnNext.show();
                $btnSubmit.hide();
            }
        }

        function validateCurrentQuestion() {
            var $q = $wrapper.find('.mentalia-question[data-index="' + currentIndex + '"]');
            var isRequired = $q.data('required') == 1;

            if (!isRequired) return true;

            var questionId = $q.data('question-id');
            var $radios = $q.find('input[name="answer_' + questionId + '"]');
            var $textarea = $q.find('textarea[name="answer_' + questionId + '"]');

            if ($radios.length > 0 && !$radios.is(':checked')) {
                $q.addClass('has-error');
                return false;
            }

            if ($textarea.length > 0 && $.trim($textarea.val()) === '') {
                $q.addClass('has-error');
                return false;
            }

            $q.removeClass('has-error');
            return true;
        }

        // Timer
        function startTimer() {
            var $timer = $wrapper.find('.mentalia-timer');
            if (!$timer.length) return;

            var minutes = parseInt($timer.data('minutes'));
            if (minutes <= 0) return;

            var totalSeconds = minutes * 60;
            var $display = $timer.find('.timer-display');

            timerInterval = setInterval(function () {
                totalSeconds--;

                if (totalSeconds <= 0) {
                    clearInterval(timerInterval);
                    submitTest();
                    return;
                }

                var m = Math.floor(totalSeconds / 60);
                var s = totalSeconds % 60;
                $display.text(String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'));

                // Warning at 1 minute
                if (totalSeconds <= 60) {
                    $timer.addClass('warning');
                }
            }, 1000);

            // Initial display
            var m = Math.floor(totalSeconds / 60);
            var s = totalSeconds % 60;
            $display.text(String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'));
        }

        // Submit
        $btnSubmit.on('click', function () {
            if (!validateCurrentQuestion()) return;
            submitTest();
        });

        function submitTest() {
            if (timerInterval) clearInterval(timerInterval);

            $btnSubmit.html('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Menghitung...').prop('disabled', true);

            // Collect answers
            var answers = {};
            $wrapper.find('.mentalia-question').each(function () {
                var qid = $(this).data('question-id');
                var $radio = $(this).find('input[name="answer_' + qid + '"]:checked');
                var $textarea = $(this).find('textarea[name="answer_' + qid + '"]');

                if ($radio.length) {
                    answers[qid] = $radio.val();
                } else if ($textarea.length) {
                    answers[qid] = $textarea.val();
                }
            });

            var postData = {
                action: 'mentalia_submit_test',
                nonce: mentaliaPublic.nonce,
                test_id: testId,
                answers: answers,
                guest_name: $wrapper.find('.guest-name').val() || '',
                guest_email: $wrapper.find('.guest-email').val() || ''
            };

            $.ajax({
                url: mentaliaPublic.ajax_url,
                type: 'POST',
                data: postData,
                success: function (res) {
                    if (res.success) {
                        showResult(res.data);
                    } else {
                        alert('Gagal mengirim jawaban: ' + res.data);
                        $btnSubmit.html('Selesai & Kirim').prop('disabled', false);
                    }
                },
                error: function () {
                    alert('Terjadi kesalahan jaringan.');
                    $btnSubmit.html('Selesai & Kirim').prop('disabled', false);
                }
            });
        }

        function showResult(data) {
            $questions.fadeOut(300, function () {
                var html = '<div class="result-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>';
                html += '<h2 class="result-title">Tes Selesai!</h2>';
                html += '<p class="result-subtitle">Terima kasih telah mengerjakan tes ini.</p>';

                if (data.show_score) {
                    html += '<div class="result-score-box">';
                    html += '<span class="result-score-value">' + data.total_score + '</span>';
                    if (data.max_possible_score > 0) {
                        html += '<span class="result-score-label">dari ' + data.max_possible_score + ' poin</span>';
                    }
                    html += '</div>';
                }

                if (data.category) {
                    html += '<div class="result-category">';
                    html += '<span class="result-category-badge" style="background:' + (data.category.color || '#764ba2') + ';">' + data.category.label + '</span>';
                    if (data.category.description) {
                        html += '<p class="result-category-desc">' + data.category.description + '</p>';
                    }
                    html += '</div>';
                }

                html += '<div class="result-actions">';
                html += '<button onclick="window.print()" class="mentalia-btn mentalia-btn-print">';
                html += '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>';
                html += ' Cetak Hasil</button>';
                html += '<button onclick="location.reload()" class="mentalia-btn mentalia-btn-retake">';
                html += '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>';
                html += ' Ulangi Tes</button>';
                html += '</div>';

                $result.html(html).fadeIn(400);
                $('html, body').animate({ scrollTop: $wrapper.offset().top - 20 }, 300);
            });
        }

        // Initial state
        updateNavButtons();
    });

})(jQuery);
