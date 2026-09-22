<div class="panel">
  <div class="panel-heading">
    <i class="icon-envelope"></i>
    {l s='Usage history' mod='set_demo_order_generator'}
  </div>
  <p class="help-block">
    {l s='The last 100 successful test orders for the current shop. This history is available only from the module configuration page and is not shown on the order generator page.' mod='set_demo_order_generator'}
  </p>

  {if $sdog_usage_history|count}
    <div class="table-responsive-row clearfix">
      <table class="table">
        <thead>
          <tr>
            <th>{l s='Date' mod='set_demo_order_generator'}</th>
            <th>{l s='Customer' mod='set_demo_order_generator'}</th>
            <th>{l s='Email' mod='set_demo_order_generator'}</th>
            <th>{l s='Order' mod='set_demo_order_generator'}</th>
            <th>{l s='Total' mod='set_demo_order_generator'}</th>
            <th>{l s='Coupon' mod='set_demo_order_generator'}</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$sdog_usage_history item=usage}
            <tr>
              <td>{$usage.date_add|escape:'htmlall':'UTF-8'}</td>
              <td>
                <a href="{$usage.customer_url|escape:'htmlall':'UTF-8'}">
                  {$usage.firstname|escape:'htmlall':'UTF-8'} {$usage.lastname|escape:'htmlall':'UTF-8'}
                </a>
              </td>
              <td><a href="mailto:{$usage.email|escape:'htmlall':'UTF-8'}">{$usage.email|escape:'htmlall':'UTF-8'}</a></td>
              <td><a href="{$usage.order_url|escape:'htmlall':'UTF-8'}">#{$usage.id_order|intval}</a></td>
              <td>{$usage.total_paid|string_format:'%.2f'} {$usage.currency_iso|escape:'htmlall':'UTF-8'}</td>
              <td>
                {if $usage.coupon_code}
                  <code>{$usage.coupon_code|escape:'htmlall':'UTF-8'}</code>
                  <small>({$usage.coupon_status|escape:'htmlall':'UTF-8'})</small>
                {else}
                  —
                {/if}
              </td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  {else}
    <p class="text-muted">{l s='No test orders have been recorded yet.' mod='set_demo_order_generator'}</p>
  {/if}
</div>
