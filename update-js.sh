#!/bin/sh

dest=src/Bridge/Symfony/Bundle/Resources/public/swagger-ui/

yarn add --production --no-lockfile swagger-ui-dist
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/swagger-ui-dist/swagger-ui-bundle.js $dest
cp node_modules/swagger-ui-dist/swagger-ui-standalone-preset.js $dest
cp node_modules/swagger-ui-dist/swagger-ui.css $dest

dest=src/Bridge/Symfony/Bundle/Resources/public/es6-promise/

yarn add --production --no-lockfile es6-promise
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/es6-promise/dist/es6-promise.auto.min.js $dest

dest=src/Bridge/Symfony/Bundle/Resources/public/fetch/

yarn add --production --no-lockfile fetch
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/fetch/lib/fetch.js $dest

dest=src/Bridge/Symfony/Bundle/Resources/public/react/

yarn add --production --no-lockfile react react-dom
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/react/dist/react.min.js $dest
cp node_modules/react-dom/dist/react-dom.min.js $dest

dest=src/Bridge/Symfony/Bundle/Resources/public/graphiql/

yarn add --production --no-lockfile graphiql
if [ -d $dest ]; then
  rm -Rf $dest
fi
mkdir -p $dest
cp node_modules/graphiql/graphiql.min.js $dest
cp node_modules/graphiql/graphiql.css $dest

rm -Rf package.json node_modules/
