# API Platform documentation

## Guides

A guide is a PHP executable file that will be transformed into documentation. It follows [Diataxis How-To Guides](https://diataxis.fr/how-to-guides/) practice which is a must read before writing a guide.

Guides are transformed to Markdown using [php-documentation-generator](https://github.com/php-documentation-generator/php-documentation-generator) which is merely a version of [docco](https://ashkenas.com/docco/) in PHP adapted to output markdown. 

## WASM

Guides are executable in a browser environment and need to be preloaded using:

```
docker run -v $(pwd):/src -v $(pwd)/public/php-wasm:/public -w /public php-wasm python3 /emsdk/upstream/emscripten/tools/file_packager.py php-web.data --preload "/src" --js-output=php-web.data.js --no-node --exclude '*Tests*' '*features*' '*public*' '*/.*'
```

A build of [php-wasm](https://github.com/soyuka/php-wasm) is needed in the `public/php-wasm` directory to try it out.
