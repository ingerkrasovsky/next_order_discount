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
    <h3><i class="icon icon-dashboard"></i> {l s='Coupon funnel' d='Modules.Setnextorderdiscount.Admin'}</h3>
    <div class="row">
        {foreach from=$snod_funnel item=step}
            <div class="col-lg-2 col-md-4 col-xs-6" style="margin-bottom:15px;">
                <div style="border:1px solid #e0e6ed; border-radius:6px; padding:14px; text-align:center;">
                    <div style="font-size:26px; font-weight:bold; color:#2b3a67;">{$step.value|intval}</div>
                    <div class="text-muted" style="text-transform:uppercase; font-size:11px; letter-spacing:1px;">{$step.label|escape:'html':'UTF-8'}</div>
                    <div class="progress" style="margin:8px 0 2px; height:6px;">
                        <div class="progress-bar progress-bar-info" role="progressbar" style="width:{$step.percent|floatval}%;"></div>
                    </div>
                    <small class="text-muted">{$step.percent|floatval}%</small>
                </div>
            </div>
        {/foreach}
    </div>
    <p class="text-muted" style="margin-top:10px;">
        {l s='Conversion (used vs generated):' d='Modules.Setnextorderdiscount.Admin'}
        <strong>{$snod_conversion_rate|floatval}%</strong>
        &mdash; {l s='based on' d='Modules.Setnextorderdiscount.Admin'} {$snod_funnel_generated|intval} {l s='generated coupons' d='Modules.Setnextorderdiscount.Admin'}.
    </p>
</div>

<div class="panel">
    <h3><i class="icon icon-tasks"></i> {l s='Dispatch queue' d='Modules.Setnextorderdiscount.Admin'}</h3>
    <ul class="list-inline">
        <li><span class="label label-default">{l s='Pending' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.pending|intval}</span></li>
        <li><span class="label label-info">{l s='Processing' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.processing|intval}</span></li>
        <li><span class="label label-success">{l s='Done' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.done|intval}</span></li>
        <li><span class="label label-danger">{l s='Failed' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.failed|intval}</span></li>
    </ul>
</div>
