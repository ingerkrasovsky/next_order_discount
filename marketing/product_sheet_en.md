# Next Order Discount module listing for PrestaShop

Ready-to-use English copy for the product listing, the module website and sales materials. The wording describes the actual capabilities of version 1.0.0, with no promises of guaranteed sales growth.

---

## 1. Module name

### Recommended option

**Next Order Discount: personal coupon for the next order**

The name explains the mechanics of the module straight away, highlights the personal nature of the offer and contains the main search phrase "coupon for the next order".

### Option focused on the business goal

**Personal coupon for the next order — bring your customers back**

### Option focused on automation

**Automatic personal coupon after the purchase**

---

## 2. Short description

**Create a reason to buy again the moment an order is completed: issue personal coupons automatically, send the emails and reminders, and track the result in a single funnel.**

---

## 3. Full description

# Bring your customer back with a personal coupon

Once the order is complete, send your customer a personal coupon for their next purchase — a percentage discount, a fixed amount or free shipping.

**Next Order Discount** creates a personal coupon as soon as an order matches the conditions you set, and emails the customer the code together with its terms of use. If the coupon stays unused, the module can send up to two automatic reminders. Issuing rules, emails and statistics are all available in the PrestaShop back office.

## How it works

1. You create a rule and choose which orders take part in the campaign.
2. When an order matches every condition and reaches the required status, the module creates a personal coupon and immediately emails it to the customer.
3. The module sends the scheduled reminders automatically, retries failed sends and updates the coupon statuses.
4. The dashboard shows you the journey of your coupons, from creation to redemption.

After the initial setup the campaign runs on its own, and the results stay under your control.

## Build different offers for different goals

Instead of one blanket discount, build separate offers for new customers, regulars, large orders, particular countries or selected product categories. Each rule defines who gets a coupon, what benefit they see and how long they have to use it.

Every rule offers three types of benefit:

- a percentage discount;
- a fixed discount amount;
- free shipping.

The coupon validity period and the minimum next order amount are set separately.

Example scenarios:

- **10% after the first order** — gently nudge a new customer towards a second purchase;
- **€15 off after an order of €120 or more** — reward customers with a high basket value;
- **free shipping for regulars** — offer a privilege to a specific group;
- **a seasonal coupon for selected brands or categories** — support a targeted campaign without discounting the whole catalogue.

## Issue a coupon only to the right customers

All the conditions inside a rule are checked at the same time. You can configure:

- the order statuses that create a coupon;
- the minimum and maximum total of the source order;
- the campaign period;
- the customer's order number — for example, the first order only, or from the third onwards;
- customer groups;
- countries;
- currencies;
- product categories;
- brands.

List conditions come with three modes: "All", "Only the selected" and "All except the selected".

Rules are evaluated by priority. The stop option lets you decide whether the customer receives a single coupon or several coupons from different matching rules.

## Tailor every email to the campaign

Each rule has its own set of emails:

- the main coupon email;
- the first reminder;
- the second reminder.

The subject and the HTML content can be configured separately for every shop language. Placeholders insert the code and the discount value, the expiry date, the minimum order amount, the customer name, the shop name and other data.

Before launching the campaign, open the email preview and send a test copy to your own address.

## Draw attention back to an unused coupon

Set up one or two reminders and choose how the date is calculated:

- a given number of days after the main email was sent;
- a given number of days before the coupon expires.

If the coupon has already been used, has expired or has been canceled, no further reminders are sent.

## Control the promo code format

For each rule you can configure:

- the length of the random part of the code;
- the character set — letters, digits or a combination of both;
- a template with the `%key%` variable.

For example, the template `RETURN-%key%` can produce the code `RETURN-AB12CD8X`.

## Track the result in a single funnel

The dashboard shows the number of coupons at each stage:

**generated → emailed → reminded → used → expired → canceled**.

It also calculates the conversion from generated to used coupons and displays the state of the resend and reminder queue. This helps you judge the real uptake of the offer and spot a sending problem in time.

## Automate the background tasks

The cron scheduler handles planning and sending the reminders, retrying after a failed main email, and moving lapsed coupons to the corresponding status.

On the tools tab you can:

- install the cron task automatically, if the server supports it;
- copy a ready-made command for a system or external scheduler;
- run all the tasks manually to check them;
- see the time of the last run and the state of each task;
- check the resend and reminder queue.

## Protect the campaign from mistakes and needless discounts

- For one source order and one rule, the coupon is created only once.
- If the source order is canceled or refunded, the associated coupon can be deactivated automatically.
- Random codes are checked for uniqueness.
- The event log helps you track down errors and follow the background operations.
- The log retention period is configurable.

## What the shop gains

- a ready-made customer win-back scenario that starts the moment an order is completed;
- personal offers instead of one discount for everyone;
- personal coupons built on standard PrestaShop cart rules;
- configurable emails and up to two reminders;
- control over the validity period and automatic cancellation of coupons that are no longer relevant;
- a coupon usage funnel and a log of the module's activity;
- multistore support and emails in the shop languages.

---

## 4. Main features

### Rules and discounts

- an unlimited number of rules;
- rule priority and stop-after-this-rule;
- percentage discount, fixed amount or free shipping;
- validity period and minimum next order amount for each rule;
- a personal coupon tied to the customer;
- protection against issuing twice for the same order and rule;
- a configurable coupon code format.

### Issuing conditions

- selected order statuses;
- source order total range;
- rule activity period;
- customer's order number;
- customer groups, countries and currencies;
- product categories and brands;
- include and exclude modes for list conditions.

### Emails and reminders

- a separate main email for each rule;
- up to two reminder emails;
- email texts for every shop language;
- dynamic placeholders;
- email preview;
- test send;
- reminders stop automatically once the coupon is used, canceled or expired.

### Control and automation

- coupon status funnel and usage conversion;
- list of issued coupons with filters;
- manual resend of the email and the reminders;
- resend and reminder queue;
- cron setup assistant;
- manual task runs and task state monitoring;
- automatic coupon expiry;
- automatic coupon cancellation on selected source order statuses;
- event log with filters and a configurable retention period;
- debug mode;
- multistore support.

---

## 5. Questions and answers

### How is this module different from an ordinary promo code?

An ordinary promo code is usually advertised to every customer before or during the current purchase. Next Order Discount creates a personal coupon for a specific customer after a qualifying order and motivates them to come back for their next purchase.

### When is the coupon created?

When the order reaches one of the statuses selected in the rule and, at the same time, matches all the other conditions of that rule.

### Can the coupon be issued only after the first order?

Yes. Set both the minimum and the maximum order number to 1. You can configure a campaign for the second, third or later orders in the same way.

### Can I offer different discounts to different customers?

Yes. Create several rules with different conditions, discounts and priorities. You can take into account the customer group, country, currency, order total, products from specific categories or brands, and other parameters.

### Can several coupons be issued for one order?

Yes, if the order matches several rules and the stop option is not enabled in those rules. If you only want one coupon, set the priorities and enable the stop option on the rule you need.

### Which discount types are supported?

Percentage discount, fixed amount and free shipping.

### Can I require a minimum amount on the next order?

Yes. The minimum amount for redemption is set separately for each rule.

### Does the module send the coupon email itself?

Yes. After a qualifying order changes status, the module creates the coupon and immediately sends the email through the PrestaShop mail system. If the send fails, the email goes into a queue for another attempt via cron.

### How many reminders can be sent?

Up to two. Each reminder can be disabled, and its send date can be calculated from the main email or from the coupon expiry date.

### Will a reminder be sent after the coupon has been used?

No. Reminders are not scheduled for used, expired or canceled coupons.

### What happens if the source order is canceled or refunded?

If the new order status is on the list of cancellation statuses, the module deactivates the associated coupon and marks it as canceled.

### Can I change the email text?

Yes. For each rule and each shop language you can change the subject and the HTML content of the main email and of the two reminders. Preview and test send are available.

### Will the customer see a new block on the storefront?

No. The module works after the order and announces the coupon by email, so it does not require a widget to be embedded in the shop theme.

### How do I measure the effectiveness of the campaign?

The dashboard shows how many coupons were generated, emailed, used, expired and canceled, together with the conversion from generated to used coupons.

### Is multistore supported?

Yes. Data and settings respect the selected shop, and the all-shops mode gives you consolidated statistics.

### Which versions are supported?

The module is designed for PrestaShop 8.1 and newer. The minimum PHP version is 7.2; the server must also meet the system requirements of the installed PrestaShop version.

---

## 6. Search keywords

next order coupon, next order discount, promo code after purchase, automatic coupon, personal coupon, repeat purchase, repeat sales, customer win-back, customer retention, discount after order, coupon email, coupon reminder, discount automation, PrestaShop discount rules, PrestaShop coupon, PrestaShop loyalty program, free shipping next order, coupon conversion

---

## 7. Text for the "What's new" section

**Version 1.0.0 — first release**

- Automatic creation of a personal coupon once the order moves to the selected status.
- An unlimited number of rules with priority and flexible conditions.
- Percentage discount, fixed amount or free shipping.
- Targeting by order total and order number, campaign period, groups, countries, currencies, categories and brands.
- Dedicated emails for each rule and each shop language.
- Up to two automatic reminders.
- Configurable coupon code format.
- Automatic cancellation and expiry handling.
- Coupon funnel, resend and reminder queue, cron tools and event log.
- Multistore support.

---

## 8. Recommended emphasis for the product listing

The first screen of the listing should answer three questions:

1. **What does the module do?** It creates a personal coupon after an order.
2. **Why is it needed?** It gives the customer a concrete reason to come back.
3. **Why is the solution convenient?** Rules, emails, reminders and monitoring are gathered in one module.

Recommended headline for the first screen:

> **Turn a completed order into a reason for the next purchase**

Recommended subheading:

> **Create personal coupons after the purchase, remind customers of the benefit and track the result in a single funnel.**

For the listing screenshots, the best things to show are:

1. the dashboard with the coupon funnel;
2. the rules list and their priorities;
3. the targeting conditions;
4. the discount and validity settings;
5. the email editor and preview;
6. the list of issued coupons;
7. the state of cron and of the resend and reminder queue.

Use one short point per image rather than a list of every feature. The through-line of the whole listing: **"A personal reason to come back — automatically, after every qualifying order"**.
