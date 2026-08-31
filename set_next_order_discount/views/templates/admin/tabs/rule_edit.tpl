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

        <ul class="nav nav-tabs" role="tablist" style="margin-bottom:15px;">
            <li class="active"><a href="#snod-tab-general" data-toggle="tab">{l s='General' d='Modules.Setnextorderdiscount.Admin'}</a></li>
            <li><a href="#snod-tab-conditions" data-toggle="tab">{l s='Conditions' d='Modules.Setnextorderdiscount.Admin'}</a></li>
            <li><a href="#snod-tab-code" data-toggle="tab">{l s='Code' d='Modules.Setnextorderdiscount.Admin'}</a></li>
            <li><a href="#snod-tab-email" data-toggle="tab">{l s='Email' d='Modules.Setnextorderdiscount.Admin'}</a></li>
        </ul>

        <div class="tab-content">

            {* ===================== GENERAL ===================== *}
            <div class="tab-pane active" id="snod-tab-general">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Rule name' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-5">
                        <input type="text" name="snod_rule_name" class="form-control" value="{$snod_rule_form.name|escape:'html':'UTF-8'}" maxlength="128">
                        <p class="help-block">{l s='Internal label shown in the rules list.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Voucher name' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-5">
                        <input type="text" name="snod_rule_voucher_name" class="form-control" value="{$snod_rule_form.voucher_name|escape:'html':'UTF-8'}" maxlength="255" placeholder="{l s='Next Order Discount' d='Modules.Setnextorderdiscount.Admin'}">
                        <p class="help-block">{l s='Name shown to the customer on the voucher. Leave empty to use the default "Next Order Discount".' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Voucher description' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-5">
                        <textarea name="snod_rule_voucher_description" class="form-control" rows="2" maxlength="255">{$snod_rule_form.voucher_description|escape:'html':'UTF-8'}</textarea>
                        <p class="help-block">{l s='Optional description stored on the voucher (visible in the back office). Leave empty for none.' d='Modules.Setnextorderdiscount.Admin'}</p>
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

                <hr>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Send reminders' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-5">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" id="snod_rule_reminder_on" name="snod_rule_reminder_enabled" value="1" {if $snod_rule_form.reminder_enabled}checked{/if}>
                            <label class="radioCheck" for="snod_rule_reminder_on"><i class="color_success"></i>{l s='Yes' d='Modules.Setnextorderdiscount.Admin'}</label>
                            <input type="radio" id="snod_rule_reminder_off" name="snod_rule_reminder_enabled" value="0" {if !$snod_rule_form.reminder_enabled}checked{/if}>
                            <label class="radioCheck" for="snod_rule_reminder_off"><i class="color_danger"></i>{l s='No' d='Modules.Setnextorderdiscount.Admin'}</label>
                            <a class="slide-button btn"></a>
                        </span>
                        <p class="help-block">{l s='Remind the customer about their unused coupon by email. Reminders stop automatically once the coupon is used or expires.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Reminder timing' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-3">
                        <select name="snod_rule_reminder_basis" class="form-control">
                            {foreach from=$snod_reminder_bases item=basis}
                                <option value="{$basis.id|escape:'html':'UTF-8'}" {if $snod_rule_form.reminder_basis == $basis.id}selected{/if}>
                                    {$basis.name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Choose whether the day values below count from the coupon email, or backwards from the coupon expiry.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='First reminder (days)' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-3">
                        <input type="text" name="snod_rule_reminder1_days" class="form-control" value="{$snod_rule_form.reminder1_days|escape:'html':'UTF-8'}" placeholder="1">
                        <p class="help-block">{l s='Interpreted per the timing above. 0 or empty = this reminder is not sent.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Second reminder (days)' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-3">
                        <input type="text" name="snod_rule_reminder2_days" class="form-control" value="{$snod_rule_form.reminder2_days|escape:'html':'UTF-8'}" placeholder="3">
                        <p class="help-block">{l s='Interpreted per the timing above. 0 or empty = this reminder is not sent.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>
            </div>

            {* ===================== CONDITIONS ===================== *}
            <div class="tab-pane" id="snod-tab-conditions">
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
                        <p class="help-block">{l s='Leave empty to trigger on any status (no restriction). Hold Ctrl/Cmd to select several.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                {foreach from=$snod_conditions item=cond}
                    <div class="form-group">
                        <label class="control-label col-lg-3">{$cond.label|escape:'html':'UTF-8'}</label>
                        <div class="col-lg-5">
                            <select name="snod_rule_{$cond.type|escape:'html':'UTF-8'}_mode" class="form-control" style="margin-bottom:6px;">
                                <option value="all" {if $cond.mode == 'all'}selected{/if}>{l s='All (no restriction)' d='Modules.Setnextorderdiscount.Admin'}</option>
                                <option value="include" {if $cond.mode == 'include'}selected{/if}>{l s='Only the selected' d='Modules.Setnextorderdiscount.Admin'}</option>
                                <option value="exclude" {if $cond.mode == 'exclude'}selected{/if}>{l s='All except the selected' d='Modules.Setnextorderdiscount.Admin'}</option>
                            </select>
                            <select name="snod_rule_{$cond.type|escape:'html':'UTF-8'}_ids[]" class="form-control" multiple size="6">
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
            </div>

            {* ===================== CODE ===================== *}
            <div class="tab-pane" id="snod-tab-code">
                <p class="text-muted" style="margin-bottom:15px;">
                    {l s='Set the coupon code format for this rule. Leave a field empty to use the built-in default.' d='Modules.Setnextorderdiscount.Admin'}
                </p>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Key length' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-3">
                        <input type="text" name="snod_rule_code_length" class="form-control" value="{$snod_rule_form.code_length|escape:'html':'UTF-8'}">
                        <p class="help-block">{l s='Number of random characters in %key% (clamped between 4 and 32).' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Key type' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-3">
                        <select name="snod_rule_code_type" class="form-control">
                            {foreach from=$snod_key_types item=keyType}
                                <option value="{$keyType.id|intval}" {if (int)$snod_rule_form.code_type == $keyType.id}selected{/if}>
                                    {$keyType.name|escape:'html':'UTF-8'}
                                </option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Character set used for the generated %key%.' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Key template' d='Modules.Setnextorderdiscount.Admin'}</label>
                    <div class="col-lg-3">
                        <input type="text" name="snod_rule_code_template" class="form-control" value="{$snod_rule_form.code_template|escape:'html':'UTF-8'}" maxlength="64">
                        <p class="help-block">{l s='Use %key% as a placeholder for the generated key. Example: NOD-%key% → NOD-AB12CD8X' d='Modules.Setnextorderdiscount.Admin'}</p>
                    </div>
                </div>
            </div>

            {* ===================== EMAIL ===================== *}
            <div class="tab-pane" id="snod-tab-email" data-ajax-url="{$AdminLink|escape:'html':'UTF-8'}">
                <p class="text-muted" style="margin-bottom:15px;">
                    {l s='Each rule has its own email content, pre-filled with the default template. Placeholders (coupon_code, coupon_value, valid_to, minimum_amount, customer_firstname, shop_name) are replaced when the email is sent.' d='Modules.Setnextorderdiscount.Admin'}
                </p>

                {foreach from=$snod_email_types item=etype}
                    <div class="panel" style="border:1px solid #e0e6ed; padding:15px; margin-bottom:15px;">
                        <h4 style="margin-top:0;">{$etype.label|escape:'html':'UTF-8'}</h4>
                        <div class="snod-email-block" data-type="{$etype.type|escape:'html':'UTF-8'}">
                            <ul class="nav nav-pills" style="margin-bottom:12px;">
                                {foreach from=$snod_languages item=lang name=langloop}
                                    <li class="{if $smarty.foreach.langloop.first}active{/if}">
                                        <a href="#" class="snod-lang-btn" data-type="{$etype.type|escape:'html':'UTF-8'}" data-lang="{$lang.id_lang|intval}">{$lang.iso_code|escape:'html':'UTF-8'|upper}</a>
                                    </li>
                                {/foreach}
                            </ul>

                            {foreach from=$snod_languages item=lang name=langpanes}
                                <div class="snod-email-lang" data-type="{$etype.type|escape:'html':'UTF-8'}" data-lang="{$lang.id_lang|intval}"{if !$smarty.foreach.langpanes.first} style="display:none;"{/if}>
                                    <div class="form-group">
                                        <label>{l s='Subject' d='Modules.Setnextorderdiscount.Admin'}</label>
                                        <input type="text" name="snod_email[{$etype.type|escape:'html':'UTF-8'}][subject][{$lang.id_lang|intval}]" class="form-control" maxlength="255" value="{$snod_email_content[$etype.type][$lang.id_lang].subject|escape:'html':'UTF-8'}">
                                    </div>
                                    <div class="form-group">
                                        <label>{l s='HTML content' d='Modules.Setnextorderdiscount.Admin'}</label>
                                        <textarea name="snod_email[{$etype.type|escape:'html':'UTF-8'}][html][{$lang.id_lang|intval}]" class="form-control snod-email-html" rows="14" style="font-family:monospace;">{$snod_email_content[$etype.type][$lang.id_lang].html|escape:'html':'UTF-8'}</textarea>
                                    </div>
                                </div>
                            {/foreach}

                            <div class="snod-email-actions" style="margin-top:10px; padding-top:10px; border-top:1px solid #eee;">
                                <button type="button" class="btn btn-default btn-sm snod-preview-btn">
                                    <i class="icon icon-eye"></i> {l s='Preview' d='Modules.Setnextorderdiscount.Admin'}
                                </button>
                                <div class="input-group" style="display:inline-table; width:320px; vertical-align:middle; margin-left:8px;">
                                    <input type="email" class="form-control input-sm snod-test-email" placeholder="test@example.com">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default btn-sm snod-sendtest-btn">{l s='Send test email' d='Modules.Setnextorderdiscount.Admin'}</button>
                                    </span>
                                </div>
                                <span class="snod-email-result" style="margin-left:10px;"></span>
                            </div>
                        </div>
                    </div>
                {/foreach}

                <div id="snod-preview-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.55); z-index:99999;">
                    <div style="position:absolute; top:3%; left:50%; transform:translateX(-50%); width:700px; max-width:95%; height:90%; background:#fff; border-radius:6px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 4px 24px rgba(0,0,0,0.3);">
                        <div style="padding:10px 14px; border-bottom:1px solid #e0e6ed; display:flex; justify-content:space-between; align-items:center;">
                            <strong>{l s='Preview' d='Modules.Setnextorderdiscount.Admin'}</strong>
                            <button type="button" class="btn btn-default btn-sm" id="snod-preview-close" aria-label="Close">&times;</button>
                        </div>
                        <iframe id="snod-preview-frame" title="Email preview" style="border:0; width:100%; flex:1 1 auto; background:#f4f5f7;"></iframe>
                    </div>
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
