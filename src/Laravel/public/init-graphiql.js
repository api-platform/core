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

createRoot(document.getElementById('graphiql')).render(
    createElement(GraphiQL, {
        fetcher,
        initialQuery: params.get('query') ?? undefined,
        initialVariables,
        operationName: params.get('operationName') ?? undefined,
    }),
);
