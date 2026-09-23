<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Public SEO landing for the live demo.
 *
 * One dedicated page carries the marketing copy, the FAQ and the JSON-LD, so the
 * structured data matches its page and nothing leaks onto product/category pages.
 * Reachable at /next-order-discount-demo (see set_demo::hookModuleRoutes).
 */
class set_demoSeoModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $shopUrl = rtrim($this->context->shop->getBaseURL(true), '/');
        $frontDemoUrl = $shopUrl . '/';
        $adminDir = (string) Configuration::get(set_demo::CONFIG_ADMIN_DIR);
        if ($adminDir === '' || !preg_match('#^[a-zA-Z0-9_-]+$#', $adminDir)) {
            $adminDir = 'admin-demo';
        }
        $adminDemoUrl = $shopUrl . '/' . trim($adminDir, '/') . '/';
        $dashboardUrl = $adminDemoUrl . '?controller=NextOrderDiscount&tab=dashboard';
        $rulesUrl = $adminDemoUrl . '?controller=NextOrderDiscount&tab=rules';
        $orderGeneratorUrl = $adminDemoUrl . '?controller=DemoOrderGenerator';
        $canonical = $this->context->link->getModuleLink('set_demo', 'seo', [], true);
        $faq = $this->getFaqItems();

        $this->context->smarty->assign([
            'seo_front_demo_url' => $frontDemoUrl,
            'seo_admin_demo_url' => $adminDemoUrl,
            'seo_dashboard_url' => $dashboardUrl,
            'seo_rules_url' => $rulesUrl,
            'seo_order_generator_url' => $orderGeneratorUrl,
            'seo_addons_url' => $this->getAddonsUrl(),
            'seo_canonical_url' => $canonical,
            'seo_faq' => $faq,
            'seo_jsonld_software' => $this->buildSoftwareJsonLd($canonical),
            'seo_jsonld_faq' => $this->buildFaqJsonLd($faq),
        ]);

        $this->setTemplate('module:set_demo/views/templates/front/seo.tpl');
    }

    /**
     * Page meta for this controller (title/description shown in the SERP snippet).
     * Overriding getTemplateVarPage is the supported way to set meta from a module
     * front controller in supported PrestaShop versions.
     *
     * @return array
     */
    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->module->getSeoMetaTitle();
        $page['meta']['description'] = $this->module->getSeoMetaDescription();
        $page['canonical'] = $this->context->link->getModuleLink('set_demo', 'seo', [], true);

        return $page;
    }

    /**
     * @return string configured Addons listing URL (may be empty)
     */
    private function getAddonsUrl()
    {
        return (string) Configuration::get(set_demo::CONFIG_SEO_ADDONS_URL);
    }

    /**
     * @param string $url canonical page url
     *
     * @return string valid JSON (SoftwareApplication)
     */
    private function buildSoftwareJsonLd($url)
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Next Order Discount for PrestaShop',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'PrestaShop',
            'softwareVersion' => '1.0.0',
            'url' => $url,
            'description' => 'A PrestaShop module that creates a personal coupon after a qualifying order, sends the coupon email and reminders, and tracks coupon conversion.',
            'featureList' => [
                'Rule-based post-purchase coupons',
                'Percentage, fixed-amount and free-shipping discounts',
                'Localized coupon emails and two reminders',
                'Coupon funnel, 30-day trends and event logs',
                'Multistore support',
            ],
        ];

        // Offer only when both price and listing URL are known (no fake data otherwise).
        $price = (string) Configuration::get(set_demo::CONFIG_SEO_PRICE);
        $addonsUrl = $this->getAddonsUrl();
        if ($price !== '' && $addonsUrl !== '') {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'EUR',
                'url' => $addonsUrl,
            ];
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array visible FAQ items; also used to build FAQPage JSON-LD
     */
    private function getFaqItems()
    {
        return [
            [
                'What does Next Order Discount do?',
                'It creates a personal coupon after an order matches an active rule and reaches an allowed status. The customer can use that coupon on a later order.',
            ],
            [
                'Which orders can receive a coupon?',
                'Rules can target order statuses, order total, campaign dates, customer order number, customer groups, countries, currencies, product categories and brands. All conditions in a rule must match.',
            ],
            [
                'Which discount types are supported?',
                'Percentage discounts, fixed-amount discounts and free shipping. Each rule also controls coupon validity and the minimum total of the next order.',
            ],
            [
                'Is the first coupon email sent immediately?',
                'Yes. The module attempts to send the main coupon email as soon as the coupon is created. If that attempt fails, the email enters the dispatch queue for a cron retry.',
            ],
            [
                'Why does the module need cron?',
                'Cron retries failed coupon emails, plans and sends reminders, and marks expired coupons. The module provides ready-to-use curl, wget and external web-cron URLs.',
            ],
            [
                'Can I test the complete flow before installing the module?',
                'Yes. The live demo includes the back office, a guided tour and Demo Order Generator, which creates a real test order for an email address you control and shows the resulting coupon.',
            ],
        ];
    }

    /**
     * @param array $qa visible FAQ items
     *
     * @return string valid JSON (FAQPage) — answers match the visible FAQ.
     */
    private function buildFaqJsonLd(array $qa)
    {
        $entities = [];
        foreach ($qa as $pair) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $pair[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $pair[1]],
            ];
        }

        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
