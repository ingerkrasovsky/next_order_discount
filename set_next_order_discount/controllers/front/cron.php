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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Public cron endpoint for the module's background tasks.
 *
 * Reachable as an AJAX module front controller, it authenticates the caller with
 * the secret cron token, then delegates to the cron router which runs the
 * requested task under a lock. The response is always JSON. No task is ever run
 * without a valid token.
 *
 * @property set_next_order_discount $module
 */
class Set_Next_Order_DiscountCronModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    /**
     * @return void
     */
    public function initContent()
    {
        try {
            parent::initContent();
            $this->handleCronRequest();
        } catch (\Throwable $e) {
            // A public endpoint must always answer JSON and must never leak an
            // internal error page (which can expose a stack trace in dev mode).
            $this->respond(['success' => false, 'error' => 'internal_error'], 500);
        }
    }

    /**
     * Authenticates the caller and runs the requested task.
     *
     * @return void
     */
    private function handleCronRequest()
    {
        $token = Tools::getValue('token');
        if (!$this->module->getCronSecurityService()->isValidToken($token)) {
            $this->respond(['success' => false, 'error' => 'forbidden'], 403);
        }

        $task = (string) Tools::getValue('task');
        $idShop = (int) Tools::getValue('id_shop', 0);

        $result = $this->module->getCronRouter()->run($task, $idShop);

        $this->respond($result, $this->statusFor($result));
    }

    /**
     * Maps a router result to an HTTP status code.
     *
     * @param array $result
     *
     * @return int
     */
    private function statusFor(array $result)
    {
        if (!empty($result['success'])) {
            return 200;
        }
        if (!empty($result['locked'])) {
            return 200;
        }
        if (isset($result['error']) && $result['error'] === 'unknown_task') {
            return 400;
        }

        return 500;
    }

    /**
     * Emits a JSON response and terminates the request.
     *
     * @param array $data
     * @param int   $httpCode
     *
     * @return void
     */
    private function respond(array $data, $httpCode = 200)
    {
        if (function_exists('http_response_code')) {
            http_response_code((int) $httpCode);
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
