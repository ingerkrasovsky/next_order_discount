# Next Order Discount

## Overview

`set_next_order_discount` is a commercial PrestaShop module compatible with PrestaShop 8.1+.

Stage 1 provides the technical foundation:

- module install and uninstall flow;
- SQL schema bootstrap for core `snod_` tables;
- base hook registration;
- default configuration bootstrap.

## Compatibility

- PrestaShop: 8.1+
- PHP: 7.2+

## Installed Database Tables

- `PREFIX_snod_coupon_link`
- `PREFIX_snod_dispatch_queue`
- `PREFIX_snod_cron_lock`

## Registered Hooks

- `actionValidateOrder`
- `actionOrderStatusPostUpdate`

## Configuration Keys

- `SNOD_ENABLED`
- `SNOD_DISCOUNT_TYPE`
- `SNOD_DISCOUNT_VALUE`
- `SNOD_VALIDITY_DAYS`
- `SNOD_MIN_ORDER_AMOUNT`
- `SNOD_TARGET_STATUSES`
- `SNOD_DEBUG_MODE`

## Installation Check

1. Install the module in Back Office.
2. Verify that `snod_` tables exist in the database.
3. Uninstall the module.
4. Verify that the tables and `SNOD_*` configuration keys are removed.
5. Reinstall to confirm clean repeated installation.