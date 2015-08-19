# AngularJS integration

DunglasApiBundle works fine with [AngularJS](http://angularjs.org). The popular [Restangular](https://github.com/mgonto/restangular)
REST client library for Angular can easily be configured to handle the API format.

Here is a working Restangular config:

```javascript
'use strict';

var app = angular
    .module('myAngularjsApp')
    .config(['RestangularProvider', function (RestangularProvider) {
        // The URL of the API endpoint
        RestangularProvider.setBaseUrl('http://localhost:8000');

        // JSON-LD @id support
        RestangularProvider.setRestangularFields({
            id: '@id'
        });
        RestangularProvider.setSelfLinkAbsoluteUrl(false);

        // Hydra collections support
        RestangularProvider.addResponseInterceptor(function (data, operation) {
            // Remove trailing slash to make Restangular working
            function populateHref(data) {
                if (data['@id']) {
                    data.href = data['@id'].substring(1);
                }
            }

            // Populate href property for the collection
            populateHref(data);

            if ('getList' === operation) {
                var collectionResponse = data['hydra:member'];
                collectionResponse.metadata = {};

                // Put metadata in a property of the collection
                angular.forEach(data, function (value, key) {
                    if ('hydra:member' !== key) {
                        collectionResponse.metadata[key] = value;
                    }
                });

                // Populate href property for all elements of the collection
                angular.forEach(collectionResponse, function (value) {
                    populateHref(value);
                });

                return collectionResponse;
            }

            return data;
        });
    }])
;

```

Previous chapter: [Performance](performance.md)
