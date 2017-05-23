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
rm -Rf package.json node_modules/
