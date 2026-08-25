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

<div id="snod-cron-tools" data-ajax-url="{$snod_admin_link|escape:'html':'UTF-8'}">
    <div class="panel">
        <h3><i class="icon icon-cogs"></i> {l s='Cron / Tools' d='Modules.Setnextorderdiscount.Admin'}</h3>
        <p class="text-muted">
            {l s='Trigger the background tasks from your server cron using the URLs below. Keep the token secret: anyone with a URL can run the corresponding task.' d='Modules.Setnextorderdiscount.Admin'}
        </p>

        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Task' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Cron URL' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th class="text-center">{l s='Lock' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th class="text-center">{l s='Manual run' d='Modules.Setnextorderdiscount.Admin'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$snod_cron_tasks item=task}
                    <tr>
                        <td>
                            <strong>{$task.label|escape:'html':'UTF-8'}</strong><br />
                            <code>{$task.task|escape:'html':'UTF-8'}</code>
                        </td>
                        <td>
                            <input type="text" class="form-control input-sm" readonly="readonly"
                                   onclick="this.select();"
                                   value="{$task.url|escape:'html':'UTF-8'}" />
                        </td>
                        <td class="text-center">
                            {if $task.locked}
                                <span class="label label-warning">{l s='Running' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {else}
                                <span class="label label-success">{l s='Free' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-primary btn-sm snod-run-task" data-task="{$task.task|escape:'html':'UTF-8'}">
                                <i class="icon icon-play"></i> {l s='Run now' d='Modules.Setnextorderdiscount.Admin'}
                            </button>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <div id="snod-cron-result"></div>
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
</div>
