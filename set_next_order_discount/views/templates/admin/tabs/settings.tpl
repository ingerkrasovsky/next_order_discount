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
                    <p class="help-block">{l s='If disabled, no coupons are generated for new orders. Discounts themselves are configured in the Rules tab.' d='Modules.Setnextorderdiscount.Admin'}</p>
                </div>
            </div>

            <hr>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Cancel coupon on order statuses' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-5">
                    <select name="snod_cancel_statuses[]" class="form-control" multiple size="8">
                        {foreach from=$snod_order_states item=orderState}
                            <option value="{$orderState.id_order_state|intval}" {if in_array($orderState.id_order_state, $snod_cancel_statuses)}selected{/if}>
                                {$orderState.name|escape:'html':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                    <p class="help-block">{l s='When the order that issued a coupon moves to one of these statuses, that coupon is voided (deactivated and marked canceled) and no new coupon is issued for it. Defaults to Canceled and Refunded. Leave empty to never auto-cancel. Hold Ctrl/Cmd to select several.' d='Modules.Setnextorderdiscount.Admin'}</p>
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

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Keep logs for' d='Modules.Setnextorderdiscount.Admin'}
                </label>
                <div class="col-lg-2">
                    <div class="input-group">
                        <input type="number" name="snod_log_retention_days" class="form-control" min="0" max="3650" step="1" value="{$snod_log_retention_days|intval}">
                        <span class="input-group-addon">{l s='days' d='Modules.Setnextorderdiscount.Admin'}</span>
                    </div>
                    <p class="help-block">{l s='Older log entries are deleted automatically (during cron). Set 0 to keep logs forever.' d='Modules.Setnextorderdiscount.Admin'}</p>
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
