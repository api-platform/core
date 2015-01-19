<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Tools\SchemaTool;

$loader = require __DIR__ . '/../../../vendor/autoload.php';
require 'AppKernel.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

// Create database schema
$kernel = new AppKernel('test', true);
$kernel->boot();

$manager = $kernel->getContainer()->get('doctrine')->getManager();

$st = new SchemaTool($manager);
$classes = $manager->getMetadataFactory()->getAllMetadata();
$st->dropSchema($classes);
$st->createSchema($classes);

return $loader;
