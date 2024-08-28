<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ config('api-platform.title') }} - API Platform</title>

    <link rel="stylesheet" href="/vendor/api-platform/graphiql/graphiql.css">
    <link rel="stylesheet" href="/vendor/api-platform/graphiql-style.css">

    <script id="graphiql-data" type="application/json">{!! Illuminate\Support\Js::encode($graphiql_data) !!}</script>
</head>

<body>
<div id="graphiql">Loading...</div>
<script src="/vendor/api-platform/react/react.production.min.js"></script>
<script src="/vendor/api-platform/react/react-dom.production.min.js"></script>
<script src="/vendor/api-platform/graphiql/graphiql.min.js"></script>
<script src="/vendor/api-platform/init-graphiql.js"></script>

</body>
</html>
