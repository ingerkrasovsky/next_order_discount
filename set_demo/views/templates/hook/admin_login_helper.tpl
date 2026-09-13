<div class="set-demo-login-helper alert alert-info" role="note">
  <strong>Demo login</strong>
  <span class="set-demo-login-helper__text">
    Login: demo@demo.com | Password: demodemo
  </span>
  <button type="button" class="btn btn-sm btn-primary set-demo-login-helper__apply" data-set-demo-fill>
    Fill form
  </button>
</div>

<script>
  window.setDemoLoginCredentials = {
    login: 'demo@demo.com',
    password: 'demodemo'
  };
</script>
<script src="{$set_demo_login_js|escape:'html':'UTF-8'}"></script>

<style>
  .set-demo-login-helper {
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
  }

  .set-demo-login-helper__text {
    font-size: 13px;
    line-height: 1.4;
  }

  .set-demo-login-helper__apply {
    margin-left: auto;
  }
</style>
