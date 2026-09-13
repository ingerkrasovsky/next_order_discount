(function ($) {
    'use strict';

    var PENDING_TUTORIAL_KEY = 'set_demo_pending_tutorial';
    var PENDING_TUTORIAL_PARAM = 'set_demo_tutorial';

    function getLoaderByTutorialId(tutorialId) {
        if (!tutorialId || tutorialId.indexOf('tutorial') !== 0) {
            return null;
        }

        var suffix = tutorialId.replace('tutorial', '');
        var loaderName = 'loadTutorial' + suffix;
        var loader = window[loaderName];

        return (typeof loader === 'function') ? loader : null;
    }

    function startTutorialFromLink(linkElement) {
        var $link = $(linkElement);
        var tutorialId = $link.attr('id') || '';
        var href = $link.attr('href') || '';
        var loader = getLoaderByTutorialId(tutorialId);

        if (!loader) {
            return false;
        }

        if (!href || typeof isCurrentUrlMatchHref !== 'function' || !isCurrentUrlMatchHref(href)) {
            return false;
        }

        try {
            highlightElem(linkElement);
        } catch (error) {
            return false;
        }

        try {
            continueTutorial();
        } catch (error) {
            // Continue tutorial start even if cookie state could not be updated.
        }

        try {
            loader();
        } catch (error) {
            return false;
        }

        try {
            window.sessionStorage.removeItem(PENDING_TUTORIAL_KEY);
        } catch (error) {
            // Ignore storage errors.
        }

        try {
            var currentUrl = new URL(window.location.href);
            if (currentUrl.searchParams.has(PENDING_TUTORIAL_PARAM)) {
                currentUrl.searchParams.delete(PENDING_TUTORIAL_PARAM);
                window.history.replaceState({}, document.title, currentUrl.toString());
            }
        } catch (error) {
            // Ignore URL parsing errors.
        }

        return true;
    }

    function savePendingTutorial(tutorialId) {
        try {
            window.sessionStorage.setItem(PENDING_TUTORIAL_KEY, tutorialId);
        } catch (error) {
            // Ignore storage errors.
        }
    }

    function getPendingTutorialFromUrl() {
        try {
            var currentUrl = new URL(window.location.href);
            return currentUrl.searchParams.get(PENDING_TUTORIAL_PARAM) || '';
        } catch (error) {
            return '';
        }
    }

    function buildHrefWithPendingTutorial(href, tutorialId) {
        try {
            var targetUrl = new URL(href, window.location.origin);
            targetUrl.searchParams.set(PENDING_TUTORIAL_PARAM, tutorialId);
            return targetUrl.toString();
        } catch (error) {
            return href;
        }
    }

    function tryAutostartPendingTutorial() {
        var pendingId = getPendingTutorialFromUrl();

        if (!pendingId) {
            try {
                pendingId = window.sessionStorage.getItem(PENDING_TUTORIAL_KEY) || '';
            } catch (error) {
                pendingId = '';
            }
        }

        if (!pendingId) {
            return;
        }

        var attempts = 0;
        var maxAttempts = 20;

        function attemptStart() {
            attempts += 1;

            var pendingLink = $('#' + pendingId);
            if (pendingLink.length && startTutorialFromLink(pendingLink.get(0))) {
                return;
            }

            if (attempts < maxAttempts) {
                setTimeout(attemptStart, 150);
            }
        }

        setTimeout(attemptStart, 150);
    }

    $(function () {
        if (typeof initScenario === 'function') {
            initScenario();
        }

        if (typeof refreshTutorialLinksHighlight === 'function') {
            refreshTutorialLinksHighlight();
        }

        // Fallback: keep menu entry visible even if cookie state was not initialized.
        $('.set-demo-controls__button--tutorials').show();

        $('.set-tutorials-group__link[id^="tutorial"]').on('click', function (event) {
            var tutorialId = $(this).attr('id') || '';
            var href = $(this).attr('href') || '';

            event.preventDefault();
            savePendingTutorial(tutorialId);

            if (startTutorialFromLink(event.currentTarget)) {
                return false;
            }

            if (href && (typeof isCurrentUrlMatchHref !== 'function' || !isCurrentUrlMatchHref(href))) {
                // Переход на другую вкладку — её тур начинаем сначала (не с сохранённого шага).
                if (window.SLMDemo && typeof SLMDemo.tourKeyForHref === 'function' && typeof SLMDemo.clearSavedStep === 'function') {
                    var targetKey = SLMDemo.tourKeyForHref(href);
                    if (targetKey) { SLMDemo.clearSavedStep(targetKey); }
                }
                window.location.href = buildHrefWithPendingTutorial(href, tutorialId);
            }

            return false;
        });

        tryAutostartPendingTutorial();
    });
})(jQuery);
