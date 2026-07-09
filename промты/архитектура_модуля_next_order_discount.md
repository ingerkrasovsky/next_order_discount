# Архитектура коммерческого модуля PrestaShop: Next Order Discount

## Цель модуля

Модуль автоматически создает персональный купон после успешного оформления заказа и управляет полным жизненным циклом этого купона:

- генерация по событию заказа;
- отправка письма с купоном;
- напоминания до истечения;
- деактивация/очистка просроченных купонов;
- аудит и повторяемость процессов (идемпотентность).

Модуль использует только стандартные механизмы PrestaShop:

- Cart Rules;
- Hooks;
- ModuleAdminController + Tab (Back Office);
- штатный Mail API;
- штатная мультиязычность и multishop.

Без override ядра.

---

## 0. Базовый эталон: set_loyalty_milestones

Архитектура модуля ОБЯЗАНА повторять базовую архитектуру модуля `set_loyalty_milestones` (эталонная реализация). Это касается:

1. Структуры директорий (плоская классическая структура модуля PrestaShop, без Symfony DI и без DDD-слоев `src/`).
2. Механизма установки/удаления (одинаковый `install()`/`uninstall()` в главном файле модуля).
3. Табовой админки: все табы объявляются в одном админ-контроллере, каждый таб — отдельная вьюшка `views/templates/admin/tabs/<tab>.tpl`, общий каркас `views/templates/admin/main.tpl`.
4. Нейминга контроллеров, переводов и служебных файлов.

### Эталонные паттерны (из set_loyalty_milestones)

- Главный файл модуля — тонкий bootstrap: метаданные в `__construct()`, `install()`/`uninstall()`, регистрация хуков, дефолтные `Configuration`-значения, `installTab()`/`uninstallTab()` как приватные методы прямо в главном файле (НЕ отдельный класс-инсталлер), обработчики хуков.
- `install()` по шагам:
  1. `include dirname(__FILE__) . '/sql/install.php';`
  2. `parent::install()`;
  3. запись дефолтных значений конфигурации через `Configuration::updateValue()` (мультиязычные значения — массивом по `id_lang`);
  4. регистрация хуков из константы-списка `MODULE_HOOKS`;
  5. `$this->installTab()` — создание вкладки BO через ObjectModel `Tab` (`class_name` контроллера, имя по языкам, `id_parent` от родительской вкладки, `module`).
- `uninstall()` зеркально: `include sql/uninstall.php` → `parent::uninstall()` → снятие хуков → `uninstallTab()`.
- `getContent()` содержит ТОЛЬКО редирект:
  ```
  Tools::redirectAdmin($this->context->link->getAdminLink('NextOrderDiscount'));
  return '';
  ```
- `sql/install.php` и `sql/uninstall.php` — PHP-файлы (не .sql): собирают массив `$sql[]` строк с `_DB_PREFIX_` и `_MYSQL_ENGINE_`, выполняют через `Db::getInstance()->execute()` в цикле и возвращают `true/false`.
- Обновления версий — `upgrade/upgrade-X.Y.Z.php` с функцией `upgrade_module_X_Y_Z($module)`; миграции идемпотентны (проверка `SHOW COLUMNS ... LIKE` перед `ALTER`).
- Бизнес-логика — обычные PHP-классы в `classes/` (сгруппированы по подпапкам), подключаются через `require_once` списком-константой в главном файле (паттерн `ensure...ClassesLoaded()`), без composer-автозагрузки и без namespace.
- В каждой директории — заглушка `index.php`.
- В каждом файле — лицензионный header (NOTICE OF LICENSE) и guard `if (!defined('_PS_VERSION_')) { exit; }`.
- Переводы: `$this->trans('...', [], 'Modules.Setnextorderdiscount.Admin')`; файлы переводов в `translations/<locale>/` (формат: поддиректория локали, один файл на домен `<DomainNoDots>.<locale>.xlf`).

### Эталонный админ-контроллер (табовая архитектура)

Файл: `controllers/admin/NextOrderDiscount.php`, класс `NextOrderDiscountController extends ModuleAdminController` (по аналогии с `LoyaltyMilestones.php` / `LoyaltyMilestonesController`). Никакого префикса `Admin` в имени класса/файла.

Обязательные элементы контроллера:

1. `public $bootstrap = true;` и `$this->adminLink = $this->context->link->getAdminLink('NextOrderDiscount');` в конструкторе.
2. Все табы объявляются массивом прямо в `initContent()`:
   ```
   $tabs = [
       'dashboard'  => ['name' => 'Dashboard',  'url' => $this->adminLink . '&tab=dashboard',  'level' => 0],
       'settings'   => ['name' => 'Settings',   'url' => $this->adminLink . '&tab=settings',   'level' => 0],
       'coupons'    => ['name' => 'Coupons',    'url' => $this->adminLink . '&tab=coupons',    'level' => 0],
       'logs'       => ['name' => 'Logs',       'url' => $this->adminLink . '&tab=logs',       'level' => 0],
       'cron_tools' => ['name' => 'Cron/Tools', 'url' => $this->adminLink . '&tab=cron_tools', 'level' => 0],
   ];
   ```
   `level => 1` + `parent` — для дочерних экранов (например, карточка купона), как `point_add` в лояльности.
3. Активный таб — из query-параметра `tab` (`Tools::getValue('tab')`), с fallback на дефолтный таб; активному табу (и его parent) проставляется `active`.
4. Данные таба готовит одноименный метод контроллера `{tabcode}Tab()` (например, `settingsTab()`, `couponsTab()`, `crontoolsTab()`), вызываемый динамически:
   ```
   $functionName = str_replace('_', '', $currentTabCode);
   if (method_exists($this, $functionName . 'Tab')) {
       $this->{$functionName . 'Tab'}();
   }
   ```
5. POST-действия (сохранение настроек, действия с купонами, ручной запуск cron-задач) обрабатываются в этом же контроллере; ajax-ответы — через приватный `respondJson()` с проверкой токена.
6. Подключение ассетов: `$this->addCSS('/modules/set_next_order_discount/views/css/back.css')`, `$this->addJS(.../back.js)`, `Media::addJsDef([...])` для ajax URL/токена/i18n-строк.
7. В smarty передаются: `arTabs`, `AdminLink`, `currentTab`, `currentTabCode`, `parentCode`, `isPs9` (`version_compare(_PS_VERSION_, '9.0.0', '>=')`).
8. Рендер: `$this->setTemplate('main.tpl');` (относительный путь, шаблон лежит в `views/templates/admin/main.tpl`).

### Эталонные вьюшки

- `views/templates/admin/main.tpl` — общий каркас: nav-pills по `$arTabs` (рендерятся только `level == 0`), затем `{include file="./tabs/$currentTabCode.tpl"}`, внизу — панель «Need help?» со ссылкой на Addons contact form.
- `views/templates/admin/tabs/<tab>.tpl` — по одной отдельной вьюшке на каждый таб: `dashboard.tpl`, `settings.tpl`, `coupons.tpl`, `logs.tpl`, `cron_tools.tpl` (+ дочерние экраны вида `coupon_view.tpl` при необходимости).
- Никаких `configure.tpl`, `tabs/nav.tpl` и `module:`-путей в `setTemplate` — навигация целиком в `main.tpl`.

Front-контроллеры (cron endpoint) — по паттерну `controllers/front/ajax.php` лояльности: класс `Set_Next_Order_DiscountCronModuleFrontController extends ModuleFrontController` с `public $ajax = true;`.

---

## 1. Общая архитектура модуля

Стиль: классический модуль PrestaShop (как set_loyalty_milestones) с логическим разделением ответственности по классам, но без формальных DDD-слоев и без Symfony DI.

Разделение ответственности:

1. Главный файл модуля (`set_next_order_discount.php`)
- метаданные, install/uninstall, регистрация хуков;
- тонкие обработчики хуков, делегирующие в классы из `classes/`;
- `getContent()` — только редирект в админ-контроллер.

2. `classes/` — бизнес-логика
- политики выдачи/валидности купона;
- генерация кода и создание CartRule;
- очередь задач, напоминания, expire;
- репозитории собственных таблиц;
- почта, cron-безопасность, lock, логгер.

3. `controllers/admin/NextOrderDiscount.php` — вся BO-часть
- табовая навигация;
- обработка POST/ajax действий;
- подготовка данных для каждого таба.

4. `controllers/front/cron.php` — cron endpoint.

### Ключевые архитектурные принципы

- SOLID: тонкие классы с одной ответственностью (в рамках `classes/`).
- DRY: единые политики генерации и валидации, без дублирования в хуках/cron.
- KISS: минимум обязательных сущностей; тот же стек, что в лояльности.
- PSR-12: единый стиль кода.
- Идемпотентность: защита от повторной генерации письма/купона при ретраях hook/cron.

---

## 2. Структура директорий

Структура повторяет set_loyalty_milestones:

```text
set_next_order_discount/
  set_next_order_discount.php      # главный файл модуля (bootstrap)
  index.php                        # guard (и в каждой поддиректории)
  logo.png
  classes/
    Coupon/
      CouponEligibilityResolver.php
      CouponCodeGenerator.php
      CouponGenerationService.php
      CartRuleAdapter.php
      CouponLifecycleManager.php
    Repository/
      CouponLinkRepository.php
      DispatchQueueRepository.php
      CronLockRepository.php
    Queue/
      QueueService.php
      QueueRetryPolicy.php
    Reminder/
      ReminderPlanner.php
    Cron/
      CronRouter.php
      CronSecurityService.php
      LockManager.php
    Mail/
      CouponMailer.php
      MailTemplateResolver.php
    Logger/
      ModuleLogger.php
  controllers/
    admin/
      NextOrderDiscount.php        # NextOrderDiscountController (все табы здесь)
    front/
      cron.php                     # cron endpoint (паттерн ajax.php лояльности)
  sql/
    install.php                    # PHP: $sql[] + Db::execute, return bool
    uninstall.php
  upgrade/
    upgrade-1.0.1.php              # function upgrade_module_1_0_1($module)
    ...
  views/
    templates/
      admin/
        main.tpl                   # каркас: nav-pills + include таба
        tabs/
          dashboard.tpl
          settings.tpl
          coupons.tpl
          logs.tpl
          cron_tools.tpl
      hook/                        # фронт-шаблоны (если понадобятся)
    css/
      back.css
    js/
      back.js
  mails/
    en/
      next_order_discount.html
      next_order_discount.txt
      reminder_next_order_discount.html
      reminder_next_order_discount.txt
    ru/
      ...
  translations/
    ru-RU/
    fr-FR/
    ...
  README.md
```

Примечания:

- Вся бизнес-логика в `classes/`, а не в главном файле модуля; классы подключаются `require_once` через список-константу в главном файле.
- Без `composer.json`, без `config/services.yml`, без `src/` — как в эталоне.
- SQL-миграции — только через `upgrade/upgrade-X.Y.Z.php`.
- Email-шаблоны хранятся по языкам стандартным способом PrestaShop.

---

## 3. Структура базы данных

Используем CartRule как основную сущность купона + собственные технические таблицы для идемпотентности, аудита и планирования напоминаний. Таблицы создаются в `sql/install.php` (массив `$sql[]`, `_DB_PREFIX_`, `_MYSQL_ENGINE_`, `utf8mb4`), удаляются в `sql/uninstall.php`.

### Таблица 1: ps_snod_coupon_link

Назначение: связь бизнес-события с созданным CartRule.

Поля:

- id_snod_coupon_link (PK)
- id_shop (FK/индекс)
- id_shop_group (индекс)
- id_customer (индекс)
- id_order_source (индекс) — заказ, по которому выдан купон
- id_cart_rule (индекс)
- coupon_code (индекс, уникальность в рамках shop/group)
- status (created|emailed|reminded|used|expired|canceled)
- valid_from
- valid_to
- generated_at
- emailed_at (nullable)
- first_reminder_at (nullable)
- second_reminder_at (nullable)
- used_at (nullable)
- expired_at (nullable)
- metadata_json (nullable) — расширяемые атрибуты
- created_at
- updated_at

Ограничения и индексы:

- unique(id_shop, id_order_source) для стратегии “1 купон на заказ”;
- индекс(id_customer, status, valid_to);
- индекс(id_cart_rule);
- индекс(valid_to, status) для batch-expire.

### Таблица 2: ps_snod_dispatch_queue

Назначение: очередь технических задач (email/reminder), чтобы не блокировать hook.

Поля:

- id_snod_dispatch (PK)
- id_shop
- task_type (coupon_email|reminder_1|reminder_2|expire)
- payload_json
- status (pending|processing|done|failed)
- attempts
- available_at
- processed_at (nullable)
- last_error (nullable)
- correlation_id (для идемпотентности/трассировки)
- created_at
- updated_at

Индексы:

- индекс(status, available_at);
- unique(correlation_id).

### Таблица 3: ps_snod_cron_lock

Назначение: мягкая блокировка параллельных cron-процессов.

Поля:

- lock_name (PK)
- locked_until
- owner_token
- updated_at

### Почему не только CartRule

CartRule хранит скидочные правила, но не дает удобного бизнес-аудита “что, когда и почему создано”, плюс нет безопасной идемпотентности для повторных запусков hook/cron. Поэтому нужны легкие сервисные таблицы.

---

## 4. Какие классы понадобятся (classes/)

Все классы — обычные PHP-классы без namespace (как в set_loyalty_milestones), с говорящими уникальными именами; зависимости передаются через конструктор или создаются фабричным методом в главном файле/контроллере.

### Coupon (генерация и жизненный цикл)

- CouponEligibilityResolver — решает, положен ли купон по заказу (аналог PointsEligibilityResolver в лояльности): статус заказа, минимальная сумма, группы, магазин.
- CouponCodeGenerator — код по маске/префиксу, криптостойкая случайная часть, проверка уникальности с ретраями.
- CartRuleAdapter — обертка над PrestaShop CartRule API (создание, деактивация).
- CouponGenerationService — оркестрация: eligibility → идемпотентность → код → CartRule → запись в ps_snod_coupon_link → задача в очередь.
- CouponLifecycleManager — переводы статусов (created → emailed → reminded → used/expired).

### Repository (свои таблицы, через Db::getInstance())

- CouponLinkRepository
- DispatchQueueRepository
- CronLockRepository

### Queue

- QueueService — постановка/выборка/завершение задач, correlation id.
- QueueRetryPolicy — ретраи с backoff, перевод в failed.

### Reminder / Expiration

- ReminderPlanner — вычисляет «созревшие» напоминания, ставит задачи без дублей.
- (expire-логика — в CouponLifecycleManager + CartRuleAdapter).

### Cron

- CronRouter — маршрутизация task-параметра (process_queue|plan_reminders|expire_coupons).
- CronSecurityService — проверка токена.
- LockManager — таблличная блокировка с TTL.

### Mail

- MailTemplateResolver — выбор шаблона по языку клиента с fallback.
- CouponMailer — отправка через штатный Mail API, переменные шаблона.

### Logger

- ModuleLogger — логирование с correlation id, уровень через SNOD_LOG_LEVEL.

### Presentation

- NextOrderDiscountController (controllers/admin/NextOrderDiscount.php) — единственный админ-контроллер со всеми табами и методами `{tab}Tab()`.
- Set_Next_Order_DiscountCronModuleFrontController (controllers/front/cron.php).

### Главный модульный класс

- set_next_order_discount — тонкий bootstrap:
  - install/uninstall/register hooks/installTab/uninstallTab (все внутри этого файла);
  - `require_once` классов из `classes/` (константа-список + `ensure...ClassesLoaded()`);
  - обработчики хуков, делегирующие в CouponGenerationService.

---

## 5. Какие хуки использовать

С учетом версий 8.1+ и 9+ лучше опираться на событие подтвержденного заказа, а не только создание Order.

Кандидаты:

1. actionValidateOrder
- момент, когда заказ валидирован;
- удобно создавать запись-кандидат в очередь.

2. actionOrderStatusPostUpdate
- контроль перехода в “оплачено/принято” статус;
- позволяет запускать генерацию только при нужном статусе.

3. actionObjectCartRuleDeleteAfter (опционально)
- синхронизация, если правило удалено вручную.

4. displayCustomerAccount / displayOrderConfirmation (опционально)
- показать сообщение “купон отправлен” или сам код (по настройке).

Подход:

- основной триггер генерации: actionOrderStatusPostUpdate по whitelist целевых статусов;
- actionValidateOrder использовать для первичной регистрации события и корреляции;
- список хуков — константой `MODULE_HOOKS` в главном файле (как в лояльности).

Так снижается риск преждевременной выдачи купона до оплаты.

---

## 6. Логика генерации купонов

### Бизнес-пайплайн

1. Получить событие заказа (hook).
2. Проверить eligibility (CouponEligibilityResolver):
- статус заказа;
- минимальная сумма заказа;
- исключенные товары/категории/бренды;
- группа клиента;
- магазин/витрина.
3. Проверить идемпотентность:
- есть ли уже запись по id_order_source + id_shop.
4. Сформировать параметры купона:
- тип скидки (fixed/percent);
- величина;
- валидность (N дней);
- минимальная сумма следующего заказа;
- ограничения по customer, carrier, currency, country.
5. Сгенерировать код (CouponCodeGenerator):
- шаблон префикса/маски;
- криптостойкая случайная часть;
- проверка уникальности.
6. Создать CartRule через CartRuleAdapter.
7. Записать связь в ps_snod_coupon_link со статусом created.
8. Поставить задачу отправки письма в очередь (QueueService).

### Важные правила

- Персональность: id_customer обязательно фиксируется в CartRule.
- Одноразовость: quantity=1, quantity_per_user=1 (или настраиваемо).
- Без stack с другими скидками (настраиваемо через compatibility настройки CartRule).
- Время действия строго в timezone магазина.

---

## 7. Логика отправки email

### Сценарий

1. Обработчик очереди берет задачу coupon_email.
2. Загружает купон и проверяет актуальность:
- статус created;
- купон еще не истек;
- клиент активен.
3. Подбирает язык и шаблон (MailTemplateResolver):
- язык клиента;
- fallback на дефолтный язык магазина.
4. Формирует переменные шаблона:
- {coupon_code}
- {coupon_value}
- {valid_to}
- {shop_name}
- {customer_firstname}
- {minimum_amount}
5. Отправляет письмо штатным Mail API (CouponMailer).
6. Обновляет статус в ps_snod_coupon_link: emailed.
7. Планирует напоминания (если включены).

### Надежность

- retries по queue с backoff;
- idempotency key на задачу отправки;
- логирование ошибок без утечки персональных данных.

---

## 8. Логика напоминаний

### Модель

Поддержка 0..N напоминаний, но базово 2:

- Reminder #1: за X дней до истечения;
- Reminder #2: за Y часов до истечения.

### Пайплайн

1. Ночной cron выбирает купоны в статусе emailed/reminded.
2. ReminderPlanner вычисляет, какие напоминания “созрели”.
3. Не дублирует уже отправленные (по first_reminder_at/second_reminder_at).
4. Ставит задачи в очередь.
5. Email worker отправляет reminder шаблон.
6. Обновляет таймстемпы и статус.

### Ограничения

- не отправлять напоминание, если купон уже использован/просрочен;
- не отправлять чаще, чем разрешено политикой anti-spam.

---

## 9. Логика удаления просроченных купонов

Рекомендуется двухфазная стратегия:

1. Soft-expire (обязательно)
- смена статуса в ps_snod_coupon_link на expired;
- деактивация связанного CartRule (active=0).

2. Hard cleanup (опционально, по сроку хранения)
- физическое удаление старых записей и/или CartRule через N месяцев;
- только если это разрешено политикой хранения данных.

Преимущество: сохраняется аудит, но купон не применяется.

---

## 10. Логика Cron

Нужно 3 независимых процесса (вызываются одним endpoint `controllers/front/cron.php` с параметром task):

1. process_queue
- обрабатывает pending задачи отправки/служебные команды;
- ограничение batch size;
- retries + dead-letter статус failed.

2. plan_reminders
- ищет купоны, по которым пора напоминать;
- добавляет задачи reminder в очередь.

3. expire_coupons
- деактивирует истекшие купоны;
- помечает статус expired.

### Безопасность cron

- секретный токен в конфиге (SNOD_CRON_TOKEN);
- проверка токена в query/header (CronSecurityService);
- опциональный allowlist IP.

### Защита от гонок

- lock в ps_snod_cron_lock на каждую задачу (LockManager);
- TTL lock + безопасное продление;
- при конфликте lock — корректный skip.

---

## 11. Логика конфигурации

Конфигурация должна быть разделена на области:

### Общие

- включение/выключение модуля;
- режим теста (dry-run для писем);
- уровень логирования.

### Генерация купонов

- целевые статусы заказа;
- тип скидки и величина;
- срок действия (дней/часов);
- минимальная сумма следующего заказа;
- маска кода (префикс, длина).

### Ограничения

- по группам клиентов;
- по категориям/производителям;
- по магазинам (multishop scope);
- совместимость с другими cart rules.

### Email

- включение письма при генерации;
- тема письма (multilang);
- включение/параметры напоминаний.

### Cron

- токен;
- размер batch;
- retries;
- расписание рекомендаций.

### Технически

- использовать Configuration API с учетом scope:
  - global;
  - shop group;
  - shop.
- дефолтные значения задаются при install() в главном файле (метод `getDefaultConfigurationValues()` + `updateConfigurationValues()`, как в лояльности);
- мультиязычные поля хранить массивами по id_lang;
- BO строго по эталону set_loyalty_milestones (см. раздел 0):
  - отдельная вкладка в меню через Tab (installTab в главном файле);
  - `getContent()` только для редиректа в NextOrderDiscountController;
  - внутри страницы админки табы: Dashboard, Settings, Coupons, Logs, Cron/Tools;
  - чтение/сохранение значений — через Configuration API в методах `{tab}Tab()`/postProcess контроллера.

### Нейминг технических сущностей

- SQL-таблицы модуля: префикс `snod_` (например, `ps_snod_coupon_link`).
- Конфигурационные ключи: префикс `SNOD_`.
- Внутренние correlation/lock ключи: префикс `snod:`.
- Домен переводов: `Modules.Setnextorderdiscount.Admin` (+ `.Shop` для фронта, `.Emails` для писем).

---

## 12. Приведение уже написанного кода к эталону

Начатый код частично расходится с эталоном. При реализации обязательно:

1. Переименовать админ-контроллер: `controllers/admin/AdminNextOrderDiscount.php` (класс `AdminNextOrderDiscountController`) → `controllers/admin/NextOrderDiscount.php` (класс `NextOrderDiscountController`); обновить `class_name` в installTab и `getAdminLink()` в `getContent()`.
2. Удалить `src/` полностью (`src/Infrastructure/Bootstrap/ModuleInstaller.php`, `ModuleUninstaller.php`, `src/Presentation/Admin/Tabs/TabRegistry.php`, `src/Presentation/Admin/Tab/AdminTabInstaller.php`) — их обязанности переходят в главный файл модуля и в админ-контроллер.
3. Удалить `config/services.yml`, `composer.json` и подключение `vendor/autoload.php` из главного файла.
4. Заменить `views/templates/admin/configure.tpl` + `tabs/nav.tpl` на `views/templates/admin/main.tpl` по эталону (nav-pills из `$arTabs` + `{include file="./tabs/$currentTabCode.tpl"}` + панель Need help).
5. Перевести контроллер на эталонную механику: массив `$tabs` в `initContent()`, параметр `tab` (вместо `snod_tab`), методы `{tab}Tab()`, smarty-переменные `arTabs/currentTabCode/...`, `setTemplate('main.tpl')`.
6. Привести `install()`/`uninstall()` к порядку шагов эталона (см. раздел 0); `sql/install.php`/`sql/uninstall.php` — формат `$sql[]` как в лояльности.
7. Добавить `index.php`-заглушки во все директории и лицензионные header во все файлы.

---

## 13. Возможности для будущего расширения

1. Расширенные стратегии выдачи
- не только “следующий заказ”, но и “N-й заказ”, “день рождения”, “после категории X”.

2. Сегментация и personalization
- RFM-сегменты;
- разные офферы по LTV/частоте покупок.

3. A/B тестирование
- разные скидки/шаблоны email;
- измерение uplift по конверсии купона.

4. Rule Engine
- декларативные правила через UI-конструктор условий.

5. Каналы коммуникаций
- кроме email: web push, SMS, мессенджеры (через адаптеры).

6. BI и отчеты
- воронка: generated -> emailed -> reminded -> used -> expired;
- ROI кампании по магазинам/языкам/сегментам.

7. API интеграции
- экспорт событий в CRM/CDP;
- webhook по жизненному циклу купона.

8. GDPR/Compliance
- гибкая ретенция данных;
- анонимизация старых записей.

---

## Дополнительные технические акценты для коммерческого качества

- Полная деинсталляция:
  - удаление конфигов;
  - удаление собственных таблиц (по флагу в uninstall);
  - удаление вкладок/cron токенов/служебных данных.
- Тестируемость:
  - unit-тесты доменных политик;
  - интеграционные тесты CartRuleAdapter/CouponMailer;
  - smoke-тесты cron сценариев.
- Производительность:
  - batch-обработка;
  - индексы под фильтры cron;
  - отсутствие тяжелой логики в hook без очереди.
- Обратная совместимость PS 8.1/9:
  - избегать нестабильных internal API;
  - использовать публичные сервисы и ObjectModel/Adapter уровни;
  - флаг `isPs9` для отличий верстки BO (как в лояльности).

---

## Результат внедрения

В итоге модуль получает предсказуемую и расширяемую архитектуру, идентичную по базовому каркасу set_loyalty_milestones:

- безопасная автоматическая выдача персонального купона;
- стабильная доставка email и напоминаний;
- прозрачный аудит и контроль жизненного цикла;
- готовность к масштабированию под multishop и будущие бизнес-сценарии;
- единый паттерн поддержки обоих модулей (одинаковая установка, табовая админка, upgrade-скрипты).
