'use strict';

window.onload = function() {
    var data = JSON.parse(document.getElementById('swagger-data').innerText);

    var config = Object.assign({
        content: data.spec,
        theme: 'default',
    }, data.scalarExtraConfiguration || {});

    Scalar.createApiReference('#swagger-ui', config);
};
