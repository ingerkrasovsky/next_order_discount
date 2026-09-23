/**
 * Next Order Discount — интерактивные туры по админке модуля.
 *
 * Каждая вкладка модуля открывается отдельным URL (&tab=...), поэтому под каждую
 * вкладку — свой загрузчик loadTutorialXxx(), который вызывается из панели
 * «Навигация по демо» (см. panel.tpl / scenario_admin.js).
 *
 * Тексты основаны на guides/GUIDE_RU.md (модуль next_order_discount).
 */

var SLMDemo = window.SLMDemo || {};

/** Безопасный querySelector: не падает на кривом селекторе и на отсутствующем узле. */
SLMDemo.q = function (selector) {
    try {
        return document.querySelector(selector);
    } catch (error) {
        return null;
    }
};

/** Обёртка текста шага в фирменный абзац intro.js. */
SLMDemo.text = function (html) {
    return "<p class='intro-main-text'>" + html + "</p>";
};

/**
 * Язык туров = язык демо-панели (её переключатель хранит выбор в localStorage).
 * Русский используется только как внутренний язык ключей и не показывается посетителю.
 */
SLMDemo.curLang = function () {
    try {
        var l = localStorage.getItem('setDemoPanelLang');
        return ['en', 'fr', 'de', 'pl', 'es'].indexOf(l) !== -1 ? l : 'en';
    } catch (e) { return 'en'; }
};

/** Перевод строки шага по каталогу tutorial_i18n.js. Нет перевода — возвращаем как есть. */
SLMDemo.tr = function (s) {
    if (typeof s !== 'string') { return s; }
    var lang = SLMDemo.curLang();
    var m = window.SLM_TOUR_I18N && window.SLM_TOUR_I18N[lang];
    return (m && m[s] != null) ? m[s] : s;
};

/** Подставить динамические значения ({barDesign}, {settings}) в уже собранный (и переведённый) шаг. */
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
 * Если элемент не найден на странице (например, таблица этапов пустая),
 * шаг превращается в информационный (без подсветки), а не ломает тур.
 */
SLMDemo.step = function (selector, title, body, position) {
    var html = "<strong>" + SLMDemo.tr(title) + "</strong><br><br>" + SLMDemo.tr(body);
    var step = { intro: SLMDemo.text(html) };
    var element = selector ? SLMDemo.q(selector) : null;

    if (element) {
        step.element = element;
        step.position = position || 'bottom';
    }

    return step;
};

/**
 * То же, что step(), но элемент передаётся напрямую (а не селектором).
 * Нужен, когда элемент выбирается по индексу (например, N-я группа настроек).
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

/** N-й элемент по селектору (документный порядок) или null. */
SLMDemo.nth = function (selector, index) {
    var list;
    try {
        list = document.querySelectorAll(selector);
    } catch (error) {
        return null;
    }

    return list[index] || null;
};

/**
 * Шаг, подсвечивающий ВСЮ строку с лейблом (а не только само поле).
 * По селектору находит контрол и поднимается до его строки (.form-group или label).
 */
SLMDemo.stepRow = function (selector, title, body, position) {
    var element = selector ? SLMDemo.q(selector) : null;
    var row = null;

    if (element && typeof element.closest === 'function') {
        row = element.closest('.form-group') || element.closest('label') || element;
    } else {
        row = element;
    }

    return SLMDemo.stepEl(row, title, body, position);
};

/**
 * Выполнить callback, когда страница полностью загружена и раскладка устоялась
 * (window load + готовность шрифтов + два кадра отрисовки). Нужно, чтобы intro.js
 * привязывал подсветку к финальным позициям элементов, а не к ещё «прыгающим».
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
        setTimeout(settle, 3000); // страховка, если какое-то ожидание не разрешится
    } else {
        settle();
    }
};

/** Ключ хранения текущего шага тура — по вкладке админки (или пути на витрине). */
SLMDemo.tourKey = function () {
    var id = 'default';
    try {
        var url = new URL(location.href);
        var tab = url.searchParams.get('tab');
        var controller = url.searchParams.get('controller');
        id = tab || controller || location.pathname;
    } catch (e) { /* оставляем default */ }
    return 'slm_tour_step_' + id;
};

/** Ключ хранения шага для произвольной ссылки (по её tab / пути). */
SLMDemo.tourKeyForHref = function (href) {
    try {
        var u = new URL(href, location.origin);
        var tab = u.searchParams.get('tab');
        var controller = u.searchParams.get('controller');
        if (tab) { return 'slm_tour_step_' + tab; }
        if (controller) { return 'slm_tour_step_' + controller; }
        return 'slm_tour_step_front' + u.pathname;
    } catch (e) {
        return null;
    }
};

/** Прочитать сохранённый шаг тура (0, если нет). */
SLMDemo.getSavedStep = function (key) {
    try {
        var v = parseInt(window.sessionStorage.getItem(key), 10);
        return isNaN(v) ? 0 : v;
    } catch (e) {
        return 0;
    }
};

/** Сохранить текущий шаг тура (для возобновления после закрытия). */
SLMDemo.setSavedStep = function (key, step) {
    try { window.sessionStorage.setItem(key, String(step)); } catch (e) { /* хранилище недоступно */ }
};

/** Очистить сохранённый шаг (тур пройден до конца — в следующий раз с начала). */
SLMDemo.clearSavedStep = function (key) {
    try { window.sessionStorage.removeItem(key); } catch (e) { /* хранилище недоступно */ }
};

/**
 * Прокрутить элемент в видимую зону с учётом позиции тултипа: intro.js сам не докручивает,
 * а при position:'bottom' у центрированного высокого элемента тултипу не хватает места снизу —
 * и он «сваливается» в центр экрана. Ставим элемент повыше/пониже, чтобы тултип поместился.
 */
SLMDemo.scrollToStep = function (element, position) {
    if (!element || typeof element.getBoundingClientRect !== 'function') {
        return false;
    }
    var rect = element.getBoundingClientRect();
    var vh = window.innerHeight || document.documentElement.clientHeight || 800;

    // Сколько сверху занимает фиксированная шапка (BO: #header_infos + .page-head + вкладки ~190px;
    // на фронте — липкий хедер темы, если есть). Измеряем реально, иначе элемент уезжает ПОД неё.
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
    } catch (e) { headerClear = 150; }

    var targetTop;
    if (position === 'bottom') {
        targetTop = headerClear;                                    // элемент выше → место под тултип снизу
    } else if (position === 'top') {
        targetTop = Math.max(headerClear, Math.round(vh * 0.5));    // элемент ниже → место над тултипом
    } else {
        targetTop = Math.max(headerClear, Math.round((vh - rect.height) / 2)); // left/right → центр
    }
    var delta = rect.top - targetTop;
    if (Math.abs(delta) < 2) {
        return false;
    }
    try { window.scrollBy({ top: delta, left: 0, behavior: 'auto' }); }
    catch (e) { window.scrollBy(0, delta); }
    return true;
};

/**
 * Запустить тур.
 *
 * @param {Array}  steps  массив шагов
 * @param {Object} chain  { id: 'tutorialXxx', label: 'Следующий раздел →' } — кнопка перехода
 *                        к следующему туру на последнем шаге (необязательно)
 * @param {Object} hooks  { beforeChange(target) } — подготовка состояния перед шагом (необязательно)
 *
 * Тур запоминает текущий шаг: при закрытии не пройденного тура он в следующий раз
 * продолжается с того же шага (resume). При прохождении до конца — сбрасывается.
 */
SLMDemo.run = function (steps, chain, hooks) {
    var intro = introJs();

    intro.setOptions({
        steps: steps,
        // Свою прокрутку intro отключаем — она выравнивает по верху и элемент уходит под
        // фиксированную шапку BO. Центрируем сами в onbeforechange (до позиционирования тултипа).
        scrollToElement: false,
        exitOnOverlayClick: true,
        showStepNumbers: false,
        showBullets: false,
        nextLabel: SLMDemo.tr('Далее →'),
        prevLabel: SLMDemo.tr('← Назад'),
        skipLabel: SLMDemo.tr('Закрыть'),
        doneLabel: SLMDemo.tr('Закрыть')
    });

    var storageKey = SLMDemo.tourKey();
    var lastStep = steps.length - 1;

    // Эта версия intro.js НЕ обрабатывает фиксированных предков (introjs-fixParent не применяется),
    // поэтому элемент внутри position:fixed шапки BO остаётся под затемнением. Раньше работало,
    // потому что вверху страницы шапка BO — static (fixed включается только при скролле).
    // Решение (как штатный introjs-fixParent): синхронно делаем фикс-предков position:absolute —
    // они тоже ВНЕ потока (без лишнего отступа сверху), но при z-index:auto не создают стек-контекст,
    // поэтому кнопка всплывает штатно (сам элемент/текст не трогаем). Плюс скролл в верх, чтобы
    // absolute-шапка (позиционируется от документа) была видна. На переходе/выходе — возврат стилей.
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
        // true только для фиксированной шапки BO (тогда нужен скролл в верх + пропуск scrollToStep).
        // Колонку предпросмотра Bar Design гасим отдельно, но обычную прокрутку к элементу оставляем.
        // Обходим только ПРЕДКОВ: сам подсвечиваемый элемент поднимает introjs (z-index в
        // .introjs-showElement); если занулить ему position/z-index — подсветка не сработает и
        // тултип «накроет» блок (случай sticky-панели Save).
        var foundFixedHeader = false, node = el.parentElement;
        while (node && node !== document.body && node.nodeType === 1) {
            var posv = window.getComputedStyle(node).position;
            var isPreviewCol = node.classList && node.classList.contains('slm-preview-live-sticky');
            if (isPreviewCol) {
                // Колонка предпросмотра сама переключается sticky ⇄ fixed ⇄ absolute (back.js) и в
                // fixed-режиме ещё и overflow:auto + max-height — внутренний скролл. Всё это создаёт
                // стек-контекст (панель под затемнением intro) и обрезает контент. Гасим целиком в
                // static: панель в общем потоке, дальше её найдёт обычный scrollToStep. syncSticky
                // ставит inline-стили без !important, поэтому наши !important переживают его пересчёт.
                staticized.push({ node: node, cssText: node.style.cssText });
                node.style.setProperty('position', 'static', 'important');
                node.style.setProperty('z-index', 'auto', 'important');
                node.style.setProperty('max-height', 'none', 'important');
                node.style.setProperty('overflow', 'visible', 'important');
                node.style.setProperty('width', 'auto', 'important');
                node.style.setProperty('left', 'auto', 'important');
                // Распорка back.js держит высоту «в рост колонки»; при static-колонке это лишний
                // пустой отступ, сдвигающий цель. Прячем её (syncSticky ставит display без !important).
                var ph = node.previousElementSibling;
                if (ph && ph.classList && ph.classList.contains('slm-preview-sticky-placeholder')) {
                    staticized.push({ node: ph, cssText: ph.style.cssText });
                    ph.style.setProperty('display', 'none', 'important');
                }
            } else if (posv === 'sticky') {
                // Прочий sticky (например, панель Save внутри колонки) — тоже стек-контекст. В static.
                staticized.push({ node: node, cssText: node.style.cssText });
                node.style.setProperty('position', 'static', 'important');
                node.style.setProperty('z-index', 'auto', 'important');
            } else if (posv === 'fixed') {
                // Фиксированная шапка BO — в absolute (вне потока, без отступа), затем скролл в верх.
                staticized.push({ node: node, cssText: node.style.cssText });
                node.style.setProperty('position', 'absolute', 'important');
                node.style.setProperty('z-index', 'auto', 'important');
                foundFixedHeader = true;
            }
            node = node.parentElement;
        }
        if (foundFixedHeader) {
            try { window.scrollTo({ top: 0, left: 0, behavior: 'auto' }); }
            catch (e) { window.scrollTo(0, 0); }
        }
        return foundFixedHeader;
    }

    // Колбэки регистрируем ДО старта, чтобы они сработали и на первом шаге.
    intro.onbeforechange(function (targetElement) {
        if (hooks && typeof hooks.beforeChange === 'function') {
            hooks.beforeChange(targetElement);
        }
        // Прокрутить подсвечиваемый элемент (с учётом позиции тултипа) ДО отрисовки тултипа.
        var pos = 'bottom';
        for (var i = 0; i < steps.length; i++) {
            if (steps[i].element === targetElement) { pos = steps[i].position || 'bottom'; break; }
        }
        // Элемент в фиксированной шапке: делаем предков static + скролл в верх; иначе — прокрутка.
        if (!neutralizeFixedAncestors(targetElement)) {
            SLMDemo.scrollToStep(targetElement, pos);
            // Второй проход после кадра: фиксированная шапка BO становится fixed по событию scroll
            // (асинхронно), поэтому в первом проходе elementsFromPoint её ещё не видит и элемент
            // может уехать под меню. К следующему кадру шапка уже зафиксирована — корректируем и
            // пересчитываем тултип, но только если реально пришлось доскроллить (без лишних дёрганий).
            var raf = window.requestAnimationFrame || function (fn) { return setTimeout(fn, 16); };
            raf(function () {
                if (SLMDemo.scrollToStep(targetElement, pos)) {
                    try { intro.refresh(); } catch (e) { /* refresh недоступен */ }
                }
            });
        }
        if (typeof removeNextTutorialButton === 'function') {
            removeNextTutorialButton();
        }
    });

    intro.onafterchange(function () {
        var current = intro.getCurrentStep();
        // Запоминаем текущий шаг для возобновления. На последнем шаге сбрасываем —
        // тур считается пройденным, «(продолжить)» на нём быть не должно.
        if (current >= lastStep) {
            SLMDemo.clearSavedStep(storageKey);
        } else {
            SLMDemo.setSavedStep(storageKey, current);
        }

        // Кнопка перехода к следующему туру — на последнем шаге.
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
        // Тур пройден до конца — в следующий раз начинаем с начала.
        SLMDemo.clearSavedStep(storageKey);
        finish();
    });

    intro.onexit(function () {
        // Закрыли не завершив — сохранённый шаг остаётся, тур продолжится с него.
        // Но если закрыли уже на последнем шаге — считаем пройденным.
        try {
            if (intro.getCurrentStep() >= lastStep) {
                SLMDemo.clearSavedStep(storageKey);
            }
        } catch (e) { /* getCurrentStep недоступен */ }
        finish();
    });

    // Стартуем только когда страница полностью загружена и раскладка устоялась —
    // иначе intro.js привязывает подсветку к ещё «прыгающим» элементам и тултип съезжает.
    SLMDemo.whenReady(function () {
        // Подготовить состояние формы перед первым шагом (например, раскрыть скрытое поле).
        if (hooks && typeof hooks.beforeChange === 'function' && steps.length) {
            hooks.beforeChange(steps[0].element || null);
        }

        if (typeof ifCanStartIntro !== 'function' || ifCanStartIntro()) {
            // Возобновление и refresh делаем ПОСЛЕ того, как start() завершится. В этой сборке
            // intro.start()/goToStep()/refresh() асинхронны (возвращают промис). Если дёргать
            // goToStep/refresh, пока start() ещё строит шаги, на тяжёлом admin-DOM возникает гонка:
            // внутренний current-item на миг undefined → «Uncaught (in promise) … n is undefined».
            // На лёгком фронте start() успевает — поэтому там не воспроизводилось.
            var swallow = function (p) { if (p && typeof p.catch === 'function') { p.catch(function () {}); } };

            var afterStart = function () {
                // Возобновление: если прошлый раз тур закрыли на шаге N — продолжаем с него.
                var saved = SLMDemo.getSavedStep(storageKey);
                if (saved > 0 && saved <= lastStep) {
                    try { swallow(intro.goToStep(saved + 1)); } catch (e) { /* goToStep недоступен */ }
                }

                // Прокручиваем текущий (первый/возобновлённый) шаг под его позицию и пересчитываем тултип.
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
/** Вкладка Dashboard — воронка купонов и динамика по дням. */
function loadTutorialDashboard() {
    var steps = [
        SLMDemo.step(
            '.panel.page-content .panel-heading',
            'Dashboard — результаты работы купонов',
            'Здесь собрана общая картина: сколько купонов создано, по скольким отправлены письма, сколько использовано, просрочено или отменено. По этим показателям можно быстро оценить, как работают правила и письма.',
            'bottom'
        ),
        SLMDemo.step(
            '.snod-dash-cards',
            'Coupon funnel — воронка купонов',
            'Каждая карточка показывает, сколько купонов достигло этого этапа:'
                + '<ul style="margin:4px 0 0; padding-left:18px;">'
                + '<li><b>Generated</b> — купон создан после срабатывания правила.</li>'
                + '<li><b>Emailed</b> — письмо с купоном успешно отправлено.</li>'
                + '<li><b>Reminded</b> — отправлено хотя бы одно напоминание.</li>'
                + '<li><b>Used</b> — покупатель применил купон в новом заказе.</li>'
                + '<li><b>Expired</b> — срок действия купона закончился.</li>'
                + '<li><b>Canceled</b> — купон отменён и больше недоступен.</li>'
                + '</ul>'
                + 'Проценты считаются от <b>Generated</b>. Этапы могут пересекаться, поэтому их проценты не обязаны складываться в 100%.',
            'bottom'
        ),
        SLMDemo.step(
            '.snod-dash-conversion',
            'Conversion — конверсия',
            'Доля купонов, которые покупатели использовали в следующем заказе, от всех созданных купонов. Показатель помогает понять, какая доля выданных купонов привела к повторной покупке.',
            'top'
        ),
        SLMDemo.step(
            '.snod-dash-chart-wrap',
            'Daily dynamics — динамика по дням',
            'График показывает за последние 30 дней, сколько купонов было создано, по скольким успешно отправлено письмо и сколько было использовано. Линии можно скрывать кликом по легенде; если данных за период нет, график не отображается.',
            'top'
        )
    ];

    SLMDemo.run(steps, { id: 'tutorialRules', label: 'Дальше: Discount rules →' });
}

/** Вкладка Rules — таблица правил. */
function loadTutorialRules() {
    var steps = [
        SLMDemo.step(
            '.panel.page-content .panel-heading',
            'Discount rules — правила выдачи купонов',
            'Здесь настраиваются правила выдачи купонов. Каждое правило определяет, какой заказ должен сработать и какую скидку получит покупатель на следующий заказ. Правила проверяются по приоритету сверху вниз.',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-rules-table thead',
            'Столбцы таблицы',
            'Каждая строка — одно правило:'
                + '<ul style="margin:4px 0 0; padding-left:18px;">'
                + '<li><b>Priority</b> — приоритет и стрелки перемещения; в этом порядке правила проверяются.</li>'
                + '<li><b>Name</b> — название, рядом бейджи <i>Stop</i> и напоминаний.</li>'
                + '<li><b>Discount</b> — итог скидки (10%, 15 €, Free shipping).</li>'
                + '<li><b>Validity</b> — срок действия купона в днях.</li>'
                + '<li><b>Trigger statuses</b> — статусы заказа, на которые срабатывает (или Any status).</li>'
                + '<li><b>Conditions</b> — бейджи активных условий.</li>'
                + '</ul>',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-rules-table tbody tr td .icon-arrow-down, #snod-rules-table tbody tr td .icon-arrow-up',
            'Priority — приоритет и порядок',
            'Стрелки ▲ ▼ сдвигают правило выше/ниже. Правила проверяются сверху вниз — порядок важен, когда у правила включён <b>Stop after this rule</b> или когда более специфичное правило должно сработать раньше общего. Приоритеты автоматически идут 1..N.',
            'right'
        ),
        SLMDemo.step(
            '#snod-rules-table .prestashop-switch',
            'Active — включение правила',
            'Быстрый переключатель прямо в строке. Выключенное правило не участвует в выдаче купонов.',
            'left'
        ),
        SLMDemo.step(
            '#snod-rules-table .btn-group',
            'Edit / Delete — действия',
            'Карандаш открывает форму редактирования правила. Корзина удаляет правило (с подтверждением). Уже выданные по нему купоны в таблице Coupons остаются.',
            'left'
        ),
        SLMDemo.step(
            '#page-header-desc-configuration-new_rule',
            'Add a rule — новое правило',
            'Кнопка в шапке страницы создаёт новое правило и открывает форму его настройки.',
            'bottom'
        )
    ];

    SLMDemo.run(steps, { id: 'tutorialRuleEdit', label: 'Дальше: создание правила →' });
}

/** Форма правила (Rule) — вкладки General / Conditions / Code / Email. */
function loadTutorialRuleEdit() {
    // Форма разбита на bootstrap-вкладки (панели display:none, пока не активны).
    // Перед подсветкой поля тур сам активирует нужную под-вкладку по её .tab-pane.
    var PANES = ['general', 'conditions', 'code', 'email'];
    function activatePane(key) {
        PANES.forEach(function (k) {
            var pane = document.getElementById('snod-tab-' + k);
            var link = document.querySelector('.nav-tabs a[href="#snod-tab-' + k + '"]');
            var li = link && link.parentElement;
            var on = (k === key);
            if (pane) { pane.classList.toggle('active', on); pane.classList.toggle('in', on); }
            if (li) { li.classList.toggle('active', on); }
        });
    }
    function paneKeyOf(target) {
        if (!target || typeof target.closest !== 'function') { return null; }
        var pane = target.closest('.tab-pane');
        if (!pane || !pane.id) { return null; }
        return pane.id.replace('snod-tab-', '');
    }

    var steps = [
        SLMDemo.step(
            '.defaultForm .panel.page-content > .nav.nav-tabs',
            'Форма правила',
            'Одно правило объединяет условия срабатывания и скидку для следующего заказа. Настройки разделены на четыре вкладки: <b>General</b>, <b>Conditions</b>, <b>Code</b> и <b>Email</b>. Кнопки Save и Cancel находятся внизу формы.',
            'bottom'
        ),

        // ---------- General ----------
        SLMDemo.stepRow(
            '[name="snod_rule_name"]',
            'Rule name — название правила',
            'Внутреннее название правила. Оно отображается в таблице Discount rules и помогает администратору различать правила. Обязательное поле; покупатель это название не видит.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_voucher_name"]',
            'Voucher name — название купона',
            'Название, которое покупатель увидит у купона. Если оставить поле пустым, будет использовано название <b>Next Order Discount</b>.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_voucher_description"]',
            'Voucher description — описание купона',
            'Необязательное описание, которое сохраняется в купоне и видно в админке. Оставьте поле пустым, если отдельное описание не нужно.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '#snod-discount-type',
            'Discount type — тип скидки',
            'Три типа: <b>Percentage (%)</b> — процент от суммы следующего заказа; <b>Fixed amount</b> — фиксированная сумма; <b>Free shipping</b> — бесплатная доставка. От выбора зависит подпись у поля значения.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '#snod-discount-value-group',
            'Discount value — величина скидки',
            'Для процента ограничена 100; для бесплатной доставки игнорируется. Подпись справа (% или знак валюты) меняется под тип скидки.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_validity_days"]',
            'Validity period — срок действия',
            'Сколько дней покупатель сможет использовать выданный купон. Укажите целое число не меньше 1.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_next_min"]',
            'Minimum next order amount — минимальная сумма',
            'Минимальная сумма следующего заказа, при которой купон можно применить. Ограничение относится именно к новому заказу, а не к заказу, за который был выдан купон. Значение <b>0</b> отключает минимальную сумму.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '#snod_rule_stop_on',
            'Stop after this rule — остановиться',
            'Если Yes — после срабатывания этого правила остальные не проверяются, покупатель получит один купон. Если No — другие подходящие правила тоже смогут выдать свои купоны.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '#snod_rule_reminder_on',
            'Send reminders — напоминания',
            'Включить письма-напоминания о неиспользованном купоне. Они прекращаются сами, когда купон использован или просрочен.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_reminder_basis"]',
            'Reminder timing — отсчёт напоминаний',
            'От чего считать дни: <b>Days after the coupon email</b> — через N дней после купонного письма; <b>Days before the coupon expires</b> — за N дней до истечения. Ниже — First / Second reminder (дни; 0 или пусто = это напоминание не отправляется).',
            'bottom'
        ),

        // ---------- Conditions ----------
        SLMDemo.stepRow(
            '[name="snod_rule_statuses[]"]',
            'Trigger on order statuses — триггерные статусы',
            'Выберите статусы, в которых заказ может выдать купон. Проверка выполняется при создании заказа и при каждом изменении его статуса. Если список пуст, статус не ограничивает правило. Для выбора нескольких статусов удерживайте Ctrl/Cmd. Подходящий статус сам по себе не гарантирует выдачу: заказ должен соответствовать и остальным условиям правила.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '.snod-cond-mode',
            'Списочные условия — режим All / Include / Exclude',
            'Пять списков: <b>Customer groups</b>, <b>Countries</b>, <b>Currencies</b>, <b>Product categories</b>, <b>Brands</b>. У каждого свой режим: <b>All</b> — не ограничивать; <b>Only the selected</b> — только выбранные; <b>All except the selected</b> — все, кроме выбранных. Категории и бренды смотрят на товары заказа-источника.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_source_min"]',
            'Source order total — сумма заказа-источника',
            'Диапазон суммы заказа (Min / Max), при котором срабатывает правило. 0 = без ограничения по этой границе.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_date_from"]',
            'Active date window — окно активности',
            'Даты From / To, когда правило активно. Оба пусто = правило активно всегда.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_order_count_min"]',
            'Customer order number — номер заказа покупателя',
            'Сколько валидных заказов должно быть у покупателя (Min / Max). Оба значения 1 = только первый заказ. 0 = без ограничения.',
            'bottom'
        ),

        // ---------- Code ----------
        SLMDemo.stepRow(
            '[name="snod_rule_code_length"]',
            'Key length — длина ключа',
            'Число случайных символов в %key% (ограничивается диапазоном 4–32). Любое поле вкладки можно оставить пустым — тогда берётся встроенное значение по умолчанию.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_code_type"]',
            'Key type — набор символов',
            '<b>Alphabetic (A-Z)</b> — только буквы; <b>Numeric (0-9)</b> — только цифры; <b>Alphanumeric</b> — буквы и цифры.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_rule_code_template"]',
            'Key template — шаблон кода',
            'Шаблон с плейсхолдером %key%. Пример: <code>NOD-%key%</code> → <code>NOD-AB12CD8X</code>.',
            'bottom'
        ),

        // ---------- Email ----------
        SLMDemo.step(
            '.snod-email-block',
            'Email — письма правила',
            'У каждого правила свои письма, предзаполненные шаблоном по умолчанию. Три типа: <b>Coupon email</b> (при выдаче купона), <b>First reminder</b>, <b>Second reminder</b>. У каждого — переключатель языка (тема и HTML отдельно на каждый язык магазина), поле Subject и HTML content.',
            'top'
        ),
        SLMDemo.step(
            '.snod-ph-chips',
            'Плейсхолдеры',
            'Подставляются при отправке. Клик по чипу вставляет его в HTML: {coupon_code}, {coupon_value}, {valid_to}, {minimum_amount}, {customer_firstname}, {shop_name}, {shop_logo} и др.',
            'top'
        ),
        SLMDemo.step(
            '.snod-preview-btn',
            'Preview / Send test email',
            '<b>Preview</b> — предпросмотр письма с примерными значениями (в окне). <b>Send test email</b> — отправка тестовой копии на указанный адрес. Удобно проверить вёрстку до боевой отправки.',
            'top'
        ),
        SLMDemo.step(
            '.panel-footer',
            'Save / Cancel',
            '<b>Save</b> проверяет и сохраняет правило, возвращает к списку Rules (при ошибках форма остаётся открытой с подсказками). <b>Cancel</b> — назад без сохранения.',
            'top'
        )
    ];

    SLMDemo.run(
        steps,
        { id: 'tutorialCoupons', label: 'Дальше: Coupons →' },
        {
            beforeChange: function (target) {
                var key = paneKeyOf(target);
                // Шаг «Форма правила» подсвечивает панель вкладок — оставляем General.
                if (!key) {
                    if (target && typeof target.closest === 'function' && target.closest('.panel-footer')) {
                        return; // футер — вне панелей, вкладку не трогаем
                    }
                    key = 'general';
                }
                activatePane(key);
            }
        }
    );
}

/** Demo Order Generator — создание реального тестового заказа на свой email. */
function loadTutorialOrderGenerator() {
    function tabLink(id, label) {
        var el = document.getElementById(id);
        var href = el ? el.getAttribute('href') : '';
        if (!href) { return SLMDemo.tr(label); }
        var sep = href.indexOf('?') > -1 ? '&' : '?';
        return '<a href="' + href + sep + 'set_demo_tutorial=' + id + '">' + SLMDemo.tr(label) + '</a>';
    }

    var rulesLink = tabLink('tutorialRules', 'Discount rules');
    var couponsLink = tabLink('tutorialCoupons', 'Coupons');
    var cronLink = tabLink('tutorialCronTools', 'Cron/Tools');
    var steps = [
        SLMDemo.step(
            '.alert.alert-info',
            'Demo Order Generator — быстрый end-to-end тест',
            'Здесь можно создать <b>настоящий тестовый заказ</b> через штатный API PrestaShop.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="sdog_email"]',
            'Ваш email',
            'Укажите <b>ящик, к которому у вас есть доступ</b>: на него могут прийти подтверждение заказа и письмо с купоном. Если клиент с таким email уже есть, он будет использован повторно — так можно тестировать условия по номеру заказа.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="sdog_target_total"]',
            'Точная сумма заказа',
            'Задайте итоговую сумму с налогами и доставкой. Генератор подберёт количество товара и временную скидку, чтобы получилась именно эта сумма. Она должна попадать в диапазон активного правила.',
            'bottom'
        ),
        SLMDemo.fill(SLMDemo.step(
            'form.defaultForm .form-wrapper',
            'Товар и данные покупателя',
            'Выберите товар, валюту, страну, группу и язык письма. Категория и бренд товара, а также остальные значения должны совпадать с условиями в {rules}.',
            'bottom'
        ), { rules: rulesLink }),
        SLMDemo.stepRow(
            '[name="sdog_id_order_state"]',
            'Статус заказа',
            'Выберите статус, указанный как триггер в правиле. Генератор создаст заказ сразу в этом статусе, поэтому вручную менять его не нужно.',
            'bottom'
        ),
        SLMDemo.fill(SLMDemo.step(
            '#set_demo_order_generator_form_submit_btn',
            'Создать и проверить',
            'Закройте тур и нажмите <b>Create test order</b>. Сверху появятся ссылка на заказ и код купона. Купон также виден в {coupons}. Если письмо не пришло, откройте {cron} и нажмите <b>Run all tasks now</b>.',
            'top'
        ), { coupons: couponsLink, cron: cronLink })
    ];

    SLMDemo.run(steps, { id: 'tutorialFront', label: 'Дальше: как это видит покупатель →' });
}

/** Вкладка Coupons — выданные купоны. */
function loadTutorialCoupons() {
    var steps = [
        SLMDemo.step(
            '#snod-coupons .panel-heading',
            'Coupons — выданные купоны',
            'Список выданных купонов. Здесь можно проверить код, покупателя, заказ-источник, правило, статус и срок действия, а также повторно отправить письмо или напоминание.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_filter_status"]',
            'Фильтры',
            '<b>Status</b> — фильтр по статусу купона (created / emailed / reminded / used / expired / canceled) или «All statuses». <b>Code</b> — поиск по коду. Список постраничный (30 на страницу).',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-coupons table thead',
            'Столбцы таблицы',
            '<b>Code</b> — код купона; <b>Customer</b> — имя и e-mail покупателя; <b>Source order</b> — заказ-источник; <b>Rule</b> — правило; <b>Status</b> — статус (цветной бейдж) + бейджи 1/2 отправленных напоминаний; <b>Valid until</b> — срок действия; <b>Generated</b> — дата создания.',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-coupons .btn-group',
            'Resend / напоминания',
            'Пока купон ещё можно использовать (не used / expired / canceled): <b>Resend</b> — повторно отправить купонное письмо; <b>1</b> / <b>2</b> — отправить напоминание №1 или №2 немедленно (появляются, если у правила купона включены соответствующие напоминания).',
            'left'
        )
    ];

    SLMDemo.run(steps, { id: 'tutorialSettings', label: 'Дальше: Settings →' });
}

/** Вкладка Settings — общие настройки. */
function loadTutorialSettings() {
    var steps = [
        SLMDemo.stepRow(
            '#snod_enabled_on',
            'Module active — общий выключатель',
            'Если <b>No</b>, купоны для новых заказов не выдаются, независимо от правил. Сами скидки и условия задаются во вкладке Rules, не здесь.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_cancel_statuses[]"]',
            'Cancel coupon on order statuses — отмена купона',
            'Статусы заказа, при переходе в которые выданный этим заказом купон отменяется (деактивируется и помечается canceled), а новый по этому заказу не создаётся. По умолчанию — «Отменён» и «Возврат». Пусто = никогда не отменять автоматически.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '#snod_debug_mode_on',
            'Debug mode — режим отладки',
            'Подробное логирование для диагностики (влияет на глубину вкладки Logs). В продакшене держите выключенным.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_log_retention_days"]',
            'Keep logs for — срок хранения журнала',
            'Сколько дней хранить записи журнала; более старые удаляются автоматически (во время cron). 0 = хранить бессрочно.',
            'bottom'
        ),
        SLMDemo.step(
            '.panel-footer',
            'Save — применение',
            'Нажмите Save, чтобы применить. Настройки сохраняются в контексте текущего магазина (мультимагазин).',
            'top'
        )
    ];

    SLMDemo.run(steps, { id: 'tutorialCronTools', label: 'Дальше: Cron/Tools →' });
}

/** Вкладка Cron/Tools — фоновые задачи. */
function loadTutorialCronTools() {
    var steps = [
        SLMDemo.step(
            '#snod-cron-tools .alert-info',
            'Зачем нужен cron',
            'После создания купона модуль сразу пытается отправить основное письмо. Если отправка не удалась, письмо попадает в очередь, а cron повторяет попытку. Cron также планирует и отправляет напоминания и переводит купоны с истёкшим сроком в <b>expired</b>. Рекомендуемый запуск — одна HTTP-строка в crontab с интервалом 5 минут.',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-cron-install-box',
            'One-click install',
            'Если на сервере доступна автоматическая настройка crontab, кнопка <b>Install cron automatically</b> добавит нужную строку, а <b>Remove cron</b> удалит её. Строка помечается маркерами и также удаляется при удалении модуля. Если автоустановка недоступна (например, shell_exec заблокирован), используйте строки ниже.',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-cron-tools .form-group input[readonly]',
            'Crontab / внешний cron',
            'Готовые строки для ручной вставки в crontab (curl и wget) и отдельный URL для внешнего web-cron сервиса (например cron-job.org) с интервалом 5 минут. <b>Держите токен в секрете</b>: любой, кто знает URL, может запустить задачу.',
            'bottom'
        ),
        SLMDemo.step(
            '.snod-run-task[data-task="all"]',
            'Run all tasks now',
            'Ручной запуск всех задач сразу — быстрая проверка, что эндпоинт работает. Ниже (Your server) — проба окружения: версия PHP, наличие curl (CLI) и доступность shell_exec.',
            'bottom'
        ),
        SLMDemo.step(
            '#snod-cron-tools table',
            'Tasks — задачи и их здоровье',
            'Фоновые задачи, расписание, время последнего запуска, персональный URL, блокировка и ручной запуск:'
                + '<ul style="margin:4px 0 0; padding-left:18px;">'
                + '<li><b>Process the dispatch queue</b> — повторяет неудавшуюся отправку основного письма и отправляет запланированные напоминания. Каждые 5 минут.</li>'
                + '<li><b>Plan coupon reminders</b> — находит напоминания, срок которых наступил, и добавляет их в очередь. Каждые 30 минут.</li>'
                + '<li><b>Expire lapsed coupons</b> — просрочивает купоны. Раз в день.</li>'
                + '</ul>'
                + '<b>Last run</b>: OK / Late / Not running / Never run. <b>Lock</b>: Running / Free (не даёт двум запускам пересечься).',
            'top'
        ),
        SLMDemo.step(
            '#snod-cron-tools .snod-targeting-badges',
            'Dispatch queue — очередь отправки',
            'Состояние фоновых задач на отправку: <b>Pending</b> — ожидают, <b>Processing</b> — выполняются, <b>Done</b> — завершены, <b>Failed</b> — завершились с ошибкой. Растущий Pending или Failed — повод проверить cron и настройки почты.',
            'top'
        )
    ];

    SLMDemo.run(steps, { id: 'tutorialLogs', label: 'Дальше: Logs →' });
}

/** Вкладка Logs — журнал событий. */
function loadTutorialLogs() {
    // «вкладка Settings» в тексте — ссылка на её тур.
    var el = document.getElementById('tutorialSettings');
    var href = el ? el.getAttribute('href') : '';
    var settingsLabel = SLMDemo.tr('вкладка Settings');
    if (href) {
        var sep = href.indexOf('?') > -1 ? '&' : '?';
        settingsLabel = '<a href="' + href + sep + 'set_demo_tutorial=tutorialSettings">' + SLMDemo.tr('вкладка Settings') + '</a>';
    }

    var steps = [
        SLMDemo.step(
            '.panel.page-content h3',
            'Logs — журнал событий',
            'Журнал событий модуля: выдача купонов, отправка писем, ошибки хуков и т.д. Помогает понять, что произошло с конкретным заказом или письмом.',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_log_level"]',
            'Level — уровень',
            'Фильтр по уровню записи: debug / info / warning / error (или «All»).',
            'bottom'
        ),
        SLMDemo.stepRow(
            '[name="snod_log_channel"]',
            'Channel — канал',
            'Фильтр по каналу события (например cron, queue, coupon).',
            'bottom'
        ),
        SLMDemo.step(
            '.panel.page-content table thead',
            'Столбцы',
            '<b>Date</b> — время; <b>Level</b> — уровень (цветной бейдж); <b>Channel</b> — канал; <b>Message</b> — сообщение с деталями контекста; <b>Correlation</b> — id для связывания записей одного события.',
            'bottom'
        ),
        SLMDemo.fill(SLMDemo.step(
            null,
            'Глубина и хранение',
            'Глубина логирования зависит от <b>Debug mode</b>, а срок хранения — от <b>Keep logs for</b> (обе настройки — {settings}).',
            'bottom'
        ), { settings: settingsLabel })
    ];

    SLMDemo.run(steps, { id: 'tutorialOrderGenerator', label: 'Дальше: тестовый заказ →' });
}
