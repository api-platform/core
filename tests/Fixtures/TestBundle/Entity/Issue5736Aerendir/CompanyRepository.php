<?php

declare(strict_types=1);

/*
 * This file is part of Coommercio.
 *
 * Copyright (c) Adamo Aerendir Crespi <hello@aerendir.me>.
 *
 * This code is to consider private and non disclosable to anyone for whatever reason.
 * Every right on this code is reserved.
 *
 * For the full copyright and license information, please view the LICENSE file that
 * was distributed with this source code.
 */

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5736Aerendir;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Company|null findOneById(string $is)
 */
final class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Company::class);
    }

    public function findOneByIdOrThrow(int $id): Company
    {
        $company = $this->findOneById((string) $id);
        if ( ! $company instanceof Company) {
            throw new EntityNotFoundException(sprintf('The company with ID "%s" was not found.', $id));
        }

        return $company;
    }
}
