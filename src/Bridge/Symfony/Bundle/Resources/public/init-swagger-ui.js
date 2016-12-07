$(function () {
    var data = JSON.parse($('#swagger-data').html());
    window.swaggerUi = new SwaggerUi({
        url: data.url,
        spec: data.spec,
        dom_id: 'swagger-ui-container',
        supportedSubmitMethods: ['get', 'post', 'put', 'delete'],
        onComplete: function() {
            $('pre code').each(function(i, e) {
                hljs.highlightBlock(e)
            });

            if (data.operationId !== undefined) {
                var queryParameters = data.queryParameters;
                var domSelector = '#' + data.shortName+'_'+data.operationId;

                $(domSelector + ' form.sandbox input.parameter').each(function (i, e) {
                    var $e = $(e);
                    var name = $e.attr('name');

                    if (name in queryParameters) {
                        $e.val(queryParameters[name]);
                    }
                });

                if (data.id) {
                    $(domSelector + ' form.sandbox input[name="id"]').val(data.id);
                }

                $(domSelector + ' form.sandbox').submit();
                document.location.hash = '#!/' + data.shortName + '/' + data.operationId;
            }
        },
        onFailure: function() {
            log('Unable to Load SwaggerUI');
        },
        docExpansion: 'list',
        jsonEditor: false,
        defaultModelRendering: 'schema',
        showRequestHeaders: true
    });

    window.swaggerUi.load();

    function log() {
        if ('console' in window) {
            console.log.apply(console, arguments);
        }
    }
});
