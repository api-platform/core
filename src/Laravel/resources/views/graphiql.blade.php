<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ config('api-platform.title') }} - API Platform</title>

    <script type="importmap">
    {
        "imports": {
            "react": "https://esm.sh/react@19.2.5",
            "react/": "https://esm.sh/react@19.2.5/",
            "react-dom": "https://esm.sh/react-dom@19.2.5",
            "react-dom/": "https://esm.sh/react-dom@19.2.5/",
            "graphql": "https://esm.sh/graphql@16.13.2",
            "graphiql": "https://esm.sh/graphiql@5.2.2?standalone&external=react,react-dom,@graphiql/react,graphql",
            "graphiql/": "https://esm.sh/graphiql@5.2.2/",
            "@graphiql/react": "https://esm.sh/@graphiql/react@0.37.3?standalone&external=react,react-dom,graphql,@graphiql/toolkit,@emotion/is-prop-valid",
            "@graphiql/toolkit": "https://esm.sh/@graphiql/toolkit@0.11.3?standalone&external=graphql",
            "@emotion/is-prop-valid": "data:text/javascript,"
        }
    }
    </script>

    <link rel="stylesheet" href="https://esm.sh/graphiql@5/dist/style.css">
    <link rel="stylesheet" href="/vendor/api-platform/graphiql-style.css">

    <script id="graphiql-data" type="application/json">{!! Illuminate\Support\Js::encode($graphiql_data) !!}</script>
</head>

<body>
<div id="graphiql">Loading...</div>
<script type="module" src="/vendor/api-platform/init-graphiql.js"></script>

</body>
</html>
