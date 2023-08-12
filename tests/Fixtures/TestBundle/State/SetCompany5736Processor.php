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

namespace ApiPlatform\Tests\Fixtures\TestBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5736Aerendir\Company;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5736Aerendir\CompanyAwareInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5736Aerendir\CompanyRepository;

class SetCompany5736Processor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly CompanyRepository $companyRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (false === $data instanceof CompanyAwareInterface) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        if (false === array_key_exists(Company::API_ID_PLACEHOLDER, $uriVariables)) {
            throw new \LogicException(sprintf('The uri variable "%1$s" doesn\'t exist. Please, set "uriVariables.%1$s" on entity "%2$s"', Company::API_ID_PLACEHOLDER, $data::class));
        }

        // Here we need to get the Account from the repository
        $account = $this->companyRepository->findOneByIdOrThrow($uriVariables[Company::API_ID_PLACEHOLDER]);
        $data->setCompany($account);

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
