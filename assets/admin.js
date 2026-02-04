(() => {
    const settings = window.MPImporterAdmin || {};
    const button = document.querySelector('#mp-importer-run');
    const progress = document.querySelector('#mp-importer-progress');
    const bar = progress ? progress.querySelector('.mp-progress__bar-fill') : null;
    const barWrapper = progress ? progress.querySelector('.mp-progress__bar') : null;
    const label = document.querySelector('#mp-importer-progress-label');
    const status = document.querySelector('#mp-importer-status');

    if (!button) {
        return;
    }

    const formatStats = (stats) => {
        if (!stats) {
            return '';
        }

        return 'Created: ' + (stats.created || 0)
            + ', Updated: ' + (stats.updated || 0)
            + ', Skipped: ' + (stats.skipped || 0)
            + ', Errors: ' + (stats.errors || 0) + '.';
    };

    const setStatus = (text) => {
        if (status) {
            status.textContent = text || '';
        }
    };

    const updateProgress = (processed, total) => {
        const pct = total > 0 ? Math.round((processed / total) * 100) : 0;
        const value = Math.min(Math.max(pct, 0), 100);

        if (bar) {
            bar.style.width = value + '%';
        }

        if (barWrapper) {
            barWrapper.setAttribute('aria-valuenow', String(value));
        }

        if (label) {
            label.textContent = value + '% (' + processed + '/' + total + ')';
        }
    };

    const handleError = (message) => {
        button.disabled = false;
        button.textContent = 'Import / Refresh MPs';
        setStatus(message || (settings.strings && settings.strings.error) || 'Import failed.');
    };

    const post = (payload) => {
        const body = new URLSearchParams(payload);

        return fetch(settings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: body.toString(),
        }).then((response) => response.json());
    };

    const runStep = (token, total) => {
        post({
            action: 'mp_importer_step',
            nonce: settings.nonce,
            token: token,
            batchSize: settings.batchSize || 15,
        })
            .then((response) => {
                if (!response || !response.success) {
                    const message = response && response.data && response.data.message ? response.data.message : null;
                    handleError(message);
                    return;
                }

                const data = response.data || {};
                updateProgress(data.processed || 0, total);
                setStatus(formatStats(data.stats));

                if (data.done) {
                    button.disabled = false;
                    button.textContent = 'Import / Refresh MPs';
                    if (settings.strings && settings.strings.done) {
                        setStatus(settings.strings.done + ' ' + formatStats(data.stats));
                    }
                    return;
                }

                setTimeout(() => {
                    runStep(token, total);
                }, 200);
            })
            .catch(() => {
                handleError();
            });
    };

    button.addEventListener('click', () => {
        button.disabled = true;
        button.textContent = 'Importing...';
        if (progress) {
            progress.hidden = false;
        }
        updateProgress(0, 0);
        setStatus(settings.strings && settings.strings.starting ? settings.strings.starting : 'Starting import...');

        post({
            action: 'mp_importer_start',
            nonce: settings.nonce,
        })
            .then((response) => {
                if (!response || !response.success) {
                    const message = response && response.data && response.data.message ? response.data.message : null;
                    handleError(message);
                    return;
                }

                const data = response.data || {};
                const total = data.total || 0;
                updateProgress(0, total);
                setStatus(settings.strings && settings.strings.running ? settings.strings.running : 'Import in progress...');

                runStep(data.token, total);
            })
            .catch(() => {
                handleError();
            });
    });
})();
