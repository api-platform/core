#!/bin/bash
rm -f package.lock
rm -f package-lock.json
echo "{}" > package.json
# GraphiQL v5 + React are loaded at runtime via importmap + esm.sh CDN
# (see src/Symfony/Bundle/Resources/views/Graphiql/index.html.twig and
# src/Laravel/resources/views/graphiql.blade.php). React 19 removed UMD
# builds, so no React/GraphiQL assets are bundled here.
npm i @fontsource/open-sans swagger-ui redoc

for public in src/Symfony/Bundle/Resources/public/ src/Laravel/public/; do

dest="${public}fonts/open-sans/"
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

dest="${public}swagger-ui/"
if [[ -d "$dest" ]]; then
rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/swagger-ui/dist/swagger-ui-bundle.js "$dest"
cp node_modules/swagger-ui/dist/swagger-ui-standalone-preset.js "$dest"
cp node_modules/swagger-ui/dist/swagger-ui.css "$dest"
cp node_modules/swagger-ui/dist/swagger-ui.css.map "$dest"
cp node_modules/swagger-ui/dist/oauth2-redirect.html "$dest"
cp node_modules/swagger-ui/dist/oauth2-redirect.js "$dest"

dest="${public}redoc/"
if [[ -d "$dest" ]]; then
rm -Rf "$dest"
fi
mkdir -p "$dest"
cp node_modules/redoc/bundles/redoc.standalone.js "$dest"

done

rm -Rf package.json node_modules/
