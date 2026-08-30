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

<div class="panel page-content">
    <div class="panel-heading">
        <i class="icon-sitemap"></i>
        {l s='Discount rules' d='Modules.Setnextorderdiscount.Admin'}
        <span class="badge">{$snod_rules_count|intval}</span>
    </div>

    {if $snod_rules_count > 0}
        <div class="table-responsive">
            <table class="table" id="snod-rules-table">
                <thead>
                <tr>
                    <th style="width:90px;">{l s='Priority' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Name' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Discount' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Validity' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Trigger statuses' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Conditions' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Active' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Actions' d='Modules.Setnextorderdiscount.Admin'}</th>
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
                                <span class="snod-badge snod-badge-exclude" title="{l s='Stop evaluating further rules after this one' d='Modules.Setnextorderdiscount.Admin'}"><i class="material-icons">block</i>{l s='Stop' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                            {if $rule.reminder_enabled && ($rule.reminder1_days > 0 || $rule.reminder2_days > 0)}
                                <span class="snod-badge snod-badge-date" title="{if $rule.reminder_basis == 'before_expiry'}{l s='Reminders: days before the coupon expires' d='Modules.Setnextorderdiscount.Admin'}{else}{l s='Reminders: days after the coupon email' d='Modules.Setnextorderdiscount.Admin'}{/if}">
                                    <i class="material-icons">notifications</i>{if $rule.reminder1_days > 0}{$rule.reminder1_days|intval}{l s='d' d='Modules.Setnextorderdiscount.Admin'}{/if}{if $rule.reminder1_days > 0 && $rule.reminder2_days > 0} · {/if}{if $rule.reminder2_days > 0}{$rule.reminder2_days|intval}{l s='d' d='Modules.Setnextorderdiscount.Admin'}{/if}
                                </span>
                            {/if}
                        </td>
                        <td>{$rule.discount_label|escape:'html':'UTF-8'}</td>
                        <td>{$rule.validity_days|intval} {l s='days' d='Modules.Setnextorderdiscount.Admin'}</td>
                        <td>
                            {if $rule.status_names|@count > 0}
                                <div class="snod-targeting-badges">
                                    {foreach from=$rule.status_names item=statusName}
                                        <span class="snod-badge snod-badge-all"><i class="material-icons">flag</i>{$statusName|escape:'html':'UTF-8'}</span>
                                    {/foreach}
                                </div>
                            {else}
                                <span class="snod-badge snod-badge-include"><i class="material-icons">done_all</i>{l s='Any status' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                        </td>
                        <td>
                            {if $rule.condition_badges|@count > 0}
                                <div class="snod-targeting-badges">
                                    {foreach from=$rule.condition_badges item=b}
                                        <span class="snod-badge snod-badge-{$b.variant|escape:'html':'UTF-8'}" title="{$b.text|escape:'html':'UTF-8'}">
                                            <i class="material-icons">{$b.icon|escape:'html':'UTF-8'}</i>{$b.text|escape:'html':'UTF-8'}
                                        </span>
                                    {/foreach}
                                </div>
                            {else}
                                <span class="text-muted">&mdash;</span>
                            {/if}
                        </td>
                        <td>
                            {assign var=toggleUrl value="`$AdminLink`&tab=rules&ruleAction=toggle&id_rule=`$rule.id_snod_rule`"}
                            <span class="switch prestashop-switch fixed-width-sm">
                                <input type="radio" id="snod_on_{$rule.id_snod_rule|intval}" name="snod_active_{$rule.id_snod_rule|intval}" value="1" {if $rule.active}checked{/if} onchange="window.location='{$toggleUrl|escape:'html':'UTF-8'}';">
                                <label class="radioCheck" for="snod_on_{$rule.id_snod_rule|intval}"><i class="color_success"></i></label>
                                <input type="radio" id="snod_off_{$rule.id_snod_rule|intval}" name="snod_active_{$rule.id_snod_rule|intval}" value="0" {if !$rule.active}checked{/if} onchange="window.location='{$toggleUrl|escape:'html':'UTF-8'}';">
                                <label class="radioCheck" for="snod_off_{$rule.id_snod_rule|intval}"><i class="color_danger"></i></label>
                                <a class="slide-button btn"></a>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rule_edit&id_rule={$rule.id_snod_rule|intval}" class="btn btn-default btn-sm" title="{l s='Edit' d='Modules.Setnextorderdiscount.Admin'}">
                                    <i class="material-icons">edit</i>
                                </a>
                                <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules&ruleAction=delete&id_rule={$rule.id_snod_rule|intval}" class="btn btn-default btn-sm" onclick="return confirm('{l s='Delete this rule?' d='Modules.Setnextorderdiscount.Admin' js=1}');" title="{l s='Delete' d='Modules.Setnextorderdiscount.Admin'}">
                                    <i class="material-icons">delete</i>
                                </a>
                            </div>
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
