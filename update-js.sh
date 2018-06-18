#!/bin/sh

yarn add --production --no-lockfile swagger-ui-dist es6-promise fetch react react-dom graphiql

dest=src/Bridge/Symfony/Bundle/Resources/public/swagger-ui/
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/swagger-ui-dist/swagger-ui-bundle.js $dest
cp node_modules/swagger-ui-dist/swagger-ui-standalone-preset.js $dest
cp node_modules/swagger-ui-dist/swagger-ui.css $dest

dest=src/Bridge/Symfony/Bundle/Resources/public/react/
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/react/umd/react.production.min.js $dest
cp node_modules/react-dom/umd/react-dom.production.min.js $dest

dest=src/Bridge/Symfony/Bundle/Resources/public/graphiql/
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/graphiql/graphiql.min.js $dest
cp node_modules/graphiql/graphiql.css $dest

rm -Rf package.json node_modules/
