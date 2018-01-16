<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\Type;

date_default_timezone_set('UTC');

$loader = require __DIR__.'/../../../vendor/autoload.php';
require __DIR__.'/AppKernel.php';

AnnotationRegistry::registerLoader('class_exists');

if (!array_key_exists('date_immutable', Type::getTypesMap())) {
    // Hack to avoid Unknown column type "date_immutable" requested with doctrine < 2.6 when loading DummyImmutableDate
    Type::addType('date_immutable', DateType::class);
}

return $loader;
