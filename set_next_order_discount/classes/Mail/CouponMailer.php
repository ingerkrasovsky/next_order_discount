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
    private $defaultEmailProvider;

    /**
     * @param CouponLinkRepository $couponLinkRepository
     * @param MailTemplateResolver $templateResolver
     * @param RuleEmailRepository $ruleEmailRepository
     */
    public function __construct(
        CouponLinkRepository $couponLinkRepository,
        MailTemplateResolver $templateResolver,
        RuleEmailRepository $ruleEmailRepository
    ) {
        $this->couponLinkRepository = $couponLinkRepository;
        $this->templateResolver = $templateResolver;
        $this->ruleEmailRepository = $ruleEmailRepository;
        $this->defaultEmailProvider = new DefaultEmailProvider();
    }

    /**
     * Sends the coupon email for one coupon link.
     *
     * @param int $idCouponLink snod_coupon_link primary key
     * @param bool $force when true, resend even if the coupon was already
     *                    emailed or has moved to a later/terminal state
     *                    (manual back-office resend). The lifecycle status
     *                    is never regressed — only `emailed_at` is refreshed
     *                    for a coupon that is past the "created" stage.
     * @param int $forceLang when > 0, the language id the email is rendered and
     *                       sent in, overriding the customer's own language (manual
     *                       back-office send). 0 keeps the customer's language.
     *
     * @return bool true when the email was sent (or was already sent), false on
     *              a missing/invalid record or a mail delivery failure
     */
    public function sendForCouponLink($idCouponLink, $force = false, $forceLang = 0)
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
        $orderLang = isset($link['id_lang']) ? (int) $link['id_lang'] : 0;
        $idLang = $this->resolveSendLang($orderLang, $customer, $idShop, $forceLang);
        $iso = $this->defaultEmailProvider->resolveIso($idLang, $idShop);

        try {
            $templateVars = $this->buildTemplateVars($link, $customer, $idShop, $idLang, $iso);

            $content = $this->resolveRuleEmail(
                (int) $link['id_snod_rule'],
                RuleEmailRepository::TYPE_COUPON,
                $idLang,
            );

            $sent = $this->sendWrapped($idLang, $iso, $content, $templateVars, $customer, $idShop);
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
     * Returns the email content (subject + HTML) to send for a rule in one
     * language: the rule's own stored content for that exact language, or — when
     * there is none — the shipped default in that same language. It never falls
     * back to another language's content, so the email always matches the language
     * it is sent in. The content is injected into the pass-through coupon template,
     * which carries no content itself.
     *
     * @param int $idRule
     * @param string $emailType
     * @param int $idLang
     *
     * @return array ['subject' => string, 'html' => string]
     */
    private function resolveRuleEmail($idRule, $emailType, $idLang)
    {
        $idRule = (int) $idRule;
        if ($idRule > 0) {
            // Only the requested language: never borrow another language's stored
            // content, otherwise the customer's language (or an explicit manual
            // choice, e.g. FR) would silently send an email in the wrong language.
            $content = $this->ruleEmailRepository->findContent($idRule, $emailType, (int) $idLang);
            if ($content !== null && trim((string) $content['html']) !== '') {
                return $content;
            }
        }

        // No stored content for this language → the shipped default in the SAME
        // language, so the email always matches the language it is sent in.
        return $this->defaultEmailProvider->getDefault($emailType, (int) $idLang);
    }

    /**
     * Renders the rule's subject and HTML body — substituting the template
     * placeholders — and delivers it through the pass-through coupon template
     * (mails/<iso>/next_order_discount.*), a thin shell of {snod_body_html} /
     * {snod_body_txt} that carries only the merchant's own body.
     *
     * @param int $idLang
     * @param string $iso resolved template ISO code (for the subject fallback)
     * @param array $content ['subject' => string, 'html' => string]
     * @param array $templateVars placeholder map
     * @param \Customer $customer
     * @param int $idShop
     *
     * @return bool
     */
    private function sendWrapped($idLang, $iso, array $content, array $templateVars, \Customer $customer, $idShop)
    {
        // The body is injected into the pass-through template via {snod_body_html},
        // so the core mailer's own {shop_name}/{shop_url}/{shop_logo} substitution
        // (which runs on the template before the body is inserted) never reaches
        // it. We therefore resolve those shop placeholders here, mirroring the core
        // mailer, so they are replaced inside the merchant's own body and subject.
        $vars = array_merge($templateVars, $this->shopVars($idShop));
        $subject = strtr((string) $content['subject'], $vars);
        $html = strtr((string) $content['html'], $vars);

        return (bool) \Mail::send(
            $idLang,
            $this->templateResolver->getTemplateName(),
            $subject !== '' ? $subject : $this->defaultEmailProvider->getSubject(RuleEmailRepository::TYPE_COUPON, $iso),
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
     * The shop placeholders the core mailer normally substitutes, resolved here
     * so they work inside the merchant's injected body. {shop_logo} maps to the
     * inline CID the core mailer embeds for every HTML email (otherwise it would
     * arrive as a dangling attachment).
     *
     * @param int $idShop
     *
     * @return array
     */
    private function shopVars($idShop)
    {
        $idShop = (int) $idShop;
        $shopName = (string) \Configuration::get('PS_SHOP_NAME', null, null, $idShop > 0 ? $idShop : null);

        return [
            '{shop_name}' => \Tools::safeOutput($shopName),
            '{shop_url}' => \Tools::getShopDomainSsl(true, true),
            '{shop_logo}' => 'cid:shop_logo',
        ];
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
     * Resolves the language the email is sent in, in priority order: an explicit
     * installed override (manual back-office send), then the language of the
     * source order (what the customer actually ordered in), then the customer's
     * account language, then the shop default. Only installed languages win at
     * each step.
     *
     * @param int $orderLang the source order's language id (0 if unknown)
     * @param \Customer $customer
     * @param int $idShop
     * @param int $forceLang overriding language id, or 0 for none
     *
     * @return int
     */
    private function resolveSendLang($orderLang, \Customer $customer, $idShop, $forceLang)
    {
        $forceLang = (int) $forceLang;
        if ($forceLang > 0 && \Validate::isLoadedObject(new \Language($forceLang))) {
            return $forceLang;
        }

        $orderLang = (int) $orderLang;
        if ($orderLang > 0 && \Validate::isLoadedObject(new \Language($orderLang))) {
            return $orderLang;
        }

        return $this->resolveCustomerLang($customer, $idShop);
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

        // Shop placeholders ({shop_name}, {shop_url}, {shop_logo}) are added later
        // in shopVars(), since the core mailer's own substitution never reaches the
        // injected body. {customer_firstname} is the only customer-controlled value,
        // so it is escaped here as a defense-in-depth measure before it reaches the
        // raw HTML substitution.
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
            return $this->defaultEmailProvider->getFreeShippingLabel($iso);
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
