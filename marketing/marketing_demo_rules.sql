-- Marketing-ready demo rules for the local PrestaShop Docker database.
--
-- Target environment:
--   database: prestashop90
--   table prefix: ps
--   shop: store1 (id_shop = 1)
--
-- The statements update only the 14 demo rules that already exist. Conditions,
-- email templates and generated coupons are not deleted or recreated.

START TRANSACTION;

UPDATE `pssnod_rule`
SET
    `name` = 'New customer comeback - 10% after first order',
    `voucher_name` = '10% off your second order',
    `voucher_description` = 'A thank-you reward for your first purchase. Use it on your next order within 30 days.',
    `active` = 1,
    `priority` = 1,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'SECOND-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 30 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Premium order reward - free shipping from 500',
    `voucher_name` = 'Free shipping on your next order',
    `voucher_description` = 'Thank you for your premium order. Enjoy free shipping on your next purchase within 45 days.',
    `active` = 1,
    `priority` = 2,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'SHIP-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 28 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'High-value order reward - 20% from 100 to 500',
    `voucher_name` = '20% off your next order',
    `voucher_description` = 'A personal thank-you for an order between 100 and 500. Redeem it within 60 days.',
    `active` = 1,
    `priority` = 3,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'PREMIUM-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 29 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Spend booster - 5 off after orders from 50 to 100',
    `voucher_name` = '5 off your next order',
    `voucher_description` = 'Use this reward on a next order of at least 50. The offer is valid for 14 days.',
    `active` = 1,
    `priority` = 4,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'SAVE5-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 27 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Studio Design follow-up - 10% + reminder',
    `voucher_name` = 'Your Studio Design reward',
    `voucher_description` = 'Thank you for choosing Studio Design. Enjoy 10% off your next order within 30 days.',
    `active` = 1,
    `priority` = 6,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'DESIGN-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 39 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Clothing follow-up - 15%',
    `voucher_name` = '15% off your next order',
    `voucher_description` = 'Purchased from our clothing collection? Enjoy 15% off your next order within 30 days.',
    `active` = 1,
    `priority` = 5,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 1,
    `code_template` = 'STYLE-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 34 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'France & US retention - 12%',
    `voucher_name` = '12% thank-you discount',
    `voucher_description` = 'A personal reward for customers in France and the United States. Valid for 30 days.',
    `active` = 1,
    `priority` = 7,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'THANKS-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 32 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'US-dollar market reward - $10',
    `voucher_name` = '$10 off your next order',
    `voucher_description` = 'A fixed-value reward for orders placed in USD. Redeem it on your next purchase within 30 days.',
    `active` = 1,
    `priority` = 8,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'EURO-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 33 AND `id_shop` = 1;

UPDATE `pssnod_rule_currency`
SET `id_currency` = 1
WHERE `id_snod_rule` = 33;

UPDATE `pssnod_rule`
SET
    `name` = 'Core collection reward - 10% (Accessories excluded)',
    `voucher_name` = '10% off your next order',
    `voucher_description` = 'A thank-you reward for purchases outside the Accessories category. Valid for 30 days.',
    `active` = 1,
    `priority` = 9,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'CORE-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 35 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Always-on thank-you - 15%',
    `voucher_name` = '15% thank-you reward',
    `voucher_description` = 'A personal thank-you for your purchase. Use it on your next order within 30 days.',
    `active` = 1,
    `priority` = 10,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'RETURN-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 26 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Customer-segment reward - 25% (template)',
    `voucher_name` = '25% loyalty reward',
    `voucher_description` = 'An exclusive reward for a selected customer segment. Assign a dedicated VIP group before activation.',
    `active` = 0,
    `priority` = 11,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'VIP-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 31 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'UK numeric-code campaign - 10% (template)',
    `voucher_name` = '10% UK customer reward',
    `voucher_description` = 'A ready-to-activate UK campaign with an easy numeric code. Enable the UK market before launch.',
    `active` = 0,
    `priority` = 12,
    `stop_further` = 1,
    `code_length` = 6,
    `code_type` = 2,
    `code_template` = 'UK-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 37 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Germany expiry campaign - 10% (template)',
    `voucher_name` = '10% comeback reward',
    `voucher_description` = 'A 20-day campaign with reminders 5 days and 1 day before expiry. Enable Germany before launch.',
    `active` = 0,
    `priority` = 13,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'LASTCALL-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 36 AND `id_shop` = 1;

UPDATE `pssnod_rule`
SET
    `name` = 'Seasonal flash campaign - 30% (template)',
    `voucher_name` = '30% seasonal reward',
    `voucher_description` = 'A campaign template for a short promotional window. Set start and end dates before activation.',
    `active` = 0,
    `priority` = 14,
    `stop_further` = 1,
    `code_length` = 8,
    `code_type` = 3,
    `code_template` = 'SEASON-%key%',
    `updated_at` = NOW()
WHERE `id_snod_rule` = 38 AND `id_shop` = 1;

COMMIT;
