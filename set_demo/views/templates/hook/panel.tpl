<section class="set-tutorials-panel">
  <header class="set-tutorials-panel__header js-set-tutorials-close-tutorials-panel">
    <h3 class="set-tutorials-panel__title" data-i18n="title">{$set_demo_i18n.title|escape:'htmlall':'UTF-8'}</h3>
    <div class="set-tutorials-lang" role="group" aria-label="Language">
{*      <button type="button" class="set-tutorials-lang__btn" data-lang="en">EN</button>*}
{*      <button type="button" class="set-tutorials-lang__btn" data-lang="fr">FR</button>*}
      <button type="button" class="set-tutorials-lang__btn" data-lang="ru">RU</button>
    </div>
    <button type="button" class="set-tutorials-panel__close" data-i18n-title="close" data-i18n="close" title="{$set_demo_i18n.close|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.close|escape:'htmlall':'UTF-8'}</button>
  </header>

  <div class="set-tutorials-panel__content">
    <div class="set-tutorials-panel__header_talk set-tutorial-talk">
      <p class="set-tutorial-talk__text" data-i18n="talk_text">{$set_demo_i18n.talk_text|escape:'htmlall':'UTF-8'}</p>
      <p class="set-tutorial-talk__action-wrapper">
        <a href="{$set_demo_support_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">
          <button class="set-tutorial-talk__action" type="button" data-i18n="talk_action">{$set_demo_i18n.talk_action|escape:'htmlall':'UTF-8'}</button>
        </a>
      </p>
    </div>

    <p class="set-tutorials-panel__description" data-i18n="description">{$set_demo_i18n.description|escape:'htmlall':'UTF-8'}</p>

    <div class="set-tutorials-group">
      <h4 class="set-tutorials-group__title" data-i18n="group_admin">{$set_demo_i18n.group_admin|escape:'htmlall':'UTF-8'}</h4>
      <ul class="set-tutorials-group__list">
        <li class="set-tutorials-group__item">
          <a id="tutorialDashboard" class="set-tutorials-group__link" data-i18n="link_dashboard" href="{$set_demo_slm_url_dashboard|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_dashboard|escape:'htmlall':'UTF-8'}</a>
        </li>
        <li class="set-tutorials-group__item">
          <a id="tutorialRules" class="set-tutorials-group__link" data-i18n="link_rules" href="{$set_demo_slm_url_rules|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_rules|escape:'htmlall':'UTF-8'}</a>
        </li>
        <li class="set-tutorials-group__item">
          <a id="tutorialRuleEdit" class="set-tutorials-group__link" data-i18n="link_rule_edit" href="{$set_demo_slm_url_rule_edit|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_rule_edit|escape:'htmlall':'UTF-8'}</a>
        </li>
        <li class="set-tutorials-group__item">
          <a id="tutorialCoupons" class="set-tutorials-group__link" data-i18n="link_coupons" href="{$set_demo_slm_url_coupons|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_coupons|escape:'htmlall':'UTF-8'}</a>
        </li>
        <li class="set-tutorials-group__item">
          <a id="tutorialSettings" class="set-tutorials-group__link" data-i18n="link_settings" href="{$set_demo_slm_url_settings|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_settings|escape:'htmlall':'UTF-8'}</a>
        </li>
        <li class="set-tutorials-group__item">
          <a id="tutorialCronTools" class="set-tutorials-group__link" data-i18n="link_cron_tools" href="{$set_demo_slm_url_cron_tools|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_cron_tools|escape:'htmlall':'UTF-8'}</a>
        </li>
        <li class="set-tutorials-group__item">
          <a id="tutorialLogs" class="set-tutorials-group__link" data-i18n="link_logs" href="{$set_demo_slm_url_logs|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_logs|escape:'htmlall':'UTF-8'}</a>
        </li>
      </ul>
    </div>

    <div class="set-tutorials-group">
      <h4 class="set-tutorials-group__title" data-i18n="group_front">{$set_demo_i18n.group_front|escape:'htmlall':'UTF-8'}</h4>
      <ul class="set-tutorials-group__list">
        <li class="set-tutorials-group__item">
          <a id="tutorialFront" class="set-tutorials-group__link" data-i18n="link_front" href="{$set_demo_front_url|escape:'htmlall':'UTF-8'}">{$set_demo_i18n.link_front|escape:'htmlall':'UTF-8'}</a>
        </li>
      </ul>
    </div>

    <div class="set-tutorials-group buy-button-wrapper">
      <h4 class="set-tutorials-group__title buy-button-title" data-i18n="buy_title">{$set_demo_i18n.buy_title|escape:'htmlall':'UTF-8'}</h4>
      <button
        id="configure-and-buy-button"
        type="button"
        data-i18n="buy_button"
        onclick="window.open('{$set_demo_product_link|escape:'htmlall':'UTF-8'}', '_blank');"
        class="product__buy-button button2 button2--orange"
      >
        {$set_demo_i18n.buy_button|escape:'htmlall':'UTF-8'}
      </button>
    </div>
  </div>
</section>

<div class="set-demo-controls">
  <button class="set-demo-controls__button set-demo-controls__button--tutorials js-set-tutorials-open-tutorials-panel" type="button" style="display: none">
    <svg class="set-icon-bulb" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.13 2 5 5.13 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26C17.81 13.47 19 11.38 19 9c0-3.87-3.13-7-7-7z"/>
    </svg>
    <span data-i18n="title">{$set_demo_i18n.title|escape:'htmlall':'UTF-8'}</span>
  </button>
</div>

<script>
(function () {
  var DICT = {$set_demo_i18n_json nofilter};
  var INITIAL = '{$set_demo_i18n_lang|escape:'javascript':'UTF-8'}';
  var STORE_KEY = 'setDemoPanelLang';

  var roots = [
    document.querySelector('.set-tutorials-panel'),
    document.querySelector('.set-demo-controls')
  ].filter(Boolean);
  if (!roots.length) { return; }

  var currentLang = 'en';

  // Пометка у пунктов: «(продолжить)» — если тур был закрыт не до конца (в sessionStorage
  // сохранён шаг > 0), иначе «(start)». Ключи совпадают с tutorial_data_*.js. Последний шаг
  // сам сбрасывается в туре, поэтому сохранённый шаг всегда означает незавершённый тур.
  function savedStep(key) {
    try { var v = parseInt(sessionStorage.getItem(key), 10); return isNaN(v) ? 0 : v; } catch (e) { return 0; }
  }
  function tourKeyForLink(link) {
    var href = link.getAttribute('href') || '';
    if (link.id === 'tutorialFront') {
      try { return 'slm_tour_step_front' + new URL(href, location.origin).pathname; } catch (e) { return null; }
    }
    try {
      var tab = new URL(href, location.origin).searchParams.get('tab');
      return tab ? ('slm_tour_step_' + tab) : null;
    } catch (e) { return null; }
  }
  // Ключ тура текущей страницы (активной вкладки).
  function currentPageKey() {
    try {
      var tab = new URL(location.href).searchParams.get('tab');
      if (tab) { return 'slm_tour_step_' + tab; }
    } catch (e) {}
    try { return 'slm_tour_step_front' + location.pathname; } catch (e) { return null; }
  }
  function markResume(lang) {
    var d = DICT[lang] || DICT.en || {};
    var resumeWord = d.resume || 'resume';
    var startWord = d.start || 'start';
    var activeKey = currentPageKey();
    document.querySelectorAll('.set-tutorials-group__link[id^="tutorial"]').forEach(function (link) {
      var old = link.querySelector('.set-tutorials-resume');
      if (old) { old.parentNode.removeChild(old); }
      var key = tourKeyForLink(link);
      // Метка — только у пункта активной вкладки (текущей страницы).
      if (!key || key !== activeKey) { return; }
      var resume = savedStep(key) > 0;
      var span = document.createElement('span');
      span.className = 'set-tutorials-resume ' + (resume ? 'set-tutorials-resume--resume' : 'set-tutorials-resume--start');
      span.textContent = ' (' + (resume ? resumeWord : startWord) + ')';
      link.appendChild(span);
    });
  }

  function applyLang(lang) {
    currentLang = lang;
    var d = DICT[lang] || DICT.en;
    if (!d) { return; }
    roots.forEach(function (root) {
      root.querySelectorAll('[data-i18n]').forEach(function (el) {
        var k = el.getAttribute('data-i18n');
        if (d[k] != null) { el.textContent = d[k]; }
      });
      root.querySelectorAll('[data-i18n-title]').forEach(function (el) {
        var k = el.getAttribute('data-i18n-title');
        if (d[k] != null) { el.setAttribute('title', d[k]); }
      });
    });
    document.querySelectorAll('.set-tutorials-lang__btn').forEach(function (b) {
      b.classList.toggle('set-tutorials-lang__btn--active', b.getAttribute('data-lang') === lang);
    });
    // Метки «(продолжить)» затираются при смене текста ссылок — навешиваем заново.
    markResume(lang);
    try { localStorage.setItem(STORE_KEY, lang); } catch (e) {}
  }

  var stored = null;
  try { stored = localStorage.getItem(STORE_KEY); } catch (e) {}
  var start = (stored && DICT[stored]) ? stored : (DICT[INITIAL] ? INITIAL : 'en');

  document.querySelectorAll('.set-tutorials-lang__btn').forEach(function (b) {
    b.addEventListener('click', function (ev) {
      ev.preventDefault();
      ev.stopPropagation();
      applyLang(b.getAttribute('data-lang'));
    });
  });

  // Тур при закрытии/завершении шлёт это событие — обновляем метки без перезагрузки.
  document.addEventListener('slm-demo-resume-refresh', function () { markResume(currentLang); });

  applyLang(start);
})();
</script>
