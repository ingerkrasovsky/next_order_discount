(function ($) {
    'use strict';

    var PRODUCT_SLUG = '1-1-hummingbird-printed-t-shirt.html';
    var AUTOSTART_KEY = 'set_demo_f1_autostart';

    function onProductPage() {
        return window.location.pathname.indexOf(PRODUCT_SLUG) !== -1;
    }

    function buildTour() {
        var accessories = document.querySelector('.product-accessories');
        var intro = introJs();
        var steps = [];

        if (accessories) {
            steps.push({
                element: accessories,
                intro: "<p class='intro-main-text'><strong>Product accessories</strong> block is here. Use it to recommend related products and increase average order value.</p>",
                position: 'top'
            });
        } else {
            steps.push({
                intro: "<p class='intro-main-text'>Cannot find <strong>.product-accessories</strong> on this page.</p>"
            });
        }

        intro.setOptions({
            steps: steps,
            showBullets: false,
            showStepNumbers: false,
            exitOnOverlayClick: true,
            skipLabel: 'Close',
            doneLabel: 'Done',
            scrollToElement: true
        });

        return {
            intro: intro,
            accessories: accessories
        };
    }

    function startTour() {
        if (typeof introJs !== 'function') {
            return;
        }

        if (typeof continueTutorial === 'function') {
            continueTutorial();
        }
        if (typeof hideTutorialPanel === 'function') {
            hideTutorialPanel();
        }

        var data = buildTour();

        data.intro.onexit(function () {
            if (typeof stopIntro === 'function') {
                stopIntro('intro', data.intro);
            }
            jQuery('#tutorialF1').removeClass('tutorial_link_highlighted');
        });

        data.intro.oncomplete(function () {
            if (typeof stopIntro === 'function') {
                stopIntro('intro', data.intro);
            }
            jQuery('#tutorialF1').removeClass('tutorial_link_highlighted');
        });

        data.intro.start();

        if (data.accessories && typeof data.accessories.scrollIntoView === 'function') {
            setTimeout(function () {
                data.accessories.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 200);
        }
    }

    $(function () {
        var link = $('#tutorialF1');

        if (link.length) {
            link.off('click');
            link.on('click.setDemoF1', function () {
                var href = $(this).attr('href') || '';

                jQuery(this).addClass('tutorial_link_highlighted');

                if (!onProductPage()) {
                    try {
                        window.sessionStorage.setItem(AUTOSTART_KEY, '1');
                    } catch (error) {
                        // Continue redirect even if storage is unavailable.
                    }

                    if (href) {
                        window.location.href = href;
                    }

                    return false;
                }

                startTour();
                return false;
            });
        }

        if (onProductPage()) {
            var autostart = false;

            try {
                autostart = window.sessionStorage.getItem(AUTOSTART_KEY) === '1';
                if (autostart) {
                    window.sessionStorage.removeItem(AUTOSTART_KEY);
                }
            } catch (error) {
                autostart = false;
            }

            if (autostart) {
                setTimeout(startTour, 250);
            }
        }
    });
})(jQuery);
