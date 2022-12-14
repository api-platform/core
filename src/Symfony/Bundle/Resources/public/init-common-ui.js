'use strict';

const graphiQlLink = document.querySelector('.graphiql-link');
if (graphiQlLink) {
    graphiQlLink.addEventListener('click', e => {
        if (!e.target.hasAttribute('href')) {
            alert('GraphQL support is not enabled, see https://api-platform.com/docs/core/graphql/');
        }
    });
}
