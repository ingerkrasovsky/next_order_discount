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

<form class="defaultForm form-horizontal" method="post">
    <input type="hidden" name="id_rule" value="{$snod_rule_id|intval}">

    {if $snod_rule_errors|@count > 0}
        <div class="alert alert-danger" role="alert" style="margin-top: 15px;">
            <ul style="margin: 0; padding-left: 18px;">
                {foreach from=$snod_rule_errors item=ruleError}
                    <li>{$ruleError|escape:'html':'UTF-8'}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <div class="panel page-content">
        <div class="panel-heading">
            <i class="icon-sitemap"></i>
            {if $snod_is_edit}{l s='Edit rule' d='Modules.Setnextorderdiscount.Admin'}{else}{l s='New rule' d='Modules.Setnextorderdiscount.Admin'}{/if}
        </div>
        <div class="form-wrapper">

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Rule name' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <input type="text" name="snod_rule_name" class="form-control" value="{$snod_rule_form.name|escape:'html':'UTF-8'}" maxlength="128">
                    <p class="help-block">{l s='Internal label shown in the rules list.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Active' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" id="snod_rule_active_on" name="snod_rule_active" value="1" {if $snod_rule_form.active}checked{/if}>
                        <label class="radioCheck" for="snod_rule_active_on"><i class="color_success"></i>{l s='Yes' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <input type="radio" id="snod_rule_active_off" name="snod_rule_active" value="0" {if !$snod_rule_form.active}checked{/if}>
                        <label class="radioCheck" for="snod_rule_active_off"><i class="color_danger"></i>{l s='No' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Discount type' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-3">
                    <select name="snod_rule_discount_type" class="form-control">
                        <option value="percent" {if $snod_rule_form.discount_type == 'percent'}selected{/if}>{l s='Percentage (%)' d='Modules.Setnextorderdiscount.Admin'}</option>
                        <option value="amount" {if $snod_rule_form.discount_type == 'amount'}selected{/if}>{l s='Fixed amount' d='Modules.Setnextorderdiscount.Admin'}</option>
                        <option value="free_shipping" {if $snod_rule_form.discount_type == 'free_shipping'}selected{/if}>{l s='Free shipping' d='Modules.Setnextorderdiscount.Admin'}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Discount value' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-3">
                    <div class="input-group">
                        <input type="text" name="snod_rule_discount_value" class="form-control" value="{$snod_rule_form.discount_value|escape:'html':'UTF-8'}">
                        <span class="input-group-addon">{if $snod_rule_form.discount_type == 'amount' && $snod_currency_sign != ''}{$snod_currency_sign|escape:'html':'UTF-8'}{else}%{/if}</span>
                    </div>
                    <p class="help-block">{l s='Ignored for free shipping. Percentage is capped at 100.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Validity period (days)' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-3">
                    <input type="text" name="snod_rule_validity_days" class="form-control" value="{$snod_rule_form.validity_days|escape:'html':'UTF-8'}">
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Minimum next order amount' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-3">
                    <div class="input-group">
                        <input type="text" name="snod_rule_next_min" class="form-control" value="{$snod_rule_form.next_order_min_amount|escape:'html':'UTF-8'}">
                        {if $snod_currency_sign != ''}<span class="input-group-addon">{$snod_currency_sign|escape:'html':'UTF-8'}</span>{/if}
                    </div>
                    <p class="help-block">{l s='Minimum total the coupon requires on the next order. 0 to disable.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Trigger on order statuses' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <select name="snod_rule_statuses[]" class="form-control" multiple size="8">
                        {foreach from=$snod_order_states item=orderState}
                            <option value="{$orderState.id_order_state|intval}" {if in_array($orderState.id_order_state, $snod_rule_form.status_ids)}selected{/if}>
                                {$orderState.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                    <p class="help-block">{l s='Leave empty to trigger on any paid status. Hold Ctrl/Cmd to select several.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            {foreach from=$snod_conditions item=cond}
                <div class="form-group">
                    <label class="control-label col-lg-3">{$cond.label|escape:'html':'UTF-8'}</label>
                    <div class="col-lg-5">
                        <select name="snod_rule_{$cond.type}_mode" class="form-control" style="margin-bottom:6px;">
                            <option value="all" {if $cond.mode == 'all'}selected{/if}>{l s='All (no restriction)' d='Modules.Setnextorderdiscount.Admin'}</option>
                            <option value="include" {if $cond.mode == 'include'}selected{/if}>{l s='Only the selected' d='Modules.Setnextorderdiscount.Admin'}</option>
                            <option value="exclude" {if $cond.mode == 'exclude'}selected{/if}>{l s='All except the selected' d='Modules.Setnextorderdiscount.Admin'}</option>
                        </select>
                        <select name="snod_rule_{$cond.type}_ids[]" class="form-control" multiple size="6">
                            {foreach from=$cond.list item=entity}
                                <option value="{$entity.id|intval}" {if in_array($entity.id, $cond.ids)}selected{/if}>{$entity.name|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Restrict this rule by the selected items. "All" ignores the list.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>
            {/foreach}

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Source order total' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='Min' d='Modules.Setnextorderdiscount.Admin'}</span>
                                <input type="text" name="snod_rule_source_min" class="form-control" value="{$snod_rule_form.source_total_min|escape:'html':'UTF-8'}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='Max' d='Modules.Setnextorderdiscount.Admin'}</span>
                                <input type="text" name="snod_rule_source_max" class="form-control" value="{$snod_rule_form.source_total_max|escape:'html':'UTF-8'}">
                            </div>
                        </div>
                    </div>
                    <p class="help-block">{l s='Order total range that triggers this rule. Use 0 for no limit.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Active date window' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='From' d='Modules.Setnextorderdiscount.Admin'}</span>
                                <input type="date" name="snod_rule_date_from" class="form-control" value="{$snod_rule_form.date_from|escape:'html':'UTF-8'}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='To' d='Modules.Setnextorderdiscount.Admin'}</span>
                                <input type="date" name="snod_rule_date_to" class="form-control" value="{$snod_rule_form.date_to|escape:'html':'UTF-8'}">
                            </div>
                        </div>
                    </div>
                    <p class="help-block">{l s='Optional. Leave both empty for an always-active rule.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Customer order number' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='Min' d='Modules.Setnextorderdiscount.Admin'}</span>
                                <input type="text" name="snod_rule_order_count_min" class="form-control" value="{$snod_rule_form.customer_order_count_min|escape:'html':'UTF-8'}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='Max' d='Modules.Setnextorderdiscount.Admin'}</span>
                                <input type="text" name="snod_rule_order_count_max" class="form-control" value="{$snod_rule_form.customer_order_count_max|escape:'html':'UTF-8'}">
                            </div>
                        </div>
                    </div>
                    <p class="help-block">{l s='How many valid orders the customer must have. Set both to 1 for first-order-only. Use 0 for no limit.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="control-label col-lg-3">{l s='Stop after this rule' d='Modules.Setnextorderdiscount.Admin'}</label>
                <div class="col-lg-5">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" id="snod_rule_stop_on" name="snod_rule_stop" value="1" {if $snod_rule_form.stop_further}checked{/if}>
                        <label class="radioCheck" for="snod_rule_stop_on"><i class="color_success"></i>{l s='Yes' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <input type="radio" id="snod_rule_stop_off" name="snod_rule_stop" value="0" {if !$snod_rule_form.stop_further}checked{/if}>
                        <label class="radioCheck" for="snod_rule_stop_off"><i class="color_danger"></i>{l s='No' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='When enabled, no further rules are evaluated after this one matches (single coupon). Disable to allow additional matching rules to issue their own coupons.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

        </div>
        <div class="panel-footer">
            <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=rules" class="btn btn-default">
                <i class="process-icon-cancel"></i> {l s='Cancel' d='Modules.Setnextorderdiscount.Admin'}
            </a>
            <button class="btn btn-default pull-right" type="submit" name="saveRule" value="1">
                <i class="process-icon-save"></i> {l s='Save' d='Modules.Setnextorderdiscount.Admin'}
            </button>
        </div>
    </div>
</form>
