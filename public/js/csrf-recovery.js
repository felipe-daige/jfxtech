// Global recovery for expired Laravel CSRF tokens.
(function () {
    if (window.JfxCsrfRecovery) {
        return;
    }

    var keys = {
        current: 'jfxtech.currentUrl',
        previous: 'jfxtech.previousUrl',
        recovering: 'jfxtech.csrfRecovering',
        returnTo: 'jfxtech.csrfReturnTo'
    };

    function safeUrl(value) {
        try {
            var url = new URL(value || '', window.location.origin);
            return url.origin === window.location.origin ? url.href : null;
        } catch (error) {
            return null;
        }
    }

    function rememberPage() {
        try {
            var current = window.location.href;
            var lastCurrent = sessionStorage.getItem(keys.current);
            var referrer = safeUrl(document.referrer);

            if (lastCurrent && lastCurrent !== current) {
                sessionStorage.setItem(keys.previous, lastCurrent);
            } else if (referrer && referrer !== current && !sessionStorage.getItem(keys.previous)) {
                sessionStorage.setItem(keys.previous, referrer);
            }

            sessionStorage.setItem(keys.current, current);
            sessionStorage.removeItem(keys.recovering);
        } catch (error) {
            // Storage can be unavailable in restrictive browser modes.
        }
    }

    function fallbackReturnUrl(redirectUrl) {
        var safeRedirect = safeUrl(redirectUrl);
        if (safeRedirect) {
            return safeRedirect;
        }

        try {
            return sessionStorage.getItem(keys.returnTo)
                || sessionStorage.getItem(keys.previous)
                || window.location.href;
        } catch (error) {
            return window.location.href;
        }
    }

    function recover(redirectUrl) {
        var target = fallbackReturnUrl(redirectUrl);

        try {
            if (sessionStorage.getItem(keys.recovering) === '1') {
                return;
            }

            sessionStorage.setItem(keys.recovering, '1');
            sessionStorage.setItem(keys.returnTo, target || window.location.href);
        } catch (error) {
            // Continue with navigation even if storage is blocked.
        }

        window.setTimeout(function () {
            if (!target || target === window.location.href) {
                window.location.reload();
                return;
            }

            window.location.href = target;
        }, 50);
    }

    function watchFetch() {
        if (typeof window.fetch !== 'function') {
            return;
        }

        var originalFetch = window.fetch;

        window.fetch = function () {
            return originalFetch.apply(this, arguments).then(function (response) {
                if (response && response.status === 419) {
                    recover(response.headers.get('X-CSRF-Redirect'));
                }

                return response;
            });
        };
    }

    function watchJquery() {
        if (!window.jQuery) {
            return;
        }

        window.jQuery(document).ajaxError(function (_event, xhr) {
            if (xhr && xhr.status === 419) {
                recover(xhr.getResponseHeader('X-CSRF-Redirect'));
            }
        });
    }

    document.addEventListener('submit', function () {
        try {
            sessionStorage.setItem(keys.returnTo, window.location.href);
        } catch (error) {
            // Ignore storage failures.
        }
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        rememberPage();
        watchJquery();
    });

    watchFetch();

    window.JfxCsrfRecovery = {
        recover: recover,
        returnUrl: fallbackReturnUrl,
        rememberPage: rememberPage
    };
})();
