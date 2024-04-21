# API Platform documentation

## Guides

A guide is a PHP executable file that will be transformed into documentation. It follows [Diataxis How-To Guides](https://diataxis.fr/how-to-guides/) practice which is a must read before writing a guide.

Read the "[How To Guide](./guides/how-to.php)" to understand how to write an API Platform guide.

Guides are transformed to Markdown using [php-documentation-generator](https://github.com/php-documentation-generator/php-documentation-generator) which is merely a version of [docco](https://ashkenas.com/docco/) in PHP adapted to output markdown. 

## WASM

Guides are executable in a browser environment and need to be preloaded using:

```
docker run -v $(pwd):/src -v $(pwd)/public/php-wasm:/public -w /public php-wasm python3 /emsdk/upstream/emscripten/tools/file_packager.py php-web.data --preload "/src" --js-output=php-web.data.js --no-node --exclude '*Tests*' '*features*' '*public*' '*/.*'
```

A build of [php-wasm](https://github.com/soyuka/php-wasm) is needed in the `public/php-wasm` directory to try it out.

## Local tests

First run `composer update`. 

Then, get the [`pdg-phpunit`](https://github.com/php-documentation-generator/php-documentation-generator/tags) binary that allows to run single-file test. 

Use `KERNEL_CLASS` and `PDG_AUTOLOAD` to run a guide:

```
APP_DEBUG=0 \
PDG_AUTOLOAD='vendor/autoload.php' \
KERNEL_CLASS='\ApiPlatform\Playground\Kernel' pdg-phpunit guides/doctrine-search-filter.php
```
