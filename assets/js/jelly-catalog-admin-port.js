(function ($) {
    $(function () {
        var settings = window.jcPortImport || {};
        var $form = $('#jc-import-form');
        var $progress = $('#jc-import-progress');
        var $bar = $('#jc-import-progress-bar');
        var $percent = $('#jc-import-progress-percent');
        var $message = $('#jc-import-progress-message');
        var $processed = $('#jc-import-progress-processed');
        var $total = $('#jc-import-progress-total');
        var $imported = $('#jc-import-progress-imported');
        var $errors = $('#jc-import-progress-errors');
        var $retry = $('#jc-import-retry');
        var $submit = $form.find('[type="submit"], #import');
        var jobId = settings.resumeJobId || '';
        var isRunning = false;
        var maxRetries = parseInt(settings.maxRetries, 10);
        var retryDelay = parseInt(settings.retryDelay, 10);
        var maxLockWaits = parseInt(settings.maxLockWaits, 10);
        var lockWaits = 0;

        maxRetries = isNaN(maxRetries) ? 3 : Math.max(0, maxRetries);
        retryDelay = isNaN(retryDelay) ? 1500 : Math.max(250, retryDelay);
        maxLockWaits = isNaN(maxLockWaits) ? 60 : Math.max(1, maxLockWaits);

        function setFormDisabled(disabled) {
            $form.find('input, button, select, textarea').prop('disabled', disabled);
        }

        function showProgress(message) {
            $progress.removeClass('hidden').show();
            $message.text(message || '');
        }

        function updateResumeUrl(nextJobId) {
            if (!window.history || !window.history.replaceState || !window.URL) {
                return;
            }

            var url = new URL(window.location.href);
            if (nextJobId) {
                url.searchParams.set('jc_import_job', nextJobId);
            } else {
                url.searchParams.delete('jc_import_job');
            }

            window.history.replaceState({}, '', url.toString());
        }

        function setProgress(data) {
            var total = parseInt(data.total, 10) || 0;
            var processed = parseInt(data.processed, 10) || 0;
            var imported = parseInt(data.imported, 10) || 0;
            var errors = parseInt(data.errors, 10) || 0;
            var percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;

            $bar.css('width', percent + '%');
            $bar.attr('aria-valuenow', percent);
            $percent.text(percent + '%');
            $processed.text(processed);
            $total.text(total);
            $imported.text(imported);
            $errors.text(errors);

            if (data.message) {
                $message.text(data.message);
            }
        }

        function failImport(message, canResume) {
            canResume = canResume === undefined ? !!jobId : !!(jobId && canResume);

            isRunning = false;
            setFormDisabled(false);
            $submit.prop('disabled', false);
            $progress.addClass('jc-port-progress--error');
            $retry.toggleClass('hidden', !canResume).toggle(canResume);
            $message.text((message || settings.messages.failed) + (canResume ? ' ' + settings.messages.resumeReady : ''));
        }

        function getResponseMessage(response, fallback) {
            return response && response.data && response.data.message ? response.data.message : fallback;
        }

        function isRetryableResponse(response) {
            return !response || !!(response.data && response.data.retryable === true);
        }

        function isRetryableAjaxFailure(xhr, response) {
            if (response) {
                return isRetryableResponse(response);
            }

            if (!xhr) {
                return true;
            }

            return xhr.status === 0 || xhr.status === 408 || xhr.status === 429 || xhr.status >= 500;
        }

        function scheduleRetry(callback, retryCount, message) {
            if (retryCount >= maxRetries) {
                return false;
            }

            var nextRetry = retryCount + 1;
            var retryMessage = (message ? message + ' ' : '') + settings.messages.retrying + ' (' + nextRetry + '/' + maxRetries + ')';

            $retry.addClass('hidden').hide();
            $progress.removeClass('jc-port-progress--error');
            $message.text(retryMessage);

            window.setTimeout(function () {
                if (isRunning) {
                    callback(nextRetry);
                }
            }, retryDelay * nextRetry);

            return true;
        }

        function processNextBatch(retryCount) {
            retryCount = retryCount || 0;

            if (!jobId || !isRunning) {
                return;
            }

            $.post(settings.ajaxUrl, {
                action: 'jc_process_import_products',
                nonce: settings.nonce,
                job_id: jobId
            }).done(function (response) {
                if (!response || !response.success) {
                    var retryable = isRetryableResponse(response);
                    var message = getResponseMessage(response, settings.messages.failed);

                    if (retryable && scheduleRetry(processNextBatch, retryCount, message)) {
                        return;
                    }

                    failImport(message, retryable);
                    return;
                }

                setProgress(response.data);

                if (response.data.status === 'complete') {
                    isRunning = false;
                    lockWaits = 0;
                    setFormDisabled(false);
                    $submit.prop('disabled', false);
                    $retry.addClass('hidden').hide();
                    updateResumeUrl('');
                    $message.text(settings.messages.complete);
                    return;
                }

                if (response.data.status === 'error') {
                    failImport(response.data.message || settings.messages.failed, true);
                    return;
                }

                if (response.data.status === 'waiting') {
                    lockWaits++;

                    if (lockWaits > maxLockWaits) {
                        failImport(settings.messages.lockTimeout || settings.messages.failed, true);
                        return;
                    }
                } else {
                    lockWaits = 0;
                }

                var nextDelay = parseInt(response.data.next_delay, 10) || 250;
                window.setTimeout(function () {
                    processNextBatch(0);
                }, nextDelay);
            }).fail(function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                var retryable = isRetryableAjaxFailure(xhr, response);
                var message = getResponseMessage(response, settings.messages.networkError);

                if (retryable && scheduleRetry(processNextBatch, retryCount, message)) {
                    return;
                }

                failImport(message, retryable);
            });
        }

        function startImport(formData, retryCount) {
            retryCount = retryCount || 0;

            $.ajax({
                url: settings.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (!response || !response.success) {
                    var retryable = isRetryableResponse(response);
                    var message = getResponseMessage(response, settings.messages.failed);

                    if (retryable && scheduleRetry(function (nextRetry) {
                        startImport(formData, nextRetry);
                    }, retryCount, message)) {
                        return;
                    }

                    failImport(message, retryable);
                    return;
                }

                jobId = response.data.job_id;
                updateResumeUrl(jobId);
                setProgress(response.data);
                $message.text(settings.messages.processing);
                processNextBatch(0);
            }).fail(function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                var retryable = isRetryableAjaxFailure(xhr, response);
                var message = getResponseMessage(response, settings.messages.networkError);

                if (retryable && scheduleRetry(function (nextRetry) {
                    startImport(formData, nextRetry);
                }, retryCount, message || settings.messages.networkError)) {
                    return;
                }

                failImport(message || settings.messages.networkError, retryable);
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            if (isRunning) {
                return;
            }

            var formData = new FormData(this);
            formData.set('action', 'jc_start_import_products');
            formData.set('nonce', settings.nonce);

            isRunning = true;
            lockWaits = 0;
            setFormDisabled(true);
            $submit.prop('disabled', true);
            $retry.addClass('hidden').hide();
            $progress.removeClass('jc-port-progress--error');
            showProgress(settings.messages.uploading);
            setProgress({ total: 0, processed: 0, imported: 0, errors: 0 });

            startImport(formData, 0);
        });

        $retry.on('click', function () {
            if (!jobId || isRunning) {
                return;
            }

            isRunning = true;
            lockWaits = 0;
            setFormDisabled(true);
            $submit.prop('disabled', true);
            $retry.addClass('hidden').hide();
            $progress.removeClass('jc-port-progress--error');
            showProgress(settings.messages.processing);
            processNextBatch(0);
        });

        if (jobId) {
            isRunning = true;
            lockWaits = 0;
            setFormDisabled(true);
            $submit.prop('disabled', true);
            $retry.addClass('hidden').hide();
            updateResumeUrl(jobId);
            showProgress(settings.messages.processing);
            processNextBatch(0);
        }
    });
})(jQuery);
