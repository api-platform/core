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

namespace ApiPlatform\Core\Bridge\Symfony\Validator;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException;
use ApiPlatform\Core\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Validates an item using the Symfony validator component.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class Validator implements ValidatorInterface
{
    private $validator;
    private $container;
    private $decoder;

    public function __construct(SymfonyValidatorInterface $validator, ContainerInterface $container = null, DecoderInterface $decoder = null)
    {
        $this->validator = $validator;
        $this->container = $container;
        $this->decoder = $decoder;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($data, array $context = [])
    {
        if (null !== $validationGroups = $context['groups'] ?? null) {
            if (
                $this->container &&
                \is_string($validationGroups) &&
                $this->container->has($validationGroups) &&
                ($service = $this->container->get($validationGroups)) &&
                \is_callable($service)
            ) {
                $validationGroups = $service($data);
            } elseif (\is_callable($validationGroups)) {
                $validationGroups = $validationGroups($data);
            }

            if (!$validationGroups instanceof GroupSequence) {
                $validationGroups = (array) $validationGroups;
            }
        }

        $violations = $this->getViolations($data, $validationGroups);

        if (0 !== \count($violations)) {
            throw new ValidationException($violations);
        }
    }

    private function getViolations($data, $validationGroups): ConstraintViolationListInterface
    {
        if ($this->container && $this->decoder) {
            /** @var RequestStack $requestStack */
            $requestStack = $this->container->get('request_stack');
            $request = $requestStack->getCurrentRequest();

            if ($request && !$request->attributes->get('_graphql') && 'post' !== strtolower($request->getMethod())) {
                $ctx = $this->validator->startContext();
                $decoded = $this->decoder->decode($request->getContent(), $request->getRequestFormat());

                foreach ($decoded as $postKey => $postValue) {
                    $ctx->validateProperty($data, $postKey, $validationGroups);
                }

                return $ctx->getViolations();
            }
        }

        return $this->validator->validate($data, null, $validationGroups);
    }
}
