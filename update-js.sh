#!/bin/bash
rm -f package.lock
rm -f package-lock.json
echo "{}" > package.json
// /!\ UMD is removed since react@19: https://react.dev/blog/2024/04/25/react-19-upgrade-guide#umd-builds-removed
npm i @fontsource/open-sans swagger-ui es6-promise fetch react@18 react-dom@18 graphiql graphql-playground-react redoc

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
cp node_modules/@fontsource/open-sans/files/open-sans-hebrew-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-math-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-symbols-400-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-ext-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-ext-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-vietnamese-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-ext-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-hebrew-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-math-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-symbols-400-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/700.css "$dest"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-ext-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-ext-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-vietnamese-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-ext-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-hebrew-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-math-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-symbols-700-normal.woff2 "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-ext-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-cyrillic-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-ext-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-greek-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-vietnamese-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-ext-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-latin-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-hebrew-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-math-700-normal.woff "${dest}files/"
cp node_modules/@fontsource/open-sans/files/open-sans-symbols-700-normal.woff "${dest}files/"

dest=src/Symfony/Bundle/Resources/public/swagger-ui/
if [[ -d "$dest" ]]; then
rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/swagger-ui/dist/swagger-ui-bundle.js "$dest"
cp node_modules/swagger-ui/dist/swagger-ui-standalone-preset.js "$dest"
cp node_modules/swagger-ui/dist/swagger-ui.css "$dest"
cp node_modules/swagger-ui/dist/swagger-ui.css.map "$dest"
cp node_modules/swagger-ui/dist/oauth2-redirect.html "$dest"

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
# TODO Laravel public files
