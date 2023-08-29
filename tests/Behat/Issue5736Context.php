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

namespace ApiPlatform\Tests\Behat;

use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5736Aerendir\Company;
use Behat\Behat\Context\Context;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\SchemaManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;

/**
 * Defines context for Issue 5736.
 */
final class Issue5736Context implements Context
{
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    private ObjectManager $manager;
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    private ?SchemaTool $schemaTool;
    // @noRector \Rector\Php81\Rector\Property\ReadOnlyPropertyRector
    private ?SchemaManager $schemaManager;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
        $this->manager = $doctrine->getManager();
        $this->schemaTool = $this->manager instanceof EntityManagerInterface ? new SchemaTool($this->manager) : null;
        $this->schemaManager = $this->manager instanceof DocumentManager ? $this->manager->getSchemaManager() : null;
    }

    /**
     * @Given there are :nb companies
     */
    public function thereAreNbCompanies(int $nb): void
    {
        for ($i = 1; $i <= $nb; ++$i) {
            $company = new Company();
            $company->setName('Company #'.$i);

            $this->manager->persist($company);
        }

        $this->manager->flush();
    }
}
