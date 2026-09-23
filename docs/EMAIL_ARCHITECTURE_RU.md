# Next Order Discount — почтовая система

Документ описывает, **как модуль отправляет письма сейчас** (купон и напоминания),
и что изменилось в ходе рефакторинга. Версия модуля: 1.0.0.

---

## 1. Ключевая идея

- Есть **один тип оболочки** письма — тонкий pass-through шаблон.
- **Весь контент письма хранится в БД по каждому правилу и языку** и подставляется
  в оболочку при отправке.
- Язык письма — это **язык ЗАКАЗА** (на котором клиент оформил), а не язык его
  аккаунта; при ручной отправке можно явно выбрать язык. Язык заказа сохраняется в
  `snod_coupon_link.id_lang`. Какой файл шаблона лежит на диске — на язык письма не
  влияет.

```
Правило (БД snod_rule_email)                 Ядро PrestaShop
  subject + html  ──(подстановка плейсхолдеров)──►  Mail::send(iso получателя)
        │                                              │
        │ если пусто → shipped-дефолт                  ▼
        │ (DefaultEmailProvider)              mails/<iso>/<template>.html
        ▼                                     = {snod_body_html}  (pass-through)
  {snod_body_html} / {snod_body_txt}  ───────────────► готовое письмо
```

---

## 2. Шаблоны писем (`mails/`) — чистые pass-through

Файлы `mails/<iso>/next_order_discount.*` и `mails/<iso>/reminder_next_order_discount.*`
содержат **только переменные**, без HTML/текста:

- `*.html` → `{snod_body_html}`
- `*.txt`  → `{snod_body_txt}`

Купон отправляется через шаблон `next_order_discount`, напоминание — через
`reminder_next_order_discount`. Отдельного шаблона-обёртки (`custom`/`wrapper`) нет.

### Почему нужны папки по языкам и откуда они берутся
`Mail::send` ищет файл шаблона по iso получателя (`mails/<iso>/`), с фолбэком
**язык клиента → язык магазина → en**, и **пишет в лог «template missing»** на
каждый ненайденный iso. PrestaShop **не создаёт** эти папки для кастомного модуля
автоматически. Поэтому:

- В репозитории лежит только `mails/en/` (канонический pass-through + терминальный
  фолбэк ядра).
- Оболочки для остальных языков модуль **генерит сам**:
  - при установке — `MailTemplateInstaller::installForAllLanguages()` в `install()`
    (по всем установленным языкам, `Language::getLanguages(false)`);
  - при добавлении нового языка — хук `actionObjectLanguageAddAfter`
    (`hookActionObjectLanguageAddAfter` → `installForIso($iso)`); срабатывает и при
    установке языкового пака (идёт через `Language::add()`).

> ВАЖНО: новый хук на **уже установленном** модуле не регистрируется сам. Нужен
> reset модуля в BO (перерегистрирует все хуки) или ручной `registerHook`.

---

## 3. Источник контента — БД `snod_rule_email`

Каждое правило хранит собственный контент письма **по типу и языку**:

- типы: `coupon`, `reminder_1`, `reminder_2`;
- поля: `subject`, `html` (на каждый `id_lang`).

Редактируется в UI модуля (вкладка правила): плейсхолдеры «вставить», превью,
тест-письмо. Именно этот контент реально уходит клиенту. `txt`-версия письма
генерится из `html` автоматически (`htmlToText`).

Если у правила контент пуст — берётся shipped-дефолт (см. ниже), но письмо всё
равно собирается через ту же pass-through оболочку.

---

## 4. Shipped-дефолты — единый источник `DefaultEmailProvider`

`classes/Mail/DefaultEmailProvider.php` — **единственное** место, где живут:

- дефолтные темы (`SUBJECTS`) по типам;
- лейбл «Бесплатная доставка» (`FREE_SHIPPING_LABELS`);
- выбор iso для локализованных строк (`resolveIso()`).

Локализация дефолтов: **`en` + `fr`**. Любой другой язык → фолбэк на `en`.

Дефолтные тела письма (полный styled HTML) лежат **вне** `mails/` — в
`views/email_defaults/<iso>/` (`en`, `fr`): `next_order_discount.html`,
`reminder_next_order_discount.html`. Роли:

1. сид поля письма при создании нового правила;
2. last-resort контент, если у правила пусто.

Раньше `SUBJECTS`/`FREE_SHIPPING_LABELS` дублировались в трёх классах — теперь
только в `DefaultEmailProvider`; `MailTemplateResolver` ужат до путей/имени
шаблона, `CouponMailer`/`ReminderMailer` спрашивают дефолты у `DefaultEmailProvider`.

---

## 5. Сборка письма и плейсхолдеры

Мейлеры (`CouponMailer`, `ReminderMailer`) в `sendWrapped()`:

1. берут контент правила (или дефолт);
2. подставляют плейсхолдеры в `subject` и `html`;
3. отдают готовый HTML/текст в оболочку как `{snod_body_html}`/`{snod_body_txt}`.

### Категории плейсхолдеров
- **Персональные** — `buildTemplateVars()`: `{coupon_code}`, `{coupon_value}`,
  `{valid_to}`, `{minimum_amount}`, `{customer_firstname/lastname/fullname/title/email}`.
- **Магазинные** — `shopVars()`: `{shop_name}`, `{shop_url}`, `{shop_logo}`.

> GOTCHA: магазинные плейсхолдеры подставляем **мы сами** в `shopVars()`. Ядро
> `Mail::send` подставляет `{shop_name}` и т.п. в шаблон **до** вставки тела через
> `{snod_body_html}`, поэтому внутри тела правила оно их не ловит (симптом был:
> в письме приходило буквально `The {shop_name} team`). **Любой новый магазинный
> плейсхолдер в шаблоне нужно добавлять в `shopVars()`.**

Превью и тест-письмо в BO используют образцовые значения из `sampleEmailVars()`
(John Doe и т.д.) — держать их в синхроне с набором плейсхолдеров.

---

## 6. Выбор языка отправки

Приоритет в `resolveSendLang($orderLang, $customer, $idShop, $forceLang)` (в обоих
мейлерах), берётся первый доступный установленный язык:

1. **force** — явно выбранный язык при ручной отправке из BO;
2. **язык заказа** — `snod_coupon_link.id_lang` (то, на чём клиент оформил заказ);
3. **язык аккаунта** клиента (`customer.id_lang`);
4. **язык магазина** по умолчанию.

Почему это важно: PrestaShop создаёт клиенту запись аккаунта на дефолтном языке,
даже если он заказывал на другом. Раньше письмо шло на языке аккаунта → приходило
не на том языке. Теперь база — язык заказа (сохранён в `snod_coupon_link.id_lang`
при создании купона из `$order->id_lang`).

Контент под язык: `resolveRuleEmail()` берёт контент правила **строго на этом
языке**, иначе дефолт **того же языка** (без заимствования контента другого языка).

Ручная отправка (BO → Купоны):
- параметр `$forceLang` в `sendForCouponLink()` / `sendReminder()`;
- контроллер валидирует `id_lang` (`getRequestedSendLang()`) по установленным языкам;
- дропдаун языка в списке купонов (если языков > 1), **дефолт = язык заказа**
  (`cl.id_lang` → аккаунт → магазин);
- автоматическая отправка (cron/по статусу) идёт без `forceLang` → язык заказа.

---

## 7. Даты в списке купонов

Колонки «Valid until» и «Generated» выводятся **по локали PrestaShop** (с временем):
контроллер форматирует их через `Tools::displayDate($date, true)` в поля
`valid_to_display` / `generated_at_display`; пустые/нулевые даты → прочерк.

---

## 7a. Язык в логах

При выпуске купона лог-запись «Coupon issued» (и «already issued» / «generation
failed») содержит `id_lang` и `lang` (iso) — язык, на котором уйдёт письмо. Видно
по логу, на каком языке выпущен купон. Уровень `Coupon issued` = INFO (виден и при
выключенном debug).

---

## 8. Карта изменённых/ключевых файлов

| Файл | Роль |
|---|---|
| `mails/en/*` | Единственная канонная pass-through оболочка (+ фолбэк ядра) |
| `classes/Mail/MailTemplateInstaller.php` | Генерация оболочек по всем языкам (install + хук) |
| `classes/Mail/DefaultEmailProvider.php` | Единый источник дефолтов: subjects, free-ship, resolveIso, тела |
| `classes/Mail/MailTemplateResolver.php` | Только путь `mails/` и имя купон-шаблона |
| `classes/Mail/CouponMailer.php` | Отправка купона через pass-through + `shopVars()` + `$forceLang` |
| `classes/Reminder/ReminderMailer.php` | Отправка напоминания (аналогично) |
| `classes/Repository/RuleEmailRepository.php` | Контент писем по правилу/типу/языку (БД) |
| `classes/Coupon/CouponGenerationService.php` | Создание купона: сохраняет `id_lang` заказа, пишет язык в лог |
| `classes/Repository/CouponLinkRepository.php` | `snod_coupon_link` (+ колонка `id_lang`) |
| `classes/Rule/RuleMatcher.php` | Матчинг правил, вкл. `matchesGuest()` (исключение гостей) |
| `views/email_defaults/{en,fr}/*` | Дефолтные тела писем (сид + фолбэк), НЕ шаблоны отправки |
| `controllers/admin/NextOrderDiscount.php` | Ручная отправка с языком, превью/тест, формат дат |
| `views/templates/admin/tabs/coupons.tpl` | Список купонов: дропдаун языка, локальные даты |

---

## 9. Эксплуатация (dev / деплой)

- Дев-докер: `prestashop9.1` (:8123), модуль bind-mount в
  `Docker/Prestashop9.1/modules/set_next_order_discount` — **отдельная копия** от
  рабочей папки. После правок:
  ```
  rsync -a --exclude='.git' --exclude='.idea' --exclude='.DS_Store' <рабочая>/ <докер>/
  ```
  (без `--delete` — иначе снесёт сгенерированный `config.xml`; устаревшее удалять
  точечно), затем сброс кэша:
  ```
  docker exec prestashop9.1 php /var/www/html/bin/console cache:clear
  ```
- После изменения списка хуков — **reset модуля в BO** (иначе новый хук не
  зарегистрируется).
- Добавили язык после установки без reset — оболочку под него можно догенерить
  переустановкой модуля или ручным вызовом `MailTemplateInstaller`.
- **Изменения схемы (на существующих установках нужен ALTER или reinstall):**
  ```sql
  ALTER TABLE `ps_snod_coupon_link` ADD COLUMN `id_lang` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `id_customer`;
  ALTER TABLE `ps_snod_rule` ADD COLUMN `exclude_guests` TINYINT(1) NOT NULL DEFAULT 0 AFTER `customer_order_count_max`;
  ```
  (префикс `ps_` заменить на свой). Старые строки получают `0` = прежнее поведение.

---

## 10. Смежные недавние правки (таргетинг и логи)

Не про саму «оболочку письма», но входят в последние правки и влияют на то, кому и
когда уходит письмо:

- **«Первый заказ» считается по email, а не по `id_customer`.** PrestaShop создаёт
  новую запись customer почти на каждый заказ (гости, дубли), поэтому счёт по
  `id_customer` всегда давал 1 → правило «after first order» срабатывало каждый раз.
  Теперь `countCustomerOrdersByEmail()` объединяет все заказы с одним email.
  Оговорка: гость с новым email каждый раз — это разные личности, дедупнуть нельзя.
- **Переключатель правила «Registered customers only» (исключить гостей).** Колонка
  `snod_rule.exclude_guests`; при `1` гостевой заказ (`customer.is_guest`) не
  матчится (`RuleMatcher::matchesGuest()`). Рекомендуется для «first order» /
  comeback-правил.
- **Пустая страница логов при выключенном debug — исправлено.** Cron из CLI пишет
  логи с `id_shop = 0`, а страница фильтровала по текущему магазину. Теперь
  шоп-скоуп включает глобальные записи: `(id_shop = <shop> OR id_shop = 0)`.
