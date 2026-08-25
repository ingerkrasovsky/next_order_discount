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
 * Formats a rule row for the admin list (discount label, trigger status names,
 * a compact condition summary). Translation-agnostic: it receives already
 * translated labels, so it stays free of the PrestaShop translation layer and
 * iterates {@see RuleConditionSchema} rather than hard-coding condition
 * types.
 */
class RulePresenter
{
    /**
     * @param array  $rule         a rule row with 'conditions'
     * @param string $currencySign
     * @param array  $statusNames  id_order_state => localized name
     * @param array  $labels       translated labels: free_shipping, order_total,
     *                             order_no, date_window, conditions[type]
     *
     * @return array
     */
    public function present(array $rule, $currencySign, array $statusNames, array $labels)
    {
        $conditions = isset($rule['conditions']) ? $rule['conditions'] : [];

        return [
            'id_snod_rule' => (int) $rule['id_snod_rule'],
            'name' => (string) $rule['name'],
            'active' => (int) $rule['active'],
            'priority' => (int) $rule['priority'],
            'stop_further' => (int) $rule['stop_further'],
            'validity_days' => (int) $rule['validity_days'],
            'discount_label' => $this->discountLabel($rule, $currencySign, $labels),
            'status_names' => $this->statusNames($conditions, $statusNames),
            'summary' => $this->summary($rule, $conditions, $currencySign, $labels),
        ];
    }

    /**
     * @param array  $rule
     * @param string $currencySign
     * @param array  $labels
     *
     * @return string
     */
    private function discountLabel(array $rule, $currencySign, array $labels)
    {
        $type = (string) $rule['discount_type'];
        $value = RuleValueFormatter::decimal($rule['discount_value']);

        if ($type === RuleRepository::DISCOUNT_AMOUNT) {
            return $value . ' ' . $currencySign;
        }
        if ($type === RuleRepository::DISCOUNT_FREE_SHIPPING) {
            return isset($labels['free_shipping']) ? $labels['free_shipping'] : 'Free shipping';
        }

        return $value . ' %';
    }

    /**
     * @param array $conditions
     * @param array $statusNames
     *
     * @return array
     */
    private function statusNames(array $conditions, array $statusNames)
    {
        $list = [];
        $statusIds = isset($conditions[RuleConditionSchema::TYPE_STATUS])
            ? $conditions[RuleConditionSchema::TYPE_STATUS]
            : [];

        foreach ((array) $statusIds as $id) {
            $id = (int) $id;
            $list[] = isset($statusNames[$id]) ? $statusNames[$id] : ('#' . $id);
        }

        return $list;
    }

    /**
     * @param array  $rule
     * @param array  $conditions
     * @param string $currencySign
     * @param array  $labels
     *
     * @return array short human-readable condition strings
     */
    private function summary(array $rule, array $conditions, $currencySign, array $labels)
    {
        $parts = [];

        $min = (float) $rule['source_total_min'];
        $max = (float) $rule['source_total_max'];
        $totalLabel = isset($labels['order_total']) ? $labels['order_total'] : 'Order total';
        if ($min > 0 && $max > 0) {
            $parts[] = $totalLabel . ' ' . RuleValueFormatter::decimal($min) . '–' . RuleValueFormatter::decimal($max) . ' ' . $currencySign;
        } elseif ($min > 0) {
            $parts[] = $totalLabel . ' ≥ ' . RuleValueFormatter::decimal($min) . ' ' . $currencySign;
        } elseif ($max > 0) {
            $parts[] = $totalLabel . ' ≤ ' . RuleValueFormatter::decimal($max) . ' ' . $currencySign;
        }

        $conditionLabels = isset($labels['conditions']) ? $labels['conditions'] : [];
        foreach (RuleConditionSchema::modeTypes() as $type) {
            $mode = (string) $rule[RuleConditionSchema::modeColumn($type)];
            $count = count((array) (isset($conditions[$type]) ? $conditions[$type] : []));
            if ($mode !== RuleConditionSchema::MODE_ALL && $count > 0) {
                $label = isset($conditionLabels[$type]) ? $conditionLabels[$type] : RuleConditionSchema::label($type);
                $parts[] = $label . ': ' . $mode . ' (' . $count . ')';
            }
        }

        $cmin = (int) $rule['customer_order_count_min'];
        $cmax = (int) $rule['customer_order_count_max'];
        if ($cmin > 0 || $cmax > 0) {
            $orderNo = isset($labels['order_no']) ? $labels['order_no'] : 'Order no.';
            $parts[] = $orderNo . ' ' . ($cmin > 0 ? $cmin : '…') . '–' . ($cmax > 0 ? $cmax : '…');
        }

        if (!empty($rule['date_from']) || !empty($rule['date_to'])) {
            $parts[] = isset($labels['date_window']) ? $labels['date_window'] : 'Date window';
        }

        return $parts;
    }
}
