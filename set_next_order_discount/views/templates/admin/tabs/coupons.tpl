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
        <i class="icon-ticket"></i>
        {l s='Coupons' d='Modules.Setnextorderdiscount.Admin'}
        <span class="badge">{$snod_total_coupons|intval}</span>
    </div>

    <form method="get" action="" class="form-inline" style="margin-bottom:15px;">
        <input type="hidden" name="controller" value="NextOrderDiscount">
        <input type="hidden" name="token" value="{$snod_admin_token|escape:'html':'UTF-8'}">
        <input type="hidden" name="tab" value="coupons">

        <div class="form-group" style="margin-right:8px;">
            <label class="control-label" style="margin-right:6px;">{l s='Status' d='Modules.Setnextorderdiscount.Admin'}</label>
            <select name="snod_filter_status" class="form-control">
                <option value="">{l s='All statuses' d='Modules.Setnextorderdiscount.Admin'}</option>
                {foreach from=$snod_coupon_statuses item=statusCode}
                    <option value="{$statusCode|escape:'html':'UTF-8'}" {if $snod_filter_status == $statusCode}selected{/if}>
                        {$snod_coupon_status_labels[$statusCode]|escape:'html':'UTF-8'}
                    </option>
                {/foreach}
            </select>
        </div>

        <div class="form-group" style="margin-right:8px;">
            <label class="control-label" style="margin-right:6px;">{l s='Code' d='Modules.Setnextorderdiscount.Admin'}</label>
            <input type="text" name="snod_filter_code" class="form-control" value="{$snod_filter_code|escape:'html':'UTF-8'}" placeholder="{l s='Search code' d='Modules.Setnextorderdiscount.Admin'}">
        </div>

        <button type="submit" class="btn btn-default">
            <i class="icon-search"></i> {l s='Filter' d='Modules.Setnextorderdiscount.Admin'}
        </button>
        <a href="{$AdminLink|escape:'html':'UTF-8'}&tab=coupons" class="btn btn-link">
            {l s='Reset' d='Modules.Setnextorderdiscount.Admin'}
        </a>
    </form>

    {if $snod_coupons|@count > 0}
        <div class="table-responsive">
            <table class="table">
                <thead>
                <tr>
                    <th>{l s='Code' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Customer' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Source order' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Status' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Valid until' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Generated' d='Modules.Setnextorderdiscount.Admin'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$snod_coupons item=coupon}
                    <tr>
                        <td><code>{$coupon.coupon_code|escape:'html':'UTF-8'}</code></td>
                        <td>
                            {if $coupon.customer_name && $coupon.customer_name|trim != ''}
                                {$coupon.customer_name|escape:'html':'UTF-8'}
                            {else}
                                <span class="text-muted">#{$coupon.id_customer|intval}</span>
                            {/if}
                            {if $coupon.customer_email}
                                <div class="text-muted" style="font-size:11px;">{$coupon.customer_email|escape:'html':'UTF-8'}</div>
                            {/if}
                        </td>
                        <td>#{$coupon.id_order_source|intval}</td>
                        <td>
                            {assign var=statusCode value=$coupon.status}
                            {assign var=statusLabel value=$snod_coupon_status_labels[$statusCode]|default:$statusCode}
                            {if $statusCode == 'used'}
                                <span class="label label-success">{$statusLabel|escape:'html':'UTF-8'}</span>
                            {elseif $statusCode == 'expired' || $statusCode == 'canceled'}
                                <span class="label label-danger">{$statusLabel|escape:'html':'UTF-8'}</span>
                            {elseif $statusCode == 'emailed' || $statusCode == 'reminded'}
                                <span class="label label-info">{$statusLabel|escape:'html':'UTF-8'}</span>
                            {else}
                                <span class="label label-default">{$statusLabel|escape:'html':'UTF-8'}</span>
                            {/if}
                        </td>
                        <td>{if $coupon.valid_to}{$coupon.valid_to|escape:'html':'UTF-8'}{else}<span class="text-muted">&mdash;</span>{/if}</td>
                        <td>{if $coupon.generated_at}{$coupon.generated_at|escape:'html':'UTF-8'}{else}<span class="text-muted">&mdash;</span>{/if}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

        {if $snod_total_pages > 1}
            <div class="text-center">
                <ul class="pagination">
                    {if $snod_page > 1}
                        <li><a href="{$snod_coupons_base_url|escape:'html':'UTF-8'}&snod_page={$snod_prev_page|intval}">&laquo; {l s='Previous' d='Modules.Setnextorderdiscount.Admin'}</a></li>
                    {else}
                        <li class="disabled"><span>&laquo; {l s='Previous' d='Modules.Setnextorderdiscount.Admin'}</span></li>
                    {/if}

                    <li class="disabled"><span>{l s='Page' d='Modules.Setnextorderdiscount.Admin'} {$snod_page|intval} / {$snod_total_pages|intval}</span></li>

                    {if $snod_page < $snod_total_pages}
                        <li><a href="{$snod_coupons_base_url|escape:'html':'UTF-8'}&snod_page={$snod_next_page|intval}">{l s='Next' d='Modules.Setnextorderdiscount.Admin'} &raquo;</a></li>
                    {else}
                        <li class="disabled"><span>{l s='Next' d='Modules.Setnextorderdiscount.Admin'} &raquo;</span></li>
                    {/if}
                </ul>
            </div>
        {/if}
    {else}
        <div class="alert alert-info" style="margin-bottom:0;">
            {l s='No coupons found yet. Coupons appear here once they are generated for qualifying orders.' d='Modules.Setnextorderdiscount.Admin'}
        </div>
    {/if}
</div>
