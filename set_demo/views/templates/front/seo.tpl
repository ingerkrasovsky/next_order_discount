{extends file='page.tpl'}

{block name='page_header_container'}{/block}

{block name='page_content'}
<article class="set-demo-seo">
  <header class="set-demo-seo__hero">
    <div class="set-demo-seo__hero-copy">
      <p class="set-demo-seo__eyebrow">PrestaShop module · Live demo</p>
      <h1>Turn a completed order into a reason to come back</h1>
      <p class="set-demo-seo__lead">Next Order Discount automatically creates a personal coupon after a qualifying purchase, sends it to the customer and helps you track whether it leads to another order.</p>

      <div class="set-demo-seo__actions">
        <a class="set-demo-seo__button set-demo-seo__button--primary" href="{$seo_admin_demo_url|escape:'html':'UTF-8'}" rel="nofollow">Explore the back office <span aria-hidden="true">→</span></a>
        <a class="set-demo-seo__button set-demo-seo__button--secondary" href="{$seo_order_generator_url|escape:'html':'UTF-8'}" rel="nofollow">Create a test order</a>
      </div>

      <ul class="set-demo-seo__hero-points" aria-label="Key benefits">
        <li>No storefront widget required</li>
        <li>Rule-based targeting</li>
        <li>Built on PrestaShop cart rules</li>
      </ul>
    </div>

    <div class="set-demo-seo__flow" aria-label="Coupon lifecycle">
      <div class="set-demo-seo__flow-head">
        <span>Coupon lifecycle</span>
        <span class="set-demo-seo__live"><i></i> Live demo</span>
      </div>
      <div class="set-demo-seo__flow-row">
        <span class="set-demo-seo__flow-icon">01</span>
        <div><strong>Order matches a rule</strong><small>Status, total, customer and product conditions</small></div>
      </div>
      <div class="set-demo-seo__flow-line"></div>
      <div class="set-demo-seo__flow-row">
        <span class="set-demo-seo__flow-icon">02</span>
        <div><strong>Personal coupon is created</strong><small>Percentage, fixed amount or free shipping</small></div>
      </div>
      <div class="set-demo-seo__flow-line"></div>
      <div class="set-demo-seo__flow-row">
        <span class="set-demo-seo__flow-icon">03</span>
        <div><strong>Email is sent immediately</strong><small>Optional reminders keep the offer visible</small></div>
      </div>
      <div class="set-demo-seo__flow-line"></div>
      <div class="set-demo-seo__flow-row set-demo-seo__flow-row--success">
        <span class="set-demo-seo__flow-icon">04</span>
        <div><strong>Customer uses it later</strong><small>The dashboard records coupon conversion</small></div>
      </div>
    </div>
  </header>

  <section class="set-demo-seo__section set-demo-seo__section--intro">
    <p class="set-demo-seo__kicker">Post-purchase retention</p>
    <h2>A targeted next-order coupon, not another blanket promotion</h2>
    <p>Define who should receive an offer, when it should be issued and what the customer gets. The module handles coupon creation, delivery, reminders, expiry and cancellation while keeping every issued coupon visible in the back office.</p>
  </section>

  <section class="set-demo-seo__section" aria-labelledby="seo-features-title">
    <div class="set-demo-seo__section-heading">
      <div>
        <p class="set-demo-seo__kicker">Flexible rule engine</p>
        <h2 id="seo-features-title">Build campaigns around real order data</h2>
      </div>
      <a class="set-demo-seo__text-link" href="{$seo_rules_url|escape:'html':'UTF-8'}" rel="nofollow">Open Discount rules →</a>
    </div>

    <div class="set-demo-seo__grid set-demo-seo__grid--features">
      <article class="set-demo-seo__card">
        <span class="set-demo-seo__card-number">01</span>
        <h3>Choose the qualifying order</h3>
        <p>Combine trigger statuses, source-order total, campaign dates and the customer’s order number.</p>
      </article>
      <article class="set-demo-seo__card">
        <span class="set-demo-seo__card-number">02</span>
        <h3>Target the right audience</h3>
        <p>Include or exclude customer groups, countries, currencies, product categories and brands.</p>
      </article>
      <article class="set-demo-seo__card">
        <span class="set-demo-seo__card-number">03</span>
        <h3>Define the next-order benefit</h3>
        <p>Offer a percentage, fixed amount or free shipping, with its own validity and minimum order total.</p>
      </article>
      <article class="set-demo-seo__card">
        <span class="set-demo-seo__card-number">04</span>
        <h3>Control rule priority</h3>
        <p>Reorder rules and decide whether processing stops after the first match or issues several coupons.</p>
      </article>
    </div>
  </section>

  <section class="set-demo-seo__section set-demo-seo__section--dark" aria-labelledby="seo-email-title">
    <div class="set-demo-seo__dark-copy">
      <p class="set-demo-seo__kicker">Customer communication</p>
      <h2 id="seo-email-title">Send the coupon now. Remind the customer later.</h2>
      <p>The main coupon email is attempted immediately after creation. If delivery fails, the dispatch queue keeps it available for a cron retry. Up to two optional reminders can be scheduled after the first email or before the coupon expires.</p>
      <ul class="set-demo-seo__checklist">
        <li>Separate coupon and reminder templates for every rule</li>
        <li>Subject and HTML content for each shop language</li>
        <li>Preview and test email before launch</li>
        <li>Automatic stop after use, expiry or cancellation</li>
      </ul>
    </div>
    <div class="set-demo-seo__mail-card">
      <div class="set-demo-seo__mail-top"><span></span><span></span><span></span></div>
      <p class="set-demo-seo__mail-label">YOUR NEXT ORDER</p>
      <strong>10% off</strong>
      <div class="set-demo-seo__coupon-code">RETURN-AB12CD8X</div>
      <small>Personal coupon · valid for 30 days</small>
    </div>
  </section>

  <section class="set-demo-seo__section" aria-labelledby="seo-insights-title">
    <div class="set-demo-seo__section-heading">
      <div>
        <p class="set-demo-seo__kicker">Measurable workflow</p>
        <h2 id="seo-insights-title">See what happens after the coupon is issued</h2>
      </div>
      <a class="set-demo-seo__text-link" href="{$seo_dashboard_url|escape:'html':'UTF-8'}" rel="nofollow">View the live dashboard →</a>
    </div>

    <div class="set-demo-seo__metrics">
      <div><strong>Generated</strong><span>Rule matched and coupon created</span></div>
      <div><strong>Emailed</strong><span>Main coupon email delivered</span></div>
      <div><strong>Reminded</strong><span>At least one reminder sent</span></div>
      <div><strong>Used</strong><span>Coupon applied to a later order</span></div>
    </div>
    <p class="set-demo-seo__metrics-note">The dashboard includes the complete coupon funnel, used/generated conversion and 30-day daily trends. Coupons, queue health and event logs provide the detail behind every number.</p>
  </section>

  <section class="set-demo-seo__section set-demo-seo__section--demo" aria-labelledby="seo-demo-title">
    <div class="set-demo-seo__section-heading">
      <div>
        <p class="set-demo-seo__kicker">Try before you install</p>
        <h2 id="seo-demo-title">Test the complete flow in the live store</h2>
      </div>
    </div>

    <div class="set-demo-seo__grid set-demo-seo__grid--demo">
      <a class="set-demo-seo__demo-card" href="{$seo_admin_demo_url|escape:'html':'UTF-8'}" rel="nofollow">
        <span>Guided back office</span>
        <strong>Explore every screen with the interactive tour</strong>
        <small>Dashboard, rules, coupons, settings, cron and logs <b>→</b></small>
      </a>
      <a class="set-demo-seo__demo-card set-demo-seo__demo-card--accent" href="{$seo_order_generator_url|escape:'html':'UTF-8'}" rel="nofollow">
        <span>Demo Order Generator</span>
        <strong>Create a real test order for your own email</strong>
        <small>Choose matching data and see the coupon result <b>→</b></small>
      </a>
      <a class="set-demo-seo__demo-card" href="{$seo_front_demo_url|escape:'html':'UTF-8'}" rel="nofollow">
        <span>Customer journey</span>
        <strong>Place an order through the storefront</strong>
        <small>Use the ordinary checkout flow as a customer <b>→</b></small>
      </a>
    </div>
  </section>

  <section class="set-demo-seo__section set-demo-seo__section--faq" aria-labelledby="seo-faq-title">
    <p class="set-demo-seo__kicker">FAQ</p>
    <h2 id="seo-faq-title">Questions about Next Order Discount</h2>
    <div class="set-demo-seo__faq-list">
      {foreach from=$seo_faq item=faq name=seoFaq}
        <details class="set-demo-seo__faq-item"{if $smarty.foreach.seoFaq.first} open{/if}>
          <summary>{$faq.0|escape:'html':'UTF-8'}<span aria-hidden="true">+</span></summary>
          <p>{$faq.1|escape:'html':'UTF-8'}</p>
        </details>
      {/foreach}
    </div>
  </section>

  <section class="set-demo-seo__cta">
    <div>
      <p class="set-demo-seo__kicker">Ready to explore?</p>
      <h2>Follow the guided tour or create your first test coupon</h2>
    </div>
    <div class="set-demo-seo__actions">
      <a class="set-demo-seo__button set-demo-seo__button--light" href="{$seo_admin_demo_url|escape:'html':'UTF-8'}" rel="nofollow">Open live demo</a>
      {if $seo_addons_url}
        <a class="set-demo-seo__button set-demo-seo__button--outline-light" href="{$seo_addons_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener nofollow">View on PrestaShop Marketplace</a>
      {/if}
    </div>
  </section>

  <script type="application/ld+json">{$seo_jsonld_software nofilter}</script>
  <script type="application/ld+json">{$seo_jsonld_faq nofilter}</script>
</article>
{/block}
