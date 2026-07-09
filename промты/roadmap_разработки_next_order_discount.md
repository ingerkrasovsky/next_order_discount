# Roadmap разработки модуля Next Order Discount (без кода)

Документ описывает поэтапную реализацию модуля `set_next_order_discount` в формате независимых микроэтапов.

Требования к этапам:

- каждый этап независим (имеет собственный завершенный результат);
- каждый этап рассчитан примерно на `300–500` строк кода;
- после каждого этапа есть проверяемый рабочий инкремент;
- код здесь не приводится, только план работ.

Принятые конвенции (см. `архитектура_модуля_next_order_discount.md`, раздел 0):

- базовый эталон архитектуры — модуль `set_loyalty_milestones`: плоская классическая структура PrestaShop-модуля, без Symfony DI, без `composer.json`, без `config/services.yml`, без DDD-слоев `src/`;
- бизнес-логика — обычные PHP-классы без namespace в `classes/` (по подпапкам), подключаются `require_once` через список-константу в главном файле;
- префикс SQL-таблиц: `snod_` (например, `ps_snod_coupon_link`);
- префикс ключей конфигурации: `SNOD_`;
- админ-подход: как в `set_loyalty_milestones` — вкладка BO через `Tab` (`installTab()` в главном файле), единый `ModuleAdminController` (`controllers/admin/NextOrderDiscount.php`, класс `NextOrderDiscountController`), редирект из `getContent()`;
- структура админки: все табы объявляются массивом в `initContent()` контроллера, данные каждого таба готовит метод `{tab}Tab()`, каждый таб — отдельная вьюшка `views/templates/admin/tabs/<tab>.tpl`, общий каркас — `views/templates/admin/main.tpl`;
- SQL-скрипты — PHP-файлы `sql/install.php` / `sql/uninstall.php` (массив `$sql[]` + `Db::getInstance()->execute()`), миграции — `upgrade/upgrade-X.Y.Z.php`;
- в каждой директории `index.php`-заглушка, в каждом файле лицензионный header и guard `_PS_VERSION_`.

Матрица табов (обязательная реализация):

- `Dashboard`: агрегированные метрики по воронке купонов.
- `Rules`: набор правил «условия → скидка» (список + add/edit, приоритет, вкл/выкл).
- `Settings`: только глобальное (вкл/выкл модуля, debug, формат кода `SNOD_CODE_*`).
- `Coupons`: список выданных купонов, статусы, поиск/фильтры, служебные действия.
- `Logs`: журнал операций модуля и ошибок с correlation id.
- `Cron/Tools`: ручной запуск задач, токен, состояние lock/очереди.

---

## ⚡ Изменение направления (v1.0): движок правил скидок (rule-engine)

Модуль строится не на одной глобальной скидке, а на **наборе правил** «условия → скидка». Плоские настройки (`SNOD_DISCOUNT_*`, `SNOD_MIN_ORDER_AMOUNT`, `SNOD_TARGET_STATUSES`) заменяются правилами; в Settings остаётся только глобальное (вкл/выкл модуля, debug, формат кода `SNOD_CODE_*`).

### Ключевые решения

- Заказ может породить **несколько купонов** — по одному на каждое сработавшее правило.
- Флаг правила `stop_further`: матчер идёт по правилам в порядке `priority`, на каждом совпавшем выдаёт купон и **останавливается после правила со `stop_further=1`**. Один купон = включить stop у топ-правила; несколько = оставить выключенным.
- **Дефолт-правило** при install/upgrade сидится из старых `SNOD_*` со `stop_further=1` → поведение «из коробки» как раньше (один купон), ничего не ломается.
- Идемпотентность: unique-индекс становится `(id_shop, id_order_source, id_snod_rule)`; guard — по паре «заказ + правило».
- Нюанс для мерчанта (в README): несколько next-order купонов у одного клиента могут стакаться в следующей корзине в зависимости от совместимости cart rules.

### Модель данных (в дополнение к `snod_coupon_link` / `snod_dispatch_queue` / `snod_cron_lock`)

- `snod_rule`: `id_shop`, `id_shop_group`, `name` (админ-лейбл, single), `active`, `priority`, `stop_further`, `discount_type` (percent|amount|free_shipping), `discount_value`, `validity_days`, `next_order_min_amount`, `source_total_min`, `source_total_max`, `date_from`, `date_to`, `customer_order_count_min`, `customer_order_count_max`, `group_mode`, `country_mode`, `currency_mode`, `category_mode`, `manufacturer_mode`, `created_at`, `updated_at`.
- M2M условий: `snod_rule_status`, `snod_rule_group`, `snod_rule_country`, `snod_rule_currency`, `snod_rule_category`, `snod_rule_manufacturer`.
- `snod_coupon_link` дополняется колонкой `id_snod_rule`.

### Влияние на готовые этапы

Остаются: **1** (каркас/install), **2** (табовая админка), **4** (сервисные таблицы/репозитории), **6** (генератор кода), **9** (хуки). Переопределяются R-этапами: **3** (Settings), **5** (eligibility → matcher), **7** (генерация), **8** (идемпотентность).

### R-этапы

**R1. Хранилище правил.** Нативно в `sql/install.php`: таблицы `snod_rule` + 6 M2M-таблиц условий; колонка `id_snod_rule` в `snod_coupon_link`; unique → `(id_shop, id_order_source, id_snod_rule)`; сид дефолт-правила прямо при install (percent 10 / 30 дней / `stop_further=1` / все условия `all`). Класс `SnodRuleRepository` (CRUD + загрузка/сохранение условий). Без upgrade-скрипта и без переноса `SNOD_*` — для dev-инстанса модуль переустанавливается.

**R2. Таб Rules — список.** Новый таб `Rules`: список правил (имя, приоритет, вкл/выкл, сводка условий, скидка), toggle `active`, изменение `priority`, удаление. Ajax через `respondJson` с проверкой токена.

**R3. Форма правила — outcome + базовые условия.** Add/edit: тип/значение скидки, срок, мин. сумма след. заказа, `source_total_min/max`, окно дат, целевые статусы (мультиселект), `stop_further`.

**R4. Условия II — клиенты/гео.** Группы клиентов, страны, валюты с режимами `all|include|exclude`.

**R5. Условия III — товары/лояльность.** Категории и бренды («в заказе есть товар из…»), `customer_order_count_min/max` (N-й заказ / только первый).

**R6. `SnodRuleMatcher`.** Заменяет `SnodCouponEligibilityResolver`: по контексту заказа возвращает **упорядоченный список** сработавших правил (условия AND внутри правила), с учётом `priority` и `stop_further`. Unit-тестируемо.

**R7. Интеграция в генерацию.** `SnodCouponGenerationService` — цикл по правилам от матчера: на каждое idempotency-guard `(shop, order, rule)` → код → CartRule из outcome правила → `snod_coupon_link` с `id_snod_rule`. Хуки без изменений. Таб Coupons получает колонку «Rule».

**R8. Финализация.** Settings ужимается до глобального; legacy-ключи `SNOD_DISCOUNT_*` / `SNOD_MIN_ORDER_AMOUNT` / `SNOD_TARGET_STATUSES` удаляются из install/uninstall; multilang/multishop hardening; README-заметка про стакинг.

После R-этапов возвращаемся к линейному плану ниже (email → reminders → cron → dashboard → …), но outcome/условия берутся из правил, а не из глобального конфига.

---

## Этап 1. Каркас модуля и базовая установка (по эталону)

### Цель

Собрать минимально рабочий каркас коммерческого модуля, идентичный по механике `set_loyalty_milestones`: установка/удаление, регистрация базовых хуков, подключение SQL-скриптов. Одновременно убрать из начатого кода всё, что расходится с эталоном.

### Какие файлы создаются

- `set_next_order_discount/sql/install.php` (PHP-формат: `$sql[]`, `_DB_PREFIX_`, `_MYSQL_ENGINE_`, return bool)
- `set_next_order_discount/sql/uninstall.php`
- `set_next_order_discount/index.php` (+ `index.php`-заглушки во всех поддиректориях)
- `set_next_order_discount/logo.png`
- `set_next_order_discount/README.md`

### Какие файлы изменяются

- `set_next_order_discount/set_next_order_discount.php` — переписать под эталон: метаданные в `__construct()`, `install()` по шагам (sql/install.php → `parent::install()` → дефолтные `Configuration`-значения через `getDefaultConfigurationValues()`/`updateConfigurationValues()` → регистрация хуков из константы `MODULE_HOOKS` → `installTab()`), зеркальный `uninstall()`, приватные `installTab()`/`uninstallTab()` прямо в главном файле.

### Какие файлы удаляются (расхождения с эталоном)

- `set_next_order_discount/composer.json`
- `set_next_order_discount/config/services.yml` (и вся папка `config/`)
- `set_next_order_discount/src/` полностью (`Infrastructure/Bootstrap/ModuleInstaller.php`, `ModuleUninstaller.php`, `Presentation/Admin/Tabs/TabRegistry.php`, `Presentation/Admin/Tab/AdminTabInstaller.php`) — их обязанности переходят в главный файл модуля и админ-контроллер.

### Что должно заработать после завершения этапа

- модуль устанавливается и удаляется без ошибок;
- создаются и удаляются базовые структуры БД;
- регистрируются ключевые хуки (пока без бизнес-логики);
- модуль корректно отображается в списке модулей и не вызывает warning/notice.

### Как протестировать результат

1. Установить модуль в BO.
2. Проверить, что в БД появились таблицы `ps_snod_*`.
3. Удалить модуль и убедиться, что таблицы и конфиги удалены.
4. Повторно установить модуль и проверить, что повторная установка проходит чисто.

---

## Этап 2. Каркас табовой админки по паттерну set_loyalty_milestones

### Цель

Сделать простую и стабильную BO-архитектуру: отдельная вкладка меню, единый `ModuleAdminController` со всеми табами, редирект из `getContent()` и табовая навигация как в модуле лояльности.

### Какие файлы создаются

- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — класс `NextOrderDiscountController extends ModuleAdminController`; массив `$tabs` в `initContent()`, активный таб из query-параметра `tab`, динамический вызов методов `{tab}Tab()`, smarty-переменные `arTabs/AdminLink/currentTab/currentTabCode/parentCode/isPs9`, рендер через `setTemplate('main.tpl')`
- `set_next_order_discount/views/templates/admin/main.tpl` — общий каркас: nav-pills по `$arTabs` (только `level == 0`), `{include file="./tabs/$currentTabCode.tpl"}`, панель «Need help?»
- `set_next_order_discount/views/templates/admin/tabs/dashboard.tpl`
- `set_next_order_discount/views/templates/admin/tabs/settings.tpl`
- `set_next_order_discount/views/templates/admin/tabs/coupons.tpl`
- `set_next_order_discount/views/templates/admin/tabs/logs.tpl`
- `set_next_order_discount/views/templates/admin/tabs/cron_tools.tpl`
- `set_next_order_discount/views/css/back.css`
- `set_next_order_discount/views/js/back.js`

### Какие файлы изменяются

- `set_next_order_discount/set_next_order_discount.php` — `installTab()` регистрирует `class_name = NextOrderDiscount`; `getContent()` содержит только `Tools::redirectAdmin($this->context->link->getAdminLink('NextOrderDiscount'))`.

### Какие файлы удаляются (расхождения с эталоном)

- `set_next_order_discount/controllers/admin/AdminNextOrderDiscount.php` (переименование по эталону — без префикса `Admin`)
- `set_next_order_discount/views/templates/admin/configure.tpl`
- `set_next_order_discount/views/templates/admin/tabs/nav.tpl` (навигация целиком уходит в `main.tpl`)

### Что должно заработать после завершения этапа

- в BO появляется отдельный пункт меню модуля;
- переход в конфигурацию модуля открывает `NextOrderDiscountController`;
- `getContent()` больше не содержит сложной логики, только редирект;
- открывается страница с табами `Dashboard`, `Settings`, `Coupons`, `Logs`, `Cron/Tools` (пока с заглушками содержимого).

### Как протестировать результат

1. Нажать “Настроить” у модуля.
2. Проверить редирект на admin-контроллер модуля.
3. Проверить, что вкладка в меню создается и удаляется при install/uninstall.
4. Проверить переключение между табами и сохранение активного таба в URL (параметр `tab`).
5. Проверить открытие страницы на нескольких языках BO.

---

## Этап 3. Таб Settings: конфигурация ядра модуля

### Цель

Реализовать вкладку `Settings` с сохранением и чтением базовых настроек через `Configuration` API с учетом multishop — чтение/сохранение в методе `settingsTab()` и postProcess контроллера, без отдельных сервисных классов конфигурации.

### Какие файлы создаются

— (новых файлов нет: логика в контроллере, по эталону)

### Какие файлы изменяются

- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — `settingsTab()`: чтение значений, обработка POST-сохранения, валидация невалидных значений
- `set_next_order_discount/views/templates/admin/tabs/settings.tpl` — форма настроек
- `set_next_order_discount/set_next_order_discount.php` — дефолтные значения новых ключей в `getDefaultConfigurationValues()`

### Что должно заработать после завершения этапа

- можно сохранить базовые настройки:
  - `SNOD_ENABLED`
  - `SNOD_DISCOUNT_TYPE`
  - `SNOD_DISCOUNT_VALUE`
  - `SNOD_VALIDITY_DAYS`
  - `SNOD_MIN_ORDER_AMOUNT`
- значения корректно читаются в текущем shop-context;
- вкладка `Settings` работает в рамках единого tabbed UI.

### Как протестировать результат

1. Включить модуль в одном магазине и выключить в другом (multishop).
2. Сохранить настройки и обновить страницу.
3. Убедиться, что значения не “протекают” между магазинами при разном scope.
4. Проверить валидацию невалидных значений (пустые/отрицательные/нечисловые).

---

## Этап 4. Таблицы жизненного цикла купонов и репозитории

### Цель

Добавить сервисные таблицы и репозитории (`classes/Repository/`, через `Db::getInstance()`) для жизненного цикла купона, очереди отправки и cron-lock.

### Какие файлы создаются

- `set_next_order_discount/classes/Repository/CouponLinkRepository.php`
- `set_next_order_discount/classes/Repository/DispatchQueueRepository.php`
- `set_next_order_discount/classes/Repository/CronLockRepository.php`

### Какие файлы изменяются

- `set_next_order_discount/sql/install.php` — таблицы `ps_snod_coupon_link`, `ps_snod_dispatch_queue`, `ps_snod_cron_lock` с индексами
- `set_next_order_discount/sql/uninstall.php`
- `set_next_order_discount/set_next_order_discount.php` — классы в список-константу `require_once` (паттерн `ensure...ClassesLoaded()`)

### Что должно заработать после завершения этапа

- таблицы `ps_snod_coupon_link`, `ps_snod_dispatch_queue`, `ps_snod_cron_lock` созданы с индексами;
- репозитории умеют делать базовые CRUD-операции;
- можно записать и прочитать тестовую запись жизненного цикла купона.

### Как протестировать результат

1. Выполнить install и проверить структуру таблиц/индексов.
2. Через временный debug-action записать тестовые данные в каждую таблицу.
3. Убедиться, что данные корректно читаются обратно.
4. Проверить uninstall и повторный install.

---

## Этап 5. Правила eligibility

### Цель

Выделить бизнес-решение “можно ли выдавать купон” в отдельный класс (аналог `PointsEligibilityResolver` в лояльности), не зависящий от hook/контроллера.

### Какие файлы создаются

- `set_next_order_discount/classes/Coupon/CouponEligibilityResolver.php` — статус заказа, минимальная сумма, включенность модуля, группа клиента, магазин

### Какие файлы изменяются

- `set_next_order_discount/set_next_order_discount.php` — подключение класса в список `require_once`

### Что должно заработать после завершения этапа

- есть единая точка принятия решения eligibility;
- проверяются: статус заказа, минимальная сумма, включенность модуля, базовые ограничения;
- логика не зависит от hook/controller (входные данные передаются параметрами).

### Как протестировать результат

1. Подготовить несколько тест-кейсов входных данных (валидный/невалидный заказ).
2. Проверить, что resolver возвращает ожидаемое решение для каждого кейса.
3. Проверить граничные значения по суммам и срокам.

---

## Этап 6. Генерация кода купона и проверка уникальности

### Цель

Сделать надежный генератор кода с шаблоном/префиксом и гарантией уникальности.

### Какие файлы создаются

- `set_next_order_discount/classes/Coupon/CouponCodeGenerator.php` — маска/префикс из настроек, криптостойкая случайная часть, проверка уникальности с ретраями до лимита попыток

### Какие файлы изменяются

- `set_next_order_discount/set_next_order_discount.php` — подключение класса

### Что должно заработать после завершения этапа

- формируется код нужной длины и формата;
- учитывается префикс/маска из настроек (`SNOD_`-ключи);
- при коллизии генерируется новый вариант до успеха/лимита попыток.

### Как протестировать результат

1. Сгенерировать большую серию кодов и проверить отсутствие дублей.
2. Протестировать разные маски и длины.
3. Вручную создать коллизию и проверить fallback-генерацию.

---

## Этап 7. Адаптер CartRule, сервис генерации + таб Coupons

### Цель

Реализовать создание `CartRule` на основе параметров из настроек и привязки к конкретному клиенту, оркестрацию генерации купона, а также базовый таб `Coupons` в админке.

### Какие файлы создаются

- `set_next_order_discount/classes/Coupon/CartRuleAdapter.php` — обертка над PrestaShop CartRule API (создание, деактивация)
- `set_next_order_discount/classes/Coupon/CouponGenerationService.php` — оркестрация: eligibility → код → CartRule → запись в `ps_snod_coupon_link`

### Какие файлы изменяются

- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — метод `couponsTab()`: список купонов, фильтры
- `set_next_order_discount/views/templates/admin/tabs/coupons.tpl`
- `set_next_order_discount/set_next_order_discount.php` — подключение классов

### Что должно заработать после завершения этапа

- из входных параметров создается валидный `CartRule`;
- купон персональный (`id_customer`), одноразовый и ограниченный по сроку;
- создается запись связи в `ps_snod_coupon_link` со статусом `created`;
- в табе `Coupons` отображается список купонов (минимум: код, клиент, статус, срок действия).

### Как протестировать результат

1. Запустить сервис на тестовом заказе и проверить появление CartRule.
2. Проверить ограничения купона (customer, quantity, validity).
3. Проверить запись в `ps_snod_coupon_link`.
4. Проверить, что новый купон отображается в табе `Coupons`.

---

## Этап 8. Идемпотентность выдачи (1 купон на заказ)

### Цель

Защитить модуль от повторной выдачи одного и того же купона при повторных событиях/ретраях.

### Какие файлы создаются

— (новых файлов нет: проверка идемпотентности встраивается в существующие классы)

### Какие файлы изменяются

- `set_next_order_discount/classes/Coupon/CouponGenerationService.php` — проверка существующей записи по `id_order_source + id_shop` до генерации, безопасный skip
- `set_next_order_discount/classes/Repository/CouponLinkRepository.php` — метод поиска по заказу/магазину
- `set_next_order_discount/sql/install.php` — уникальный индекс `(id_shop, id_order_source)`

### Что должно заработать после завершения этапа

- повторный вызов для того же `id_order` не создает второй купон;
- возвращается существующий результат или безопасный skip;
- база защищена уникальным индексом `(id_shop, id_order_source)`.

### Как протестировать результат

1. Дважды вызвать процесс генерации для одного заказа.
2. Убедиться, что в БД одна запись и один CartRule.
3. Проверить журнал/ответ на повторный вызов.

---

## Этап 9. Интеграция с hook событиями заказа

### Цель

Подключить бизнес-процесс к жизненному циклу заказа через hooks без override: тонкие обработчики хуков в главном файле модуля делегируют в `CouponGenerationService` (как обработчики хуков в лояльности).

### Какие файлы создаются

— (новых файлов нет: обработчики хуков живут в главном файле, по эталону)

### Какие файлы изменяются

- `set_next_order_discount/set_next_order_discount.php` — `hookActionValidateOrder()` (первичная регистрация события), `hookActionOrderStatusPostUpdate()` (whitelist целевых статусов → запуск генерации); список хуков в константе `MODULE_HOOKS`

### Что должно заработать после завершения этапа

- при переходе заказа в целевой статус запускается генерация купона;
- при нецелевом статусе генерация не стартует;
- модуль корректно работает в multishop-контексте.

### Как протестировать результат

1. Создать заказ и перевести в нужный статус.
2. Проверить, что купон создан только при целевом статусе.
3. Проверить сценарий с нецелевым статусом (купон не создается).

---

## Этап 10. Очередь задач отправки (dispatch queue)

### Цель

Разгрузить hooks и вынести email/reminder/служебные задачи в очередь.

### Какие файлы создаются

- `set_next_order_discount/classes/Queue/QueueService.php` — постановка/выборка/завершение задач, типы задач (`coupon_email|reminder_1|reminder_2|expire`), correlation id

### Какие файлы изменяются

- `set_next_order_discount/classes/Coupon/CouponGenerationService.php` — после генерации купона ставит задачу `coupon_email` в очередь
- `set_next_order_discount/classes/Repository/DispatchQueueRepository.php`
- `set_next_order_discount/set_next_order_discount.php` — подключение класса

### Что должно заработать после завершения этапа

- после генерации купона в очередь ставится `coupon_email`;
- hook завершает работу быстро, без отправки писем в реальном времени;
- есть базовые статусы `pending/processing/done/failed`.

### Как протестировать результат

1. Сгенерировать купон и проверить постановку задачи в `ps_snod_dispatch_queue`.
2. Проверить, что статус задачи изначально `pending`.
3. Проверить корректный payload и correlation id.

---

## Этап 11. Email: mailer и шаблоны купона

### Цель

Реализовать безопасную отправку письма с купоном через штатный Mail API с мультиязычностью.

### Какие файлы создаются

- `set_next_order_discount/classes/Mail/CouponMailer.php` — отправка через штатный Mail API, переменные шаблона
- `set_next_order_discount/classes/Mail/MailTemplateResolver.php` — выбор шаблона по языку клиента с fallback
- `set_next_order_discount/mails/en/next_order_discount.html`
- `set_next_order_discount/mails/en/next_order_discount.txt`
- `set_next_order_discount/mails/ru/next_order_discount.html`
- `set_next_order_discount/mails/ru/next_order_discount.txt`

### Какие файлы изменяются

- `set_next_order_discount/classes/Queue/QueueService.php` — обработка типа `coupon_email`
- `set_next_order_discount/set_next_order_discount.php` — подключение классов

### Что должно заработать после завершения этапа

- отправляется письмо с параметрами купона (`{coupon_code}`, `{coupon_value}`, `{valid_to}`, `{shop_name}`, `{customer_firstname}`, `{minimum_amount}`);
- выбирается шаблон по языку клиента с fallback;
- фиксируется момент отправки в `ps_snod_coupon_link.emailed_at`, статус → `emailed`.

### Как протестировать результат

1. Обработать вручную одну `coupon_email` задачу.
2. Проверить получение письма и корректность переменных.
3. Проверить fallback языка, если шаблона нет.

---

## Этап 12. Worker обработки очереди

### Цель

Сделать batch-обработчик задач очереди с ретраями и безопасным завершением.

### Какие файлы создаются

- `set_next_order_discount/classes/Queue/QueueRetryPolicy.php` — ретраи с backoff, перевод в `failed`

### Какие файлы изменяются

- `set_next_order_discount/classes/Queue/QueueService.php` — batch-выборка `pending` задач, обработка, переходы статусов
- `set_next_order_discount/classes/Repository/DispatchQueueRepository.php`
- `set_next_order_discount/set_next_order_discount.php` — подключение класса

### Что должно заработать после завершения этапа

- worker берет пачку `pending` задач и обрабатывает их;
- при ошибке задача ретраится до лимита попыток;
- после лимита уходит в `failed` с `last_error`.

### Как протестировать результат

1. Положить несколько задач в очередь и запустить batch.
2. Проверить переходы статусов `pending -> processing -> done`.
3. Смоделировать ошибку и проверить retries/failed.

---

## Этап 13. Напоминания: политика и планировщик

### Цель

Добавить логику расчета времени напоминаний и постановку reminder-задач.

### Какие файлы создаются

- `set_next_order_discount/classes/Reminder/ReminderPlanner.php` — вычисляет «созревшие» напоминания, ставит задачи без дублей
- `set_next_order_discount/mails/en/reminder_next_order_discount.html`
- `set_next_order_discount/mails/en/reminder_next_order_discount.txt`
- `set_next_order_discount/mails/ru/reminder_next_order_discount.html`
- `set_next_order_discount/mails/ru/reminder_next_order_discount.txt`

### Какие файлы изменяются

- `set_next_order_discount/classes/Repository/CouponLinkRepository.php` — выборка купонов для напоминаний, таймстемпы `first_reminder_at`/`second_reminder_at`
- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — настройки напоминаний в `settingsTab()`
- `set_next_order_discount/views/templates/admin/tabs/settings.tpl`
- `set_next_order_discount/set_next_order_discount.php` — подключение класса, дефолтные значения настроек напоминаний

### Что должно заработать после завершения этапа

- для купонов в нужных статусах планируются reminder-задачи;
- не создаются дубликаты напоминаний;
- учитываются настройки отключения/смещения напоминаний.

### Как протестировать результат

1. Создать купон с коротким TTL.
2. Запустить планировщик напоминаний.
3. Проверить появление задач `reminder_1`/`reminder_2` в очереди.
4. Повторно запустить планировщик и убедиться в отсутствии дублей.

---

## Этап 14. Истечение купонов и деактивация CartRule

### Цель

Автоматически переводить просроченные купоны в `expired` и отключать связанный CartRule (soft-expire).

### Какие файлы создаются

- `set_next_order_discount/classes/Coupon/CouponLifecycleManager.php` — переводы статусов (`created → emailed → reminded → used/expired`), expire-логика

### Какие файлы изменяются

- `set_next_order_discount/classes/Coupon/CartRuleAdapter.php` — деактивация CartRule (`active=0`)
- `set_next_order_discount/classes/Repository/CouponLinkRepository.php` — batch-выборка по `(valid_to, status)`
- `set_next_order_discount/set_next_order_discount.php` — подключение класса

### Что должно заработать после завершения этапа

- просроченные купоны помечаются как `expired`;
- CartRule переводится в `active=0`;
- повторный запуск обработчика не ломает состояние (идемпотентно).

### Как протестировать результат

1. Подготовить купон с истекшей датой.
2. Запустить expire-обработку.
3. Проверить статус в `ps_snod_coupon_link` и `active=0` у CartRule.
4. Повторно запустить и проверить отсутствие побочных эффектов.

---

## Этап 15. Таб Cron/Tools + cron endpoint + безопасность

### Цель

Открыть управляемую точку запуска фоновых задач (`process_queue`, `plan_reminders`, `expire_coupons`) с токеном и lock, и реализовать таб `Cron/Tools` для админ-управления. Front-контроллер — по паттерну `controllers/front/ajax.php` лояльности.

### Какие файлы создаются

- `set_next_order_discount/controllers/front/cron.php` — класс `Set_Next_Order_DiscountCronModuleFrontController extends ModuleFrontController`, `public $ajax = true;`
- `set_next_order_discount/classes/Cron/CronRouter.php` — маршрутизация параметра `task`
- `set_next_order_discount/classes/Cron/CronSecurityService.php` — проверка токена `SNOD_CRON_TOKEN`
- `set_next_order_discount/classes/Cron/LockManager.php` — табличная блокировка с TTL

### Какие файлы изменяются

- `set_next_order_discount/classes/Repository/CronLockRepository.php`
- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — метод `crontoolsTab()`: ручной запуск задач (ajax через `respondJson()` с проверкой токена), просмотр состояния lock/очереди, отображение cron URL
- `set_next_order_discount/views/templates/admin/tabs/cron_tools.tpl`
- `set_next_order_discount/views/js/back.js` — ajax-вызовы ручного запуска
- `set_next_order_discount/set_next_order_discount.php` — подключение классов, генерация токена при install

### Что должно заработать после завершения этапа

- cron endpoint принимает задачу и токен;
- без валидного токена запрос отклоняется;
- параллельные запуски одной задачи блокируются lock-механизмом;
- в табе `Cron/Tools` доступны ручные действия запуска задач и просмотр тех.статусов.

### Как протестировать результат

1. Вызвать cron без токена и проверить отказ.
2. Вызвать с валидным токеном и проверить успешный запуск.
3. Запустить одновременно два запроса одной задачи и проверить lock behavior.
4. Проверить ручной запуск задач через таб `Cron/Tools`.

---

## Этап 16. Табы Logs и Dashboard (наблюдаемость и метрики)

### Цель

Реализовать вкладки `Logs` и `Dashboard` в админке по паттерну модуля лояльности: логи операций, состояние очередей, базовые KPI по купонам.

### Какие файлы создаются

- `set_next_order_discount/classes/Logger/ModuleLogger.php` — логирование с correlation id, уровень через `SNOD_LOG_LEVEL`

### Какие файлы изменяются

- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — методы `logsTab()` (последние ошибки/события, фильтры) и `dashboardTab()` (агрегаты воронки из `CouponLinkRepository`)
- `set_next_order_discount/views/templates/admin/tabs/logs.tpl`
- `set_next_order_discount/views/templates/admin/tabs/dashboard.tpl`
- `set_next_order_discount/set_next_order_discount.php` — подключение класса, дефолт `SNOD_LOG_LEVEL`

### Что должно заработать после завершения этапа

- ключевые операции логируются с correlation id;
- в табе `Logs` доступны последние ошибки и тех.события;
- в табе `Dashboard` доступны агрегаты (generated/emailed/reminded/used/expired);
- есть управляемый уровень логирования через `SNOD_LOG_LEVEL`.

### Как протестировать результат

1. Выполнить успешный сценарий и проверить информационные логи.
2. Смоделировать ошибку отправки email и проверить error-лог.
3. Проверить отображение `Logs` и `Dashboard` в табовой админке.

---

## Этап 17. Multilang и multishop hardening

### Цель

Довести модуль до устойчивого поведения во всех режимах multishop + корректной мультиязычности контента. Мультиязычные конфигурационные значения — массивами по `id_lang`, переводы BO — через `$this->trans(...)` с доменом `Modules.Setnextorderdiscount.Admin` и файлами в `translations/<locale>/` (поддиректория локали, один файл на домен).

### Какие файлы создаются

- `set_next_order_discount/translations/ru-RU/*` (файлы переводов по доменам)

### Какие файлы изменяются

- `set_next_order_discount/controllers/admin/NextOrderDiscount.php` — scope-корректное чтение/запись `Configuration` (global / shop group / shop)
- `set_next_order_discount/classes/Mail/MailTemplateResolver.php` — язык клиента + fallback на дефолтный язык магазина
- `set_next_order_discount/classes/Coupon/CouponEligibilityResolver.php` — учет магазина/витрины
- `set_next_order_discount/set_next_order_discount.php` — multishop-контекст в обработчиках хуков

### Что должно заработать после завершения этапа

- настройки и процессы строго учитывают scope магазина;
- письма и шаблоны всегда выбираются в правильном языке;
- корректная работа при разных языках клиента и магазина.

### Как протестировать результат

1. Настроить разные значения в двух shop-context.
2. Создать заказы в обоих магазинах и проверить различия в купонах.
3. Проверить отправку писем на разных языках.

---

## Этап 18. Полное удаление и upgrade-стратегия

### Цель

Закрыть коммерческие требования по жизненному циклу релизов: чистый uninstall и безопасные обновления версий через штатный механизм `upgrade/upgrade-X.Y.Z.php` (без собственного upgrade-раннера — PrestaShop выполняет их сам).

### Какие файлы создаются

- `set_next_order_discount/upgrade/upgrade-1.0.1.php` — `function upgrade_module_1_0_1($module)`, идемпотентные миграции (проверка `SHOW COLUMNS ... LIKE` перед `ALTER`)
- `set_next_order_discount/upgrade/upgrade-1.0.2.php`

### Какие файлы изменяются

- `set_next_order_discount/set_next_order_discount.php` — версия модуля
- `set_next_order_discount/sql/uninstall.php` — полное удаление таблиц/конфигов
- `set_next_order_discount/README.md`

### Что должно заработать после завершения этапа

- uninstall удаляет все сущности модуля полностью (таблицы, `SNOD_*`-конфиги, вкладку BO, cron токен);
- апгрейд между версиями выполняет миграции предсказуемо и идемпотентно;
- документированы шаги обновления и rollback-подход.

### Как протестировать результат

1. Установить версию 1.0.0, наполнить данными.
2. Обновить на 1.0.1/1.0.2 и проверить миграции.
3. Выполнить uninstall и убедиться, что данные/вкладки/конфиги удалены.

---

## Этап 19. Приемочный E2E сценарий “от заказа до истечения”

### Цель

Собрать полный end-to-end путь и подтвердить готовность к production.

### Какие файлы создаются

- `set_next_order_discount/docs/e2e-checklist.md`
- `set_next_order_discount/docs/qa-test-cases.md`

### Какие файлы изменяются

- `set_next_order_discount/README.md`
- `промты/архитектура_модуля_next_order_discount.md` (при необходимости синхронизации)

### Что должно заработать после завершения этапа

- подтвержден полный цикл:
  - заказ -> генерация купона -> email -> reminder -> expire;
- подтверждена идемпотентность и стабильность cron;
- готов релиз-кандидат.

### Как протестировать результат

1. Пройти полный сценарий на staging с реальными заказами.
2. Проверить каждую точку в БД и в письмах.
3. Зафиксировать результаты по чек-листу QA.

---

## Этап 20. Релизная упаковка и коммерческая готовность

### Цель

Подготовить модуль к поставке: чистая структура, документация, контроль качества релизного архива.

### Какие файлы создаются

- `set_next_order_discount/CHANGELOG.md`
- `set_next_order_discount/docs/release-notes/1.0.0.md`
- `set_next_order_discount/docs/support-playbook.md`

### Какие файлы изменяются

- `set_next_order_discount/README.md`
- `set_next_order_discount/logo.png` (если требуется обновление брендинга)

### Что должно заработать после завершения этапа

- модуль готов к коммерческой дистрибуции;
- есть понятная документация для установки/обновления/поддержки;
- собран и проверен релизный zip-артефакт (все `index.php`-заглушки и лицензионные header на месте, никаких `src/`, `composer.json`, `config/`).

### Как протестировать результат

1. Установить модуль из релизного архива на чистый стенд.
2. Пройти минимальный smoke-тест (install/config/order/cron/uninstall).
3. Проверить целостность архива и наличие всех обязательных файлов.

---

## Рекомендации по темпу реализации

- Реализовывать этапы строго последовательно, но каждый этап должен быть merge-ready сам по себе.
- На каждый этап заводить отдельную задачу и отдельный PR.
- В каждом PR фиксировать:
  - цель этапа;
  - фактический объем (попадание в `300–500` строк);
  - чек-лист тестов из этапа.

Это даст предсказуемую разработку, контролируемый риск и удобную поддержку коммерческого модуля.
