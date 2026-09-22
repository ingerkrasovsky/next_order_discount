<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class DemoOrderGeneratorController extends ModuleAdminController
{
    /** @var set_demo_order_generator */
    public $module;

    private $hasSubmission = false;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (!Tools::isSubmit('submitCreateDemoOrder')) {
            parent::postProcess();

            return;
        }

        $this->hasSubmission = true;
        try {
            if (!Module::isInstalled('set_next_order_discount') || !Module::isEnabled('set_next_order_discount')) {
                throw new RuntimeException('Install and enable Next Order Discount before creating a test order.');
            }

            $result = $this->module->getOrderCreator()->create($this->module->readSubmittedOrderData());
            $params = [
                'created_order_id' => (int) $result['order_id'],
                'created_customer_id' => (int) $result['customer_id'],
                'created_email' => (string) $result['email'],
                'created_total' => number_format((float) $result['total'], 2, '.', ''),
                'created_currency' => (string) $result['currency'],
                'created_coupon' => (string) $result['coupon_code'],
                'created_coupon_status' => (string) $result['coupon_status'],
                'usage_saved' => !empty($result['usage_saved']) ? 1 : 0,
            ];
            Tools::redirectAdmin(
                $this->context->link->getAdminLink(set_demo_order_generator::ADMIN_CONTROLLER)
                . '&' . http_build_query($params)
            );
        } catch (Throwable $e) {
            $this->errors[] = $e->getMessage();
        }
    }

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->module->renderAdminPage($this);
        $this->context->smarty->assign('content', $this->content);
    }

    public function hasSubmission()
    {
        return $this->hasSubmission;
    }

    public function getCreatedResult()
    {
        $idOrder = (int) Tools::getValue('created_order_id');
        if ($idOrder <= 0) {
            return null;
        }

        return [
            'order_id' => $idOrder,
            'customer_id' => (int) Tools::getValue('created_customer_id'),
            'email' => (string) Tools::getValue('created_email'),
            'total' => (string) Tools::getValue('created_total'),
            'currency' => (string) Tools::getValue('created_currency'),
            'coupon_code' => (string) Tools::getValue('created_coupon'),
            'coupon_status' => (string) Tools::getValue('created_coupon_status'),
            'usage_saved' => (bool) Tools::getValue('usage_saved'),
            'order_url' => $this->context->link->getAdminLink('AdminOrders', true, [], ['id_order' => $idOrder, 'vieworder' => 1]),
        ];
    }
}
