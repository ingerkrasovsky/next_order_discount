{*
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
 *}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-sitemap"></i>
        {l s='Discount rules' d='Modules.Setnextorderdiscount.Admin'}
        <span class="badge">{$snod_rules_count|intval}</span>
        <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rule_edit" class="btn btn-primary btn-xs pull-right">
            <i class="icon-plus"></i> {l s='Add a rule' d='Modules.Setnextorderdiscount.Admin'}
        </a>
    </div>

    {if $snod_rules_count > 0}
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th style="width:90px;">{l s='Priority' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Name' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Discount' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Validity' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Trigger statuses' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Conditions' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th class="text-center">{l s='Active' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th class="text-center" style="width:70px;">{l s='Actions' d='Modules.Setnextorderdiscount.Admin'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$snod_rules item=rule name=rulesLoop}
                    <tr>
                        <td>
                            <span class="badge">{$rule.priority|intval}</span>
                            {if !$smarty.foreach.rulesLoop.first}
                                <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules&ruleAction=up&id_rule={$rule.id_snod_rule|intval}" title="{l s='Move up' d='Modules.Setnextorderdiscount.Admin'}"><i class="icon-arrow-up"></i></a>
                            {/if}
                            {if !$smarty.foreach.rulesLoop.last}
                                <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules&ruleAction=down&id_rule={$rule.id_snod_rule|intval}" title="{l s='Move down' d='Modules.Setnextorderdiscount.Admin'}"><i class="icon-arrow-down"></i></a>
                            {/if}
                        </td>
                        <td>
                            <strong>{$rule.name|escape:'html':'UTF-8'}</strong>
                            {if $rule.stop_further}
                                <span class="label label-warning" title="{l s='Stop evaluating further rules after this one' d='Modules.Setnextorderdiscount.Admin'}">{l s='Stop' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                        </td>
                        <td>{$rule.discount_label|escape:'html':'UTF-8'}</td>
                        <td>{$rule.validity_days|intval} {l s='days' d='Modules.Setnextorderdiscount.Admin'}</td>
                        <td>
                            {if $rule.status_names|@count > 0}
                                {foreach from=$rule.status_names item=statusName}
                                    <span class="label label-default">{$statusName|escape:'html':'UTF-8'}</span>
                                {/foreach}
                            {else}
                                <span class="label label-info">{l s='Any status' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                        </td>
                        <td>
                            {if $rule.summary|@count > 0}
                                {foreach from=$rule.summary item=part}
                                    <div class="text-muted" style="font-size:11px;">{$part|escape:'html':'UTF-8'}</div>
                                {/foreach}
                            {else}
                                <span class="text-muted">&mdash;</span>
                            {/if}
                        </td>
                        <td class="text-center">
                            {if $rule.active}
                                <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules&ruleAction=toggle&id_rule={$rule.id_snod_rule|intval}" title="{l s='Click to disable' d='Modules.Setnextorderdiscount.Admin'}">
                                    <i class="icon-check-circle" style="color:#00a651; font-size:18px;"></i>
                                </a>
                            {else}
                                <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules&ruleAction=toggle&id_rule={$rule.id_snod_rule|intval}" title="{l s='Click to enable' d='Modules.Setnextorderdiscount.Admin'}">
                                    <i class="icon-remove-circle" style="color:#cc0000; font-size:18px;"></i>
                                </a>
                            {/if}
                        </td>
                        <td class="text-center">
                            <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rule_edit&id_rule={$rule.id_snod_rule|intval}"
                               class="btn btn-default btn-xs"
                               title="{l s='Edit' d='Modules.Setnextorderdiscount.Admin'}">
                                <i class="icon-pencil"></i>
                            </a>
                            <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules&ruleAction=delete&id_rule={$rule.id_snod_rule|intval}"
                               class="btn btn-default btn-xs"
                               onclick="return confirm('{l s='Delete this rule?' d='Modules.Setnextorderdiscount.Admin' js=1}');"
                               title="{l s='Delete' d='Modules.Setnextorderdiscount.Admin'}">
                                <i class="icon-trash"></i>
                            </a>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="alert alert-info" style="margin-bottom:0;">
            {l s='No discount rules yet.' d='Modules.Setnextorderdiscount.Admin'}
        </div>
    {/if}
</div>
