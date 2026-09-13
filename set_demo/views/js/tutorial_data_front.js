/**
 * Next Order Discount — фронтовая часть демо.
 *
 * Файл подключается только на фронтенде, поэтому хелперы SLMDemo определяются здесь
 * самодостаточно (идемпотентно через window.SLMDemo).
 *
 * У модуля нет виджета на витрине: покупатель просто оформляет заказ, а купон на следующий
 * заказ приходит письмом. Поэтому фронт-пункт панели показывает не тур по DOM, а пошаговый
 * поп-ап (плавающие шаги intro.js) с инструкцией, как прогнать модуль на тестовом заказе
 * (см. loadTutorialFront ниже). Часть хелперов SLMDemo про подсветку баров здесь не
 * используется — оставлена ради единого движка с админкой.
 *
 * Тексты основаны на guides/GUIDE_RU.md (модуль next_order_discount).
 */

var SLMDemo = window.SLMDemo || {};

if (!SLMDemo.q) {
    SLMDemo.q = function (selector) {
        try {
            return document.querySelector(selector);
        } catch (error) {
            return null;
        }
    };

    SLMDemo.text = function (html) {
        return "<p class='intro-main-text'>" + html + "</p>";
    };

    // Язык туров = язык демо-панели (localStorage, см. panel.tpl). RU — источник строк.
    SLMDemo.curLang = function () {
        try { var l = localStorage.getItem('setDemoPanelLang'); return (l === 'en' || l === 'fr') ? l : 'ru'; }
        catch (e) { return 'ru'; }
    };

    // Перевод строки шага по каталогу tutorial_i18n.js; нет перевода — возвращаем как есть.
    SLMDemo.tr = function (s) {
        if (typeof s !== 'string') { return s; }
        var lang = SLMDemo.curLang();
        if (lang === 'ru') { return s; }
        var m = window.SLM_TOUR_I18N && window.SLM_TOUR_I18N[lang];
        return (m && m[s] != null) ? m[s] : s;
    };

    // Подставить динамические значения ({barDesign}) в уже собранный (и переведённый) шаг.
    SLMDemo.fill = function (step, vars) {
        if (step && typeof step.intro === 'string' && vars) {
            Object.keys(vars).forEach(function (k) {
                step.intro = step.intro.split('{' + k + '}').join(vars[k]);
            });
        }
        return step;
    };

    /**
     * Собрать шаг тура.
     *
     * @param {Element|null} element  DOM-узел для подсветки (или null — информационный шаг)
     */
    SLMDemo.stepEl = function (element, title, body, position) {
        var html = "<strong>" + SLMDemo.tr(title) + "</strong><br><br>" + SLMDemo.tr(body);
        var step = { intro: SLMDemo.text(html) };

        if (element) {
            step.element = element;
            step.position = position || 'bottom';
        }

        return step;
    };

    /**
     * Создаёт ли элемент собственный контекст наложения (stacking context).
     * Если такой предок есть выше бара, z-index бара «заперт» внутри него и не
     * может подняться над затемняющим оверлеем intro.js.
     */
    SLMDemo.createsStackingContext = function (computed) {
        if (computed.position === 'fixed' || computed.position === 'sticky') {
            return true;
        }
        if ((computed.position === 'relative' || computed.position === 'absolute') && computed.zIndex !== 'auto') {
            return true;
        }
        if (computed.transform !== 'none' || computed.filter !== 'none' || computed.perspective !== 'none') {
            return true;
        }
        if (parseFloat(computed.opacity) < 1) {
            return true;
        }
        if (computed.mixBlendMode && computed.mixBlendMode !== 'normal') {
            return true;
        }
        if (computed.isolation === 'isolate') {
            return true;
        }
        if (computed.willChange && /transform|opacity|filter|perspective/.test(computed.willChange)) {
            return true;
        }
        if (computed.contain && /paint|layout|strict|content/.test(computed.contain)) {
            return true;
        }
        return false;
    };

    /** Есть ли у элемента предок с собственным контекстом наложения (до <body>). */
    SLMDemo.hasTrappingAncestor = function (element) {
        var node = element.parentElement;
        while (node && node !== document.body && node.nodeType === 1) {
            if (SLMDemo.createsStackingContext(window.getComputedStyle(node))) {
                return true;
            }
            node = node.parentElement;
        }
        return false;
    };

    /**
     * Выбрать бар для тура: первый .slm-progress-wrap БЕЗ «запирающего» предка
     * (его можно чисто поднять над затемнением). Такой бар — в контенте страницы
     * (например, хук displayShoppingCart), а не в липкой шапке. Если чистого нет —
     * возвращаем первый попавшийся (лучшее из возможного).
     */
    SLMDemo.pickBar = function () {
        var wraps = document.querySelectorAll('.slm-progress-wrap');
        for (var i = 0; i < wraps.length; i++) {
            if (!SLMDemo.hasTrappingAncestor(wraps[i])) {
                return wraps[i];
            }
        }
        return wraps.length ? wraps[0] : null;
    };

    /**
     * Поднять контейнер бара над оверлеем intro.js.
     * Поднимаем ТОЛЬКО сам контейнер: его поддерево (все подсвечиваемые шаги)
     * поднимается целиком, а соседние элементы страницы (header-top и т.п.) и
     * система координат подсветки не затрагиваются. Возвращает снимок для отката.
     */
    SLMDemo.lift = function (element) {
        var snapshot = [];

        if (!element) {
            return snapshot;
        }

        var computed = window.getComputedStyle(element);
        snapshot.push({ node: element, zIndex: element.style.zIndex, position: element.style.position });

        // z-index работает только у позиционированного элемента.
        if (computed.position === 'static') {
            element.style.position = 'relative';
        }
        // Выше оверлея (999999) и helperLayer (9999998), ниже тултипа (10000000).
        element.style.zIndex = '9999999';

        return snapshot;
    };

    /** Восстановить инлайн-стили, изменённые lift(). */
    SLMDemo.unlift = function (snapshot) {
        if (!snapshot) {
            return;
        }

        snapshot.forEach(function (record) {
            record.node.style.zIndex = record.zIndex;
            record.node.style.position = record.position;
        });
    };

    /**
     * Выполнить callback, когда страница полностью загружена и раскладка устоялась
     * (window load + готовность шрифтов + два кадра), чтобы intro.js привязывался к
     * финальным позициям элементов, а не к ещё «прыгающим».
     */
    SLMDemo.whenReady = function (callback) {
        var fired = false;

        function settle() {
            if (fired) {
                return;
            }
            fired = true;

            var started = false;
            function go() {
                if (started) {
                    return;
                }
                started = true;
                callback();
            }

            var raf = window.requestAnimationFrame || function (fn) { return setTimeout(fn, 16); };
            raf(function () {
                raf(function () {
                    setTimeout(go, 60);
                });
            });
            // Страховка: если rAF приостановлен (вкладка в фоне) — стартуем не позже 400 мс.
            setTimeout(go, 400);
        }

        var waiters = [];
        if (document.readyState !== 'complete') {
            waiters.push(new Promise(function (resolve) {
                window.addEventListener('load', resolve, { once: true });
            }));
        }
        if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
            waiters.push(document.fonts.ready);
        }

        if (waiters.length && window.Promise) {
            Promise.all(waiters).then(settle);
            setTimeout(settle, 3000);
        } else {
            settle();
        }
    };

    /** Ключ хранения текущего шага тура — по пути витрины. */
    SLMDemo.tourKey = function () {
        var id = 'front';
        try {
            id = 'front' + (location.pathname || '');
        } catch (e) { /* оставляем front */ }
        return 'slm_tour_step_' + id;
    };

    /** Ключ хранения шага для произвольной ссылки (по её tab / пути). */
    SLMDemo.tourKeyForHref = function (href) {
        try {
            var u = new URL(href, location.origin);
            var tab = u.searchParams.get('tab');
            return tab ? ('slm_tour_step_' + tab) : ('slm_tour_step_front' + u.pathname);
        } catch (e) {
            return null;
        }
    };

    SLMDemo.getSavedStep = function (key) {
        try {
            var v = parseInt(window.sessionStorage.getItem(key), 10);
            return isNaN(v) ? 0 : v;
        } catch (e) {
            return 0;
        }
    };

    SLMDemo.setSavedStep = function (key, step) {
        try { window.sessionStorage.setItem(key, String(step)); } catch (e) { /* нет доступа */ }
    };

    SLMDemo.clearSavedStep = function (key) {
        try { window.sessionStorage.removeItem(key); } catch (e) { /* нет доступа */ }
    };

    /**
     * Прокрутить элемент в видимую зону с учётом позиции тултипа (intro.js сам не докручивает,
     * а при position:'bottom' у высокого элемента тултипу не хватает места и он уходит в центр).
     */
    SLMDemo.scrollToStep = function (element, position) {
        if (!element || typeof element.getBoundingClientRect !== 'function') {
            return;
        }
        var rect = element.getBoundingClientRect();
        var vh = window.innerHeight || document.documentElement.clientHeight || 800;

        // Реальная высота фиксированной/липкой шапки сверху — чтобы прокрученный элемент не уехал под неё.
        var headerClear = 20;
        try {
            var cx = Math.round((window.innerWidth || 1000) / 2);
            var ys = [4, 40, 80, 130, 180];
            for (var j = 0; j < ys.length; j++) {
                var els = document.elementsFromPoint(cx, ys[j]) || [];
                for (var i = 0; i < els.length; i++) {
                    if (els[i] === element || (element.contains && element.contains(els[i]))) { continue; }
                    var pcs = window.getComputedStyle(els[i]);
                    if (pcs.position === 'fixed' || pcs.position === 'sticky') {
                        var pb = els[i].getBoundingClientRect().bottom;
                        if (pb > headerClear && pb < vh * 0.6) { headerClear = pb; }
                    }
                }
            }
            headerClear += 14;
        } catch (e) { headerClear = 60; }

        var targetTop;
        if (position === 'bottom') {
            targetTop = headerClear;
        } else if (position === 'top') {
            targetTop = Math.max(headerClear, Math.round(vh * 0.5));
        } else {
            targetTop = Math.max(headerClear, Math.round((vh - rect.height) / 2));
        }
        var delta = rect.top - targetTop;
        if (Math.abs(delta) < 2) {
            return;
        }
        try { window.scrollBy({ top: delta, left: 0, behavior: 'auto' }); }
        catch (e) { window.scrollBy(0, delta); }
    };

    /**
     * Запустить тур.
     *
     * @param {Array}  steps    массив шагов
     * @param {Object} options  { lift: Element } — контейнер, чью цепочку предков поднять на время тура
     *
     * Тур запоминает текущий шаг: при закрытии не пройденного тура он продолжается с
     * того же шага (resume); при прохождении до конца — сбрасывается.
     */
    SLMDemo.run = function (steps, options) {
        var intro = introJs();
        var liftSnapshot = null;
        var liftTarget = options && options.lift;
        var chain = options && options.chain;
        var storageKey = SLMDemo.tourKey();
        var lastStep = steps.length - 1;

        // Как штатный introjs-fixParent: фикс-предки → position:absolute (вне потока, без отступа
        // сверху; при z-index:auto нет стек-контекста → элемент всплывает). Плюс скролл в верх.
        var staticized = [];
        function restoreStaticized() {
            for (var i = 0; i < staticized.length; i++) {
                staticized[i].node.style.cssText = staticized[i].cssText;
            }
            staticized = [];
        }
        function neutralizeFixedAncestors(el) {
            restoreStaticized();
            if (!el || el.nodeType !== 1) {
                return false;
            }
            var found = false, node = el;
            while (node && node !== document.body && node.nodeType === 1) {
                if (window.getComputedStyle(node).position === 'fixed') {
                    staticized.push({ node: node, cssText: node.style.cssText });
                    node.style.setProperty('position', 'absolute', 'important');
                    node.style.setProperty('z-index', 'auto', 'important');
                    found = true;
                }
                node = node.parentElement;
            }
            if (found) {
                try { window.scrollTo({ top: 0, left: 0, behavior: 'auto' }); }
                catch (e) { window.scrollTo(0, 0); }
            }
            return found;
        }



        intro.setOptions({
            steps: steps,
            // Свою прокрутку intro отключаем — центрируем сами (иначе тултип уходит выше видимой зоны).
            scrollToElement: false,
            exitOnOverlayClick: true,
            showStepNumbers: false,
            showBullets: false,
            nextLabel: SLMDemo.tr('Далее →'),
            prevLabel: SLMDemo.tr('← Назад'),
            skipLabel: SLMDemo.tr('Закрыть'),
            doneLabel: SLMDemo.tr('Закрыть')
        });

        intro.onbeforechange(function (targetElement) {
            // Прокрутить подсвечиваемый элемент (с учётом позиции тултипа) ДО его отрисовки.
            var pos = 'bottom';
            for (var i = 0; i < steps.length; i++) {
                if (steps[i].element === targetElement) { pos = steps[i].position || 'bottom'; break; }
            }
            if (!neutralizeFixedAncestors(targetElement)) {
                SLMDemo.scrollToStep(targetElement, pos);
            }
            if (typeof removeNextTutorialButton === 'function') {
                removeNextTutorialButton();
            }
        });

        intro.onafterchange(function () {
            // Запоминаем текущий шаг для возобновления. На последнем шаге сбрасываем —
            // тур считается пройденным (resume на нём не нужен).
            var current = intro.getCurrentStep();
            if (current >= lastStep) {
                SLMDemo.clearSavedStep(storageKey);
            } else {
                SLMDemo.setSavedStep(storageKey, current);
            }

            // На последнем шаге — кнопка перехода к следующему туру (цикличность).
            if (chain && chain.id && current === lastStep) {
                setTimeout(function () {
                    var linkElement = document.getElementById(chain.id);
                    if (linkElement && typeof addNextTutorialButton === 'function') {
                        addNextTutorialButton(linkElement.href, SLMDemo.tr(chain.label), chain.id);
                    }
                }, 200);
            }
        });

        function finish() {
            restoreStaticized();
            SLMDemo.unlift(liftSnapshot);
            liftSnapshot = null;
            if (typeof stopIntro === 'function') {
                stopIntro('intro', intro);
            }
            if (typeof refreshTutorialLinksHighlight === 'function') {
                refreshTutorialLinksHighlight();
            }
            // Обновить метки «(start)»/«(продолжить)» в панели без перезагрузки.
            try { document.dispatchEvent(new Event('slm-demo-resume-refresh')); } catch (e) { /* нет CustomEvent */ }
        }

        intro.oncomplete(function () {
            SLMDemo.clearSavedStep(storageKey);
            finish();
        });

        intro.onexit(function () {
            try {
                if (intro.getCurrentStep() >= lastStep) {
                    SLMDemo.clearSavedStep(storageKey);
                }
            } catch (e) { /* getCurrentStep недоступен */ }
            finish();
        });

        // Стартуем только после полной загрузки и устаканивания раскладки — иначе
        // подсветка бара может привязаться к ещё «прыгающему» элементу и съехать.
        SLMDemo.whenReady(function () {
            if (typeof ifCanStartIntro !== 'function' || ifCanStartIntro()) {
                if (liftTarget) {
                    liftSnapshot = SLMDemo.lift(liftTarget);
                }

                // Возобновление/refresh — только ПОСЛЕ завершения start(). start()/goToStep()/refresh()
                // в этой сборке асинхронны; дёргать их синхронно поверх ещё строящегося тура =
                // гонка «Uncaught (in promise) … n is undefined» (проявляется на тяжёлом DOM).
                var swallow = function (p) { if (p && typeof p.catch === 'function') { p.catch(function () {}); } };

                var afterStart = function () {
                    // Возобновление с сохранённого шага.
                    var saved = SLMDemo.getSavedStep(storageKey);
                    if (saved > 0 && saved <= lastStep) {
                        try { swallow(intro.goToStep(saved + 1)); } catch (e) { /* goToStep недоступен */ }
                    }

                    setTimeout(function () {
                        try {
                            var st = steps[intro.getCurrentStep()];
                            SLMDemo.scrollToStep(st && st.element, (st && st.position) || 'bottom');
                            swallow(intro.refresh());
                        } catch (e) { /* refresh/scroll недоступны — не критично */ }
                    }, 120);
                };

                var started;
                try { started = intro.start(); } catch (e) { started = null; }
                if (started && typeof started.then === 'function') {
                    started.then(afterStart, function () { /* гонка intro — глушим */ });
                } else {
                    afterStart();
                }
            }
        });

        return intro;
    };

    window.SLMDemo = SLMDemo;
}

/**
 * «Витрина» для NOD — это не виджет на странице, а путь покупателя: он оформляет заказ,
 * заказ доходит до нужного статуса, создаётся купон и уходит письмо. Поэтому фронт-пункт
 * показывает не тур по DOM, а пошаговый поп-ап (плавающие шаги intro.js) с инструкцией,
 * как прогнать модуль на тестовом заказе. Цикл замыкается переходом обратно на Dashboard.
 */
function loadTutorialFront() {
    // Ссылка на гайд админ-вкладки (открывает её и сразу запускает тур).
    var guideLink = function (tutorialId, label) {
        var el = document.getElementById(tutorialId);
        var href = el ? el.getAttribute('href') : '';
        if (!href) { return label; }
        var sep = href.indexOf('?') > -1 ? '&' : '?';
        return '<a href="' + href + sep + 'set_demo_tutorial=' + tutorialId + '">' + label + '</a>';
    };
    var couponsLink = guideLink('tutorialCoupons', 'Coupons');
    var cronLink = guideLink('tutorialCronTools', 'Cron/Tools');
    var rulesLink = guideLink('tutorialRules', 'Rules');

    var steps = [
        SLMDemo.stepEl(
            null,
            'Как покупатель получает купон',
            'У Next Order Discount нет виджета на витрине — вся работа скрыта. Покупатель просто оформляет заказ, а купон на <b>следующий</b> заказ приходит ему письмом автоматически. Ниже — как прогнать это на тестовом заказе за пару минут.'
        ),
        SLMDemo.fill(SLMDemo.stepEl(
            null,
            'Шаг 1. Оформите тестовый заказ',
            'Добавьте товар в корзину и оформите заказ в этом магазине (как обычный покупатель). Заказ должен подходить под условия правила — проверьте их в {rules}: триггерные статусы, сумма, группа, страна и т.д.'
        ), { rules: rulesLink }),
        SLMDemo.fill(SLMDemo.stepEl(
            null,
            'Шаг 2. Переведите заказ в триггерный статус',
            'В админке откройте <b>Заказы → ваш заказ</b> и смените его статус на тот, что указан в правиле (например «Оплачено»). В этот момент модуль подбирает правило и создаёт персональный купон. Проверить, что купон появился, можно во вкладке {coupons}.'
        ), { coupons: couponsLink }),
        SLMDemo.fill(SLMDemo.stepEl(
            null,
            'Шаг 3. Дождитесь письма с купоном',
            'Купонное письмо ставится в очередь и уходит при ближайшем проходе cron. Чтобы не ждать — откройте {cron} и нажмите <b>Run all tasks now</b>. После этого покупатель получит письмо с кодом купона на следующий заказ.'
        ), { cron: cronLink }),
        SLMDemo.fill(SLMDemo.stepEl(
            null,
            'Шаг 4. Купон применяется к следующему заказу',
            'Покупатель вводит код на новом заказе — и получает скидку. Купон отметится как <b>used</b>, напоминания по нему прекратятся. При необходимости письмо и напоминания можно переотправить вручную из {coupons} (Resend / 1 / 2).'
        ), { coupons: couponsLink })
    ];

    // Цикличный тур: с последнего шага — обратно на Dashboard (начало круга).
    SLMDemo.run(steps, {
        chain: { id: 'tutorialDashboard', label: 'Дальше: Dashboard →' }
    });
}
