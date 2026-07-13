# Next Order Discount

## Overview

`set_next_order_discount` is a commercial PrestaShop module (PrestaShop 8.1+) that
automatically issues personal next-order discount coupons when an order reaches a
qualifying state.

Discounts are driven by a **rule engine**: instead of a single global discount,
the merchant defines any number of **rules**, each pairing a set of conditions
with a discount outcome. When an order matches, one coupon is created per matched
rule.

## Rules

Each rule has:

- **Outcome:** discount type (percentage, fixed amount, free shipping), value,
  validity period, and an optional minimum amount for the next order.
- **Conditions** (all must pass): trigger order statuses (empty = any paid
  status), source order total range, active date window, customer order-number
  range (e.g. first order only), and list conditions with an
  `all` / `include` / `exclude` mode — customer groups, countries, currencies,
  product categories and brands.
- **Priority** and a **"stop after this rule"** flag. Rules are evaluated in
  priority order; a matched rule with the stop flag ends evaluation.

### Single vs multiple coupons

With the stop flag enabled on the highest-priority matching rule, an order gets a
single coupon. With the flag disabled, several matching rules can each issue their
own coupon.

> **Note on stacking:** multiple next-order coupons issued to the same customer
> may stack in their next cart depending on the compatibility settings of the
> underlying PrestaShop cart rules.

## Compatibility

- PrestaShop: 8.1+
- PHP: 7.2+

## Installed Database Tables

- `PREFIX_snod_rule` and its condition tables: `PREFIX_snod_rule_status`,
  `PREFIX_snod_rule_group`, `PREFIX_snod_rule_country`,
  `PREFIX_snod_rule_currency`, `PREFIX_snod_rule_category`,
  `PREFIX_snod_rule_manufacturer`
- `PREFIX_snod_coupon_link`
- `PREFIX_snod_dispatch_queue`
- `PREFIX_snod_cron_lock`

## Registered Hooks

- `actionValidateOrder`
- `actionOrderStatusPostUpdate`

## Configuration Keys (global)

- `SNOD_ENABLED` — module active
- `SNOD_DEBUG_MODE` — verbose logging
- `SNOD_CODE_PREFIX`, `SNOD_CODE_LENGTH`, `SNOD_CODE_MASK` — coupon code format

Discount, validity, minimum amount and trigger statuses are stored per rule, not
in global configuration.

## Back Office

- **Rules** — create/edit rules (conditions and discount), reorder priority,
  enable/disable, delete.
- **Settings** — global toggles and coupon code format.
- **Coupons** — issued coupons with their originating rule, customer and status.

## Installation Check

1. Install the module in Back Office.
2. Verify that `snod_` tables exist and a default rule is seeded.
3. Create a rule, move an order to a qualifying status, and confirm the coupon
   appears in the Coupons tab.
4. Uninstall the module.
5. Verify that the tables and all `SNOD_*` configuration keys are removed.
6. Reinstall to confirm clean repeated installation.
