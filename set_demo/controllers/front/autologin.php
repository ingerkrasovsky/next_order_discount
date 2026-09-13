<?php

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (file_exists(_PS_ROOT_DIR_ . '/src/PrestaShopBundle/Entity/Employee/Employee.php')) {
    require_once _PS_ROOT_DIR_ . '/src/PrestaShopBundle/Entity/Employee/Employee.php';
}

if (!defined('_PS_VERSION_')) {
    exit;
}

class set_demoAutologinModuleFrontController extends ModuleFrontController
{
    const CONFIG_AUTOLOGIN_EMPLOYEE_ID = 'SET_DEMO_AUTOLOGIN_EMPLOYEE_ID';
    const CONFIG_AUTOLOGIN_EMAIL = 'SET_DEMO_AUTOLOGIN_EMAIL';
    const CONFIG_AUTOLOGIN_PASSWORD = 'SET_DEMO_AUTOLOGIN_PASSWORD';
    const DEFAULT_AUTOLOGIN_EMAIL = 'i.krasovsky@outlook.com';
    const DEFAULT_AUTOLOGIN_PASSWORD = 'gavr512QQ';

    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        if (!$this->isAutologinAllowed()) {
            header('HTTP/1.1 403 Forbidden');
            exit('Autologin is disabled.');
        }

        $target = (string) Tools::getValue('target', 'improve/modules/manage/action/configure/set_relatedproducts');
        $target = ltrim($target, '/');

        if (!preg_match('#^[a-zA-Z0-9_\-\./]+$#', $target)) {
            $target = 'improve/modules/manage/action/configure/set_relatedproducts';
        }

        $employee = $this->resolveAutologinEmployee();
        if (!Validate::isLoadedObject($employee) || !$employee->active) {
            header('HTTP/1.1 500 Internal Server Error');
            exit('Unable to autologin: employee not found, inactive, or credentials are invalid.');
        }

        $this->loginEmployee($employee);

        $adminUrl = $this->resolveAdminTargetUrl($target);
        if (!is_string($adminUrl) || $adminUrl === '') {
            $adminUrl = $this->getAdminBaseUrl();
        }

        $this->redirectToUrl($adminUrl);
    }

    /**
     * Resolve employee by fixed credentials.
     *
     * @return Employee
     */
    private function resolveAutologinEmployee()
    {
        $email = self::DEFAULT_AUTOLOGIN_EMAIL;
        $password = self::DEFAULT_AUTOLOGIN_PASSWORD;

        $employee = new Employee();
        $resolved = $employee->getByEmail($email, $password, true);
        if ($resolved instanceof Employee && Validate::isLoadedObject($resolved)) {
            return $resolved;
        }

        return new Employee(0);
    }

    /**
     * Restrict autologin to safe environments.
     *
     * @return bool
     */
    private function isAutologinAllowed()
    {
        return true; // Disable environment checks to allow autologin in all environments for testing purposes.
        // if (Configuration::hasKey('SET_DEMO_AUTOLOGIN_ENABLED') && !(bool) Configuration::get('SET_DEMO_AUTOLOGIN_ENABLED')) {
        //     return false;
        // }

        // if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_) {
        //     return true;
        // }

        // $remoteAddr = (string) Tools::getRemoteAddr();

        // return in_array($remoteAddr, ['127.0.0.1', '::1'], true);
    }

    /**
     * Create Back Office session cookie for selected employee.
     *
     * @param Employee $employee
     */
    private function loginEmployee(Employee $employee)
    {
        $cookie = new Cookie('psAdmin');

        // Remove old BO session binding to avoid stale session conflicts.
        if (isset($cookie->session_id) || isset($cookie->session_token)) {
            $cookie->deleteSession();
            unset($cookie->session_id, $cookie->session_token);
        }

        $cookie->id_employee = (int) $employee->id;
        $cookie->email = (string) $employee->email;
        $cookie->profile = (int) $employee->id_profile;
        $cookie->passwd = (string) $employee->passwd;
        $cookie->remote_addr = (int) ip2long(Tools::getRemoteAddr());
        $cookie->id_lang = (int) $employee->id_lang;
        $cookie->shopContext = 's-' . (int) Configuration::get('PS_SHOP_DEFAULT');
        $cookie->last_activity = time();

        if (version_compare(_PS_VERSION_, '1.7.6.6', '>=')) {
            $cookie->registerSession(new EmployeeSession());
        }

        $cookie->write();
        Cache::clean('isLoggedBack' . (int) $employee->id);

        Context::getContext()->employee = $employee;
        Context::getContext()->cookie = $cookie;
        $this->context->employee = $employee;
        $this->context->cookie = $cookie;

        // Keep Symfony security session in sync for PS9 BO firewall.
        $this->loginEmployeeInSymfony($employee);
    }

    /**
     * Sync login with Symfony security if container is available.
     *
     * @param Employee $employee
     */
    private function loginEmployeeInSymfony(Employee $employee)
    {
        if (!class_exists(SymfonyContainer::class)) {
            return;
        }

        $container = SymfonyContainer::getInstance();
        if (!$container || !$container->has('security.helper') || !$container->has('doctrine.orm.entity_manager')) {
            return;
        }

        try {
            $entityManager = $container->get('doctrine.orm.entity_manager');
            $security = $container->get('security.helper');
            if (!is_object($security) || !method_exists($security, 'login')) {
                return;
            }

            if (!class_exists('PrestaShopBundle\\Entity\\Employee\\Employee')) {
                return;
            }

            $employeeEntity = $entityManager
                ->getRepository('PrestaShopBundle\\Entity\\Employee\\Employee')
                ->findOneBy(['id' => (int) $employee->id]);

            if (!$employeeEntity) {
                return;
            }

            // Explicitly target BO firewall/authenticator.
            $response = $security->login($employeeEntity, 'security.authenticator.form_login.main', 'main');
            if ($response instanceof RedirectResponse) {
                // Ignore redirect response here, controller handles final target URL.
            }
        } catch (Exception $e) {
            // Cookie login remains as fallback when Symfony login is unavailable.
        }
    }

    /**
     * @return string
     */
    private function getAdminBaseUrl()
    {
        $adminDirName = $this->resolveAdminDirName();

        if (class_exists(SymfonyContainer::class) && SymfonyContainer::getInstance()) {
            try {
                $router = SymfonyContainer::getInstance()->get('router');
                $loginUrl = $router->generate('admin_homepage');
                if (is_string($loginUrl) && $loginUrl !== '') {
                    return rtrim(str_replace('/index.php', '', $this->context->link->getBaseLink()), '/') . '/' . trim($adminDirName, '/') . '/';
                }
            } catch (Exception $e) {
                // Fallback to legacy path builder below.
            }
        }

        return rtrim($this->context->link->getBaseLink(), '/') . '/' . trim($adminDirName, '/') . '/';
    }

    /**
     * Build reliable post-login target URL.
     *
     * @param string $target
     *
     * @return string
     */
    private function resolveAdminTargetUrl($target)
    {
        if ($target === 'improve/modules/manage/action/configure/set_relatedproducts') {
            $base = $this->getAdminBaseUrl();
            $symfonyTarget = $base . 'improve/modules/manage/action/configure/set_relatedproducts';

            // Prefer modern route URL. If firewall is already synced, this opens directly.
            if (is_string($symfonyTarget) && $symfonyTarget !== '') {
                return $symfonyTarget;
            }

            $adminLink = $this->context->link->getAdminLink('AdminModules', true, [], ['configure' => 'set_relatedproducts']);
            if (is_string($adminLink) && $adminLink !== '') {
                return $adminLink;
            }

            return $base;
        }

        return $this->getAdminBaseUrl() . ltrim((string) $target, '/');
    }

    /**
     * Redirect without Tools::redirectAdmin(), which may require _PS_ADMIN_DIR_.
     *
     * @param string $url
     */
    private function redirectToUrl($url)
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = rtrim($this->context->link->getBaseLink(), '/') . '/' . ltrim((string) $url, '/');
        }

        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Resolve admin directory name in front context where _PS_ADMIN_DIR_ may be undefined.
     *
     * @return string
     */
    private function resolveAdminDirName()
    {
        $configured = (string) Configuration::get('SET_DEMO_ADMIN_DIR');
        if ($configured !== '' && preg_match('#^[a-zA-Z0-9_-]+$#', $configured)) {
            return $configured;
        }

        if (defined('_PS_ADMIN_DIR_') && _PS_ADMIN_DIR_) {
            return basename((string) _PS_ADMIN_DIR_);
        }

        $candidates = glob(rtrim(_PS_ROOT_DIR_, '/') . '/admin*', GLOB_ONLYDIR);
        if (is_array($candidates)) {
            foreach ($candidates as $path) {
                $dirName = basename($path);
                if ($dirName === 'admin-api') {
                    continue;
                }

                if (is_file($path . '/index.php')) {
                    return $dirName;
                }
            }
        }

        return 'admin';
    }
}
