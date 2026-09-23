<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class set_demo extends Module
{
    const CONFIG_AUTOLOGIN_ENABLED = 'SET_DEMO_AUTOLOGIN_ENABLED';
    const CONFIG_AUTOLOGIN_EMPLOYEE_ID = 'SET_DEMO_AUTOLOGIN_EMPLOYEE_ID';
    const CONFIG_AUTOLOGIN_EMAIL = 'SET_DEMO_AUTOLOGIN_EMAIL';
    const CONFIG_AUTOLOGIN_PASSWORD = 'SET_DEMO_AUTOLOGIN_PASSWORD';
    const CONFIG_ADMIN_DIR = 'SET_DEMO_ADMIN_DIR';

    // SEO landing settings (configurable, so no code edits per deployment).
    const CONFIG_SEO_ADDONS_URL = 'SET_DEMO_SEO_ADDONS_URL';
    const CONFIG_SEO_PRICE = 'SET_DEMO_SEO_PRICE';
    const CONFIG_SEO_META_TITLE = 'SET_DEMO_SEO_META_TITLE';
    const CONFIG_SEO_META_DESC = 'SET_DEMO_SEO_META_DESC';
    const CONFIG_SEO_OG_IMAGE = 'SET_DEMO_SEO_OG_IMAGE';

    const DEFAULT_SEO_META_TITLE = 'Next Order Discount for PrestaShop | Live Demo';
    const DEFAULT_SEO_META_DESC = 'Live demo of Next Order Discount for PrestaShop: create targeted post-purchase coupons, send reminders and track coupon conversion.';
    const LEGACY_SEO_META_TITLE = 'Next Order Discount для PrestaShop — живое демо купонов на следующий заказ';
    const LEGACY_SEO_META_DESC = 'Живое демо модуля Next Order Discount для PrestaShop: автоматические персональные купоны на следующий заказ по правилам, письма, напоминания и воронка. Попробуйте в админке.';

    /**
     * Avoid duplicated panel rendering when several hooks are executed.
     *
     * @var bool
     */
    private $panelRendered = false;

    public function __construct()
    {
        $this->name = 'set_demo';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Viking Coders';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Demo');
        $this->description = $this->l('Tutorial panel and interactive demo scenarios.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        // Keep new hook registration backward-compatible for already installed module.
        if ($this->id && !$this->isRegisteredInHook('displayAdminLogin')) {
            $this->registerHook('displayAdminLogin');
        }
        if ($this->id && !$this->isRegisteredInHook('moduleRoutes')) {
            $this->registerHook('moduleRoutes');
        }
        if ($this->id && !$this->isRegisteredInHook('gSitemapAppendUrls')) {
            $this->registerHook('gSitemapAppendUrls');
        }
    }

    /**
     * @return string[]
     */
    private function getModuleHooks()
    {
        return [
            'displayHeader',
            'displayFooter',
            'displayBackOfficeHeader',
            'displayBackOfficeFooter',
            'displayAdminLogin',
            'moduleRoutes',
            'gSitemapAppendUrls',
        ];
    }

    public function install()
    {
        $success = parent::install();

        foreach ($this->getModuleHooks() as $hook) {
            $success = $success && $this->registerHook($hook);
        }

        $success = $success && Configuration::updateValue(self::CONFIG_AUTOLOGIN_ENABLED, 1);
        $success = $success && Configuration::updateValue(self::CONFIG_AUTOLOGIN_EMPLOYEE_ID, 1);
        $success = $success && Configuration::updateValue(self::CONFIG_AUTOLOGIN_EMAIL, 'demo@demo.com');
        $success = $success && Configuration::updateValue(self::CONFIG_AUTOLOGIN_PASSWORD, 'demodemo');
        $success = $success && Configuration::updateValue(self::CONFIG_ADMIN_DIR, defined('_PS_ADMIN_DIR_') ? basename((string) _PS_ADMIN_DIR_) : '');

        $success = $success && Configuration::updateValue(self::CONFIG_SEO_ADDONS_URL, '');
        $success = $success && Configuration::updateValue(self::CONFIG_SEO_PRICE, '');
        $success = $success && Configuration::updateValue(self::CONFIG_SEO_META_TITLE, self::DEFAULT_SEO_META_TITLE);
        $success = $success && Configuration::updateValue(self::CONFIG_SEO_META_DESC, self::DEFAULT_SEO_META_DESC);
        $success = $success && Configuration::updateValue(self::CONFIG_SEO_OG_IMAGE, '');

        return $success;
    }

    public function uninstall()
    {
        $success = parent::uninstall();

        foreach ($this->getModuleHooks() as $hook) {
            $success = $success && $this->unregisterHook($hook);
        }

        $success = $success && Configuration::deleteByName(self::CONFIG_AUTOLOGIN_ENABLED);
        $success = $success && Configuration::deleteByName(self::CONFIG_AUTOLOGIN_EMPLOYEE_ID);
        $success = $success && Configuration::deleteByName(self::CONFIG_AUTOLOGIN_EMAIL);
        $success = $success && Configuration::deleteByName(self::CONFIG_AUTOLOGIN_PASSWORD);
        $success = $success && Configuration::deleteByName(self::CONFIG_ADMIN_DIR);

        $success = $success && Configuration::deleteByName(self::CONFIG_SEO_ADDONS_URL);
        $success = $success && Configuration::deleteByName(self::CONFIG_SEO_PRICE);
        $success = $success && Configuration::deleteByName(self::CONFIG_SEO_META_TITLE);
        $success = $success && Configuration::deleteByName(self::CONFIG_SEO_META_DESC);
        $success = $success && Configuration::deleteByName(self::CONFIG_SEO_OG_IMAGE);

        return $success;
    }

    public function hookDisplayHeader()
    {
        $this->registerFrontendAssets();

        // Open Graph goes into <head> only on the SEO landing — never store-wide,
        // so product/category pages keep their own social preview.
        if ($this->isSeoLandingController()) {
            return $this->renderSeoOpenGraph();
        }
    }

    /**
     * Clean URL for the SEO landing: /next-order-discount-demo -> front controller "seo".
     *
     * @return array
     */
    public function hookModuleRoutes()
    {
        return [
            'module-set_demo-seo' => [
                'controller' => 'seo',
                'rule' => 'next-order-discount-demo',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'set_demo',
                    'controller' => 'seo',
                ],
            ],
        ];
    }

    /**
     * @return bool true on the module's SEO front controller
     */
    private function isSeoLandingController()
    {
        return $this->context->controller instanceof set_demoSeoModuleFrontController
            || (Tools::getValue('module') === 'set_demo' && Tools::getValue('controller') === 'seo');
    }

    /**
     * @return string Open Graph / Twitter tags for the SEO landing
     */
    private function renderSeoOpenGraph()
    {
        $shopUrl = rtrim($this->context->shop->getBaseURL(true), '/');
        $pageUrl = $this->context->link->getModuleLink('set_demo', 'seo', [], true);

        // OG title/description reuse the page meta; the image is configurable, with a
        // sensible default path under the shop's /img.
        $image = (string) Configuration::get(self::CONFIG_SEO_OG_IMAGE);
        if ($image === '') {
            $image = $shopUrl . '/modules/set_next_order_discount/logo.png';
        }

        $this->context->smarty->assign([
            'set_demo_og_title' => $this->getSeoMetaTitle(),
            'set_demo_og_description' => $this->getSeoMetaDescription(),
            'set_demo_og_url' => $pageUrl,
            'set_demo_og_image' => $image,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/seo_opengraph.tpl');
    }

    /**
     * @return string configured SEO meta title, or the built-in default
     */
    public function getSeoMetaTitle()
    {
        $value = (string) Configuration::get(self::CONFIG_SEO_META_TITLE);

        return ($value !== '' && $value !== self::LEGACY_SEO_META_TITLE)
            ? $value
            : self::DEFAULT_SEO_META_TITLE;
    }

    /**
     * @return string configured SEO meta description, or the built-in default
     */
    public function getSeoMetaDescription()
    {
        $value = (string) Configuration::get(self::CONFIG_SEO_META_DESC);

        return ($value !== '' && $value !== self::LEGACY_SEO_META_DESC)
            ? $value
            : self::DEFAULT_SEO_META_DESC;
    }

    public function hookDisplayBackOfficeHeader()
    {
        $this->registerAdminAssets();
    }

    public function hookDisplayFooter()
    {
        $out = $this->renderPanelOnce(false);

        // A plain, always-present internal link so crawlers can discover the SEO landing.
        // Without a link (or a sitemap entry) the page is an orphan nobody reaches.
        // Skipped on the landing itself — no need to self-link.
        if (!$this->isSeoLandingController()) {
            $out .= $this->renderSeoFooterLink();
        }

        return $out;
    }

    /**
     * Adds the SEO landing to the XML sitemap generated by the "Google sitemap"
     * (gsitemap) module. No-op if that module is not installed — in that case add the
     * URL to your sitemap manually and submit it in Search Console.
     *
     * @return array
     */
    public function hookGSitemapAppendUrls($params)
    {
        return [
            [
                'type' => 'module',
                'page' => 'set_demo_seo',
                'lastmod' => date('Y-m-d'),
                'link' => $this->context->link->getModuleLink('set_demo', 'seo', [], true),
            ],
        ];
    }

    /**
     * @return string a crawlable link to the SEO landing (pretty URL)
     */
    private function renderSeoFooterLink()
    {
        $this->context->smarty->assign([
            'set_demo_seo_url' => $this->context->link->getModuleLink('set_demo', 'seo', [], true),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/seo_footer_link.tpl');
    }

    public function hookDisplayBackOfficeFooter()
    {
        return $this->renderPanelOnce(true);
    }

    public function hookDisplayAdminLogin()
    {
        $this->context->smarty->assign([
            'set_demo_login_value' => 'demo@demo.com',
            'set_demo_password_value' => 'demodemo',
            'set_demo_login_js' => $this->_path . 'views/js/admin_login_autofill.js',
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_login_helper.tpl');
    }

    /**
     * @param bool $isAdmin
     *
     * @return string
     */
    private function renderPanelOnce($isAdmin)
    {
        if ($this->panelRendered) {
            return '';
        }

        // The SEO landing is a public marketing page — keep the tour panel off it.
        if (!$isAdmin && $this->isSeoLandingController()) {
            return '';
        }

        $this->panelRendered = true;

        return $this->renderPanel($isAdmin);
    }

    /**
     * @param bool $isAdmin
     *
     * @return string
     */
    private function renderPanel($isAdmin)
    {
        $baseUrl = $this->context->shop->getBaseURL(true);

        // Admin links to the Next Order Discount controller (legacy AdminController).
        // In the Back Office getAdminLink() gives a valid, tokened URL for the current employee.
        // On the storefront there is no admin token, so build a direct admin URL and
        // pre-compute the token for the DEMO employee (CONFIG_AUTOLOGIN_EMPLOYEE_ID) whose
        // credentials the login page shows. The visitor logs in with those credentials
        // themselves and lands directly on the requested tab — the token matches that employee.
        if ($isAdmin) {
            $slmLink = (string) $this->context->link->getAdminLink('NextOrderDiscount');
            $orderGeneratorUrl = (string) $this->context->link->getAdminLink('DemoOrderGenerator');
            $slmTabUrl = static function ($tab) use ($slmLink) {
                $glue = (strpos($slmLink, '?') === false) ? '?' : '&';

                return $slmLink . $glue . 'tab=' . $tab;
            };
        } else {
            $adminDir = (string) Configuration::get(self::CONFIG_ADMIN_DIR);
            if ($adminDir === '' || !preg_match('#^[a-zA-Z0-9_-]+$#', $adminDir)) {
                $adminDir = defined('_PS_ADMIN_DIR_') ? basename((string) _PS_ADMIN_DIR_) : 'admin';
            }
            // Admin security tokens are disabled for the demo, and getAdminLink() in the Back
            // Office already emits token-less URLs ("/admin-dir/?controller=NextOrderDiscount&tab=...").
            // The storefront has no BO token to compute anyway, so mirror that exact shape. Matching
            // the BO link format also lets the front→admin tour auto-start (see normalizeTutorialPath).
            $adminBase = rtrim($baseUrl, '/') . '/' . trim($adminDir, '/') . '/';
            $orderGeneratorUrl = $adminBase . '?controller=DemoOrderGenerator';
            $slmTabUrl = static function ($tab) use ($adminBase) {
                $url = $adminBase . '?controller=NextOrderDiscount';
                if ($tab !== '') {
                    $url .= '&tab=' . $tab;
                }

                return $url;
            };
        }

        // Front demo popup anchors on the storefront home page.
        $frontUrl = rtrim($baseUrl, '/') . '/';

        $this->context->smarty->assign([
            'set_demo_base_url' => $baseUrl,
            'set_demo_is_admin' => (bool) $isAdmin,
            'set_demo_product_link' => 'https://addons.prestashop.com/',
            'set_demo_support_url' => 'https://addons.prestashop.com/contact-form.php',
            'set_demo_front_url' => $frontUrl,
            'set_demo_slm_url_dashboard' => $slmTabUrl('dashboard'),
            'set_demo_slm_url_rules' => $slmTabUrl('rules'),
            'set_demo_slm_url_rule_edit' => $slmTabUrl('rule_edit') . '&id_rule=0',
            'set_demo_order_generator_url' => $orderGeneratorUrl,
            'set_demo_slm_url_coupons' => $slmTabUrl('coupons'),
            'set_demo_slm_url_settings' => $slmTabUrl('settings'),
            'set_demo_slm_url_cron_tools' => $slmTabUrl('cron_tools'),
            'set_demo_slm_url_logs' => $slmTabUrl('logs'),
            'set_demo_i18n' => $this->panelStrings(),
            'set_demo_i18n_lang' => $this->resolvedIso(),
            'set_demo_i18n_json' => json_encode($this->panelDictionary(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/panel.tpl');
    }

    /**
     * Context language ISO if the panel is translated into it, otherwise English.
     *
     * @return string
     */
    private function resolvedIso()
    {
        $iso = strtolower((string) $this->context->language->iso_code);
        $dict = $this->panelDictionary();

        return isset($dict[$iso]) ? $iso : 'en';
    }

    /**
     * Panel strings for the current context language.
     * English is the default; EN/FR/DE/PL/ES are available in the panel.
     *
     * @return array
     */
    private function panelStrings()
    {
        $dict = $this->panelDictionary();

        return $dict[$this->resolvedIso()];
    }

    /**
     * Translation catalog for the demo navigation panel.
     *
     * @return array
     */
    private function panelDictionary()
    {
        return [
            'en' => [
                'title' => 'Demo navigation',
                'close' => 'Close panel',
                'talk_text' => 'Questions or need help with setup? Write to us — we will reply and help.',
                'talk_action' => 'Contact support',
                'description' => 'Pick a section or go through the whole demo of the coupon engine — from the back office to what the customer gets.',
                'group_admin' => 'Back office:',
                'group_front' => 'For the customer:',
                'link_dashboard' => 'Dashboard: coupon funnel',
                'link_rules' => 'Discount rules',
                'link_rule_edit' => 'Create a rule',
                'link_order_generator' => 'Test order to your email',
                'link_coupons' => 'Coupons: issued coupons',
                'link_settings' => 'Settings',
                'link_cron_tools' => 'Cron/Tools: background tasks',
                'link_logs' => 'Logs: event journal',
                'link_front' => 'How the customer gets a coupon',
                'buy_title' => 'Ready to install the module in your store?',
                'buy_button' => 'Open in the marketplace',
                'resume' => 'resume',
                'start' => 'start',
            ],
            'fr' => [
                'title' => 'Navigation de la démo',
                'close' => 'Fermer le panneau',
                'talk_text' => 'Des questions ou besoin d\'aide pour la configuration ? Écrivez-nous — nous répondrons et vous aiderons.',
                'talk_action' => 'Contacter le support',
                'description' => 'Choisissez une section ou parcourez toute la démo du moteur de coupons — du back-office à ce que reçoit le client.',
                'group_admin' => 'Back-office :',
                'group_front' => 'Pour le client :',
                'link_dashboard' => 'Dashboard : entonnoir des coupons',
                'link_rules' => 'Règles de remise',
                'link_rule_edit' => 'Créer une règle',
                'link_order_generator' => 'Commande test sur votre e-mail',
                'link_coupons' => 'Coupons : coupons émis',
                'link_settings' => 'Settings : paramètres',
                'link_cron_tools' => 'Cron/Tools : tâches de fond',
                'link_logs' => 'Logs : journal',
                'link_front' => 'Comment le client reçoit un coupon',
                'buy_title' => 'Prêt à installer le module dans votre boutique ?',
                'buy_button' => 'Ouvrir sur la marketplace',
                'resume' => 'reprendre',
                'start' => 'démarrer',
            ],
            'de' => [
                'title' => 'Demo-Navigation',
                'close' => 'Panel schließen',
                'talk_text' => 'Fragen oder Hilfe bei der Einrichtung? Schreiben Sie uns — wir helfen gerne weiter.',
                'talk_action' => 'Support kontaktieren',
                'description' => 'Wählen Sie einen Bereich oder folgen Sie der vollständigen Demo der Coupon-Engine — vom Backoffice bis zum Kundenerlebnis.',
                'group_admin' => 'Backoffice:',
                'group_front' => 'Für Kunden:',
                'link_dashboard' => 'Dashboard: Coupon-Funnel',
                'link_rules' => 'Rabattregeln',
                'link_rule_edit' => 'Regel erstellen',
                'link_order_generator' => 'Testbestellung an Ihre E-Mail',
                'link_coupons' => 'Coupons: ausgegebene Coupons',
                'link_settings' => 'Einstellungen',
                'link_cron_tools' => 'Cron/Tools: Hintergrundaufgaben',
                'link_logs' => 'Logs: Ereignisprotokoll',
                'link_front' => 'So erhält der Kunde einen Coupon',
                'buy_title' => 'Möchten Sie das Modul in Ihrem Shop installieren?',
                'buy_button' => 'Im Marketplace öffnen',
                'resume' => 'fortsetzen',
                'start' => 'starten',
            ],
            'pl' => [
                'title' => 'Nawigacja po demo',
                'close' => 'Zamknij panel',
                'talk_text' => 'Masz pytania lub potrzebujesz pomocy z konfiguracją? Napisz do nas — chętnie pomożemy.',
                'talk_action' => 'Skontaktuj się z pomocą',
                'description' => 'Wybierz sekcję lub przejdź całe demo mechanizmu kuponów — od zaplecza po widok klienta.',
                'group_admin' => 'Zaplecze:',
                'group_front' => 'Dla klienta:',
                'link_dashboard' => 'Dashboard: lejek kuponów',
                'link_rules' => 'Reguły rabatowe',
                'link_rule_edit' => 'Utwórz regułę',
                'link_order_generator' => 'Zamówienie testowe na Twój e-mail',
                'link_coupons' => 'Kupony: wydane kupony',
                'link_settings' => 'Ustawienia',
                'link_cron_tools' => 'Cron/Tools: zadania w tle',
                'link_logs' => 'Logi: dziennik zdarzeń',
                'link_front' => 'Jak klient otrzymuje kupon',
                'buy_title' => 'Chcesz zainstalować moduł w swoim sklepie?',
                'buy_button' => 'Otwórz w marketplace',
                'resume' => 'wznów',
                'start' => 'rozpocznij',
            ],
            'es' => [
                'title' => 'Navegación de la demo',
                'close' => 'Cerrar panel',
                'talk_text' => '¿Tienes preguntas o necesitas ayuda con la configuración? Escríbenos y te ayudaremos.',
                'talk_action' => 'Contactar con soporte',
                'description' => 'Elige una sección o recorre toda la demo del motor de cupones, desde el back office hasta la experiencia del cliente.',
                'group_admin' => 'Back office:',
                'group_front' => 'Para el cliente:',
                'link_dashboard' => 'Dashboard: embudo de cupones',
                'link_rules' => 'Reglas de descuento',
                'link_rule_edit' => 'Crear una regla',
                'link_order_generator' => 'Pedido de prueba a tu e-mail',
                'link_coupons' => 'Cupones: cupones emitidos',
                'link_settings' => 'Ajustes',
                'link_cron_tools' => 'Cron/Tools: tareas en segundo plano',
                'link_logs' => 'Logs: registro de eventos',
                'link_front' => 'Cómo recibe el cliente un cupón',
                'buy_title' => '¿Quieres instalar el módulo en tu tienda?',
                'buy_button' => 'Abrir en el marketplace',
                'resume' => 'continuar',
                'start' => 'iniciar',
            ],
        ];
    }

    private function registerFrontendAssets()
    {
        $this->context->controller->addCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
        $this->context->controller->addCSS($this->_path . 'views/css/introjs.css');
        $this->context->controller->addCSS($this->_path . 'views/css/main.css');
        $this->context->controller->addCSS($this->_path . 'views/css/additional_front.css');

        $this->context->controller->addJS($this->_path . 'views/js/intro.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/libs.js');
        $this->context->controller->addJS($this->_path . 'views/js/intro_helper.js');
        $this->context->controller->addJS($this->_path . 'views/js/tutorial_helper.js');
        $this->context->controller->addJS($this->_path . 'views/js/tutorial_i18n.js');
        $this->context->controller->addJS($this->_path . 'views/js/tutorial_data_front.js');
        $this->context->controller->addJS($this->_path . 'views/js/scenario_front.js');
    }

    /**
     * Module configuration page (Modules → set_demo → Configure).
     *
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitSetDemoSeo')) {
            Configuration::updateValue(self::CONFIG_SEO_ADDONS_URL, trim((string) Tools::getValue(self::CONFIG_SEO_ADDONS_URL)));
            Configuration::updateValue(self::CONFIG_SEO_PRICE, trim((string) Tools::getValue(self::CONFIG_SEO_PRICE)));
            Configuration::updateValue(self::CONFIG_SEO_META_TITLE, trim((string) Tools::getValue(self::CONFIG_SEO_META_TITLE)));
            Configuration::updateValue(self::CONFIG_SEO_META_DESC, trim((string) Tools::getValue(self::CONFIG_SEO_META_DESC)));
            Configuration::updateValue(self::CONFIG_SEO_OG_IMAGE, trim((string) Tools::getValue(self::CONFIG_SEO_OG_IMAGE)));

            $output .= $this->displayConfirmation($this->l('SEO demo settings saved.'));
        }

        return $output . $this->renderSeoConfigForm();
    }

    /**
     * @return string
     */
    private function renderSeoConfigForm()
    {
        $fields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('SEO demo landing'),
                    'icon' => 'icon-search',
                ],
                'description' => $this->l('Public landing at /next-order-discount-demo. These values feed its meta tags and JSON-LD.'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Meta title'),
                        'name' => self::CONFIG_SEO_META_TITLE,
                        'desc' => $this->l('Shown in the search snippet. Keep it under 60 characters.'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Meta description'),
                        'name' => self::CONFIG_SEO_META_DESC,
                        'desc' => $this->l('Search snippet description. Keep it under 160 characters.'),
                        'rows' => 3,
                        'cols' => 60,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('PrestaShop Addons URL'),
                        'name' => self::CONFIG_SEO_ADDONS_URL,
                        'desc' => $this->l('Marketplace listing link. Leave empty to hide the price and the "Get it on Addons" button.'),
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Module price (EUR)'),
                        'name' => self::CONFIG_SEO_PRICE,
                        'desc' => $this->l('Used only in JSON-LD, e.g. 49.99. The Offer is emitted only when both price and Addons URL are set.'),
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Open Graph image URL'),
                        'name' => self::CONFIG_SEO_OG_IMAGE,
                        'desc' => $this->l('Absolute URL of the social preview image (~1200×630). Empty falls back to the module logo.'),
                        'class' => 'fixed-width-xxl',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitSetDemoSeo';
        $helper->fields_value = [
            self::CONFIG_SEO_META_TITLE => $this->getSeoMetaTitle(),
            self::CONFIG_SEO_META_DESC => $this->getSeoMetaDescription(),
            self::CONFIG_SEO_ADDONS_URL => (string) Configuration::get(self::CONFIG_SEO_ADDONS_URL),
            self::CONFIG_SEO_PRICE => (string) Configuration::get(self::CONFIG_SEO_PRICE),
            self::CONFIG_SEO_OG_IMAGE => (string) Configuration::get(self::CONFIG_SEO_OG_IMAGE),
        ];

        return $helper->generateForm([$fields]);
    }

    private function registerAdminAssets()
    {
        $this->context->controller->addCSS('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
        $this->context->controller->addCSS($this->_path . 'views/css/introjs.css');
        $this->context->controller->addCSS($this->_path . 'views/css/main.css');
        $this->context->controller->addCSS($this->_path . 'views/css/additional_admin.css');

        $this->context->controller->addJS($this->_path . 'views/js/intro.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/libs.js');
        $this->context->controller->addJS($this->_path . 'views/js/intro_helper.js');
        $this->context->controller->addJS($this->_path . 'views/js/tutorial_helper.js');
        $this->context->controller->addJS($this->_path . 'views/js/tutorial_i18n.js');
        $this->context->controller->addJS($this->_path . 'views/js/tutorial_data_admin.js');
        $this->context->controller->addJS($this->_path . 'views/js/scenario_admin.js');
    }
}
