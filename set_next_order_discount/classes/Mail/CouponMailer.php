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
namespace Setecom\NextOrderDiscount\Mail;

use CartRule;
use Currency;
use Customer;
use Language;
use Mail;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Setecom\NextOrderDiscount\Repository\RuleEmailRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Sends the coupon email for a generated coupon link through the native
 * PrestaShop Mail API and records the delivery on the coupon link row.
 *
 * The mailer is a self-contained unit: given a coupon link id it loads the
 * customer, derives the human-readable discount details from the actual
 * CartRule voucher, resolves the template language and renders the localized
 * email. It is idempotent — a coupon already flagged as emailed is skipped —
 * and only marks the link as emailed once the core mailer confirms the send,
 * so a transient mail failure leaves the task retryable.
 */
class CouponMailer
{
    /**
     * Placeholder shown for an unbounded value (e.g. no minimum order amount).
     */
    private const EMPTY_VALUE = '—';

    private $couponLinkRepository;
    private $templateResolver;
    private $ruleEmailRepository;

    /**
     * @param CouponLinkRepository $couponLinkRepository
     * @param MailTemplateResolver $templateResolver
     * @param RuleEmailRepository $ruleEmailRepository
     */
    public function __construct(
        CouponLinkRepository $couponLinkRepository,
        MailTemplateResolver $templateResolver,
        RuleEmailRepository $ruleEmailRepository,
    ) {
        $this->couponLinkRepository = $couponLinkRepository;
        $this->templateResolver = $templateResolver;
        $this->ruleEmailRepository = $ruleEmailRepository;
    }

    /**
     * Sends the coupon email for one coupon link.
     *
     * @param int $idCouponLink ps_snod_coupon_link primary key
     * @param bool $force when true, resend even if the coupon was already
     *                    emailed or has moved to a later/terminal state
     *                    (manual back-office resend). The lifecycle status
     *                    is never regressed — only `emailed_at` is refreshed
     *                    for a coupon that is past the "created" stage.
     *
     * @return bool true when the email was sent (or was already sent), false on
     *              a missing/invalid record or a mail delivery failure
     */
    public function sendForCouponLink($idCouponLink, $force = false)
    {
        $link = $this->couponLinkRepository->findById((int) $idCouponLink);
        if ($link === null) {
            // The coupon link no longer exists (e.g. deleted). There is nothing to
            // send and never will be, so report success to drop a queued task
            // cleanly instead of retrying/failing it forever. (The manual resend
            // path checks existence before calling this, so it is unaffected.)
            return true;
        }

        if (!$force && !$this->isAwaitingEmail($link)) {
            // Already emailed (idempotency), or no longer in a state that
            // warrants the coupon email (e.g. used, expired, canceled). There is
            // nothing to send, so the task is considered handled.
            return true;
        }

        $customer = new \Customer((int) $link['id_customer']);
        if (!\Validate::isLoadedObject($customer) || !\Validate::isEmail($customer->email)) {
            return false;
        }

        $idShop = (int) $link['id_shop'];
        $idLang = $this->resolveCustomerLang($customer, $idShop);
        $iso = $this->templateResolver->resolveIso($idLang, $idShop);

        try {
            $templateVars = $this->buildTemplateVars($link, $customer, $idShop, $idLang, $iso);

            $custom = $this->resolveRuleEmail(
                (int) $link['id_snod_rule'],
                RuleEmailRepository::TYPE_COUPON,
                $idLang,
                $idShop,
            );

            if ($custom !== null) {
                $sent = $this->sendCustom($idLang, $custom, $templateVars, $customer, $idShop);
            } else {
                $sent = \Mail::send(
                    $idLang,
                    $this->templateResolver->getTemplateName(),
                    $this->templateResolver->getSubject($iso),
                    $templateVars,
                    $customer->email,
                    trim($customer->firstname . ' ' . $customer->lastname),
                    null,
                    null,
                    null,
                    null,
                    $this->templateResolver->getTemplatePath(),
                    false,
                    $idShop,
                );
            }
        } catch (\Exception $e) {
            // The mailer never throws: a transport/config error leaves the task
            // pending and retryable, and the coupon is not flagged as emailed.
            return false;
        }

        if (!$sent) {
            return false;
        }

        // Always record the latest send time. Advance the status to "emailed"
        // only from "created" so a manual resend never regresses a coupon that
        // has already moved on (reminded, used, expired, canceled).
        $update = ['emailed_at' => date('Y-m-d H:i:s')];
        if ((string) $link['status'] === CouponLinkRepository::STATUS_CREATED) {
            $update['status'] = CouponLinkRepository::STATUS_EMAILED;
        }
        $this->couponLinkRepository->update((int) $link[CouponLinkRepository::PRIMARY_KEY], $update);

        return true;
    }

    /**
     * Returns the rule's own email content for a type, preferring the customer's
     * language and falling back to the shop's default language. Returns null when
     * the rule has no usable custom content, so the caller uses the shipped
     * default template.
     *
     * @param int $idRule
     * @param string $emailType
     * @param int $idLang
     * @param int $idShop
     *
     * @return array|null ['subject' => string, 'html' => string]
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
     * Sends a rule's custom email: substitutes the template placeholders into the
     * merchant HTML and subject and delivers it through the generic pass-through
     * mail template.
     *
     * @param int $idLang
     * @param array $custom ['subject' => string, 'html' => string]
     * @param array $templateVars placeholder map
     * @param \Customer $customer
     * @param int $idShop
     *
     * @return bool
     */
    private function sendCustom($idLang, array $custom, array $templateVars, \Customer $customer, $idShop)
    {
        // The core mailer embeds the shop logo (cid:shop_logo) for every HTML
        // email, so map the placeholder to it here — otherwise the embedded image
        // would arrive as a dangling attachment instead of showing inline.
        $vars = array_merge($templateVars, ['{shop_logo}' => 'cid:shop_logo']);
        $subject = strtr((string) $custom['subject'], $vars);
        $html = strtr((string) $custom['html'], $vars);

        return (bool) \Mail::send(
            $idLang,
            'custom',
            $subject !== '' ? $subject : $this->templateResolver->getSubject($this->templateResolver->resolveIso($idLang, $idShop)),
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
            $this->templateResolver->getTemplatePath(),
            false,
            $idShop,
        );
    }

    /**
     * Derives a readable plain-text version from an HTML email body.
     *
     * @param string $html
     *
     * @return string
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
     * A coupon is awaiting its email only while it sits in the "created" state
     * and has never been emailed. This both preserves idempotency (an emailed
     * coupon is skipped) and prevents sending for coupons that have moved to a
     * later or terminal state (reminded, used, expired, canceled). A failed send
     * leaves the status untouched, so a retry still sees "created" and resends.
     *
     * @param array $link coupon link row
     *
     * @return bool
     */
    private function isAwaitingEmail(array $link)
    {
        $status = isset($link['status']) ? (string) $link['status'] : '';
        if ($status !== CouponLinkRepository::STATUS_CREATED) {
            return false;
        }

        return !$this->hasEmailedAt($link);
    }

    /**
     * @param array $link coupon link row
     *
     * @return bool whether the row already carries a real emailed_at timestamp
     */
    private function hasEmailedAt(array $link)
    {
        return isset($link['emailed_at']) && $link['emailed_at'] !== null && $link['emailed_at'] !== ''
            && strpos((string) $link['emailed_at'], '0000-00-00') !== 0;
    }

    /**
     * Falls back to the shop's default language when the customer's language id
     * is not usable.
     *
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
     * Builds the placeholder map consumed by the email template.
     *
     * @param array $link coupon link row
     * @param \Customer $customer
     * @param int $idShop
     * @param int $idLang
     * @param string $iso resolved template ISO code
     *
     * @return array
     */
    private function buildTemplateVars(array $link, \Customer $customer, $idShop, $idLang, $iso)
    {
        $cartRule = new \CartRule((int) $link['id_cart_rule']);
        $currency = $this->resolveCurrency($cartRule, $idShop);

        // {shop_name} is intentionally omitted: the core mailer always fills it
        // with a shop-scoped, escaped value (Mail::send). {customer_firstname}
        // is the only customer-controlled value, so it is escaped here as a
        // defense-in-depth measure before it reaches the raw HTML substitution.
        return [
            '{coupon_code}' => (string) $link['coupon_code'],
            '{coupon_value}' => $this->formatCouponValue($cartRule, $currency, $iso),
            '{valid_to}' => $this->formatDate(isset($link['valid_to']) ? $link['valid_to'] : null, $idLang),
            '{customer_firstname}' => \Tools::safeOutput((string) $customer->firstname),
            '{minimum_amount}' => $this->formatMinimumAmount($cartRule, $currency),
        ];
    }

    /**
     * Resolves the currency used to format monetary values, preferring the
     * voucher's own currency and falling back to the shop default.
     *
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
     * Renders the headline discount value from the actual voucher: a percentage,
     * a currency amount or a free-shipping label.
     *
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
            return $this->templateResolver->getFreeShippingLabel($iso);
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
     * Formats a percentage, trimming trailing zeros (10.00 -> "10%").
     *
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
     * Formats a monetary amount using the shop locale, with a defensive
     * fallback when the locale service is unavailable (e.g. CLI contexts).
     *
     * @param float $amount
     * @param \Currency $currency
     *
     * @return string
     */
    private function formatPrice($amount, \Currency $currency)
    {
        $iso = \Validate::isLoadedObject($currency) && $currency->iso_code ? (string) $currency->iso_code : 'USD';

        // Self-contained formatting: these emails are sent from the queue worker
        // (cron), where no reliable display context/locale is available.
        return number_format((float) $amount, 2, '.', ' ') . ' ' . $iso;
    }

    /**
     * Formats a datetime string using the customer language's short date format.
     *
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
