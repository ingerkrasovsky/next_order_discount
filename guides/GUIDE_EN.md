_Module version: 1.0.0 (Next Order Discount)_

**Next Order Discount** is a module that automatically issues the customer a personal coupon for their **next order** once their current order reaches the required status.

The module is driven by **rules**. In each rule you specify the conditions under which the customer receives a coupon and which discount applies. You can create as many rules as you need. When an order matches the conditions of one of them, the module creates a coupon and manages its entire lifecycle itself: it emails the customer and sends the reminders, watches the expiry date and cancels the coupon if the order is refunded.

What the customer gets:

- a personal coupon code for their next purchase;
- an email with the coupon right after the order is placed;
- one or two reminders while the coupon is neither used nor expired;
- a clear validity period and, where needed, a minimum amount for the next order.

**Module capabilities:**

- **discount rules**: create as many rules as you need, give each its own conditions and discount, and control the order in which they apply through priority;
- three discount types: **percentage**, **fixed amount**, **free shipping**; validity period and minimum next order amount per rule;
- **flexible trigger conditions**: the order statuses that issue a coupon, the source order total range, an activity window by date, the customer's order number (for example, "first order only"), exclusion of guest orders, plus list conditions in **All / Include / Exclude** mode — customer groups, countries, currencies, product categories and brands;
- the **Stop after this rule** flag and priority: either one coupon per order, or several coupons from different rules;
- a **custom code format** for each rule: length, character set (letters / digits / alphanumeric) and a template with the `%key%` placeholder (for example `NOD-%key%`);
- **dedicated emails per rule**: the coupon email and two reminder emails, separately **for each shop language**, with preview, test send and a language choice for manual sends from the coupon list;
- **reminders**: 1st and 2nd email, counted either from the coupon email date or from the expiry date; they stop on their own once the coupon is used or expired;
- **automatic cancellation** of the coupon if the source order moves to a canceled or refunded status;
- background tasks via **cron** (email queue, reminder planning, coupon expiry) with a setup assistant, state checks and manual runs;
- a **Dashboard** — the coupon funnel (generated → emailed → reminded → used → expired → canceled), conversion and a daily dynamics chart for the last 30 days;
- an event **log (Logs)** for the module with filters and a retention period;
- **multistore** support (rules, coupons and settings in the context of the selected shop) and multilingual emails.

**Example:**

- Rule: "10% off the next order, valid 30 days, for orders from €100".
- The customer placed a €150 order and the order reached the paid status.
**→ a personal −10% coupon was created for the customer, the email was sent, and a reminder will arrive a few days later if the coupon has not been used.**

_Screenshot: how the customer receives the coupon for their next order by email._
![img.png](img.png)



<a id="toc"></a>

## Contents

1. [Where to open the module in the back office](#t1)
2. [Which tabs are available](#t2)
3. [How the module works (coupon lifecycle)](#t3)
4. [Dashboard tab: funnel and dynamics](#t4)
5. [Rules tab: the rules table](#t5)
    - [Table structure](#t6)
    - [Available actions](#t7)
    - [Priority and rule order](#t8)
6. [Creating and editing a rule (Rule)](#t9)
    - [6.1 General tab (basics)](#t10)
    - [6.2 Conditions tab](#t11)
    - [6.3 Code tab (code format)](#t12)
    - [6.4 Email tab (messages)](#t13)
    - [Form actions](#t14)
7. [Coupons tab: issued coupons](#t15)
    - [Filters](#t16)
    - [Table columns](#t17)
    - [Sending the email and reminders manually](#t18)
8. [Settings tab](#t19)
9. [Cron/Tools tab: background tasks](#t20)
    - [Setting up cron](#t21)
    - [State of the background tasks](#t22)
    - [Dispatch queue](#t23)
10. [Logs tab: the log](#t24)
11. [Support](#t25)
12. [Quick start (setup in 5–10 minutes)](#t26)
13. [Diagnostic checklist](#t27)

---

<a id="t1"></a>

## 1. Where to open the module in the back office

1. Sign in to the PrestaShop back office.
2. Open the **Catalog** section.
3. Find the **Next Order Discount** entry.

The module installs its own tab in the "Catalog" menu and opens on its own page with internal tabs (Dashboard, Rules, Coupons, Settings, Cron/Tools, Logs).

_Screenshot: the module entry in the "Catalog" section._
![img_1.png](img_1.png)

<a id="t2"></a>

## 2. Which tabs are available

When opened, the module shows the **Dashboard** tab by default. The available tabs are:

- **Dashboard** — the coupon funnel, conversion and the daily dynamics of coupon issuing, sending and usage over the last 30 days. Opens by default.
- **Rules** — rule management: create, edit, enable/disable, prioritise, delete. This is where the discounts and the conditions for issuing them are defined.
- **Coupons** — a list of every issued coupon with its rule, customer, status and validity period; manual resending of the email and the reminders.
- **Settings** — general module settings: enabled/disabled, coupon cancellation statuses, debug mode, log retention period.
- **Cron/Tools** — background task (cron) setup: installation assistant, task links, state checks, manual runs, queue snapshot.
- **Logs** — the module's event log with filters by level and channel.

One more sub-page opens on demand:

- **Rule** — the form for creating/editing a single rule (opened from the Rules tab).

If multistore is enabled, everything is read and saved **in the context of the shop selected at the top**. In the "All shops" context, the coupon lists, the funnel, the dynamics chart and the queue are shown aggregated across all shops.

At the bottom of every module page there is a **Need help?** block with a link to the PrestaShop Addons contact form.

_Screenshot: the module's internal tab bar._
![img_2.png](img_2.png)

---

<a id="t3"></a>

## 3. How the module works (coupon lifecycle)

Understanding the overall logic makes the rules faster to configure.

1. **Trigger.** The module listens to order events (order validation and status change). As soon as an order reaches a suitable status, the rule check starts.
2. **Rule selection.** Rules are checked **by priority** (top to bottom in the Rules table). For each rule, all of its conditions are verified. If the rule matches, a coupon is created from it (a personal PrestaShop cart rule tied to the customer).
   - If the matched rule has the **Stop after this rule** flag enabled, the check ends there — the customer receives **one** coupon.
   - If the flag is off, the following rules are checked too, and every matching rule can issue **its own** coupon.
3. **Idempotency.** For a single source order, a coupon is created only once per rule — repeated hook triggers do not produce duplicates.
4. **Email.** Right after the coupon is created, the module tries to email the customer. If the send fails, the email is queued and cron retries it (see [Cron/Tools](#t20)). The automatic send uses the language of the source order; if it is unavailable, the module falls back in turn to the customer account language and the shop's default language.
5. **Reminders.** If reminders are enabled on the rule, the module schedules the 1st and 2nd email. They are sent while the coupon is **neither used nor expired**.
6. **Usage.** When the customer applies the coupon to a new order, the matching record is marked **used** and its reminders stop.
7. **Expiry.** At the end of the validity period, the coupon moves to the **expired** status (by a background task).
8. **Cancellation.** If the **source order** moves to a status from the cancellation list (by default "Canceled" and "Refunded"), the coupon it issued is deactivated and marked **canceled**, and no new coupon is created for that order.

The coupon statuses you will see in the module: **created** → **emailed** → **reminded** → **used** / **expired** / **canceled**.

---

<a id="t4"></a>

## 4. Dashboard tab: funnel and dynamics

The **Dashboard** tab shows the overall results of your rules and the change in the key metrics day by day, in the context of the selected shop.

### Coupon funnel
Six tiles with the number of coupons at each stage and their share of the generated total. The percentage appears next to the figure and on the coloured bar below it; no percentage is shown for **Generated**, because it is the baseline value:

- **Generated** — the total number of coupons generated (the baseline for the percentages).
- **Emailed** — how many had the coupon email sent.
- **Reminded** — how many had at least one reminder sent.
- **Used** — redeemed by customers.
- **Expired** — past their validity period.
- **Canceled** — voided (including because the source order was refunded).

Below the funnel sits **Conversion (used vs generated)**: the share of used coupons out of the generated ones. This is the key performance indicator for the campaign.

> The funnel stages can overlap and do not have to add up to 100%: a used or expired coupon, for instance, still counts among those previously emailed, if the coupon email went out successfully.

### Daily dynamics

The line chart shows the metrics for each day over the last **30 days**, today included:

- **Generated** — how many coupons were created that day;
- **Emailed** — how many coupon emails were sent successfully that day;
- **Used** — how many coupons were used that day.

All three lines share one scale, so they can be compared directly. Hover over a point to see the values for that day. Clicking a metric in the legend hides or restores the corresponding line; the chart then rescales automatically. If there has not been a single event in the last 30 days, the chart is not shown.

> In multistore, the data is shown in the context of the shop selected at the top (aggregated in "All shops").

_Screenshot: the Dashboard tab (funnel and daily dynamics)._
![img_3.png](img_3.png)

---

<a id="t5"></a>

## 5. Rules tab: the rules table

This is the main working area: every coupon-issuing rule for the current shop is listed here. The **Add a rule** button (in the page header) opens the rule creation form. After a fresh installation the module does not create an active rule automatically: until you add and enable a rule yourself, no coupons will be issued.

If there are no rules yet, a **No discount rules yet** warning is shown instead of the table, along with an extra **Add a rule** button — click it, or the button of the same name in the page header, to create your first rule.

<a id="t6"></a>

### Table structure

Each row is one rule. The columns are:

- **Priority** — the priority (a number) and the arrows for moving it up/down. Rules are checked in this order.
- **Name** — the internal name of the rule. Badges may sit next to it:
  - **Stop** — the "stop after this rule" flag is enabled;
  - a badge with a bell and days (for example `1d · 3d`) — reminders are enabled, with their timing.
- **Discount** — the resulting discount (for example `10%`, `15 €`, `Free shipping`).
- **Validity** — the coupon validity period in days.
- **Trigger statuses** — the order statuses that trigger the rule (or the **Any status** badge if there is no restriction).
- **Conditions** — the set of badges for the active conditions (groups, countries, currencies, categories, brands, ranges). A dash means there are no conditions.
- **Active** — the rule enable switch (Yes/No).
- **Actions** — editing and deletion.

_Screenshot: the rules table with its columns and badges._
![img_4.png](img_4.png)

<a id="t7"></a>

### Available actions

**Edit** — opens the rule editing form.

**Delete** — deletes the rule (with confirmation). Coupons already issued from it remain in the Coupons table.

**Active (Yes/No)** — a quick switch right in the row: a disabled rule takes no part in issuing coupons.

**Priority arrows (▲ ▼)** — move the rule up or down in the checking order.

_Screenshot: the Edit / Delete buttons and the Active switch._
![img_5.png](img_5.png)

<a id="t8"></a>

### Priority and rule order

Rules are checked **top to bottom** by priority. The order matters when:

- several rules have **Stop after this rule** enabled — the first matching rule by priority applies, and the rest are not checked;
- you want a more "specific" rule (for a VIP group, say) to get a chance to fire before a general one.

Priorities are automatically kept as a continuous sequence from 1 to N (reordering with the arrows renumbers them).

_Screenshot: moving a rule with the priority arrows._
![img_6.png](img_6.png)

---

<a id="t9"></a>

## 6. Creating and editing a rule (Rule)

The rule form opens with the **Add a rule** or **Edit** button. It is split into four tabs: **General**, **Conditions**, **Code**, **Email**. At the bottom are the **Save** and **Cancel** buttons (shared by all tabs).

<a id="t10"></a>

### 6.1 General tab (basics)

**Rule name** — the internal name, shown in the rules table. Required field.

**Voucher name** — the coupon name the customer sees. Empty = the default "Next Order Discount" is used.

**Voucher description** — an optional description, stored on the voucher (visible in the back office).

**Active** — whether the rule is enabled (Yes/No).

Discount block:

- **Discount type**:
  - **Percentage (%)** — a percentage of the next order total;
  - **Fixed amount** — a fixed sum (in the currency);
  - **Free shipping**.
- **Discount value** — the size of the discount. For a percentage it is capped at 100. The label on the right (`%` or the currency sign) is filled in automatically according to the chosen type; with the **Free shipping** type the field is hidden (no discount value is needed).
- **Validity period (days)** — the coupon validity period in days (a whole number, at least 1).
- **Minimum next order amount** — the minimum next order total at which the coupon applies. `0` — no restriction.

Issuing logic block:

- **Stop after this rule** — if Yes, no further rules are checked after this one fires (the customer receives one coupon). If No, other matching rules will be able to issue their coupons too.

Reminders block:

- **Send reminders** — enable reminder emails about an unused coupon. Reminders stop automatically once the coupon is used or expired.
- **Reminder timing** — what the days are counted from:
  - **Days after the coupon email** — N days after the coupon email;
  - **Days before the coupon expires** — N days before the coupon expires.
- **First reminder (days)** / **Second reminder (days)** — the timing of the 1st and 2nd reminder. `0` or empty = that reminder is not sent.

_Screenshot: the General tab of the rule form._
![img_7.png](img_7.png)

<a id="t11"></a>

### 6.2 Conditions tab

Every condition you set must be satisfied at the same time (AND logic). An empty or `All` condition restricts nothing.

**Trigger on order statuses** — the statuses in which an order can issue a coupon. The rule is checked when the order is created and on every change of its status. An empty list means the status does not restrict the rule. Hold Ctrl/Cmd to select several statuses. The coupon is issued only if the order also matches the rule's other conditions.

List conditions — each one has a mode and a list:

- **Customer groups**;
- **Countries** — by the order's delivery address;
- **Currencies** — the order currency;
- **Product categories** — the categories of the products in the order;
- **Brands** — the brands (manufacturers) of the products in the order.

The mode of each:

- **All (no restriction)** — do not restrict (the list is ignored);
- **Only the selected** — the rule applies only to the selected items;
- **All except the selected** — the rule applies to everything except the selected items.

The selection list itself only appears in the **Only the selected** / **All except the selected** modes; in **All** mode it is hidden so it does not get in the way.

Range conditions:

- **Source order total** — the total range of the source order (**Min** / **Max**). `0` = no restriction on that bound.
- **Active date window** — the rule's activity window (**From** / **To**). Both empty = the rule is always active.
- **Customer order number** — how many orders the customer must have (**Min** / **Max**). Both values at `1` = the first order only. `0` = no restriction. A customer's orders are counted in the current shop by email address, even if PrestaShop created several customer records for the same address; the current order is already included in that figure.
- **Registered customers only** — if **Yes**, guest orders take no part in the rule. If **No**, the rule checks registered customers and guests alike. It is recommended for "first order" and win-back scenarios: with guest checkout PrestaShop may create a new customer record every time, so a returning guest can only be identified reliably by a matching email address.

> Conditions on categories and brands look at the products of the **source order**. The module only loads product data when at least one active rule actually filters by category or brand — this saves resources on all other orders.

_Screenshot: the Conditions tab._
![img_8.png](img_8.png)

<a id="t12"></a>

### 6.3 Code tab (code format)

The coupon code format is set **per rule**. Any field can be left empty — the built-in default is then used.

- **Key length** — the number of random characters in `%key%` (clamped to the range 4–32).
- **Key type** — the character set used for generation:
  - **Alphabetic (A-Z)** — letters only;
  - **Numeric (0-9)** — digits only;
  - **Alphanumeric (A-Z, 0-9)** — letters and digits.
- **Key template** — the code template with the `%key%` placeholder. Example: `NOD-%key%` → `NOD-AB12CD8X`.

_Screenshot: the Code tab._
![img_9.png](img_9.png)

<a id="t13"></a>

### 6.4 Email tab (messages)

Each rule has **its own emails**, pre-filled with the default template. Three types are configurable:

- **Coupon email** — the coupon email (sent when the coupon is issued);
- **First reminder email** — the first reminder;
- **Second reminder email** — the second reminder.

For each type:

- a **language** switcher (by ISO code) — the subject and the HTML are set **separately for each language** of the shop;
- the **Subject** field — the email subject;
- the **HTML content** field — the HTML body of the email;
- below the HTML field, the **Available placeholders (click to insert)** line: clickable placeholder "chips". Clicking a chip inserts the placeholder straight into the HTML field at the cursor position — handy, so you do not have to type them by hand.

Placeholders replaced with real values when the email is sent:

- coupon: `{coupon_code}`, `{coupon_value}`, `{valid_to}`, `{minimum_amount}`;
- customer: `{customer_firstname}`, `{customer_lastname}`, `{customer_fullname}`, `{customer_title}` (the title, for example "Mr"/"Mrs"; empty if the gender is not set), `{customer_email}`;
- shop: `{shop_name}`, `{shop_url}`, `{shop_logo}`. The `{shop_url}` placeholder is supported when sending, but has to be entered in the HTML by hand where needed.

Each language of an email uses its own subject and HTML — texts from other language versions are not substituted in. If the subject or the HTML has not been saved for the selected language, the module uses the built-in template: French for **FR**, English for **EN** and every other language. Fill in and save the emails for all shop languages before enabling a rule.

Actions under each email:

- **Preview** — a preview of the email with sample placeholder values (opens in a window).
- **Send test email** — sends a test copy to the address you specify (also with sample values). Handy for checking the layout before the real send.

_Screenshot: the Email tab._
![img_10.png](img_10.png)

<a id="t14"></a>

### Form actions

- **Save** — validates and saves the rule, then returns to the Rules list. If validation fails, the form stays open with hints.
- **Cancel** — returns to the list without saving.

_Screenshot: the Save / Cancel buttons._
![img_13.png](img_13.png)

---

<a id="t15"></a>

## 7. Coupons tab: issued coupons

The **Coupons** tab is the list of every generated coupon (view only + manual sending actions).

<a id="t16"></a>

### Filters

- **Status** — filter by coupon status (created / emailed / reminded / used / expired / canceled) or "All statuses".
- **Code** — search by coupon code.
- The **Filter** and **Reset** buttons.

The list is paginated (30 records per page).

_Screenshot: the Coupons tab filters._
![img_11.png](img_11.png)

<a id="t17"></a>

### Table columns

- **Code** — the coupon code.
- **Customer** — the customer's name and email (or their id, if the name is unavailable).
- **Source order** — the number of the order that issued the coupon.
- **Rule** — the rule the coupon was created from.
- **Status** — the current status (with a coloured badge). Badges `1` / `2` may sit next to it — they show which reminders have already been sent.
- **Valid until** — the coupon validity period; the date and time are displayed in the format of the current PrestaShop locale.
- **Generated** — the date and time of creation, in the format of the current PrestaShop locale.
- **Actions** — the manual actions (see below).

_Screenshot: the coupons table._
![img_12.png](img_12.png)

<a id="t18"></a>

### Sending the email and reminders manually

While the coupon **can still be used** (not used / expired / canceled), the Actions column offers:

- **Language of the email to send** — the language choice for the manual send (shown if the shop has more than one language installed). The language of the source order is selected by default; the chosen value applies both to resending the coupon and to sending a reminder manually;
- the **envelope** button (tooltip **Send the coupon email to the customer again**) — resend the coupon email;
- the **bell with the number 1 / 2** buttons — send the corresponding reminder immediately (they appear if those reminders are enabled in the coupon's rule).

For used, expired and canceled coupons the actions are unavailable — their email and reminders no longer serve any purpose.

_Screenshot: the resend and reminder buttons in a coupon row._
![img_13.png](img_13.png)

---

<a id="t19"></a>

## 8. Settings tab

The **Settings** tab holds the module's general settings (discounts and conditions are defined in the rules, not here).

- **Module active** — the master switch. If **No**, no coupons are issued for new orders, whatever the rules say.
- **Cancel coupon on order statuses** — the order statuses whose arrival **cancels** the coupon issued by that order (it is deactivated and marked canceled), and no new coupon is created for that order. By default — "Canceled" and "Refunded". Empty = never cancel automatically. Select several with Ctrl/Cmd.
- **Debug mode** — verbose logging for troubleshooting. Keep it disabled in production.
- **Keep logs for (days)** — the retention period for log entries; older ones are deleted automatically (during cron). `0` = keep forever.

Click **Save** to apply. The settings are saved in the context of the current shop (multistore).

_Screenshot: the Settings tab._
![img_14.png](img_14.png)

---

<a id="t20"></a>

## 9. Cron/Tools tab: background tasks

After a coupon is created the module immediately tries to send the main email. If the send fails, the email goes into a queue and **cron** retries it. Cron also plans and sends the reminder emails and moves coupons past their expiry date to **expired**.

<a id="t21"></a>

### Setting up cron

The recommended approach is to add **one line** to the server crontab that calls the combined task over HTTP every 5 minutes. This approach works on any hosting and does not depend on the PHP version on the server.

The tab offers:

- **One-click install** — if automatic crontab setup is available on the server, the **Install cron automatically** button adds the required line, and **Remove cron** deletes it. The line is wrapped in markers and is also removed when the module is uninstalled. If automatic installation is unavailable (for example, `shell_exec` is blocked on shared hosting), the module explains why and offers the line to copy manually.
- **Crontab line (curl / wget)** — ready-made lines to paste into the crontab by hand.
- **Or use an external cron service** — the URL for external web-cron services (cron-job.org, for example), with a 5-minute interval.
- **Run all tasks now** — a manual run of all the tasks at once (a quick check that the endpoint works).
- **Your server** — an environment probe: PHP version, presence of curl (CLI), availability of shell_exec — to point you to the approach that will work.

> **Keep the token secret.** The task URLs contain a secret token: anyone who knows a URL can run the corresponding task.

_Screenshot: the cron setup block._
![img_15.png](img_15.png)

<a id="t22"></a>

### State of the background tasks

The **Tasks** table lists the background tasks, their recommended schedule, the time of the last run, their personal URL, the lock state and a manual run button.

The module's tasks:

- **Process the dispatch queue** — retries the failed send of the main email and sends the scheduled reminders. Recommended **every 5 minutes**.
- **Plan coupon reminders** — finds reminders that have come due and puts them in the queue. Recommended **every 30 minutes**.
- **Expire lapsed coupons** — moves lapsed coupons to expired. Recommended **once a day**.

The **Last run** column shows the state of the task: **OK** (running on time), **Late** (falling behind), **Not running** (has not run for a long time), **Never run** (not once). The **Lock** column shows whether the task is running right now (**Running**) or free (**Free**) — the lock keeps two runs from overlapping.

A separate **Managed cron** block reports whether the cron line was installed by the module itself.

_Screenshot: the tasks table with the schedule and the current state._
![img_16.png](img_16.png)

<a id="t23"></a>

### Dispatch queue

At the bottom sits a snapshot of the queue: **Pending / Processing / Done / Failed**. A growing Pending count or a noticeable Failed count is a reason to check cron and the mail settings.

_Screenshot: the dispatch queue snapshot._
![img_17.png](img_17.png)

---

<a id="t24"></a>

## 10. Logs tab: the log

The **Logs** tab is the module's event log (coupon issuing, email sending, hook errors and so on).

- The **Level** filter — the entry level: debug / info / warning / error (or "All").
- The **Channel** filter — the channel (for example `cron`, `queue`, `coupon`).
- The columns: **Date**, **Level** (with a coloured badge), **Channel**, **Message** (with context details), **Correlation** (the id that links entries belonging to one event).

The list is paginated. The logging depth depends on **Debug mode**, and the retention period on **Keep logs for** (both settings are on the [Settings](#t19) tab). In the context of a specific shop, the general cron and queue entries created without a link to a single shop are shown as well: this lets you see background errors even with debug mode off. The entry about issuing a coupon contains the `id_lang` and the ISO code of the language the automatic email will be sent in.

_Screenshot: the Logs tab with its filters._
![img_18.png](img_18.png)

---

<a id="t25"></a>

## 11. Support

At the bottom of the module pages there is a **Need help?** block with a link to the official PrestaShop Addons contact form:

https://addons.prestashop.com/contact-form.php

Get in touch if:

- you need help with the initial setup of the rules or cron;
- coupons or emails are not behaving as expected;
- you need an enhancement or a customisation of the functionality;
- you run into errors or unstable behaviour;
- you have ideas for improvements.

_Screenshot: the support block._
![img_19.png](img_19.png)

---

<a id="t26"></a>

## 12. Quick start (setup in 5–10 minutes)

1. Open the **Settings** tab and set **Module active = Yes**. Configure **Cancel coupon on order statuses** if needed. Click **Save**.
2. Set up **cron** on the **Cron/Tools** tab: click **Install cron automatically** (if available), or copy the recommended line into the crontab / an external web-cron service. Click **Run all tasks now** to check it.
3. Create a rule on the **Rules → Add a rule** tab:
   - **General**: the name, the discount type and value, the validity period and, if needed, the minimum next order amount and the reminders;
   - **Conditions**: the order statuses that issue the coupon and, where needed, the restrictions (groups, countries, totals, order number and so on); for first-order / returning-customer rules, decide whether to enable **Registered customers only**;
   - **Code**: the code format (or leave the default);
   - **Email**: check the email texts, use **Preview** and **Send test email**.
4. Save the rule and make sure it is **Active = Yes**.
5. Check the rule on a test order: move the order to one of the statuses listed in the rule and make sure a coupon appears on the **Coupons** tab and the email was sent. If the first attempt failed, run the background tasks manually on the **Cron/Tools** tab.
6. Track the results on the **Dashboard** tab: assess the funnel, the conversion and the daily dynamics over the last 30 days.

---

<a id="t27"></a>

## 13. Diagnostic checklist

**The coupon is not created:**

1. **Module active = Yes** (Settings).
2. There is at least one rule with **Active = Yes** (Rules).
3. The order really does move to one of the rule's **Trigger statuses** (or the rule accepts "any status").
4. The order matches **all** of the rule's conditions: total range, date window, customer's order number, customer type (guest or registered), groups/countries/currencies/categories/brands.
5. Check the order: if a higher-priority rule has **Stop after this rule**, the rules below it are not checked.
6. Take a look at the **Logs** (with **Debug mode = Yes** there are more entries).

**The customer is not receiving the email:**

1. Check the shop's mail configuration; send a **test email** from the rule's Email tab.
2. For a specific coupon you can choose the language you want and click the envelope button on the Coupons tab.
3. If the first send attempt ended in an error, make sure **cron** is working (Cron/Tools → the **Process the dispatch queue** task, status **OK**) and click **Run all tasks now** to retry.
4. The queue should not hold stuck **Pending** entries or a growing number of **Failed** ones (Cron/Tools → **Dispatch queue**).
5. If the email arrived in the wrong language, check that language's text on the rule's **Email** tab. Automatically the module uses the order language; for a manual send, the language selected next to the action buttons.

**Reminders are not being sent:**

1. The rule has **Send reminders = Yes** and the timing is set (**First/Second reminder** > 0).
2. The **Plan coupon reminders** task is working (Cron/Tools).
3. The coupon **can still be used** (not used / expired / canceled) — no reminders go out for coupons that are no longer valid.

**A coupon was canceled unexpectedly:**

- The source order moved to a status from the **Cancel coupon on order statuses** list (Settings). Remove the status from the list if that behaviour is not wanted.

**Coupons are not expiring (they stay in their old statuses):**

- The **Expire lapsed coupons** task is not working — check cron (Cron/Tools).
