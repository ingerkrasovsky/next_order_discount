<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a custom commercial license.
 * You may not redistribute, resell, sublicense, or share this file.
 * One license is valid for one installation (one store).
 *
 * For full license terms, contact: info@setecom.tech
 *
 * @author    Smart Ecommerce Tech
 * @copyright 2026 Smart Ecommerce Tech
 * @license   Commercial License
 */
namespace Setecom\NextOrderDiscount\Reminder;

use Customer;
use Language;
use Mail;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Setecom\NextOrderDiscount\Repository\RuleEmailRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sends a coupon reminder email (first or second) through the native PrestaShop
 * Mail API and records it on the coupon link row.
 *
 * The mailer is self-contained and idempotent: it skips coupons that are no
 * longer usable (used, expired, canceled or past their expiry date) and coupons
 * whose reminder for the requested stage has already been recorded. It marks the
 * matching reminder timestamp (first_reminder_at / second_reminder_at) and moves
 * the coupon to the `reminded` status only once the core mailer confirms the
 * send, so a transient mail failure leaves the reminder retryable and never
 * blocks the second reminder from following the first.
 */
class ReminderMailer
{
    public const TEMPLATE_NAME = 'reminder_next_order_discount';
    public const MODULE_NAME = 'set_next_order_discount';

    private const FALLBACK_ISO = 'en';
    private const EMPTY_VALUE = '—';

    private const SUBJECTS = [
        'en' => 'Your discount is waiting — use it before it expires',
        'ru' => 'Ваша скидка ждёт — успейте воспользоваться до истечения',
    ];

    private const FREE_SHIPPING_LABELS = [
        'en' => 'Free shipping',
        'ru' => 'Бесплатная доставка',
    ];

    private $couponLinkRepository;
    private $ruleEmailRepository;

    /**
     * @param CouponLinkRepository $couponLinkRepository
     * @param RuleEmailRepository $ruleEmailRepository
     */
    public function __construct(
        CouponLinkRepository $couponLinkRepository,
        RuleEmailRepository $ruleEmailRepository,
    ) {
        $this->couponLinkRepository = $couponLinkRepository;
        $this->ruleEmailRepository = $ruleEmailRepository;
    }

    /**
     * Sends the reminder for one coupon link.
     *
     * @param int $idCouponLink ps_snod_coupon_link primary key
     * @param int $reminderNumber 1 for the first reminder, 2 for the second
     * @param bool $force when true, resend even if this stage was already
     *                    recorded (manual back-office send). The coupon
     *                    must still be usable and not expired.
     *
     * @return bool true when sent (or nothing needed to be sent), false on a
     *              missing/invalid record or a mail delivery failure
     */
    public function sendReminder($idCouponLink, $reminderNumber, $force = false)
    {
        $reminderNumber = ((int) $reminderNumber === 2) ? 2 : 1;

        $link = $this->couponLinkRepository->findById((int) $idCouponLink);
        if ($link === null) {
            return false;
        }

        if (!$this->isRemindable($link, $reminderNumber, $force)) {
            // Coupon no longer usable or this reminder already recorded: nothing
            // to send, so the task is considered handled.
            return true;
        }

        $customer = new \Customer((int) $link['id_customer']);
        if (!\Validate::isLoadedObject($customer) || !\Validate::isEmail($customer->email)) {
            return false;
        }

        $idShop = (int) $link['id_shop'];
        $idLang = $this->resolveCustomerLang($customer, $idShop);
        $iso = $this->resolveIso($idLang, $idShop);

        try {
            $templateVars = $this->buildTemplateVars($link, $customer, $idShop, $idLang, $iso);

            $emailType = $reminderNumber === 2
                ? RuleEmailRepository::TYPE_REMINDER_2
                : RuleEmailRepository::TYPE_REMINDER_1;
            $custom = $this->resolveRuleEmail((int) $link['id_snod_rule'], $emailType, $idLang, $idShop);

            if ($custom !== null) {
                $sent = $this->sendCustom($idLang, $iso, $custom, $templateVars, $customer, $idShop);
            } else {
                $sent = \Mail::send(
                    $idLang,
                    self::TEMPLATE_NAME,
                    $this->getSubject($iso),
                    $templateVars,
                    $customer->email,
                    trim($customer->firstname . ' ' . $customer->lastname),
                    null,
                    null,
                    null,
                    null,
                    $this->getTemplatePath(),
                    false,
                    $idShop,
                );
            }
        } catch (\Exception $e) {
            // The mailer never throws: a transport/config error leaves the
            // reminder retryable and the coupon unchanged.
            return false;
        }

        if (!$sent) {
            return false;
        }

        $column = $reminderNumber === 2 ? 'second_reminder_at' : 'first_reminder_at';
        $this->couponLinkRepository->update((int) $link[CouponLinkRepository::PRIMARY_KEY], [
            $column => date('Y-m-d H:i:s'),
            'status' => CouponLinkRepository::STATUS_REMINDED,
        ]);

        return true;
    }

    /**
     * Returns the rule's own email content for a reminder type, preferring the
     * customer's language and falling back to the shop default. Null means "use
     * the shipped default template".
     *
     * @param int $idRule
     * @param string $emailType
     * @param int $idLang
     * @param int $idShop
     *
     * @return array|null
     */
    private function resolveRuleEmail($idRule, $emailType, $idLang, $idShop)
    {
        $idRule = (int) $idRule;
        if ($idRule <= 0) {
            return null;
        }

        $candidates = [(int) $idLang];
        $default = (int) \Configuration::get('PS_LANG_DEFAULT', null, null, $idShop > 0 ? $idShop : null);
        if ($default > 0 && $default !== (int) $idLang) {
            $candidates[] = $default;
        }

        foreach ($candidates as $lang) {
            $content = $this->ruleEmailRepository->findContent($idRule, $emailType, $lang);
            if ($content !== null && trim((string) $content['html']) !== '') {
                return $content;
            }
        }

        return null;
    }

    /**
     * Sends a rule's custom reminder: substitutes placeholders into the merchant
     * HTML and subject and delivers it through the generic pass-through template.
     *
     * @param int $idLang
     * @param string $iso
     * @param array $custom
     * @param array $templateVars
     * @param \Customer $customer
     * @param int $idShop
     *
     * @return bool
     */
    private function sendCustom($idLang, $iso, array $custom, array $templateVars, \Customer $customer, $idShop)
    {
        // The core mailer embeds the shop logo (cid:shop_logo) for every HTML
        // email, so map the placeholder to it here to display it inline instead
        // of leaving it as a dangling attachment.
        $vars = array_merge($templateVars, ['{shop_logo}' => 'cid:shop_logo']);
        $subject = strtr((string) $custom['subject'], $vars);
        $html = strtr((string) $custom['html'], $vars);

        return (bool) \Mail::send(
            $idLang,
            'custom',
            $subject !== '' ? $subject : $this->getSubject($iso),
            [
                '{snod_body_html}' => $html,
                '{snod_body_txt}' => $this->htmlToText($html),
            ],
            $customer->email,
            trim($customer->firstname . ' ' . $customer->lastname),
            null,
            null,
            null,
            null,
            $this->getTemplatePath(),
            false,
            $idShop,
        );
    }

    /**
     * @param string $html
     *
     * @return string readable plain-text version of an HTML email body
     */
    private function htmlToText($html)
    {
        $text = (string) $html;
        $text = preg_replace('/<(head|style|script)\b[^>]*>.*?<\/\1>/is', '', $text);
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/(p|div|tr|h[1-6]|li)>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * A coupon is remindable for a stage when it is still usable, not past its
     * expiry, and the reminder for that stage has not already been recorded.
     *
     * @param array $link coupon link row
     * @param int $reminderNumber 1 or 2
     *
     * @return bool
     */
    private function isRemindable(array $link, $reminderNumber, $force = false)
    {
        $status = isset($link['status']) ? (string) $link['status'] : '';
        if (in_array($status, [
            CouponLinkRepository::STATUS_USED,
            CouponLinkRepository::STATUS_EXPIRED,
            CouponLinkRepository::STATUS_CANCELED,
        ], true)) {
            return false;
        }

        if (!$this->isStillValid($link)) {
            return false;
        }

        // A manual (forced) send ignores whether this stage was already recorded.
        if ($force) {
            return true;
        }

        $column = $reminderNumber === 2 ? 'second_reminder_at' : 'first_reminder_at';

        return !$this->hasTimestamp($link, $column);
    }

    /**
     * @param array $link coupon link row
     *
     * @return bool whether the coupon has a valid_to still in the future
     */
    private function isStillValid(array $link)
    {
        if (!isset($link['valid_to']) || $link['valid_to'] === '') {
            return false;
        }

        $validTo = strtotime((string) $link['valid_to']);

        return $validTo !== false && $validTo > time();
    }

    /**
     * @param array $link
     * @param string $column
     *
     * @return bool whether the given timestamp column already holds a real value
     */
    private function hasTimestamp(array $link, $column)
    {
        return isset($link[$column]) && $link[$column] !== null && $link[$column] !== ''
            && strpos((string) $link[$column], '0000-00-00') !== 0;
    }

    /**
     * @param \Customer $customer
     * @param int $idShop
     *
     * @return int
     */
    private function resolveCustomerLang(\Customer $customer, $idShop)
    {
        $idLang = (int) $customer->id_lang;
        if ($idLang > 0) {
            return $idLang;
        }

        return (int) \Configuration::get('PS_LANG_DEFAULT', null, null, $idShop > 0 ? $idShop : null);
    }

    /**
     * Resolves the ISO code whose reminder template files will be used, mirroring
     * the core mailer fallback order (customer language, shop default, English)
     * so the subject matches the template body's language.
     *
     * @param int $idLang
     * @param int $idShop
     *
     * @return string
     */
    private function resolveIso($idLang, $idShop)
    {
        foreach ($this->buildIsoCandidates((int) $idLang, (int) $idShop) as $iso) {
            if ($this->templateExists($iso)) {
                return $iso;
            }
        }

        return self::FALLBACK_ISO;
    }

    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return string[]
     */
    private function buildIsoCandidates($idLang, $idShop)
    {
        $candidates = [];

        $customerIso = $idLang > 0 ? (string) \Language::getIsoById($idLang) : '';
        if ($customerIso !== '') {
            $candidates[] = $customerIso;
        }

        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT', null, null, $idShop > 0 ? $idShop : null);
        $defaultIso = $defaultLangId > 0 ? (string) \Language::getIsoById($defaultLangId) : '';
        if ($defaultIso !== '') {
            $candidates[] = $defaultIso;
        }

        $candidates[] = self::FALLBACK_ISO;

        return array_values(array_unique($candidates));
    }

    /**
     * @param string $iso
     *
     * @return bool
     */
    private function templateExists($iso)
    {
        $base = $this->getTemplatePath() . $iso . '/' . self::TEMPLATE_NAME;

        return is_file($base . '.html') && is_file($base . '.txt');
    }

    /**
     * @return string absolute path to the module's mails/ directory
     */
    private function getTemplatePath()
    {
        return rtrim(_PS_MODULE_DIR_, '/') . '/' . self::MODULE_NAME . '/mails/';
    }

    /**
     * @param string $iso
     *
     * @return string localized subject
     */
    private function getSubject($iso)
    {
        $iso = (string) $iso;

        return isset(self::SUBJECTS[$iso]) ? self::SUBJECTS[$iso] : self::SUBJECTS[self::FALLBACK_ISO];
    }

    /**
     * Builds the placeholder map consumed by the reminder template. {shop_name}
     * is intentionally omitted: the core mailer fills it with a shop-scoped,
     * escaped value. {customer_firstname} is the only customer-controlled value,
     * so it is escaped before it reaches the raw HTML substitution.
     *
     * @param array $link
     * @param \Customer $customer
     * @param int $idShop
     * @param int $idLang
     * @param string $iso
     *
     * @return array
     */
    private function buildTemplateVars(array $link, \Customer $customer, $idShop, $idLang, $iso)
    {
        $cartRule = new \CartRule((int) $link['id_cart_rule']);
        $currency = $this->resolveCurrency($cartRule, $idShop);

        return [
            '{coupon_code}' => (string) $link['coupon_code'],
            '{coupon_value}' => $this->formatCouponValue($cartRule, $currency, $iso),
            '{valid_to}' => $this->formatDate(isset($link['valid_to']) ? $link['valid_to'] : null, $idLang),
            '{customer_firstname}' => \Tools::safeOutput((string) $customer->firstname),
            '{customer_lastname}' => \Tools::safeOutput((string) $customer->lastname),
            '{customer_fullname}' => \Tools::safeOutput(trim($customer->firstname . ' ' . $customer->lastname)),
            '{customer_title}' => $this->resolveCustomerTitle($customer, $idLang),
            '{customer_email}' => \Tools::safeOutput((string) $customer->email),
            '{minimum_amount}' => $this->formatMinimumAmount($cartRule, $currency),
        ];
    }

    /**
     * Resolves the localized social title (e.g. "Mr", "Mrs") for the customer,
     * returning an empty string when no gender is set. The value is escaped as a
     * defense-in-depth measure before it reaches the raw HTML substitution.
     *
     * @param \Customer $customer
     * @param int $idLang
     *
     * @return string
     */
    private function resolveCustomerTitle(\Customer $customer, $idLang)
    {
        $idGender = (int) $customer->id_gender;
        if ($idGender <= 0) {
            return '';
        }

        $gender = new \Gender($idGender, (int) $idLang);
        if (!\Validate::isLoadedObject($gender)) {
            return '';
        }

        return \Tools::safeOutput((string) $gender->name);
    }

    /**
     * @param \CartRule $cartRule
     * @param int $idShop
     *
     * @return \Currency
     */
    private function resolveCurrency(\CartRule $cartRule, $idShop)
    {
        $candidates = [];
        if (\Validate::isLoadedObject($cartRule)) {
            $candidates[] = (int) $cartRule->reduction_currency;
            $candidates[] = (int) $cartRule->minimum_amount_currency;
        }
        $candidates[] = (int) \Configuration::get('PS_CURRENCY_DEFAULT', null, null, $idShop > 0 ? $idShop : null);

        foreach ($candidates as $idCurrency) {
            if ($idCurrency <= 0) {
                continue;
            }
            $currency = new \Currency($idCurrency);
            if (\Validate::isLoadedObject($currency)) {
                return $currency;
            }
        }

        return new \Currency((int) \Configuration::get('PS_CURRENCY_DEFAULT', null, null, $idShop > 0 ? $idShop : null));
    }

    /**
     * @param \CartRule $cartRule
     * @param \Currency $currency
     * @param string $iso
     *
     * @return string
     */
    private function formatCouponValue(\CartRule $cartRule, \Currency $currency, $iso)
    {
        if (!\Validate::isLoadedObject($cartRule)) {
            return self::EMPTY_VALUE;
        }

        $percent = (float) $cartRule->reduction_percent;
        if ($percent > 0) {
            return $this->formatPercent($percent);
        }

        $amount = (float) $cartRule->reduction_amount;
        if ($amount > 0) {
            return $this->formatPrice($amount, $currency);
        }

        if ((bool) $cartRule->free_shipping) {
            $iso = (string) $iso;

            return isset(self::FREE_SHIPPING_LABELS[$iso])
                ? self::FREE_SHIPPING_LABELS[$iso]
                : self::FREE_SHIPPING_LABELS[self::FALLBACK_ISO];
        }

        return self::EMPTY_VALUE;
    }

    /**
     * @param \CartRule $cartRule
     * @param \Currency $currency
     *
     * @return string
     */
    private function formatMinimumAmount(\CartRule $cartRule, \Currency $currency)
    {
        if (!\Validate::isLoadedObject($cartRule)) {
            return self::EMPTY_VALUE;
        }

        $minimum = (float) $cartRule->minimum_amount;
        if ($minimum <= 0) {
            return self::EMPTY_VALUE;
        }

        return $this->formatPrice($minimum, $currency);
    }

    /**
     * @param float $percent
     *
     * @return string
     */
    private function formatPercent($percent)
    {
        $formatted = rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.');

        return $formatted . '%';
    }

    /**
     * @param float $amount
     * @param \Currency $currency
     *
     * @return string
     */
    private function formatPrice($amount, \Currency $currency)
    {
        $iso = \Validate::isLoadedObject($currency) && $currency->iso_code ? (string) $currency->iso_code : 'USD';

        // Self-contained formatting: reminders are sent from the queue worker
        // (cron), where no reliable display context/locale is available.
        return number_format((float) $amount, 2, '.', ' ') . ' ' . $iso;
    }

    /**
     * @param string|null $date
     * @param int $idLang
     *
     * @return string
     */
    private function formatDate($date, $idLang)
    {
        $date = (string) $date;
        if ($date === '' || strpos($date, '0000-00-00') === 0) {
            return self::EMPTY_VALUE;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $format = 'Y-m-d';
        $language = new \Language((int) $idLang);
        if (\Validate::isLoadedObject($language) && !empty($language->date_format_lite)) {
            $format = (string) $language->date_format_lite;
        }

        return date($format, $timestamp);
    }
}
