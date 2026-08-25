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
namespace Setecom\NextOrderDiscount\Rule;

use Tools;
use Setecom\NextOrderDiscount\Repository\RuleEmailRepository;
use Setecom\NextOrderDiscount\Repository\RuleRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Application service for the rule add/edit form: validates a raw input array,
 * persists the rule and its conditions, and builds the values shown in the
 * form. It is decoupled from the PrestaShop request and translation layers —
 * it takes a plain input array and returns error *codes* (the controller maps
 * them to translated messages), which keeps it unit-testable.
 */
class RuleFormHandler
{
    public const ERR_NAME_REQUIRED = 'name_required';
    public const ERR_DISCOUNT_VALUE = 'discount_value_invalid';
    public const ERR_DISCOUNT_PERCENT_MAX = 'discount_percent_too_high';
    public const ERR_VALIDITY = 'validity_invalid';
    public const ERR_SOURCE_RANGE = 'source_min_gt_max';
    public const ERR_ORDER_COUNT_RANGE = 'order_count_min_gt_max';
    public const ERR_DATE_RANGE = 'date_from_after_to';
    public const ERR_SAVE_FAILED = 'save_failed';
    public const ERR_NOT_FOUND = 'not_found';

    private const NAME_MAX_LENGTH = 128;
    private const DEFAULT_VALIDITY_DAYS = 30;

    private $repository;
    private $emailRepository;

    /**
     * @param RuleRepository      $repository
     * @param RuleEmailRepository $emailRepository
     */
    public function __construct(RuleRepository $repository, RuleEmailRepository $emailRepository)
    {
        $this->repository = $repository;
        $this->emailRepository = $emailRepository;
    }

    /**
     * Validates and persists the form.
     *
     * @param array $input       raw form values (see form value keys)
     * @param int   $idShop
     * @param int   $idRule      0 for a new rule
     * @param int   $idShopGroup
     *
     * @return array 'errors' => string[] (codes), 'id_rule' => int
     */
    public function save(array $input, $idShop, $idRule, $idShopGroup)
    {
        $idShop = (int) $idShop;
        $idRule = (int) $idRule;

        $processed = $this->process($input);
        if (!empty($processed['errors'])) {
            return ['errors' => $processed['errors'], 'id_rule' => $idRule];
        }

        $data = $processed['data'];

        if ($idRule > 0) {
            $existing = $this->repository->findById($idRule);
            if ($existing === null || (int) $existing['id_shop'] !== $idShop) {
                return ['errors' => [self::ERR_NOT_FOUND], 'id_rule' => 0];
            }
            $this->repository->update($idRule, $data);
        } else {
            $data['id_shop'] = $idShop;
            $data['id_shop_group'] = (int) $idShopGroup;
            $data['priority'] = $this->repository->nextPriority($idShop);
            $idRule = $this->repository->insert($data);
            if ($idRule <= 0) {
                return ['errors' => [self::ERR_SAVE_FAILED], 'id_rule' => 0];
            }
        }

        $this->persistEmails($idRule, $input);

        $this->repository->setConditions(
            $idRule,
            RuleConditionSchema::TYPE_STATUS,
            $this->intList(isset($input['status_ids']) ? $input['status_ids'] : [])
        );

        // Persist the list conditions present in the input (all/include/exclude
        // types). Types the form did not submit keep their stored selection.
        foreach (RuleConditionSchema::modeTypes() as $type) {
            $idsKey = $type . '_ids';
            if (!array_key_exists($idsKey, $input)) {
                continue;
            }
            $this->repository->setConditions($idRule, $type, $this->intList($input[$idsKey]));
        }

        return ['errors' => [], 'id_rule' => $idRule];
    }

    /**
     * @return array default form values for a new rule
     */
    public function defaultValues()
    {
        $values = [
            'name' => '',
            'active' => 1,
            'stop_further' => 1,
            'discount_type' => RuleRepository::DISCOUNT_PERCENT,
            'discount_value' => '10',
            'validity_days' => (string) self::DEFAULT_VALIDITY_DAYS,
            'next_order_min_amount' => '0',
            'source_total_min' => '0',
            'source_total_max' => '0',
            'date_from' => '',
            'date_to' => '',
            'customer_order_count_min' => '0',
            'customer_order_count_max' => '0',
            'status_ids' => [],
            'code_prefix' => '',
            'code_length' => '',
            'code_mask' => '',
        ];

        foreach (RuleConditionSchema::modeTypes() as $type) {
            $values[$type . '_mode'] = RuleConditionSchema::MODE_ALL;
            $values[$type . '_ids'] = [];
        }

        return $values;
    }

    /**
     * @param array $rule a rule row with 'conditions'
     *
     * @return array
     */
    public function valuesFromRule(array $rule)
    {
        $conditions = isset($rule['conditions']) ? $rule['conditions'] : [];

        $values = [
            'name' => (string) $rule['name'],
            'active' => (int) $rule['active'],
            'stop_further' => (int) $rule['stop_further'],
            'discount_type' => (string) $rule['discount_type'],
            'discount_value' => RuleValueFormatter::decimal($rule['discount_value']),
            'validity_days' => (int) $rule['validity_days'],
            'next_order_min_amount' => RuleValueFormatter::decimal($rule['next_order_min_amount']),
            'source_total_min' => RuleValueFormatter::decimal($rule['source_total_min']),
            'source_total_max' => RuleValueFormatter::decimal($rule['source_total_max']),
            'date_from' => RuleValueFormatter::dateColumnToInput($rule['date_from']),
            'date_to' => RuleValueFormatter::dateColumnToInput($rule['date_to']),
            'customer_order_count_min' => (int) $rule['customer_order_count_min'],
            'customer_order_count_max' => (int) $rule['customer_order_count_max'],
            'status_ids' => isset($conditions[RuleConditionSchema::TYPE_STATUS])
                ? $conditions[RuleConditionSchema::TYPE_STATUS]
                : [],
            'code_prefix' => isset($rule['code_prefix']) ? (string) $rule['code_prefix'] : '',
            'code_length' => (isset($rule['code_length']) && (int) $rule['code_length'] > 0)
                ? (string) (int) $rule['code_length']
                : '',
            'code_mask' => isset($rule['code_mask']) ? (string) $rule['code_mask'] : '',
        ];

        foreach (RuleConditionSchema::modeTypes() as $type) {
            $modeColumn = RuleConditionSchema::modeColumn($type);
            $values[$type . '_mode'] = isset($rule[$modeColumn]) ? (string) $rule[$modeColumn] : RuleConditionSchema::MODE_ALL;
            $values[$type . '_ids'] = isset($conditions[$type]) ? $conditions[$type] : [];
        }

        return $values;
    }

    /**
     * @param array $input raw submitted values
     *
     * @return array form values echoed back to the user
     */
    public function valuesFromInput(array $input)
    {
        $values = [
            'name' => $this->str($input, 'name'),
            'active' => $this->str($input, 'active') === '1' ? 1 : 0,
            'stop_further' => $this->str($input, 'stop_further') === '1' ? 1 : 0,
            'discount_type' => $this->str($input, 'discount_type'),
            'discount_value' => $this->str($input, 'discount_value'),
            'validity_days' => $this->str($input, 'validity_days'),
            'next_order_min_amount' => $this->str($input, 'next_order_min_amount'),
            'source_total_min' => $this->str($input, 'source_total_min'),
            'source_total_max' => $this->str($input, 'source_total_max'),
            'date_from' => $this->str($input, 'date_from'),
            'date_to' => $this->str($input, 'date_to'),
            'customer_order_count_min' => $this->str($input, 'customer_order_count_min'),
            'customer_order_count_max' => $this->str($input, 'customer_order_count_max'),
            'status_ids' => $this->intList(isset($input['status_ids']) ? $input['status_ids'] : []),
            'code_prefix' => $this->str($input, 'code_prefix'),
            'code_length' => $this->str($input, 'code_length'),
            'code_mask' => $this->str($input, 'code_mask'),
        ];

        foreach (RuleConditionSchema::modeTypes() as $type) {
            $values[$type . '_mode'] = RuleConditionSchema::normalizeMode($this->str($input, $type . '_mode'));
            $values[$type . '_ids'] = $this->intList(isset($input[$type . '_ids']) ? $input[$type . '_ids'] : []);
        }

        return $values;
    }

    /**
     * Validates the input and builds the persistable data array.
     *
     * @param array $input
     *
     * @return array 'errors' => string[], 'data' => array
     */
    private function process(array $input)
    {
        $errors = [];
        $data = [];

        $name = trim($this->str($input, 'name'));
        if ($name === '') {
            $errors[] = self::ERR_NAME_REQUIRED;
        } else {
            $name = Tools::substr($name, 0, self::NAME_MAX_LENGTH);
        }
        $data['name'] = $name;

        $type = $this->str($input, 'discount_type');
        if (!in_array($type, RuleRepository::discountTypes(), true)) {
            $type = RuleRepository::DISCOUNT_PERCENT;
        }
        $data['discount_type'] = $type;

        $data['discount_value'] = 0.0;
        if ($type !== RuleRepository::DISCOUNT_FREE_SHIPPING) {
            $raw = str_replace(',', '.', trim($this->str($input, 'discount_value')));
            if ($raw === '' || !is_numeric($raw) || (float) $raw <= 0) {
                $errors[] = self::ERR_DISCOUNT_VALUE;
            } elseif ($type === RuleRepository::DISCOUNT_PERCENT && (float) $raw > 100) {
                $errors[] = self::ERR_DISCOUNT_PERCENT_MAX;
            } else {
                $data['discount_value'] = (float) $raw;
            }
        }

        $validityRaw = trim($this->str($input, 'validity_days'));
        if ($validityRaw === '' || !ctype_digit($validityRaw) || (int) $validityRaw < 1) {
            $errors[] = self::ERR_VALIDITY;
            $data['validity_days'] = self::DEFAULT_VALIDITY_DAYS;
        } else {
            $data['validity_days'] = (int) $validityRaw;
        }

        $data['next_order_min_amount'] = $this->decimal($input, 'next_order_min_amount');

        $min = $this->decimal($input, 'source_total_min');
        $max = $this->decimal($input, 'source_total_max');
        if ($this->rangeHasError($min, $max)) {
            $errors[] = self::ERR_SOURCE_RANGE;
        }
        $data['source_total_min'] = $min;
        $data['source_total_max'] = $max;

        $countMin = $this->nonNegativeInt($input, 'customer_order_count_min');
        $countMax = $this->nonNegativeInt($input, 'customer_order_count_max');
        if ($this->rangeHasError($countMin, $countMax)) {
            $errors[] = self::ERR_ORDER_COUNT_RANGE;
        }
        $data['customer_order_count_min'] = $countMin;
        $data['customer_order_count_max'] = $countMax;

        $from = $this->date($input, 'date_from', false);
        $to = $this->date($input, 'date_to', true);
        if ($from !== null && $to !== null && $from > $to) {
            $errors[] = self::ERR_DATE_RANGE;
        }
        $data['date_from'] = $from;
        $data['date_to'] = $to;

        $data['active'] = $this->str($input, 'active') === '1' ? 1 : 0;
        $data['stop_further'] = $this->str($input, 'stop_further') === '1' ? 1 : 0;

        // Per-rule coupon code overrides. Empty values mean "use the global
        // default from Settings", so they are stored empty/zero and resolved at
        // generation time; the values are sanitized, never hard-rejected.
        $data['code_prefix'] = $this->sanitizeCodePrefix($this->str($input, 'code_prefix'));
        $data['code_length'] = $this->codeLength($this->str($input, 'code_length'));
        $data['code_mask'] = $this->sanitizeCodeMask($this->str($input, 'code_mask'));

        // Mode columns for the list conditions that the submitted form manages
        // (a type absent from the input is left untouched, never reset).
        foreach (RuleConditionSchema::modeTypes() as $type) {
            $modeKey = $type . '_mode';
            if (!array_key_exists($modeKey, $input)) {
                continue;
            }
            $data[RuleConditionSchema::modeColumn($type)] = RuleConditionSchema::normalizeMode(
                $this->str($input, $modeKey)
            );
        }

        return ['errors' => $errors, 'data' => $data];
    }

    /**
     * @param array  $input
     * @param string $key
     *
     * @return string scalar value ('' for missing/array)
     */
    private function str(array $input, $key)
    {
        if (!isset($input[$key]) || is_array($input[$key])) {
            return '';
        }

        return (string) $input[$key];
    }

    /**
     * @param array  $input
     * @param string $key
     *
     * @return float non-negative decimal (0 on empty/invalid)
     */
    private function decimal(array $input, $key)
    {
        $raw = str_replace(',', '.', trim($this->str($input, $key)));
        if ($raw === '' || !is_numeric($raw)) {
            return 0.0;
        }

        return max(0.0, (float) $raw);
    }

    /**
     * @param array  $input
     * @param string $key
     *
     * @return int non-negative integer (0 on empty/invalid)
     */
    private function nonNegativeInt(array $input, $key)
    {
        $raw = trim($this->str($input, $key));
        if ($raw === '' || !ctype_digit($raw)) {
            return 0;
        }

        return (int) $raw;
    }

    /**
     * A bounded range is invalid when both ends are set and min exceeds max.
     *
     * @param float|int $min
     * @param float|int $max
     *
     * @return bool
     */
    private function rangeHasError($min, $max)
    {
        return $min > 0 && $max > 0 && $min > $max;
    }

    /**
     * @param array  $input
     * @param string $key
     * @param bool   $endOfDay
     *
     * @return string|null 'Y-m-d H:i:s' or null
     */
    private function date(array $input, $key, $endOfDay)
    {
        $raw = trim($this->str($input, $key));
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp) . ($endOfDay ? ' 23:59:59' : ' 00:00:00');
    }

    /**
     * Persists the per-rule email content (subject + HTML) for every email type
     * and language present in the submitted form. Absent types/languages are
     * left untouched. Empty content is stored as-is; the mailer falls back to the
     * shipped default template when a language has no usable content.
     *
     * @param int   $idRule
     * @param array $input
     *
     * @return void
     */
    private function persistEmails($idRule, array $input)
    {
        if (!isset($input['email']) || !is_array($input['email'])) {
            return;
        }

        foreach (RuleEmailRepository::types() as $type) {
            if (!isset($input['email'][$type]) || !is_array($input['email'][$type])) {
                continue;
            }

            $block = $input['email'][$type];
            $subjects = (isset($block['subject']) && is_array($block['subject'])) ? $block['subject'] : [];
            $htmls = (isset($block['html']) && is_array($block['html'])) ? $block['html'] : [];

            $langIds = array_unique(array_merge(array_keys($subjects), array_keys($htmls)));
            foreach ($langIds as $idLang) {
                $idLang = (int) $idLang;
                if ($idLang <= 0) {
                    continue;
                }
                $subject = isset($subjects[$idLang]) ? Tools::substr((string) $subjects[$idLang], 0, 255) : '';
                $html = isset($htmls[$idLang]) ? (string) $htmls[$idLang] : '';
                $this->emailRepository->save($idRule, $type, $idLang, $subject, $html);
            }
        }
    }

    /**
     * @param string $raw
     *
     * @return string sanitized prefix (A-Z0-9, <=20); '' means "use global"
     */
    private function sanitizeCodePrefix($raw)
    {
        $prefix = preg_replace('/[^A-Z0-9]/', '', Tools::strtoupper(trim((string) $raw)));

        return is_string($prefix) ? Tools::substr($prefix, 0, 20) : '';
    }

    /**
     * @param string $raw
     *
     * @return string sanitized mask (A-Z0-9#_-, <=64); '' means "use global"
     */
    private function sanitizeCodeMask($raw)
    {
        $mask = preg_replace('/[^A-Z0-9#_\-]/', '', Tools::strtoupper(trim((string) $raw)));

        return is_string($mask) ? Tools::substr($mask, 0, 64) : '';
    }

    /**
     * @param string $raw
     *
     * @return int random-part length, or 0 to use the global default
     */
    private function codeLength($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '' || !ctype_digit($raw)) {
            return 0;
        }

        return (int) $raw;
    }

    /**
     * @param mixed $ids
     *
     * @return array integer ids
     */
    private function intList($ids)
    {
        if (!is_array($ids)) {
            return [];
        }

        return array_map('intval', $ids);
    }
}
