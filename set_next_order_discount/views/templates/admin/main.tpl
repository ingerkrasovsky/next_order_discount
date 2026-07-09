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

<div id="head_tabs" class="page-head-tabs{if $isPs9} snod-page-head-tabs-ps9{/if}">
    <ul class="nav nav-pills">
        {foreach from=$arTabs item=tab key=tabCode}
            <li class="nav-item">
                {if isset($tab.level) && $tab.level == 0 }
                    <a class="nav-link tab {if isset($tab.active)}active{/if}" href="{$tab.url|escape:'html':'UTF-8'}">
                        {$tab.name|escape:'html':'UTF-8'}
                    </a>
                {/if}
            </li>
        {/foreach}
    </ul>
</div>
<br>
{include file="./tabs/$currentTabCode.tpl"}

<div class="panel" style="margin-top:20px; border-left:4px solid #25b9d7;">
    <div class="panel-body" style="padding:12px 18px; display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
        <i class="material-icons" style="font-size:28px; color:#25b9d7;">support_agent</i>
        <div>
            <strong style="font-size:13px;">{l s='Need help?' d='Modules.Setnextorderdiscount.Admin'}</strong><br>
            <span style="font-size:12px; color:#666;">
                {l s='Documentation, feature requests or bug reports:' d='Modules.Setnextorderdiscount.Admin'}
                <a href="https://addons.prestashop.com/contact-form.php" target="_blank" rel="noopener noreferrer" style="margin-left:6px;">
                    {l s='PrestaShop Addons contact form' d='Modules.Setnextorderdiscount.Admin'}
                </a>
            </span>
        </div>
    </div>
</div>
