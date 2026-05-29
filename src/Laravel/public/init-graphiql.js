import 'graphiql/setup-workers/esm.sh';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { GraphiQL } from 'graphiql';
import { createGraphiQLFetcher } from '@graphiql/toolkit';

const data = JSON.parse(document.getElementById('graphiql-data').textContent);

const params = new URLSearchParams(window.location.search);
const rawVariables = params.get('variables');
let initialVariables;
if (rawVariables) {
    try {
        initialVariables = JSON.stringify(JSON.parse(rawVariables), null, 2);
    } catch {
        initialVariables = rawVariables;
    }
}

const fetcher = createGraphiQLFetcher({
    url: data.entrypoint,
    fetch: (url, init) => fetch(url, { ...init, credentials: 'include' }),
});

function updateUrl({ query, variables, operationName }) {
    const next = new URLSearchParams();
    if (query) next.set('query', query);
    if (variables) next.set('variables', variables);
    if (operationName) next.set('operationName', operationName);
    const search = next.toString();
    window.history.replaceState(null, '', search ? `?${search}` : window.location.pathname);
}

function onTabChange({ tabs, activeTabIndex }) {
    const tab = tabs[activeTabIndex];
    if (!tab) {
        return;
    }
    updateUrl({
        query: tab.query,
        variables: tab.variables,
        operationName: tab.operationName,
    });
}

createRoot(document.getElementById('graphiql')).render(
    createElement(GraphiQL, {
        fetcher,
        initialQuery: params.get('query') ?? undefined,
        initialVariables,
        operationName: params.get('operationName') ?? undefined,
        onTabChange,
    }),
);
