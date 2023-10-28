#!/bin/bash
rm -f yarn.lock
# Keep the yarn.lock file in the repo to benefit from GitHub security alerts: https://github.blog/2019-07-02-yarn-support-for-security-alerts/
echo "{}" > package.json
yarn add @fontsource/open-sans swagger-ui-dist
yarn add --production es6-promise fetch react react-dom graphiql graphql-playground-react@1.7.26 redoc

dest=src/Symfony/Bundle/Resources/public/fonts/open-sans/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "${dest}files/"
cp node_modules/@fontsource/open-sans/400.css "$dest"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-ext-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-ext-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-vietnamese-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-ext-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/700.css "$dest"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-ext-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-ext-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-vietnamese-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-ext-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-700-normal.woff2 "${dest}files/"

dest=src/Symfony/Bundle/Resources/public/swagger-ui/
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

dest=src/Symfony/Bundle/Resources/public/react/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/react/umd/react.production.min.js "$dest"
cp node_modules/react-dom/umd/react-dom.production.min.js "$dest"

dest=src/Symfony/Bundle/Resources/public/graphiql/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/graphiql/graphiql.min.js "$dest"
cp node_modules/graphiql/graphiql.css "$dest"

dest=src/Symfony/Bundle/Resources/public/graphql-playground/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/graphql-playground-react/build/static/js/middleware.js "$dest"
cp node_modules/graphql-playground-react/build/static/css/index.css "$dest"

dest=src/Symfony/Bundle/Resources/public/redoc/
if [[ -d "$dest" ]]; then
  rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/redoc/bundles/redoc.standalone.js "$dest"

rm -Rf package.json node_modules/
