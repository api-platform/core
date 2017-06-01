window.onload = () => {
  const data = JSON.parse(document.getElementById('swagger-data').innerText);
  const ui = SwaggerUIBundle({
    spec: data.spec,
    dom_id: '#swagger-ui',
    validatorUrl: null,
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    layout: 'StandaloneLayout'
  });

  if (data.oauth.enabled) {
    ui.initOAuth({
      clientId: data.oauth.clientId,
      clientSecret: data.oauth.clientSecret,
      realm: data.oauth.type,
      appName: data.spec.info.title,
      scopeSeparator: ' ',
      additionalQueryStringParams: {}
    });
  }

  window.ui = ui;

  if (!data.operationId) return;

  const observer = new MutationObserver(function (mutations, self) {
    const op = document.getElementById(`operations,${data.method}-${data.path},${data.shortName}`);
    if (!op) return;

    self.disconnect();

    op.querySelector('.opblock-summary').click();
    op.querySelector('.try-out__btn').click();

    if (data.id) {
      const inputId = op.querySelector('.parameters input[placeholder="id"]');
      inputId.value = data.id;
      inputId.dispatchEvent(new Event('input', { bubbles: true }));
    }

    for (let input of op.querySelectorAll('.parameters input')) {
      if (input.placeholder in data.queryParameters) {
        input.value = data.queryParameters[input.placeholder];
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }

    op.querySelector('.execute').click();
    op.scrollIntoView();
  });

  observer.observe(document, {childList: true, subtree: true});
};
