# Next Order Discount — почтовая система

Документ описывает, **как модуль отправляет письма сейчас** (купон и напоминания),
и что изменилось в ходе рефакторинга. Версия модуля: 1.0.0.

---

## 1. Ключевая идея

- Есть **один тип оболочки** письма — тонкий pass-through шаблон.
- **Весь контент письма хранится в БД по каждому правилу и языку** и подставляется
  в оболочку при отправке.
- Язык письма определяется **языком получателя** (или явно выбранным при ручной
  отправке), а не тем, какой файл шаблона лежит на диске.

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

## 6. Выбор языка при ручной отправке (BO → Купоны)

- В `sendForCouponLink()` и `sendReminder()` есть параметр `$forceLang`.
- Контроллер валидирует `id_lang` (`getRequestedSendLang()`) по установленным языкам.
- В списке купонов — дропдаун языка (если языков > 1), **дефолт = язык покупателя**
  (`c.id_lang`, фолбэк на язык магазина).
- Автоматическая отправка через очередь (cron) идёт без `forceLang` → язык покупателя.

---

## 7. Даты в списке купонов

Колонки «Valid until» и «Generated» выводятся **по локали PrestaShop** (с временем):
контроллер форматирует их через `Tools::displayDate($date, true)` в поля
`valid_to_display` / `generated_at_display`; пустые/нулевые даты → прочерк.

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
