/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a custom commercial license.
 * You may not redistribute, resell, sublicense, or share this file.
 * One license is valid for one installation (one store).
 *
 * For full license terms, contact: info@setecom.tech
 *
 * @author    Smart Ecommerce Tech
 * @copyright 2026 Smart Ecommerce Tech
 * @license   Commercial License
 */

(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function escapeHtml(value) {
        var holder = document.createElement('div');
        holder.textContent = value;
        return holder.innerHTML;
    }

    function renderResult(container, task, data, requestOk) {
        if (!container) {
            return;
        }

        var cssClass = 'alert-danger';
        if (requestOk && data && data.success) {
            cssClass = 'alert-success';
        } else if (data && data.locked) {
            cssClass = 'alert-warning';
        }

        var payload = data ? JSON.stringify(data) : 'request failed';
        container.innerHTML = '<div class="alert ' + cssClass + '">' +
            '<strong>' + escapeHtml(task) + '</strong>: ' + escapeHtml(payload) +
            '</div>';
    }

    function initCronTools() {
        var root = document.getElementById('snod-cron-tools');
        if (!root) {
            return;
        }

        var ajaxUrl = root.getAttribute('data-ajax-url');
        var resultBox = document.getElementById('snod-cron-result');
        var buttons = root.querySelectorAll('.snod-run-task');

        Array.prototype.forEach.call(buttons, function (button) {
            button.addEventListener('click', function () {
                var task = button.getAttribute('data-task');
                var originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '…';

                var url = ajaxUrl +
                    '&ajax=1&action=runCronTask&task=' + encodeURIComponent(task);

                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (payload) {
                        renderResult(resultBox, task, payload.data, payload.ok);
                    })
                    .catch(function () {
                        renderResult(resultBox, task, null, false);
                    })
                    .then(function () {
                        button.disabled = false;
                        button.innerHTML = originalHtml;
                    });
            });
        });
    }

    function initRuleEmailTabs() {
        var buttons = document.querySelectorAll('.snod-lang-btn');
        if (!buttons.length) {
            return;
        }

        Array.prototype.forEach.call(buttons, function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var type = button.getAttribute('data-type');
                var lang = button.getAttribute('data-lang');

                var panes = document.querySelectorAll('.snod-email-lang[data-type="' + type + '"]');
                Array.prototype.forEach.call(panes, function (pane) {
                    pane.style.display = (pane.getAttribute('data-lang') === lang) ? '' : 'none';
                });

                var block = button.closest('.snod-email-block');
                if (block) {
                    Array.prototype.forEach.call(block.querySelectorAll('.nav-pills li'), function (li) {
                        li.classList.remove('active');
                    });
                }
                if (button.parentNode) {
                    button.parentNode.classList.add('active');
                }
            });
        });
    }

    function b64Utf8(str) {
        return btoa(unescape(encodeURIComponent(str || '')));
    }

    function visiblePane(block) {
        var panes = block.querySelectorAll('.snod-email-lang');
        var active = null;
        Array.prototype.forEach.call(panes, function (pane) {
            if (pane.style.display !== 'none') {
                active = pane;
            }
        });
        return active || panes[0] || null;
    }

    function showPreview(html) {
        var modal = document.getElementById('snod-preview-modal');
        var frame = document.getElementById('snod-preview-frame');
        if (!modal || !frame) {
            return;
        }
        frame.srcdoc = html || '';
        modal.style.display = 'block';
    }

    function initPreviewModal() {
        var modal = document.getElementById('snod-preview-modal');
        if (!modal) {
            return;
        }
        var closeBtn = document.getElementById('snod-preview-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                modal.style.display = 'none';
            });
        }
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }

    function initRuleEmailActions() {
        var root = document.getElementById('snod-tab-email');
        if (!root) {
            return;
        }
        var ajaxUrl = root.getAttribute('data-ajax-url');

        function gather(button) {
            var block = button.closest('.snod-email-block');
            var pane = block ? visiblePane(block) : null;
            if (!pane) {
                return null;
            }
            var subject = pane.querySelector('input');
            var html = pane.querySelector('textarea');
            return {
                actions: button.closest('.snod-email-actions'),
                idLang: pane.getAttribute('data-lang'),
                subject: subject ? subject.value : '',
                html: html ? html.value : ''
            };
        }

        Array.prototype.forEach.call(root.querySelectorAll('.snod-preview-btn'), function (button) {
            button.addEventListener('click', function () {
                var data = gather(button);
                if (!data) {
                    return;
                }
                var url = ajaxUrl + '&ajax=1&action=previewRuleEmail&id_lang=' + encodeURIComponent(data.idLang) +
                    '&subject_b64=' + encodeURIComponent(b64Utf8(data.subject)) +
                    '&html_b64=' + encodeURIComponent(b64Utf8(data.html));
                fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        showPreview(res && res.html ? res.html : '');
                    })
                    .catch(function () {});
            });
        });

        Array.prototype.forEach.call(root.querySelectorAll('.snod-sendtest-btn'), function (button) {
            button.addEventListener('click', function () {
                var data = gather(button);
                if (!data) {
                    return;
                }
                var emailInput = data.actions ? data.actions.querySelector('.snod-test-email') : null;
                var result = data.actions ? data.actions.querySelector('.snod-email-result') : null;
                var email = emailInput ? emailInput.value : '';
                if (result) {
                    result.textContent = '…';
                }
                var url = ajaxUrl + '&ajax=1&action=sendTestRuleEmail&id_lang=' + encodeURIComponent(data.idLang) +
                    '&email=' + encodeURIComponent(email) +
                    '&subject_b64=' + encodeURIComponent(b64Utf8(data.subject)) +
                    '&html_b64=' + encodeURIComponent(b64Utf8(data.html));
                fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!result) {
                            return;
                        }
                        if (res && res.success) {
                            result.innerHTML = '<span class="text-success">✔ sent</span>';
                        } else if (res && res.error === 'invalid_email') {
                            result.innerHTML = '<span class="text-danger">✘ invalid email</span>';
                        } else {
                            result.innerHTML = '<span class="text-danger">✘ failed</span>';
                        }
                    })
                    .catch(function () {
                        if (result) {
                            result.innerHTML = '<span class="text-danger">✘ error</span>';
                        }
                    });
            });
        });
    }

    ready(initCronTools);
    ready(initRuleEmailTabs);
    ready(initPreviewModal);
    ready(initRuleEmailActions);
})();
