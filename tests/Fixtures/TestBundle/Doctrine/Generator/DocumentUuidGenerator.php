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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Doctrine\Generator;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Id\AbstractIdGenerator;
use Doctrine\ODM\MongoDB\Id\IdGenerator;

if (interface_exists(IdGenerator::class)) {
    class DocumentUuidGenerator implements IdGenerator
    {
        public function generate(DocumentManager $dm, object $document): Uuid
        {
            return new Uuid();
        }
    }
} else {
    // Support for old versions of Doctrine MongoDB
    class DocumentUuidGenerator extends AbstractIdGenerator
    {
        public function generate(DocumentManager $dm, $document): Uuid
        {
            return new Uuid();
        }
    }
}
