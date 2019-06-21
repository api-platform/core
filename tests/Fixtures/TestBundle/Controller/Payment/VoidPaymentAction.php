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

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Controller\Payment;

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Document\Payment as PaymentDocument;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Payment;

final class VoidPaymentAction
{
    public function __invoke($data)
    {
        if (!$data instanceof Payment && !$data instanceof PaymentDocument) {
            throw new \InvalidArgumentException();
        }
        $payment = $data;

        $payment->void();

        return $payment->getVoidPayment();
    }
}
