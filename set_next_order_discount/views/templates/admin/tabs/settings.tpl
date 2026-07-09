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
    {if isset($updatedMessage)}
        <div class="alert alert-success" role="alert" style="margin-top: 15px;">
            {l s='Updated' d='Modules.Setnextorderdiscount.Admin'}
        </div>
    {/if}

    {if isset($snodErrors) && $snodErrors|@count > 0}
        <div class="alert alert-danger" role="alert" style="margin-top: 15px;">
            <ul style="margin: 0; padding-left: 18px;">
                {foreach from=$snodErrors item=snodError}
                    <li>{$snodError|escape:'html':'UTF-8'}</li>
                {/foreach}
            </ul>
        </div>
    {/if}

    <div class="panel page-content">
        <div class="panel-heading">
            <i class="material-icons">settings</i>
            {l s='Settings' d='Modules.Setnextorderdiscount.Admin'}
        </div>
        <div class="form-wrapper">

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Module active' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-5">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" id="snod_enabled_on" name="snod_enabled" value="1" {if $snod_enabled}checked{/if}>
                        <label class="radioCheck" for="snod_enabled_on"><i class="color_success"></i>{l s='Yes' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <input type="radio" id="snod_enabled_off" name="snod_enabled" value="0" {if !$snod_enabled}checked{/if}>
                        <label class="radioCheck" for="snod_enabled_off"><i class="color_danger"></i>{l s='No' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='If disabled, no coupon is generated when an order reaches a target status.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Discount type' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-3">
                    <select name="snod_discount_type" class="form-control">
                        <option value="percent" {if $snod_discount_type == 'percent'}selected{/if}>{l s='Percentage (%)' d='Modules.Setnextorderdiscount.Admin'}</option>
                        <option value="amount" {if $snod_discount_type == 'amount'}selected{/if}>{l s='Fixed amount' d='Modules.Setnextorderdiscount.Admin'}</option>
                    </select>
                    <p class="help-block">{l s='How the coupon reduces the next order total.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Discount value' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-3">
                    <div class="input-group">
                        <input type="text" name="snod_discount_value" class="form-control" value="{$snod_discount_value|escape:'html':'UTF-8'}">
                        <span class="input-group-addon">
                            {if $snod_discount_type == 'amount' && $snod_currency_sign != ''}{$snod_currency_sign|escape:'html':'UTF-8'}{else}%{/if}
                        </span>
                    </div>
                    <p class="help-block">{l s='Must be greater than zero. For a percentage discount, the maximum is 100.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Validity period (days)' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-3">
                    <input type="text" name="snod_validity_days" class="form-control" value="{$snod_validity_days|escape:'html':'UTF-8'}">
                    <p class="help-block">{l s='Number of days the generated coupon stays valid (at least 1).' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Minimum order amount' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-3">
                    <div class="input-group">
                        <input type="text" name="snod_min_order_amount" class="form-control" value="{$snod_min_order_amount|escape:'html':'UTF-8'}">
                        {if $snod_currency_sign != ''}
                            <span class="input-group-addon">{$snod_currency_sign|escape:'html':'UTF-8'}</span>
                        {/if}
                    </div>
                    <p class="help-block">{l s='Minimum source order total required to issue a coupon. Use 0 to disable this condition.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Trigger on order statuses' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-5">
                    <select name="snod_target_statuses[]" class="form-control" multiple size="8">
                        {foreach from=$snod_order_states item=orderState}
                            <option value="{$orderState.id_order_state|intval}" {if in_array($orderState.id_order_state, $snod_target_statuses)}selected{/if}>
                                {$orderState.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                    <p class="help-block">
                        {l s='A coupon is generated when an order reaches one of the selected statuses.' d='Modules.Setnextorderdiscount.Admin'}
                        <br>{l s='Leave empty to trigger on any paid status. Hold Ctrl/Cmd to select several.' d='Modules.Setnextorderdiscount.Admin'}
                    </p>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Debug mode' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-5">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <input type="radio" id="snod_debug_mode_on" name="snod_debug_mode" value="1" {if $snod_debug_mode}checked{/if}>
                        <label class="radioCheck" for="snod_debug_mode_on"><i class="color_success"></i>{l s='Yes' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <input type="radio" id="snod_debug_mode_off" name="snod_debug_mode" value="0" {if !$snod_debug_mode}checked{/if}>
                        <label class="radioCheck" for="snod_debug_mode_off"><i class="color_danger"></i>{l s='No' d='Modules.Setnextorderdiscount.Admin'}</label>
                        <a class="slide-button btn"></a>
                    </span>
                    <p class="help-block">{l s='Enables verbose logging for troubleshooting. Keep disabled in production.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

        </div>
        <div class="panel-footer">
            <button class="btn btn-default pull-right" type="submit" name="saveSettings" value="1">
                <i class="process-icon-save"></i>
                {l s='Save' d='Modules.Setnextorderdiscount.Admin'}
            </button>
        </div>
    </div>
</form>
