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
        <i class="material-icons">insights</i>
        {l s='Dashboard' d='Modules.Setnextorderdiscount.Admin'}
    </div>

    {function name=snod_dash_help}
        <span class="snod-dash-help" tabindex="0" role="img" aria-label="{$tip|escape:'html':'UTF-8'}" data-snod-tip="{$tip|escape:'html':'UTF-8'}">
            <i class="material-icons" aria-hidden="true">help_outline</i>
        </span>
    {/function}

    <p class="help-block snod-dash-intro">
        {l s='Coupon lifecycle totals for the current shop context.' d='Modules.Setnextorderdiscount.Admin'}
    </p>

    <div class="snod-dash-section">
        <h4 class="snod-dash-section-title">
            {l s='Coupon funnel' d='Modules.Setnextorderdiscount.Admin'}
            {call name=snod_dash_help tip="{l s='Each card shows a coupon lifecycle total. Percentages use generated coupons as the baseline; lifecycle outcomes can overlap, so they do not need to add up to 100%.' d='Modules.Setnextorderdiscount.Admin'}"}
        </h4>
    </div>

    <div class="snod-dash-cards">
        {foreach from=$snod_funnel item=step}
            <div class="snod-dash-card">
                <div class="snod-dash-card-value">
                    {$step.value|intval}
                    {if $step.key != 'generated'}<span class="snod-dash-card-percent">{$step.percent|floatval}%</span>{/if}
                </div>
                <div class="snod-dash-card-label">
                    {$step.label|escape:'html':'UTF-8'}
                    {if $step.key == 'generated'}
                        {call name=snod_dash_help tip="{l s='Coupons created after an order matched an active rule. This is the baseline used for all funnel percentages.' d='Modules.Setnextorderdiscount.Admin'}"}
                    {elseif $step.key == 'emailed'}
                        {call name=snod_dash_help tip="{l s='Generated coupons whose initial customer email was sent successfully.' d='Modules.Setnextorderdiscount.Admin'}"}
                    {elseif $step.key == 'reminded'}
                        {call name=snod_dash_help tip="{l s='Coupons for which at least one reminder email was sent.' d='Modules.Setnextorderdiscount.Admin'}"}
                    {elseif $step.key == 'used'}
                        {call name=snod_dash_help tip="{l s='Coupons redeemed by customers on a later order.' d='Modules.Setnextorderdiscount.Admin'}"}
                    {elseif $step.key == 'expired'}
                        {call name=snod_dash_help tip="{l s='Unused coupons whose validity period has ended.' d='Modules.Setnextorderdiscount.Admin'}"}
                    {elseif $step.key == 'canceled'}
                        {call name=snod_dash_help tip="{l s='Coupons voided after the originating order moved to a configured cancellation status.' d='Modules.Setnextorderdiscount.Admin'}"}
                    {/if}
                </div>
                {if $step.key != 'generated'}
                    <div class="snod-dash-card-bar {if $step.key == 'used'}snod-dash-card-bar-success{elseif $step.key == 'expired' || $step.key == 'canceled'}snod-dash-card-bar-danger{elseif $step.key == 'emailed' || $step.key == 'reminded'}snod-dash-card-bar-info{/if}"
                         role="progressbar" aria-valuenow="{$step.percent|floatval}" aria-valuemin="0" aria-valuemax="100">
                        <span style="width:{$step.percent|floatval}%;"></span>
                    </div>
                {/if}
            </div>
        {/foreach}
    </div>

    <p class="help-block snod-dash-conversion">
        {l s='Conversion (used vs generated):' d='Modules.Setnextorderdiscount.Admin'}
        <strong>{$snod_conversion_rate|floatval}%</strong>
        &mdash; {l s='based on' d='Modules.Setnextorderdiscount.Admin'} {$snod_funnel_generated|intval} {l s='generated coupons' d='Modules.Setnextorderdiscount.Admin'}.
        {call name=snod_dash_help tip="{l s='Conversion rate equals used coupons divided by generated coupons for the current shop context.' d='Modules.Setnextorderdiscount.Admin'}"}
    </p>

    {if $snod_dash_has_chart}
        <div class="snod-dash-section">
            <h4 class="snod-dash-section-title">
                {l s='Daily dynamics' d='Modules.Setnextorderdiscount.Admin'}
                {call name=snod_dash_help tip="{l s='Day-by-day trend of generated, emailed and used coupons over the last 30 days. All three share one scale, so the lines are directly comparable — used naturally sits lowest. Click a legend entry to hide a metric: the chart rescales and the remaining lines become easier to read.' d='Modules.Setnextorderdiscount.Admin'}"}
            </h4>
            <div class="snod-dash-chart-wrap">
                <canvas id="snod-dash-chart" aria-label="{l s='Daily dynamics' d='Modules.Setnextorderdiscount.Admin'}" role="img"></canvas>
            </div>
        </div>
    {/if}
</div>
