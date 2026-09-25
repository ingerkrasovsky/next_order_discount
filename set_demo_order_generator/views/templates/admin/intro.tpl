{if !$sdog_main_module_ready}
  <div class="alert alert-danger">
    {l s='Next Order Discount must be installed and enabled before this tool can create test orders.' mod='set_demo_order_generator'}
  </div>
{/if}

{if $sdog_created_result}
  <div class="alert alert-success">
    <p>
      <strong>{l s='Test order created.' mod='set_demo_order_generator'}</strong>
      <a href="{$sdog_created_result.order_url|escape:'htmlall':'UTF-8'}">
        #{$sdog_created_result.order_id|intval}
      </a>
      — {$sdog_created_result.total|escape:'htmlall':'UTF-8'} {$sdog_created_result.currency|escape:'htmlall':'UTF-8'}
      — {$sdog_created_result.email|escape:'htmlall':'UTF-8'}
    </p>
    {if $sdog_created_result.coupon_code}
      <p>
        {l s='Next Order Discount coupon:' mod='set_demo_order_generator'}
        <code>{$sdog_created_result.coupon_code|escape:'htmlall':'UTF-8'}</code>
        ({$sdog_created_result.coupon_status|escape:'htmlall':'UTF-8'})
      </p>
    {else}
      <p>
        {l s='No discount coupon was generated. Check the active rule conditions and the selected order status.' mod='set_demo_order_generator'}
        <a href="{$sdog_next_order_discount_url|escape:'htmlall':'UTF-8'}">
          {l s='Open Next Order Discount' mod='set_demo_order_generator'}
        </a>
      </p>
    {/if}
    {if !$sdog_created_result.usage_saved}
      <p class="text-warning">
        {l s='The order was created, but its email could not be saved to the usage history. Check the PrestaShop logs and run the module upgrade.' mod='set_demo_order_generator'}
      </p>
    {/if}
  </div>
{/if}

<div class="alert alert-info">
  <p><strong>{l s='How to test' mod='set_demo_order_generator'}</strong></p>
  <ol>
    <li>{l s='Enter an email inbox that you own.' mod='set_demo_order_generator'}</li>
    <li>{l s='Choose values that match one of the active Next Order Discount rules.' mod='set_demo_order_generator'}</li>
    <li>{l s='Create the order and check the result above and your inbox.' mod='set_demo_order_generator'}</li>
  </ol>
  <p>{l s='Existing customers are reused by email. Their first name, last name and email language are updated from this form before the order is created.' mod='set_demo_order_generator'}</p>
</div>
