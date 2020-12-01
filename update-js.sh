#!/bin/sh
rm -f yarn.lock
# Keep the yarn.lock file in the repo to benefit from GitHub security alerts: https://github.blog/2019-07-02-yarn-support-for-security-alerts/
yarn add swagger-ui-dist
yarn add --production  es6-promise fetch react react-dom graphiql graphql-playground-react@1.7.26 redoc

dest=src/Bridge/Symfony/Bundle/Resources/public/swagger-ui/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/swagger-ui-dist/swagger-ui-bundle.js "$dest"
cp node_modules/swagger-ui-dist/swagger-ui-bundle.js.map "$dest"
cp node_modules/swagger-ui-dist/swagger-ui-standalone-preset.js "$dest"
cp node_modules/swagger-ui-dist/swagger-ui-standalone-preset.js.map "$dest"
cp node_modules/swagger-ui-dist/swagger-ui.css "$dest"
cp node_modules/swagger-ui-dist/swagger-ui.css.map "$dest"
cp node_modules/swagger-ui-dist/oauth2-redirect.html "$dest"

dest=src/Bridge/Symfony/Bundle/Resources/public/react/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/react/umd/react.production.min.js "$dest"
cp node_modules/react-dom/umd/react-dom.production.min.js "$dest"

dest=src/Bridge/Symfony/Bundle/Resources/public/graphiql/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/graphiql/graphiql.min.js "$dest"
cp node_modules/graphiql/graphiql.css "$dest"

dest=src/Bridge/Symfony/Bundle/Resources/public/graphql-playground/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/graphql-playground-react/build/static/js/middleware.js "$dest"
cp node_modules/graphql-playground-react/build/static/css/index.css "$dest"

dest=src/Bridge/Symfony/Bundle/Resources/public/redoc/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/redoc/bundles/redoc.standalone.js "$dest"

rm -Rf package.json node_modules/
