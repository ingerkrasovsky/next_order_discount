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

use Setecom\NextOrderDiscount\Repository\RuleRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Resolves which discount rules apply to an order.
 *
 * Given an order context it returns the active rules (in priority order) whose
 * conditions all pass, stopping after the first matched rule that carries the
 * stop_further flag. Pure decision logic: it depends only on the injected
 * repository and the context array, never on hooks/controllers, so it is
 * unit-testable and reusable across hooks and cron.
 */
class RuleMatcher
{
    /**
     * Maps each list-condition type to the order-context key that feeds it and
     * whether that key is multi-valued. Adding a condition type's context
     * source is a one-line change here.
     *
     * type => [context key, is multi-valued]
     */
    private const CONTEXT_SOURCES = [
        'group' => ['customer_group_ids', true],
        'country' => ['id_country', false],
        'currency' => ['id_currency', false],
        'category' => ['product_category_ids', true],
        'manufacturer' => ['product_manufacturer_ids', true],
    ];

    private $repository;

    /**
     * @param RuleRepository $repository
     */
    public function __construct(RuleRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Expected context keys:
     *  - id_shop (int)
     *  - id_order_state (int), order_is_paid (bool)
     *  - order_total_paid (float)
     *  - customer_valid_order_count (int)
     *  - customer_group_ids (int[]), id_country (int), id_currency (int)
     *  - product_category_ids (int[]), product_manufacturer_ids (int[])
     *
     * @param array $context
     *
     * @return array matching rule rows (each with a 'conditions' key), ordered
     */
    public function match(array $context)
    {
        $idShop = isset($context['id_shop']) ? (int) $context['id_shop'] : 0;
        $contextValues = $this->buildContextValues($context);
        $now = date('Y-m-d H:i:s');

        $matched = [];
        foreach ($this->repository->findAllByShop($idShop, true) as $rule) {
            if (!$this->ruleMatches($rule, $context, $contextValues, $now)) {
                continue;
            }

            $matched[] = $rule;
            if ((int) $rule['stop_further'] === 1) {
                break;
            }
        }

        return $matched;
    }

    /**
     * @param array  $rule
     * @param array  $context
     * @param array  $contextValues
     * @param string $now
     *
     * @return bool
     */
    private function ruleMatches(array $rule, array $context, array $contextValues, $now)
    {
        return $this->matchesStatus($rule, $context)
            && $this->matchesSourceTotal($rule, $context)
            && $this->matchesOrderCount($rule, $context)
            && $this->matchesDateWindow($rule, $now)
            && $this->matchesModeConditions($rule, $contextValues);
    }

    /**
     * The status condition behaves like every other rule condition: when the
     * rule lists trigger statuses the order state must be one of them; when the
     * list is empty the condition imposes no restriction (any status matches).
     * The decision is therefore driven entirely by the rule configuration, not
     * by whether the order happens to be paid.
     *
     * @param array $rule
     * @param array $context
     *
     * @return bool
     */
    private function matchesStatus(array $rule, array $context)
    {
        $statuses = $this->ruleConditionIds($rule, RuleConditionSchema::TYPE_STATUS);
        if (empty($statuses)) {
            return true;
        }

        $state = isset($context['id_order_state']) ? (int) $context['id_order_state'] : 0;

        return in_array($state, $statuses, true);
    }

    /**
     * @param array $rule
     * @param array $context
     *
     * @return bool
     */
    private function matchesSourceTotal(array $rule, array $context)
    {
        $total = isset($context['order_total_paid']) ? (float) $context['order_total_paid'] : 0.0;

        return $this->withinRange($total, $rule['source_total_min'], $rule['source_total_max']);
    }

    /**
     * @param array $rule
     * @param array $context
     *
     * @return bool
     */
    private function matchesOrderCount(array $rule, array $context)
    {
        $count = isset($context['customer_valid_order_count']) ? (int) $context['customer_valid_order_count'] : 0;

        return $this->withinRange($count, $rule['customer_order_count_min'], $rule['customer_order_count_max']);
    }

    /**
     * Inclusive range check where a bound of 0 means "no limit".
     *
     * @param float|int $value
     * @param float|int $min
     * @param float|int $max
     *
     * @return bool
     */
    private function withinRange($value, $min, $max)
    {
        $value = (float) $value;
        $min = (float) $min;
        $max = (float) $max;

        if ($min > 0 && $value < $min) {
            return false;
        }
        if ($max > 0 && $value > $max) {
            return false;
        }

        return true;
    }

    /**
     * @param array  $rule
     * @param string $now 'Y-m-d H:i:s'
     *
     * @return bool
     */
    private function matchesDateWindow(array $rule, $now)
    {
        $from = $this->normalizeDate($rule['date_from']);
        $to = $this->normalizeDate($rule['date_to']);

        if ($from !== '' && $now < $from) {
            return false;
        }
        if ($to !== '' && $now > $to) {
            return false;
        }

        return true;
    }

    /**
     * Evaluates the all/include/exclude list conditions uniformly against the
     * order's context values.
     *
     * @param array $rule
     * @param array $contextValues type => int[]
     *
     * @return bool
     */
    private function matchesModeConditions(array $rule, array $contextValues)
    {
        foreach (RuleConditionSchema::modeTypes() as $type) {
            $mode = RuleConditionSchema::normalizeMode($rule[RuleConditionSchema::modeColumn($type)]);
            if ($mode === RuleConditionSchema::MODE_ALL) {
                continue;
            }

            $ruleIds = $this->ruleConditionIds($rule, $type);
            if (empty($ruleIds)) {
                // Mode set but no items selected: treat as no restriction.
                continue;
            }

            $ctxValues = isset($contextValues[$type]) ? $contextValues[$type] : [];
            $intersects = count(array_intersect($ruleIds, $ctxValues)) > 0;

            if ($mode === RuleConditionSchema::MODE_INCLUDE && !$intersects) {
                return false;
            }
            if ($mode === RuleConditionSchema::MODE_EXCLUDE && $intersects) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $context
     *
     * @return array type => int[] context values for the mode conditions
     */
    private function buildContextValues(array $context)
    {
        $values = [];
        foreach (self::CONTEXT_SOURCES as $type => $source) {
            list($key, $isMulti) = $source;
            $raw = isset($context[$key]) ? $context[$key] : ($isMulti ? [] : 0);
            $values[$type] = $isMulti ? $this->intArray($raw) : $this->singleIntArray($raw);
        }

        return $values;
    }

    /**
     * @param array  $rule
     * @param string $type
     *
     * @return array integer condition ids
     */
    private function ruleConditionIds(array $rule, $type)
    {
        if (!isset($rule['conditions'][$type]) || !is_array($rule['conditions'][$type])) {
            return [];
        }

        return array_map('intval', $rule['conditions'][$type]);
    }

    /**
     * @param string|null $value
     *
     * @return string '' for empty/zero dates
     */
    private function normalizeDate($value)
    {
        $value = (string) $value;
        if ($value === '' || strpos($value, '0000-00-00') === 0) {
            return '';
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return array positive integers
     */
    private function intArray($value)
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            $item = (int) $item;
            if ($item > 0) {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param mixed $value
     *
     * @return array a single-element array, or empty when not positive
     */
    private function singleIntArray($value)
    {
        $value = (int) $value;

        return $value > 0 ? [$value] : [];
    }
}
