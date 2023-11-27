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

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity\Issue5998;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

class SaveProduct
{
    /**
     * @var Collection<int, ProductCode>
     */
    #[Groups(['product:write'])]
    private Collection $codes;

    public function __construct()
    {
        $this->codes = new ArrayCollection();
    }

    /**
     * @return Collection<int, ProductCode>
     */
    public function getCodes(): Collection
    {
        return $this->codes;
    }

    public function addCode(ProductCode $code): void
    {
        if (!$this->codes->contains($code)) {
            $this->codes->add($code);
        }
    }

    public function removeCode(ProductCode $code): void
    {
        if ($this->codes->contains($code)) {
            $this->codes->removeElement($code);
        }
    }
}
