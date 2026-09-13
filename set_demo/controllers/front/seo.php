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
        $adminDemoUrl = $shopUrl . '/admin-demo/';
        $canonical = $this->context->link->getModuleLink('set_demo', 'seo', [], true);

        $this->context->smarty->assign([
            'seo_front_demo_url' => $frontDemoUrl,
            'seo_admin_demo_url' => $adminDemoUrl,
            'seo_addons_url' => $this->getAddonsUrl(),
            'seo_canonical_url' => $canonical,
            'seo_jsonld_software' => $this->buildSoftwareJsonLd($canonical),
            'seo_jsonld_faq' => $this->buildFaqJsonLd(),
        ]);

        $this->setTemplate('module:set_demo/views/templates/front/seo.tpl');
    }

    /**
     * Page meta for this controller (title/description shown in the SERP snippet).
     * Overriding getTemplateVarPage is the supported way to set meta from a module
     * front controller in PrestaShop 1.7/8/9.
     *
     * @return array
     */
    public function getTemplateVarPage()
    {
        $page = parent::getTemplateVarPage();
        $page['meta']['title'] = $this->module->getSeoMetaTitle();
        $page['meta']['description'] = $this->module->getSeoMetaDescription();

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
            'name' => 'Next Order Discount — купоны на следующий заказ для PrestaShop',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'PrestaShop 1.7, 8, 9',
            'url' => $url,
            'description' => 'Модуль PrestaShop, который автоматически выдаёт покупателю персональный купон на следующий заказ, когда его заказ доходит до нужного статуса. Движок правил, три типа скидки, письма и напоминания, воронка купонов и повторные продажи.',
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
     * @return string valid JSON (FAQPage) — answers must match the visible FAQ.
     */
    private function buildFaqJsonLd()
    {
        $qa = [
            [
                'Что делает модуль Next Order Discount для PrestaShop?',
                'Он автоматически выдаёт покупателю персональный купон на следующий заказ, когда его текущий заказ доходит до нужного статуса. Купонное письмо, напоминания, срок действия и отмена при возврате заказа — модуль ведёт весь жизненный цикл купона сам.',
            ],
            [
                'Как задаётся, кому и какую скидку выдавать?',
                'Через движок правил: вы создаёте сколько угодно правил, у каждого свои условия срабатывания (триггерные статусы, сумма заказа, группы, страны, валюты, категории, бренды, номер заказа) и своя скидка. Правила проверяются по приоритету.',
            ],
            [
                'Какие типы скидки поддерживаются?',
                'Три типа: процент, фиксированная сумма и бесплатная доставка. На каждое правило задаются срок действия купона в днях и минимальная сумма следующего заказа.',
            ],
            [
                'Нужен ли cron и зачем?',
                'Да. Cron отправляет купонные письма из очереди, планирует напоминания и переводит просроченные купоны в статус expired. Модуль помогает настроить одну строку crontab (curl/wget) или внешний web-cron сервис.',
            ],
        ];

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
