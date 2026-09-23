$(document).ready(function() {
    updateScrollbarOffsetVar();
    $(window).on('resize orientationchange', updateScrollbarOffsetVar);

        refreshTutorialLinksHighlight();

        $('.js-set-tutorials-open-tutorials-panel').click(function() {
            $('body').addClass('is-set-tutorial-open');
            introData = getIntroInstance();
            introData['tutorial_panel'] = 'opened';
            setCookie('intro', JSON.stringify(introData), 1);
            refreshTutorialLinksHighlight();
        });

        $('.js-set-tutorials-close-tutorials-panel').click(function() {
            $('body').removeClass('is-set-tutorial-open');
            introData = getIntroInstance();
            introData['tutorial_panel'] = 'closed';
            setCookie('intro', JSON.stringify(introData), 1);
        });

        $('.js-set-tutorials-open-popup').click(function(event) {
            $.magnificPopup.open({
                items: {
                    src: '#' + $(this).attr('data-set-popup-name'),
                    type: 'inline'
                },
                mainClass: 'set-tutorials-popup set-tutorials-popup--zoom-in',
                removalDelay: 300,
                midClick: true,
                fixedContentPos: false,
                showCloseBtn: false
            });

            hideTutorialPanel();
            event.preventDefault();
        });

        $('.js-set-tutorials-close-popup').click(function(e) {
            $.magnificPopup.close();
            e.preventDefault();
        });

        // Submit contact form
        $('.set-talk-to-us-form').submit(function(e) {
            e.preventDefault();

            var t = $(this);
            var formData = t.serialize();
            var formURL = t.attr('action');
            var formType = t.attr('method');
            var submitButton = $('.set-talk-to-us-form__submit');

            $.ajax({
                url: formURL,
                type: formType,
                dataType: 'json',
                data: formData,
                beforeSend: function() {
                    submitButton.addClass('is-disabled').prop('disabled', true);
                },
                success: function(result) {
                    if (result.status == 'success') {
                        submitButton.prop('disabled', true);

                        $.magnificPopup.open({
                            items: {
                                src: '#set-talk-to-us-thank-you'
                            },
                            type: 'inline',
                            mainClass: 'mfp-zoom-in',
                            fixedContentPos: false,
                            removalDelay: 300
                        }, 0);
                    }
                    submitButton.removeClass('is-disabled').prop('disabled', false);
                },
                error: function(result) {
                    submitButton.removeClass('is-disabled').prop('disabled', false);
                }
            });
        });
    });

function updateScrollbarOffsetVar() {
    var scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    if (scrollbarWidth < 0) {
        scrollbarWidth = 0;
    }

    document.documentElement.style.setProperty('--set-scrollbar-offset', scrollbarWidth + 'px');
}

/**
 * Pause tutorial
 *
 * @param tutorialElement
 * @param introElement
 * @param introInstance
 */
function pauseTutorial(tutorialElement, introElement, introInstance) {
    stopIntro(introElement,introInstance);
    jQuery(tutorialElement).addClass("tutorial_link_highlighted");
    var tutorial_link_html = jQuery(tutorialElement).html();
    if (!strstr(tutorial_link_html, '(resume)')) { jQuery(tutorialElement).html(tutorial_link_html + ' (resume)'); }
    jQuery(tutorialElement).unbind("click");
    jQuery(tutorialElement).click(function(event) {
        introData = getIntroInstance();
        event.preventDefault();
        if (introData['tutorial'] == 'start') {
            throw new Error("There is no need to click it more than once!");
        }
        continueIntro(introElement,introInstance);
        jQuery(tutorialElement).html(tutorial_link_html);
    });
}

/**
 * Start tutorial
 *
 * @param introInstance
 */
function startTutorial(introInstance) {
    hideTutorialPanel();
    var currentHighlightedElem = jQuery('.tutorial_link_highlighted').first();
    if (currentHighlightedElem) {
        currentHighlightedElem.html(currentHighlightedElem.html().replace(' (start)', ''));
    }
    introInstance.start();
}

/**
 * Add next tutorial button
 * @param link
 * @param text
 * @param tutorialId
 */
function addNextTutorialButton(link, text, tutorialId) {
    jQuery('.introjs-nextbutton, .introjs-donebutton').hide();
    jQuery('.introjs-prevbutton').after(''+ '<a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">' + text + '</a> ');
    jQuery('.introjs-next-tutorial').click(function(event) {
        var targetLink = link;

        if (tutorialId) {
            try {
                window.sessionStorage.setItem('set_demo_pending_tutorial', tutorialId);
            } catch (error) {
                // Ignore storage errors.
            }

            try {
                var targetUrl = new URL(link, window.location.origin);
                targetUrl.searchParams.set('set_demo_tutorial', tutorialId);
                targetLink = targetUrl.toString();
            } catch (error) {
                // Keep original link if URL parsing fails.
            }
        }

        event.preventDefault();
        document.location.href = targetLink;
    });
}

/**
 * Add next button
 * @param link
 */
function addNextButton(link) {
    jQuery('.introjs-nextbutton').hide();
    jQuery('.introjs-prevbutton').after(''+ '<a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">Next →</a> ');
    jQuery('.introjs-next-tutorial').css('background','#315aa5');
    jQuery('.introjs-next-tutorial').click(function(event) {
        window.location = link;
    });
}

/**
 * Remove next tutorial button
 */
function removeNextTutorialButton() {
    if (jQuery('.introjs-next-tutorial') != undefined)
    {
        jQuery('.introjs-next-tutorial').remove();
        jQuery('.introjs-nextbutton, .introjs-donebutton').show();
    }
}

/**
 * Add frontend demo button in case tutorial has only one step
 *
 * @param link
 */
function addFrontendDemoButtonOnOneStep(link) {
    jQuery('.introjs-tooltipbuttons').after('' + '<div class="button-section"><a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">Frontend Demo</a></div> ');
    jQuery('.test').css("float", "right");
    jQuery('.introjs-nextbutton').click(function (event) {
        window.location = link;
    });
}

/**
 * Add one-step transition button with pending tutorial support.
 *
 * @param link
 * @param text
 * @param tutorialId
 */
function addFrontendDemoButtonOnOneStepWithTutorial(link, text, tutorialId) {
    var buttonText = text || 'Frontend Demo';
    var tooltipButtons = jQuery('.introjs-tooltipbuttons');

    removeNextTutorialButton();

    if (!tooltipButtons.length) {
        return;
    }

    tooltipButtons.append('' + '<a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">' + buttonText + '</a> ');

    jQuery('.introjs-next-tutorial').off('click').on('click', function (event) {
        var targetLink = link;

        if (tutorialId) {
            try {
                window.sessionStorage.setItem('set_demo_pending_tutorial', tutorialId);
            } catch (error) {
                // Ignore storage errors.
            }

            try {
                var targetUrl = new URL(link, window.location.origin);
                targetUrl.searchParams.set('set_demo_tutorial', tutorialId);
                targetLink = targetUrl.toString();
            } catch (error) {
                // Keep original link if URL parsing fails.
            }
        }

        event.preventDefault();
        window.location.href = targetLink;
    });
}

/**
 * Add backend demo button
 *
 * @param link
 */
function addBackendDemoButtonOnOneStep(link) {
    jQuery('.introjs-tooltipbuttons').after('' + '<div class="button-section"><a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">Backend Demo</a></div> ');
    jQuery('.test').css("float", "right");
    jQuery('.introjs-nextbutton').click(function (event) {
        window.location = link;
    });
}

/**
 * Add backend demo button
 *
 * @param link
 */
function addBackendDemoButton(link) {
    jQuery('.introjs-nextbutton, .introjs-donebutton').hide();
    jQuery('.introjs-prevbutton').after(''+ '<a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">Backend Demo</a> ');
    jQuery('.introjs-next-tutorial').click(function(event) {
        document.location.href = link
    });
}

/**
 * Add frontend demo button
 *
 * @param link
 */
function addFrontendDemoButton(link) {
    jQuery('.introjs-nextbutton, .introjs-donebutton').hide();
    jQuery('.introjs-prevbutton').after(''+ '<a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">Frontend Demo</a> ');
    jQuery('.introjs-next-tutorial').click(function(event) {
        document.location.href = link
    });
}

/**
 * Scroll to top
 */
function scrollToTop(){
    jQuery("html, body").animate({ scrollTop: 0 }, "fast");
}

/**
 * Open all configuration tabs (in system config)
 */
function openAllConfigurationTabs() {
    $$('.section-config span +a').each(function(item) {
        if (!item.hasClassName('open'))
            item.click();
    });
}

/**
 * Initialize scenario. It is executed before tutorial is launched.
 */
function initScenario() {
    if (ifCanShowTutorialPanel()) {
        showTutorialPanel();
        jQuery('.set-demo-controls__button--tutorials').hide();
    } else {
        jQuery('.set-demo-controls__button--tutorials').show();
    }
}

/**
 * Mark provided element as highlighted
 *
 * @param elem
 */
function highlightElem(elem) {
    if (!jQuery(elem).hasClass("tutorial_link_highlighted")) {
        jQuery(elem).addClass("tutorial_link_highlighted");
    }
}

/**
 * Start scenario on click menu item
 *
 * @param event
 * @param scenario
 */
function startOnClick(event, scenario)
{
    event.preventDefault();
    checkIfTutorialIsStarted();
    continueTutorial();
    scenario();
}

/**
 * Start scenario on page load
 *
 * @param elem
 * @param scenario
 */
function startOnLoad(elem, scenario)
{
    highlightElem(elem);
    if (ifCanStartIntro()) {
        scenario();
    } else {
        var originalText = elem.html();
        elem.html(originalText + ' (start)');
    }
}

function addNextButtonOnOneStep(link) {
    jQuery('.introjs-tooltipbuttons').after('' + '<div class="test"><a href="javascript:void(0);" class="introjs-button introjs-nextbutton">Next →</a></div> ');
    jQuery('.test').css("float", "right");
    jQuery('.introjs-nextbutton').click(function (event) {
        window.location = link;
    });
}

function addNextTutorialButtonOnOneStep(link, text) {
    jQuery('.introjs-tooltipbuttons').after('' + '<div class="button-section"><a href="javascript:void(0);" class="introjs-button introjs-nextbutton introjs-next-tutorial">' + text + '</a></div> ');
    jQuery('.test').css("float", "right");
    jQuery('.introjs-nextbutton').click(function (event) {
        window.location = link;
    });
}

function normalizeTutorialPath(path) {
    if (!path) {
        return '/';
    }

    var normalized = path.charAt(0) === '/' ? path : '/' + path;
    // Drop a trailing index.php: admin links built on the storefront carry
    // "/admin-dir/index.php", while getAdminLink() in the Back Office omits it
    // ("/admin-dir/"). Without this, the two forms never match and the tour that
    // should auto-start after navigating from front to admin never fires.
    normalized = normalized.replace(/\/index\.php$/i, '');
    return normalized.replace(/\/+$/, '') || '/';
}

function stripLanguagePrefix(path) {
    var normalized = normalizeTutorialPath(path);
    var parts = normalized.split('/').filter(Boolean);

    if (!parts.length) {
        return '/';
    }

    // Support storefront paths with language prefix, e.g. /en/product-slug.html.
    if (parts.length > 1 && /^[a-z]{2}(?:-[a-z]{2})?$/i.test(parts[0])) {
        parts.shift();
    }

    return '/' + parts.join('/');
}

function getPathBasename(path) {
    var parts = normalizeTutorialPath(path).split('/').filter(Boolean);
    return parts.length ? parts[parts.length - 1] : '';
}

function isCurrentUrlMatchHref(href) {
    if (!href) {
        return false;
    }

    try {
        var url = new URL(href, window.location.origin);
        var currentPath = normalizeTutorialPath(window.location.pathname);
        var targetPath = normalizeTutorialPath(url.pathname);

        var samePath = currentPath === targetPath
            || stripLanguagePrefix(currentPath) === stripLanguagePrefix(targetPath)
            || (getPathBasename(currentPath) !== '' && getPathBasename(currentPath) === getPathBasename(targetPath));

        // Legacy admin controllers share the same /admin-dir/ path. A standalone page such as
        // DemoOrderGenerator has no `tab`, so compare `controller` before treating it as current.
        var targetController = url.searchParams.get('controller');
        if (targetController !== null && targetController !== '') {
            var currentController = new URL(window.location.href).searchParams.get('controller') || '';
            if (currentController !== targetController) {
                return false;
            }
        }

        // Admin tabs of the module share one index.php path and differ only by the `tab`
        // query param (index.php?controller=...&tab=points). When the target link carries a
        // `tab`, the page matches only if the current `tab` is the same one — otherwise every
        // tab link would "match" any module page and the tour would start without navigating.
        var targetTab = url.searchParams.get('tab');
        if (targetTab !== null && targetTab !== '') {
            var currentTab = new URL(window.location.href).searchParams.get('tab') || '';
            return samePath && currentTab === targetTab;
        }

        return samePath;
    } catch (error) {
        return false;
    }
}

function refreshTutorialLinksHighlight() {
    jQuery('.set-tutorials-group__link').each(function () {
        var link = jQuery(this);
        if (isCurrentUrlMatchHref(link.attr('href'))) {
            link.addClass('tutorial_link_highlighted');
        } else {
            link.removeClass('tutorial_link_highlighted');
        }
    });
}
