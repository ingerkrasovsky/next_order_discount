{* Prevents a second submission while PrestaShop is still building the order.
   The form already carries a hidden submitCreateDemoOrder input, so disabling
   the button does not remove the action from the POST payload. *}
<style>
  .sdog-spinner {
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-right: 6px;
    vertical-align: -1px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: sdog-spin 0.7s linear infinite;
  }
  .sdog-progress .sdog-spinner {
    border-color: rgba(49, 112, 143, 0.25);
    border-top-color: #31708f;
  }
  .sdog-progress {
    display: none;
    clear: both;
    margin: 0;
  }
  .sdog-progress.sdog-visible {
    display: block;
  }
  @keyframes sdog-spin {
    to { transform: rotate(360deg); }
  }
  @media (prefers-reduced-motion: reduce) {
    .sdog-spinner { animation: none; }
  }
</style>

<div class="alert alert-info sdog-progress" id="sdog_progress" role="status" aria-live="polite">
  <span class="sdog-spinner"></span>
  {l s='Creating the test order. This can take a few seconds — do not reload the page or submit again.' mod='set_demo_order_generator'}
</div>

<script type="text/javascript">
  (function () {
    var button = document.getElementById('set_demo_order_generator_form_submit_btn')
      || document.querySelector('button[name="submitCreateDemoOrder"]');
    if (!button) {
      return;
    }
    var form = button.form || button.closest('form');
    var progress = document.getElementById('sdog_progress');
    if (!form) {
      return;
    }

    var busyLabel = '{l s='Creating the test order…' mod='set_demo_order_generator' js=1}';
    var locked = false;

    function unlock() {
      locked = false;
      button.disabled = false;
      button.removeAttribute('aria-busy');
      if (button.dataset.sdogIdleHtml !== undefined) {
        button.innerHTML = button.dataset.sdogIdleHtml;
      }
      if (progress) {
        progress.classList.remove('sdog-visible');
      }
    }

    form.addEventListener('submit', function (event) {
      if (locked) {
        event.preventDefault();
        return;
      }
      locked = true;
      if (button.dataset.sdogIdleHtml === undefined) {
        button.dataset.sdogIdleHtml = button.innerHTML;
      }
      button.innerHTML = '<span class="sdog-spinner"></span>' + busyLabel;
      button.setAttribute('aria-busy', 'true');
      if (progress) {
        progress.classList.add('sdog-visible');
      }
      // Disable after the event loop hands the submission to the browser so
      // Safari and Firefox still serialise the button itself.
      window.setTimeout(function () {
        button.disabled = true;
      }, 0);
    });

    // Restoring the page from the back/forward cache must not leave a dead button.
    window.addEventListener('pageshow', function (event) {
      if (event.persisted && locked) {
        unlock();
      }
    });
  })();
</script>
