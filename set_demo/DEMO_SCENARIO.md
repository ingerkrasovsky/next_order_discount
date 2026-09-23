# Сценарий демо — Next Order Discount

Документ описывает демонстрационный модуль **set_demo** для модуля **next_order_discount**: как
устроена демка и что показывает каждый шаг тура. В таблицах: **Элемент** — что подсвечивается на
шаге (CSS-селектор; `—` = шаг информационный, плавающий, без подсветки), **Текст шага** — что видит
посетитель. Источник текстов — `views/js/tutorial_data_admin.js`, `views/js/tutorial_data_front.js`
(основаны на `../guides/GUIDE_RU.md`).

> Демка скопирована с демо-модуля Loyalty Milestones. Имя модуля, класс и префиксы
> (`set_demo`, `SET_DEMO_*`) сохранены; под next_order_discount переделаны **SEO-лендинг**,
> **навигационная панель** и **туры**. Доступны английский, французский, немецкий, польский
> и испанский языки; английский используется по умолчанию.

---

## 1. Как устроена демка

Демка накладывается поверх обычного магазина — и на **витрине**, и в **админке** — единой панелью
«Навигация по демо».

- **Свёрнутая кнопка** — вертикальная вкладка у правого края. Клик разворачивает панель.
- **Панель навигации** — список разделов, сгруппированный на «Админка» и «Для покупателя».
- **Язык** — EN / FR / DE / PL / ES. Выбор сохраняется в `localStorage`; русский используется
  только как внутренний ключ исходных строк и в переключателе не показывается. Полный каталог
  переводов находится в `tutorial_i18n.js`.
- **Подсветка текущего пункта** — если открытая страница соответствует пункту меню, пункт
  подсвечивается: здесь можно запустить тур.
- **Запуск тура** — клик по пункту переводит на нужную вкладку (`&tab=...`) и **автоматически
  запускает пошаговый тур** (intro.js). На последнем шаге — кнопка **«Дальше: <следующий раздел> →»**.
- **Возобновление (resume)** — при закрытии не пройденного тура его шаг сохраняется (sessionStorage,
  ключ по вкладке); при повторном открытии на той же странице тур продолжается с того же шага.
- **Автологин** — на странице входа в админку подставляются демо-креды (`demo@demo.com` / `demodemo`),
  токены админки в демо-магазине отключены, поэтому ссылки строятся в виде
  `?controller=NextOrderDiscount&tab=...` без токена (это позволяет туру автостартовать после перехода
  фронт→админка).

### Полный маршрут (цепочка туров)

```
Dashboard → Discount rules → Создание правила (Rule) → Coupons → Settings → Cron/Tools → Logs →
Demo Order Generator → Для покупателя (поп-ап с тестовым сценарием) → Dashboard → …
```

Маршрут **цикличный**: с последнего шага поп-апа кнопка ведёт обратно на Dashboard. Каждый тур
можно запустить и по отдельности из панели.

Соответствие «пункт панели → загрузчик → вкладка»:

| id ссылки в панели | загрузчик | вкладка (`tab`) |
|---|---|---|
| `tutorialDashboard` | `loadTutorialDashboard` | `dashboard` |
| `tutorialRules` | `loadTutorialRules` | `rules` |
| `tutorialRuleEdit` | `loadTutorialRuleEdit` | `rule_edit&id_rule=0` |
| `tutorialCoupons` | `loadTutorialCoupons` | `coupons` |
| `tutorialSettings` | `loadTutorialSettings` | `settings` |
| `tutorialCronTools` | `loadTutorialCronTools` | `cron_tools` |
| `tutorialLogs` | `loadTutorialLogs` | `logs` |
| `tutorialOrderGenerator` | `loadTutorialOrderGenerator` | `controller=DemoOrderGenerator` |
| `tutorialFront` | `loadTutorialFront` | витрина (поп-ап) |

---

## 2. Админка — пошагово

### 2.1. Dashboard — воронка купонов → *дальше: Discount rules*

| Элемент | Текст шага |
|---|---|
| `.panel.page-content .panel-heading` | **Dashboard.** Общая картина по созданным, отправленным, использованным, просроченным и отменённым купонам. |
| `.snod-dash-cards` | **Coupon funnel.** Карточки Generated, Emailed, Reminded, Used, Expired и Canceled; проценты считаются от Generated, а этапы могут пересекаться. |
| `.snod-dash-conversion` | **Conversion.** Доля купонов, использованных в следующем заказе, от всех созданных. |
| `.snod-dash-chart-wrap` | **Daily dynamics.** Generated, Emailed и Used по дням за последние 30 дней; при отсутствии данных график не выводится. |

### 2.2. Discount rules — таблица правил → *дальше: создание правила*

| Элемент | Текст шага |
|---|---|
| `.panel.page-content .panel-heading` | **Discount rules.** Каждое правило определяет условия срабатывания и скидку для следующего заказа. |
| `#snod-rules-table thead` | **Столбцы:** Priority, Name (бейджи Stop/напоминаний), Discount, Validity, Trigger statuses, Conditions. |
| `#snod-rules-table … .icon-arrow-down/up` | **Priority.** Стрелки ▲▼; проверка сверху вниз; важно при Stop after this rule. |
| `#snod-rules-table .prestashop-switch` | **Active.** Быстрый переключатель правила. |
| `#snod-rules-table .btn-group` | **Edit / Delete.** Редактировать / удалить (выданные купоны остаются). |
| `#page-header-desc-configuration-new_rule` | **Add a rule.** Создаёт новое правило и открывает форму. |

### 2.3. Создание / редактирование правила (Rule) → *дальше: Coupons*

Форма разбита на четыре вкладки: **General / Conditions / Code / Email**. Каждый шаг тура
подсвечивает отдельное поле и объясняет его назначение.

| Элемент | Текст шага |
|---|---|
| `.defaultForm .panel.page-content > .nav.nav-tabs` | **Форма правила.** Четыре вкладки; внизу Save/Cancel. |
| `[name="snod_rule_name"]` | **Rule name** — внутреннее обязательное название; покупатель его не видит. |
| `[name="snod_rule_voucher_name"]` | **Voucher name** — название купона для покупателя; пустое поле даёт `Next Order Discount`. |
| `[name="snod_rule_voucher_description"]` | **Voucher description** — необязательное описание, сохраняемое в купоне. |
| `#snod-discount-type` | **Discount type** — percent / amount / free shipping. |
| `#snod-discount-value-group` | **Discount value** — величина (для % капается на 100). |
| `[name="snod_rule_validity_days"]` | **Validity period** — сколько дней можно использовать выданный купон. |
| `[name="snod_rule_next_min"]` | **Minimum next order amount** — минимальная сумма нового заказа для применения купона; 0 отключает ограничение. |
| `#snod_rule_stop_on` | **Stop after this rule.** |
| `#snod_rule_reminder_on` | **Send reminders.** |
| `[name="snod_rule_reminder_basis"]` | **Reminder timing** (+ First/Second reminder days). |
| `[name="snod_rule_statuses[]"]` | **Trigger on order statuses** — проверяются при создании заказа и при изменении статуса; пустой список не ограничивает правило. |
| `.snod-cond-mode` | **Списочные условия** — groups/countries/currencies/categories/brands в режиме All/Include/Exclude. |
| `[name="snod_rule_source_min"]` | **Source order total** (Min/Max). |
| `[name="snod_rule_date_from"]` | **Active date window** (From/To). |
| `[name="snod_rule_order_count_min"]` | **Customer order number** (1/1 = только первый заказ). |
| `[name="snod_rule_code_length"]` | **Key length** (Code). |
| `[name="snod_rule_code_type"]` | **Key type** (A-Z / 0-9 / alnum). |
| `[name="snod_rule_code_template"]` | **Key template** (`NOD-%key%`). |
| `.snod-email-block` | **Email.** Три типа писем × языки (Subject + HTML). |
| `.snod-ph-chips` | **Плейсхолдеры** ({coupon_code}, {valid_to}, …). |
| `.snod-preview-btn` | **Preview / Send test email.** |
| `.panel-footer` | **Save / Cancel.** |

### 2.4. Coupons — выданные купоны → *дальше: Settings*

| Элемент | Текст шага |
|---|---|
| `#snod-coupons .panel-heading` | **Coupons.** Список выданных купонов, их статусов и ручных действий по отправке. |
| `[name="snod_filter_status"]` | **Фильтры** — Status и Code, постранично. |
| `#snod-coupons table thead` | **Столбцы** — Code, Customer, Source order, Rule, Status (+1/2), Valid until, Generated. |
| `#snod-coupons .btn-group` | **Resend / 1 / 2** — пока купон активен (не used/expired/canceled). |

*(если купонов ещё нет, строковые селекторы отсутствуют — шаги становятся информационными.)*

### 2.5. Settings — общие настройки → *дальше: Cron/Tools*

| Элемент | Текст шага |
|---|---|
| `#snod_enabled_on` | **Module active** — общий выключатель. |
| `[name="snod_cancel_statuses[]"]` | **Cancel coupon on order statuses** — отмена купона при возврате/отмене заказа-источника. |
| `#snod_debug_mode_on` | **Debug mode.** |
| `[name="snod_log_retention_days"]` | **Keep logs for** — срок хранения журнала. |
| `.panel-footer` | **Save.** |

### 2.6. Cron/Tools — фоновые задачи → *дальше: Logs*

| Элемент | Текст шага |
|---|---|
| `#snod-cron-tools .alert-info` | **Зачем нужен cron.** Основное письмо отправляется сразу; cron повторяет неудавшуюся отправку, обрабатывает напоминания и просрочку купонов. |
| `#snod-cron-install-box` | **One-click install** (если доступно). |
| `#snod-cron-tools .form-group input[readonly]` | **Crontab / внешний cron** (curl/wget/URL; токен в секрете). |
| `.snod-run-task[data-task="all"]` | **Run all tasks now** (+ проба окружения Your server). |
| `#snod-cron-tools table` | **Tasks** — задачи, расписание, Last run (OK/Late/Not running/Never), Lock. |
| `#snod-cron-tools .snod-targeting-badges` | **Dispatch queue** — снимок очереди. |

### 2.7. Logs — журнал → *дальше: Demo Order Generator*

| Элемент | Текст шага |
|---|---|
| `.panel.page-content h3` | **Logs.** Журнал событий модуля. |
| `[name="snod_log_level"]` | **Level** — debug/info/warning/error. |
| `[name="snod_log_channel"]` | **Channel** — cron/queue/coupon… |
| `.panel.page-content table thead` | **Столбцы** — Date, Level, Channel, Message, Correlation. |
| — | **Глубина и хранение** — зависят от Debug mode и Keep logs for (ссылка на Settings). |

### 2.8. Demo Order Generator — заказ на свой email → *дальше: для покупателя*

Это отдельный демо-модуль `set_demo_order_generator`. Он создаёт настоящий заказ через
`PaymentModule::validateOrder()`.

| Элемент | Текст шага |
|---|---|
| `.alert.alert-info` | **Быстрый end-to-end тест.** Создаётся настоящий заказ через штатный API PrestaShop. |
| `[name="sdog_email"]` | **Ваш email.** Нужно указать свой ящик: на него могут прийти подтверждение заказа и письмо с купоном. Существующий клиент переиспользуется. |
| `[name="sdog_target_total"]` | **Точная сумма.** Итог с налогами и доставкой; должен попадать в диапазон правила. |
| `form.defaultForm .form-wrapper` | **Товар и данные покупателя.** Весь блок полей: товар, валюта, страна, группа и язык должны совпадать с условиями правила. |
| `[name="sdog_id_order_state"]` | **Статус.** Заказ сразу создаётся в выбранном триггерном статусе. |
| `#set_demo_order_generator_form_submit_btn` | **Создать и проверить.** После создания видны ссылка на заказ и код купона; основное письмо отправляется сразу, а при ошибке его можно повторно запустить через Cron/Tools. |

---

## 3. Для покупателя — поп-ап с тестовым сценарием

У Next Order Discount **нет виджета на витрине**: покупатель оформляет заказ, а купон на следующий
заказ приходит письмом. Поэтому фронт-пункт показывает не тур по DOM, а последовательность
**плавающих шагов** intro.js (поп-ап) — инструкцию, как прогнать модуль на тестовом заказе.

| Шаг | Текст |
|---|---|
| Обзор | **Как покупатель получает купон.** Виджета нет; купон приходит письмом. Сценарий можно проверить на витрине или через Demo Order Generator. |
| Шаг 1 | **Создайте тестовый заказ:** оформите его как обычный покупатель на витрине или укажите свой email и нужные условия в Demo Order Generator. |
| Шаг 2 | **Доведите заказ до триггерного статуса.** Заказ с витрины нужно перевести в нужный статус в админке; генератор создаёт заказ сразу в выбранном статусе. Купон можно проверить в Coupons. |
| Шаг 3 | **Проверьте письмо:** основная отправка выполняется сразу, а cron повторяет неудавшуюся попытку и обрабатывает напоминания. |
| Шаг 4 | **Купон применяется к следующему заказу** → статус used; Resend/напоминания в Coupons. |

**Цикличность:** на последнем шаге — кнопка **«Дальше: Dashboard →»**, замыкающая маршрут.

---

## 4. Технические заметки

- **Переходы фронт↔админка.** Ссылки в формате `?controller=NextOrderDiscount&tab=...` без токена
  (токены админки в демо отключены). Автозапуск тура сверяет текущий URL с адресом пункта по пути,
  `controller` и, если он есть, `tab`; параметр запуска `set_demo_tutorial` передаётся при переходе.
- **Под-вкладки формы правила** (General/Conditions/Code/Email) переключаются туром вручную:
  `activatePane()` тогглит классы `.active` на `.tab-pane` и на `li` навигации (без зависимости от JS
  Bootstrap).
- **Demo Order Generator:** тур открывает отдельный admin-controller `DemoOrderGenerator` модуля
  `set_demo_order_generator`; для него ключ возобновления строится по `controller`, а не по `tab`.
- **Файлы:** панель — `views/templates/hook/panel.tpl`; движок тура и данные шагов —
  `views/js/tutorial_helper.js`, `scenario_admin.js`, `scenario_front.js`,
  `tutorial_data_admin.js`, `tutorial_data_front.js`; переводы EN/FR/DE/PL/ES — `tutorial_i18n.js`;
  SEO-лендинг — `controllers/front/seo.php` + `views/templates/front/seo.tpl` (маршрут
  `/next-order-discount-demo`).
