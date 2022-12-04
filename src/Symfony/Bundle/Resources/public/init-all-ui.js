'use strict';

window.onload = () => {
    let graphQlDocLink = document.getElementsByClassName("graphql-docs-link").item(0);
    graphQlDocLink.addEventListener("click",function(e){
        if (!e.target.hasAttribute("data-graphql-enabled")) {
            e.preventDefault();
            e.stopPropagation();
            alert('GraphQL support is not enabled, see https://api-platform.com/docs/core/graphql/');
        }
    },false);
};
