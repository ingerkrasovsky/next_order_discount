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

<div id="snod-cron-tools" class="page-content" data-ajax-url="{$snod_admin_link|escape:'html':'UTF-8'}">

    {* ------------------------------------------------------------------ *}
    {* Setup helper                                                        *}
    {* ------------------------------------------------------------------ *}
    <div class="panel">
        <h3><i class="icon icon-magic"></i> {l s='Set up the cron' d='Modules.Setnextorderdiscount.Admin'}</h3>
        <p class="text-muted">
            {l s='The module needs a cron to send queued emails, plan reminders and expire lapsed coupons. The recommended way works on any hosting and is independent of your PHP version, because it calls a URL over HTTP instead of running PHP from the command line.' d='Modules.Setnextorderdiscount.Admin'}
        </p>

        <div class="alert alert-info" style="margin-bottom:15px;">
            <strong>{l s='Recommended:' d='Modules.Setnextorderdiscount.Admin'}</strong>
            {l s='add ONE line to your server crontab that runs every 5 minutes. It triggers all tasks at once:' d='Modules.Setnextorderdiscount.Admin'}
        </div>

        {* Optional one-click install: shown only when this server can manage its crontab. *}
        {if $snod_cron_caps.available}
            <div class="well well-sm" id="snod-cron-install-box" data-installed="{if $snod_cron_installed}1{else}0{/if}">
                <strong>{l s='One-click install' d='Modules.Setnextorderdiscount.Admin'}</strong>
                {l s='— this server can manage its own crontab, so you can install the cron without leaving this page.' d='Modules.Setnextorderdiscount.Admin'}
                <div style="margin-top:8px;">
                    {if $snod_cron_installed}
                        <span class="snod-badge snod-badge-success"><i class="material-icons">check_circle</i>{l s='Installed' d='Modules.Setnextorderdiscount.Admin'}</span>
                        <button type="button" class="btn btn-default btn-sm snod-cron-remove">
                            <i class="material-icons">delete</i> {l s='Remove cron' d='Modules.Setnextorderdiscount.Admin'}
                        </button>
                    {else}
                        <button type="button" class="btn btn-success btn-sm snod-cron-install">
                            <i class="material-icons">download</i> {l s='Install cron automatically' d='Modules.Setnextorderdiscount.Admin'}
                        </button>
                    {/if}
                    <span class="snod-cron-install-result" style="margin-left:8px;"></span>
                </div>
                <p class="help-block" style="margin-bottom:0;">
                    {l s='Adds one line (every 5 minutes) to this server’s crontab, wrapped in markers so it is removed cleanly when you uninstall the module.' d='Modules.Setnextorderdiscount.Admin'}
                </p>
            </div>
        {else}
            <p class="help-block">
                {l s='Automatic install is not available on this server' d='Modules.Setnextorderdiscount.Admin'}
                {if $snod_cron_caps.reason == 'shell_exec_blocked'}
                    ({l s='PHP cannot run shell commands here — common on shared hosting' d='Modules.Setnextorderdiscount.Admin'})
                {elseif $snod_cron_caps.reason == 'no_crontab'}
                    ({l s='the crontab tool is not installed on this server' d='Modules.Setnextorderdiscount.Admin'})
                {elseif $snod_cron_caps.reason == 'crontab_denied'}
                    ({l s='the web-server user is not allowed to manage the crontab' d='Modules.Setnextorderdiscount.Admin'})
                {/if}.
                {l s='Use the copy-paste line below instead.' d='Modules.Setnextorderdiscount.Admin'}
            </p>
        {/if}

        <div class="form-group">
            <label class="control-label">{l s='Crontab line (curl)' d='Modules.Setnextorderdiscount.Admin'}</label>
            <input type="text" class="form-control input-sm" readonly="readonly" onclick="this.select();"
                   value="{$snod_cron_command_curl|escape:'html':'UTF-8'}" />
            <p class="help-block">{l s='If curl is not available, use wget instead:' d='Modules.Setnextorderdiscount.Admin'}</p>
            <input type="text" class="form-control input-sm" readonly="readonly" onclick="this.select();"
                   value="{$snod_cron_command_wget|escape:'html':'UTF-8'}" />
        </div>

        <div class="form-group">
            <label class="control-label">{l s='Or use an external cron service' d='Modules.Setnextorderdiscount.Admin'}</label>
            <p class="help-block">
                {l s='No access to the server crontab? Paste this URL into a free web-cron service (e.g. cron-job.org) and set it to run every 5 minutes:' d='Modules.Setnextorderdiscount.Admin'}
            </p>
            <input type="text" class="form-control input-sm" readonly="readonly" onclick="this.select();"
                   value="{$snod_cron_all_url|escape:'html':'UTF-8'}" />
        </div>

        <p>
            <button type="button" class="btn btn-primary btn-sm snod-run-task" data-task="all">
                <i class="material-icons">play_arrow</i> {l s='Run all tasks now' d='Modules.Setnextorderdiscount.Admin'}
            </button>
            <span class="text-muted" style="margin-left:8px;">{l s='(a quick way to test that the endpoint works)' d='Modules.Setnextorderdiscount.Admin'}</span>
        </p>
        <div id="snod-cron-result"></div>

        {* Environment probe *}
        <hr>
        <h4>{l s='Your server' d='Modules.Setnextorderdiscount.Admin'}</h4>
        <ul class="list-unstyled" style="margin-bottom:8px;">
            <li>
                <strong>{l s='PHP version' d='Modules.Setnextorderdiscount.Admin'}:</strong>
                <code>{$snod_cron_env.php_version|escape:'html':'UTF-8'}</code>
            </li>
            <li>
                <strong>curl (CLI):</strong>
                {if $snod_cron_env.curl_cli === true}
                    <span class="snod-badge snod-badge-success"><i class="material-icons">check_circle</i>{l s='available' d='Modules.Setnextorderdiscount.Admin'}</span>
                {elseif $snod_cron_env.curl_cli === false}
                    <span class="snod-badge snod-badge-exclude"><i class="material-icons">warning</i>{l s='not found — use wget or an external cron service' d='Modules.Setnextorderdiscount.Admin'}</span>
                {else}
                    <span class="snod-badge snod-badge-all"><i class="material-icons">help</i>{l s='unknown (cannot probe the shell on this host)' d='Modules.Setnextorderdiscount.Admin'}</span>
                {/if}
            </li>
            <li>
                <strong>shell_exec:</strong>
                {if $snod_cron_env.shell_exec}
                    <span class="snod-badge snod-badge-success"><i class="material-icons">check_circle</i>{l s='available' d='Modules.Setnextorderdiscount.Admin'}</span>
                {else}
                    <span class="snod-badge snod-badge-all"><i class="material-icons">block</i>{l s='blocked (normal on shared hosting)' d='Modules.Setnextorderdiscount.Admin'}</span>
                {/if}
            </li>
        </ul>
        <p class="help-block">
            {l s='Tip: the HTTP cron line above does not depend on any of this — it runs through your web server, so the PHP version and shell settings shown here do not matter for it.' d='Modules.Setnextorderdiscount.Admin'}
        </p>
    </div>

    {* ------------------------------------------------------------------ *}
    {* Tasks: schedule, health, per-task URL, manual run                   *}
    {* ------------------------------------------------------------------ *}
    <div class="panel">
        <h3><i class="icon icon-cogs"></i> {l s='Tasks' d='Modules.Setnextorderdiscount.Admin'}</h3>
        <p>
            <strong>{l s='Managed cron:' d='Modules.Setnextorderdiscount.Admin'}</strong>
            {if $snod_cron_installed}
                <span class="snod-badge snod-badge-success"><i class="material-icons">check_circle</i>{l s='Installed by this module' d='Modules.Setnextorderdiscount.Admin'}</span>
                <span class="text-muted" style="font-size:11px;">{l s='(a crontab entry created from this page is active)' d='Modules.Setnextorderdiscount.Admin'}</span>
            {elseif $snod_cron_caps.crontab}
                <span class="snod-badge snod-badge-all"><i class="material-icons">remove</i>{l s='Not installed by this module' d='Modules.Setnextorderdiscount.Admin'}</span>
                <span class="text-muted" style="font-size:11px;">{l s='(the cron may still be set up manually or via a web-cron service)' d='Modules.Setnextorderdiscount.Admin'}</span>
            {else}
                <span class="snod-badge snod-badge-all"><i class="material-icons">help</i>{l s='Cannot verify' d='Modules.Setnextorderdiscount.Admin'}</span>
                <span class="text-muted" style="font-size:11px;">{l s='(no crontab access on this server — check the Last run column instead)' d='Modules.Setnextorderdiscount.Admin'}</span>
            {/if}
        </p>
        <p class="text-muted">
            {l s='Each task also has its own URL if you prefer separate cron entries. Keep the token secret: anyone with a URL can run the corresponding task.' d='Modules.Setnextorderdiscount.Admin'}
        </p>

        <table class="table">
            <thead>
                <tr>
                    <th>{l s='Task' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Recommended' d='Modules.Setnextorderdiscount.Admin'}</th>
                    <th>{l s='Last run' d='Modules.Setnextorderdiscount.Admin'}</th>
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
                            {$task.schedule|escape:'html':'UTF-8'}<br />
                            <code class="text-muted">{$task.cron_expr|escape:'html':'UTF-8'}</code>
                        </td>
                        <td>
                            {if $task.health == 'ok'}
                                <span class="snod-badge snod-badge-success"><i class="material-icons">check_circle</i>{l s='OK' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {elseif $task.health == 'warn'}
                                <span class="snod-badge snod-badge-exclude"><i class="material-icons">schedule</i>{l s='Late' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {elseif $task.health == 'danger'}
                                <span class="snod-badge snod-badge-danger"><i class="material-icons">error</i>{l s='Not running' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {else}
                                <span class="snod-badge snod-badge-all"><i class="material-icons">remove</i>{l s='Never run' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                            {if $task.last_run != ''}
                                <div class="text-muted" style="font-size:11px;">{$task.last_run_human|escape:'html':'UTF-8'}</div>
                            {/if}
                        </td>
                        <td>
                            <input type="text" class="form-control input-sm" readonly="readonly"
                                   onclick="this.select();"
                                   value="{$task.url|escape:'html':'UTF-8'}" />
                        </td>
                        <td class="text-center">
                            {if $task.locked}
                                <span class="snod-badge snod-badge-include"><i class="material-icons">autorenew</i>{l s='Running' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {else}
                                <span class="snod-badge snod-badge-all"><i class="material-icons">lock_open</i>{l s='Free' d='Modules.Setnextorderdiscount.Admin'}</span>
                            {/if}
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-default btn-sm snod-run-task" data-task="{$task.task|escape:'html':'UTF-8'}">
                                <i class="material-icons">play_arrow</i> {l s='Run now' d='Modules.Setnextorderdiscount.Admin'}
                            </button>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    {* ------------------------------------------------------------------ *}
    {* Queue snapshot                                                      *}
    {* ------------------------------------------------------------------ *}
    <div class="panel">
        <h3><i class="icon icon-tasks"></i> {l s='Dispatch queue' d='Modules.Setnextorderdiscount.Admin'}</h3>
        <div class="snod-targeting-badges">
            <span class="snod-badge snod-badge-all"><i class="material-icons">schedule</i>{l s='Pending' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.pending|intval}</span>
            <span class="snod-badge snod-badge-include"><i class="material-icons">autorenew</i>{l s='Processing' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.processing|intval}</span>
            <span class="snod-badge snod-badge-success"><i class="material-icons">check_circle</i>{l s='Done' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.done|intval}</span>
            <span class="snod-badge snod-badge-danger"><i class="material-icons">error</i>{l s='Failed' d='Modules.Setnextorderdiscount.Admin'}: {$snod_queue_counts.failed|intval}</span>
        </div>
    </div>
</div>
