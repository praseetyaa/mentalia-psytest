(function ($) {
    'use strict';

    // ===== Toast Notification =====
    function showToast(message, type) {
        type = type || 'success';
        var $toast = $('<div class="mentalia-toast ' + type + '">' + message + '</div>');
        $('body').append($toast);
        setTimeout(function () {
            $toast.fadeOut(300, function () { $(this).remove(); });
        }, 3000);
    }

    // ===== Save Test Form =====
    $(document).on('submit', '#mentalia-test-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('.mentalia-btn-save');
        var origText = $btn.html();

        $btn.html('<span class="spinner is-active" style="margin:0;float:none;"></span> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_save_test',
                nonce: mentaliaAdmin.nonce,
                test_id: $form.find('input[name="test_id"]').val(),
                title: $form.find('#test-title').val(),
                description: $form.find('#test-description').val(),
                instructions: $form.find('#test-instructions').val(),
                duration_minutes: $form.find('#test-duration').val(),
                status: $form.find('#test-status').val(),
                show_score: $form.find('input[name="show_score"]').is(':checked') ? 1 : 0,
                randomize_questions: $form.find('input[name="randomize_questions"]').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.data.message);
                    if ($form.find('input[name="test_id"]').val() === '0') {
                        // Redirect to edit page for new test
                        window.location.href = mentaliaAdmin.ajax_url.replace('admin-ajax.php',
                            'admin.php?page=mentalia-psytest-edit&test_id=' + res.data.test_id);
                    }
                } else {
                    showToast('Gagal menyimpan: ' + res.data, 'error');
                }
            },
            error: function () {
                showToast('Terjadi kesalahan!', 'error');
            },
            complete: function () {
                $btn.html(origText).prop('disabled', false);
            }
        });
    });

    // ===== Delete Test =====
    $(document).on('click', '.mentalia-btn-delete[data-test-id]', function () {
        if (!confirm('Apakah Anda yakin ingin menghapus tes ini? Semua soal dan hasil juga akan dihapus.')) return;

        var testId = $(this).data('test-id');
        var $row = $(this).closest('tr');

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_delete_test',
                nonce: mentaliaAdmin.nonce,
                test_id: testId
            },
            success: function (res) {
                if (res.success) {
                    $row.fadeOut(300, function () { $(this).remove(); });
                    showToast(res.data.message);
                } else {
                    showToast('Gagal menghapus!', 'error');
                }
            }
        });
    });

    // ===== Question Management =====
    var questionCounter = 0;
    var typeLabels = {
        'multiple_choice': 'Pilihan Ganda',
        'likert': 'Skala Likert',
        'true_false': 'Benar/Salah',
        'essay': 'Essai'
    };

    function getQuestionHTML(data) {
        data = data || {};
        questionCounter++;
        var tmpl = $('#tmpl-question-item').html();
        tmpl = tmpl.replace(/{{id}}/g, data.id || 0);
        tmpl = tmpl.replace(/{{number}}/g, questionCounter);
        tmpl = tmpl.replace(/{{type_label}}/g, typeLabels[data.question_type || 'multiple_choice']);
        tmpl = tmpl.replace(/{{question_text}}/g, data.question_text || '');
        tmpl = tmpl.replace(/{{likert_min}}/g, data.likert_min || 1);
        tmpl = tmpl.replace(/{{likert_max}}/g, data.likert_max || 5);
        tmpl = tmpl.replace(/{{likert_min_label}}/g, data.likert_min_label || 'Sangat Tidak Setuju');
        tmpl = tmpl.replace(/{{likert_max_label}}/g, data.likert_max_label || 'Sangat Setuju');
        tmpl = tmpl.replace(/{{required_checked}}/g, (data.is_required !== '0' && data.is_required !== 0) ? 'checked' : '');

        var $el = $(tmpl);
        $el.find('.q-type').val(data.question_type || 'multiple_choice');

        // Show/hide fields based on type
        updateQuestionTypeFields($el, data.question_type || 'multiple_choice');

        // Add options if available
        if (data.options && data.options.length > 0) {
            data.options.forEach(function (opt) {
                $el.find('.options-list').append(getOptionHTML(opt));
            });
        }

        return $el;
    }

    function getOptionHTML(data) {
        data = data || {};
        var tmpl = $('#tmpl-option-item').html();
        tmpl = tmpl.replace(/{{option_text}}/g, data.option_text || '');
        tmpl = tmpl.replace(/{{score}}/g, data.score || 0);
        return $(tmpl);
    }

    function updateQuestionTypeFields($item, type) {
        var $options = $item.find('.options-container');
        var $likert = $item.find('.likert-settings');
        $options.hide();
        $likert.hide();

        if (type === 'multiple_choice' || type === 'true_false') {
            $options.show();
            if (type === 'true_false' && $item.find('.options-list .mentalia-option-item').length === 0) {
                $item.find('.options-list').append(getOptionHTML({ option_text: 'Benar', score: 1 }));
                $item.find('.options-list').append(getOptionHTML({ option_text: 'Salah', score: 0 }));
            }
        } else if (type === 'likert') {
            $likert.show();
        }

        // Update badge
        $item.find('.question-type-badge').text(typeLabels[type] || type);
    }

    function renumberQuestions() {
        questionCounter = 0;
        $('#questions-container .mentalia-question-item').each(function () {
            questionCounter++;
            $(this).find('.question-number').text('Soal #' + questionCounter);
        });
    }

    // Load questions on page
    var $qContainer = $('#questions-container');
    if ($qContainer.length) {
        var testId = $qContainer.data('test-id');
        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_load_questions',
                nonce: mentaliaAdmin.nonce,
                test_id: testId
            },
            success: function (res) {
                $qContainer.empty();
                if (res.success && res.data.questions.length > 0) {
                    questionCounter = 0;
                    res.data.questions.forEach(function (q) {
                        $qContainer.append(getQuestionHTML(q));
                    });
                } else {
                    $qContainer.html('<p class="mentalia-help" style="text-align:center;padding:20px;">Belum ada soal. Klik "Tambah Soal" untuk mulai.</p>');
                }

                // Init sortable
                $qContainer.sortable({
                    handle: '.question-drag-handle',
                    placeholder: 'mentalia-sortable-placeholder',
                    update: renumberQuestions
                });
            }
        });
    }

    // Add question
    $(document).on('click', '#btn-add-question', function () {
        $qContainer.find('.mentalia-help').remove();
        var $newQ = getQuestionHTML({ question_type: 'multiple_choice' });
        // Add default options for new multiple choice
        $newQ.find('.options-list').append(getOptionHTML({ option_text: '', score: 0 }));
        $newQ.find('.options-list').append(getOptionHTML({ option_text: '', score: 0 }));
        $qContainer.append($newQ);

        // Scroll to new question
        $('html, body').animate({ scrollTop: $newQ.offset().top - 50 }, 300);
    });

    // Change question type
    $(document).on('change', '.q-type', function () {
        var $item = $(this).closest('.mentalia-question-item');
        updateQuestionTypeFields($item, $(this).val());
    });

    // Add option
    $(document).on('click', '.btn-add-option', function () {
        $(this).closest('.options-container').find('.options-list').append(getOptionHTML());
    });

    // Remove option
    $(document).on('click', '.btn-remove-option', function () {
        $(this).closest('.mentalia-option-item').remove();
    });

    // Remove question
    $(document).on('click', '.btn-remove-question', function (e) {
        e.stopPropagation();
        if (!confirm('Hapus soal ini?')) return;
        $(this).closest('.mentalia-question-item').fadeOut(300, function () {
            $(this).remove();
            renumberQuestions();
        });
    });

    // Toggle question body
    $(document).on('click', '.btn-toggle-question', function (e) {
        e.stopPropagation();
        $(this).closest('.mentalia-question-item').find('.question-body').toggleClass('collapsed');
        var $icon = $(this).find('.dashicons');
        $icon.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    // Save all questions
    $(document).on('click', '#btn-save-questions', function () {
        var $btn = $(this);
        var origText = $btn.html();
        var testId = $qContainer.data('test-id');

        var questions = [];
        $qContainer.find('.mentalia-question-item').each(function (index) {
            var $q = $(this);
            var qData = {
                id: $q.data('question-id') || 0,
                question_text: $q.find('.q-text').val(),
                question_type: $q.find('.q-type').val(),
                question_order: index,
                is_required: $q.find('.q-required').is(':checked') ? 1 : 0,
                likert_min: $q.find('.q-likert-min').val() || 1,
                likert_max: $q.find('.q-likert-max').val() || 5,
                likert_min_label: $q.find('.q-likert-min-label').val() || 'Sangat Tidak Setuju',
                likert_max_label: $q.find('.q-likert-max-label').val() || 'Sangat Setuju',
                options: []
            };

            $q.find('.mentalia-option-item').each(function () {
                qData.options.push({
                    option_text: $(this).find('.opt-text').val(),
                    score: $(this).find('.opt-score').val() || 0
                });
            });

            questions.push(qData);
        });

        $btn.html('<span class="spinner is-active" style="margin:0;float:none;"></span> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_save_questions',
                nonce: mentaliaAdmin.nonce,
                test_id: testId,
                questions: JSON.stringify(questions)
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.data.message);
                    // Reload questions to get proper IDs
                    location.reload();
                } else {
                    showToast('Gagal menyimpan: ' + res.data, 'error');
                }
            },
            error: function () {
                showToast('Terjadi kesalahan!', 'error');
            },
            complete: function () {
                $btn.html(origText).prop('disabled', false);
            }
        });
    });

    // ===== Category Management =====
    var categoryCounter = 0;
    var defaultColors = ['#4CAF50', '#FF9800', '#f44336', '#2196F3', '#9C27B0', '#FF5722', '#00BCD4', '#795548'];

    function getCategoryHTML(data) {
        data = data || {};
        categoryCounter++;
        var tmpl = $('#tmpl-category-item').html();
        var color = data.color || defaultColors[(categoryCounter - 1) % defaultColors.length];
        tmpl = tmpl.replace(/{{id}}/g, data.id || 0);
        tmpl = tmpl.replace(/{{label}}/g, data.label || '');
        tmpl = tmpl.replace(/{{min_score}}/g, data.min_score || 0);
        tmpl = tmpl.replace(/{{max_score}}/g, data.max_score || 0);
        tmpl = tmpl.replace(/{{color}}/g, color);
        tmpl = tmpl.replace(/{{description}}/g, data.description || '');
        return $(tmpl);
    }

    // Load categories
    var $catContainer = $('#categories-container');
    if ($catContainer.length) {
        var catTestId = $catContainer.data('test-id');
        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_load_categories',
                nonce: mentaliaAdmin.nonce,
                test_id: catTestId
            },
            success: function (res) {
                $catContainer.empty();
                if (res.success && res.data.categories.length > 0) {
                    categoryCounter = 0;
                    res.data.categories.forEach(function (cat) {
                        $catContainer.append(getCategoryHTML(cat));
                    });
                } else {
                    $catContainer.html('<p class="mentalia-help" style="text-align:center;padding:20px;">Belum ada kategori. Klik "Tambah Kategori" untuk mulai.</p>');
                }
            }
        });
    }

    // Add category
    $(document).on('click', '#btn-add-category', function () {
        $catContainer.find('.mentalia-help').remove();
        $catContainer.append(getCategoryHTML());
    });

    // Remove category
    $(document).on('click', '.btn-remove-category', function () {
        if (!confirm('Hapus kategori ini?')) return;
        $(this).closest('.mentalia-category-item').fadeOut(300, function () { $(this).remove(); });
    });

    // Update label preview
    $(document).on('input', '.cat-label', function () {
        $(this).closest('.mentalia-category-item').find('.category-label-preview').text($(this).val());
    });

    // Update color dot
    $(document).on('input', '.cat-color', function () {
        $(this).closest('.mentalia-category-item').find('.category-color-dot').css('background-color', $(this).val());
    });

    // Save categories
    $(document).on('click', '#btn-save-categories', function () {
        var $btn = $(this);
        var origText = $btn.html();
        var testId = $catContainer.data('test-id');

        var categories = [];
        $catContainer.find('.mentalia-category-item').each(function () {
            var $cat = $(this);
            categories.push({
                label: $cat.find('.cat-label').val(),
                min_score: $cat.find('.cat-min-score').val() || 0,
                max_score: $cat.find('.cat-max-score').val() || 0,
                color: $cat.find('.cat-color').val(),
                description: $cat.find('.cat-description').val()
            });
        });

        $btn.html('<span class="spinner is-active" style="margin:0;float:none;"></span> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_save_categories',
                nonce: mentaliaAdmin.nonce,
                test_id: testId,
                categories: JSON.stringify(categories)
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.data.message);
                } else {
                    showToast('Gagal menyimpan!', 'error');
                }
            },
            error: function () {
                showToast('Terjadi kesalahan!', 'error');
            },
            complete: function () {
                $btn.html(origText).prop('disabled', false);
            }
        });
    });

    // ===== Delete Result =====
    $(document).on('click', '.btn-delete-result', function () {
        if (!confirm('Hapus hasil tes ini?')) return;

        var resultId = $(this).data('result-id');
        var $row = $(this).closest('tr');

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_delete_result',
                nonce: mentaliaAdmin.nonce,
                result_id: resultId
            },
            success: function (res) {
                if (res.success) {
                    $row.fadeOut(300, function () { $(this).remove(); });
                    showToast(res.data.message);
                }
            }
        });
    });

    // ===== Export CSV =====
    $(document).on('click', '#btn-export-csv', function () {
        var filterTest = $(this).data('filter-test') || 0;

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_export_results',
                nonce: mentaliaAdmin.nonce,
                test_id: filterTest
            },
            success: function (res) {
                if (res.success) {
                    var csv = res.data.csv.map(function (row) {
                        return row.map(function (cell) {
                            return '"' + String(cell || '').replace(/"/g, '""') + '"';
                        }).join(',');
                    }).join('\n');

                    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'mentalia-results-' + new Date().toISOString().slice(0, 10) + '.csv';
                    link.click();
                    showToast('CSV berhasil diunduh!');
                }
            }
        });
    });

    // ===== Download Import Template =====
    function downloadCsvFromData(csvData, filename) {
        var csv = csvData.map(function (row) {
            return row.map(function (cell) {
                return '"' + String(cell || '').replace(/"/g, '""') + '"';
            }).join(',');
        }).join('\n');

        var bom = '\uFEFF';
        var blob = new Blob([bom + csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
    }

    $(document).on('click', '#btn-download-template', function () {
        var $btn = $(this);
        var origText = $btn.html();
        $btn.html('<span class="spinner is-active" style="margin:0;float:none;"></span> Mengunduh...').prop('disabled', true);

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_download_import_template',
                nonce: mentaliaAdmin.nonce
            },
            success: function (res) {
                if (res.success) {
                    downloadCsvFromData(res.data.csv, res.data.filename);
                    showToast('Template berhasil diunduh!');
                } else {
                    showToast('Gagal mengunduh template!', 'error');
                }
            },
            error: function () {
                showToast('Terjadi kesalahan!', 'error');
            },
            complete: function () {
                $btn.html(origText).prop('disabled', false);
            }
        });
    });

    // ===== Import Test - File Upload =====
    var importParsedData = null;

    function parseCSV(text) {
        var lines = text.split(/\r?\n/);
        var result = [];
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i].trim();
            if (!line) continue;

            var row = [];
            var inQuotes = false;
            var field = '';
            for (var j = 0; j < line.length; j++) {
                var ch = line[j];
                if (inQuotes) {
                    if (ch === '"') {
                        if (j + 1 < line.length && line[j + 1] === '"') {
                            field += '"';
                            j++;
                        } else {
                            inQuotes = false;
                        }
                    } else {
                        field += ch;
                    }
                } else {
                    if (ch === '"') {
                        inQuotes = true;
                    } else if (ch === ',') {
                        row.push(field);
                        field = '';
                    } else {
                        field += ch;
                    }
                }
            }
            row.push(field);
            result.push(row);
        }
        return result;
    }

    function processImportFile(file) {
        if (!file || !file.name.toLowerCase().endsWith('.csv')) {
            showToast('Hanya file CSV yang diperbolehkan!', 'error');
            return;
        }

        // Show file info
        var sizeKB = (file.size / 1024).toFixed(1);
        $('#import-file-info .file-name').text(file.name);
        $('#import-file-info .file-size').text('(' + sizeKB + ' KB)');
        $('#import-file-info').show();
        $('#import-dropzone').hide();

        // Read and parse
        var reader = new FileReader();
        reader.onload = function (e) {
            var parsed = parseCSV(e.target.result);
            if (parsed.length < 2) {
                showToast('File CSV kosong atau hanya berisi header!', 'error');
                return;
            }

            var headers = parsed[0];
            var rows = parsed.slice(1);

            // Map to objects
            importParsedData = rows.map(function (row) {
                var obj = {};
                headers.forEach(function (h, idx) {
                    obj[h.trim()] = (row[idx] || '').trim();
                });
                return obj;
            }).filter(function (obj) {
                return obj.question_text && obj.question_text.length > 0;
            });

            if (importParsedData.length === 0) {
                showToast('Tidak ada data valid dalam file!', 'error');
                return;
            }

            // Count unique questions
            var uniqueQuestions = {};
            importParsedData.forEach(function (row) {
                uniqueQuestions[row.question_text] = true;
            });
            var qCount = Object.keys(uniqueQuestions).length;

            $('#preview-question-count').text(qCount + ' soal');
            $('#preview-row-count').text(importParsedData.length + ' baris');

            // Build preview table
            var $tbody = $('#import-preview-table tbody');
            $tbody.empty();

            var typeLabelsMap = {
                'multiple_choice': 'Pilihan Ganda',
                'likert': 'Skala Likert',
                'true_false': 'Benar/Salah',
                'essay': 'Essai'
            };

            importParsedData.forEach(function (row, idx) {
                var typeLabel = typeLabelsMap[row.question_type] || row.question_type;
                $tbody.append(
                    '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + $('<span>').text(row.question_text).html() + '</td>' +
                    '<td><span class="mentalia-badge mentalia-badge-category">' + typeLabel + '</span></td>' +
                    '<td>' + (row.is_required === '1' ? 'Ya' : 'Tidak') + '</td>' +
                    '<td>' + $('<span>').text(row.option_text || '-').html() + '</td>' +
                    '<td>' + (row.option_score || '-') + '</td>' +
                    '</tr>'
                );
            });

            $('#import-preview').slideDown(300);
            $('#btn-import-test').prop('disabled', false);
        };
        reader.readAsText(file);
    }

    // Dropzone click
    $(document).on('click', '#import-dropzone', function () {
        $('#import-file').trigger('click');
    });

    // File input change
    $(document).on('change', '#import-file', function () {
        if (this.files && this.files[0]) {
            processImportFile(this.files[0]);
        }
    });

    // Drag & drop
    $(document).on('dragover', '#import-dropzone', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });

    $(document).on('dragleave', '#import-dropzone', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
    });

    $(document).on('drop', '#import-dropzone', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragover');
        if (e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files[0]) {
            processImportFile(e.originalEvent.dataTransfer.files[0]);
        }
    });

    // Remove file
    $(document).on('click', '.btn-remove-file', function () {
        importParsedData = null;
        $('#import-file').val('');
        $('#import-file-info').hide();
        $('#import-dropzone').show();
        $('#import-preview').slideUp(200);
        $('#btn-import-test').prop('disabled', true);
    });

    // ===== Import Submit =====
    $(document).on('click', '#btn-import-test', function () {
        var testId = $('#import-test-id').val();
        if (!testId) {
            showToast('Pilih tes tujuan terlebih dahulu!', 'error');
            return;
        }

        if (!importParsedData || importParsedData.length === 0) {
            showToast('Upload file CSV terlebih dahulu!', 'error');
            return;
        }

        if (!confirm('Apakah Anda yakin ingin mengimport ' + importParsedData.length + ' baris data ke tes ini?')) {
            return;
        }

        var $btn = $(this);
        var origText = $btn.html();
        $btn.html('<span class="spinner is-active" style="margin:0;float:none;"></span> Mengimport...').prop('disabled', true);

        $.ajax({
            url: mentaliaAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'mentalia_import_test',
                nonce: mentaliaAdmin.nonce,
                test_id: testId,
                csv_data: JSON.stringify(importParsedData)
            },
            success: function (res) {
                if (res.success) {
                    showToast(res.data.message);

                    // Show result card
                    var html = '<div class="mentalia-import-result-grid">' +
                        '<div class="mentalia-stat-card">' +
                        '<div class="stat-icon" style="background: linear-gradient(135deg, #4CAF50, #2e7d32);">' +
                        '<span class="dashicons dashicons-yes"></span></div>' +
                        '<div class="stat-info"><h3>' + res.data.imported_questions + '</h3><p>Soal Diimport</p></div></div>' +
                        '<div class="mentalia-stat-card">' +
                        '<div class="stat-icon" style="background: linear-gradient(135deg, #2196F3, #1565c0);">' +
                        '<span class="dashicons dashicons-list-view"></span></div>' +
                        '<div class="stat-info"><h3>' + res.data.imported_options + '</h3><p>Opsi Jawaban</p></div></div>' +
                        '</div>' +
                        '<p style="margin-top:16px;">' +
                        '<a href="' + mentaliaAdmin.ajax_url.replace('admin-ajax.php', 'admin.php?page=mentalia-psytest-questions&test_id=' + testId) + '" class="button button-primary mentalia-btn-primary">' +
                        '<span class="dashicons dashicons-list-view"></span> Lihat Soal</a>' +
                        '</p>';

                    $('#import-result-content').html(html);
                    $('#import-result').slideDown(300);

                    // Reset form
                    importParsedData = null;
                    $('#import-file').val('');
                    $('#import-file-info').hide();
                    $('#import-dropzone').show();
                    $('#import-preview').slideUp(200);
                    $btn.prop('disabled', true);
                } else {
                    showToast('Gagal import: ' + res.data, 'error');
                }
            },
            error: function () {
                showToast('Terjadi kesalahan saat import!', 'error');
            },
            complete: function () {
                $btn.html(origText).prop('disabled', false);
            }
        });
    });

})(jQuery);
