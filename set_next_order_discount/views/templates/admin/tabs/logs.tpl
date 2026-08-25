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
    <h3><i class="icon icon-file-text"></i> {l s='Logs' d='Modules.Setnextorderdiscount.Admin'}</h3>

    <form method="post" action="{$AdminLink|escape:'html':'UTF-8'}&tab=logs" class="form-inline" style="margin-bottom:15px;">
        <div class="form-group">
            <label>{l s='Level' d='Modules.Setnextorderdiscount.Admin'}</label>
            <select name="snod_log_level" class="form-control">
                <option value="">{l s='All' d='Modules.Setnextorderdiscount.Admin'}</option>
                {foreach from=$snod_log_levels item=level}
                    <option value="{$level|escape:'html':'UTF-8'}"{if $snod_log_filter_level == $level} selected="selected"{/if}>{$level|escape:'html':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
        <div class="form-group">
            <label>{l s='Channel' d='Modules.Setnextorderdiscount.Admin'}</label>
            <input type="text" name="snod_log_channel" class="form-control" value="{$snod_log_filter_channel|escape:'html':'UTF-8'}" placeholder="cron, queue…" />
        </div>
        <button type="submit" class="btn btn-default">{l s='Filter' d='Modules.Setnextorderdiscount.Admin'}</button>
    </form>

    <p class="text-muted">{l s='Total entries:' d='Modules.Setnextorderdiscount.Admin'} {$snod_log_total|intval}</p>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Date' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Level' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Channel' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Message' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Correlation' d='Modules.Setnextorderdiscount.Admin'}</th>
                </tr>
            </thead>
            <tbody>
                {if $snod_logs}
                    {foreach from=$snod_logs item=entry}
                        <tr>
                            <td style="white-space:nowrap;">{$entry.created_at|escape:'html':'UTF-8'}</td>
                            <td>
                                {if $entry.level == 'error'}<span class="label label-danger">{$entry.level|escape:'html':'UTF-8'}</span>
                                {elseif $entry.level == 'warning'}<span class="label label-warning">{$entry.level|escape:'html':'UTF-8'}</span>
                                {elseif $entry.level == 'info'}<span class="label label-info">{$entry.level|escape:'html':'UTF-8'}</span>
                                {else}<span class="label label-default">{$entry.level|escape:'html':'UTF-8'}</span>{/if}
                            </td>
                            <td><code>{$entry.channel|escape:'html':'UTF-8'}</code></td>
                            <td>
                                {$entry.message|escape:'html':'UTF-8'}
                                {if $entry.context_json}
                                    <br /><small class="text-muted">{$entry.context_json|escape:'html':'UTF-8'}</small>
                                {/if}
                            </td>
                            <td><small class="text-muted">{$entry.correlation_id|escape:'html':'UTF-8'}</small></td>
                        </tr>
                    {/foreach}
                {else}
                    <tr><td colspan="5" class="text-muted">{l s='No log entries.' d='Modules.Setnextorderdiscount.Admin'}</td></tr>
                {/if}
            </tbody>
        </table>
    </div>

    {if $snod_log_total_pages > 1}
        <div class="text-center">
            <ul class="pagination">
                <li{if $snod_log_page <= 1} class="disabled"{/if}>
                    <a href="{if $snod_log_page > 1}{$snod_logs_base_url|escape:'html':'UTF-8'}&snod_log_page={$snod_log_prev_page|intval}{else}#{/if}">&laquo;</a>
                </li>
                <li class="disabled"><a href="#">{$snod_log_page|intval} / {$snod_log_total_pages|intval}</a></li>
                <li{if $snod_log_page >= $snod_log_total_pages} class="disabled"{/if}>
                    <a href="{if $snod_log_page < $snod_log_total_pages}{$snod_logs_base_url|escape:'html':'UTF-8'}&snod_log_page={$snod_log_next_page|intval}{else}#{/if}">&raquo;</a>
                </li>
            </ul>
        </div>
    {/if}
</div>
