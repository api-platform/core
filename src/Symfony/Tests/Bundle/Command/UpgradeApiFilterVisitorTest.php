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

namespace ApiPlatform\Symfony\Tests\Bundle\Command;

use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterParameter;
use ApiPlatform\Symfony\Bundle\Command\Upgrade\UpgradeApiFilterVisitor;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit\Framework\TestCase;

final class UpgradeApiFilterVisitorTest extends TestCase
{
    private function transform(string $code, UpgradeApiFilterVisitor $visitor): string
    {
        $parser = (new ParserFactory())->createForHostVersion();
        $oldStmts = $parser->parse($code);
        $oldTokens = $parser->getTokens();

        $newStmts = (new NodeTraverser(new CloningVisitor()))->traverse($oldStmts);
        $newStmts = (new NodeTraverser($visitor))->traverse($newStmts);

        return (new Standard())->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }

    public function testBooleanFilterBecomesExactFilterQueryParameter(): void
    {
        $before = <<<'PHP'
<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiFilter(BooleanFilter::class)]
#[ApiResource]
#[ORM\Entity]
class ConvertedBoolean
{
    public $nameConverted;
}
PHP;

        // Raw transform output: import ordering and inline arrays are normalized by a
        // php-cs-fixer post-step in the command, not by the visitor itself.
        $after = <<<'PHP'
<?php

declare(strict_types=1);

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\QueryParameter;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(parameters: ['nameConverted' => new QueryParameter(filter: new ExactFilter(), nativeType: new BuiltinType(TypeIdentifier::BOOL), castToNativeType: true)])]
#[ORM\Entity]
class ConvertedBoolean
{
    public $nameConverted;
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedBoolean', [
            new UpgradeApiFilterParameter(
                key: 'nameConverted',
                filterClass: 'ApiPlatform\Doctrine\Orm\Filter\ExactFilter',
                nativeType: 'bool',
                castToNativeType: true,
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }

    public function testCustomServiceFilterKeepsConstructorArguments(): void
    {
        $before = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Serializer\Filter\GroupFilter;

#[ApiFilter(GroupFilter::class, arguments: ['parameterName' => 'foobargroups'])]
#[ApiResource]
class DummyCar
{
    public $id;
}
PHP;

        $after = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Serializer\Filter\GroupFilter;

#[ApiResource(parameters: ['foobargroups' => new QueryParameter(filter: new GroupFilter(parameterName: 'foobargroups'))])]
class DummyCar
{
    public $id;
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar', [
            new UpgradeApiFilterParameter(
                key: 'foobargroups',
                filterClass: 'ApiPlatform\Serializer\Filter\GroupFilter',
                arguments: ['parameterName' => 'foobargroups'],
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }

    public function testCustomServiceFilterIsWrappedAsIs(): void
    {
        $before = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5648;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\CustomFilter;

#[ApiFilter(CustomFilter::class)]
#[ApiResource]
class DummyResource
{
    public $id;
}
PHP;

        $after = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5648;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Tests\Fixtures\TestBundle\Filter\CustomFilter;

#[ApiResource(parameters: ['id' => new QueryParameter(filter: new CustomFilter())])]
class DummyResource
{
    public $id;
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue5648\DummyResource', [
            new UpgradeApiFilterParameter(
                key: 'id',
                filterClass: 'ApiPlatform\Tests\Fixtures\TestBundle\Filter\CustomFilter',
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }

    public function testPropertyLevelApiFilterIsStripped(): void
    {
        $before = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
class DummyCarColor
{
    #[ApiFilter(SearchFilter::class)]
    private string $prop = '';
}
PHP;

        $after = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(parameters: ['prop' => new QueryParameter(filter: new ExactFilter())])]
class DummyCarColor
{
    private string $prop = '';
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCarColor', [
            new UpgradeApiFilterParameter(
                key: 'prop',
                filterClass: 'ApiPlatform\Doctrine\Orm\Filter\ExactFilter',
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }

    public function testCaseSensitiveSearchFilterEmitsConstructorArgument(): void
    {
        $before = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
#[ApiResource]
class DummyCar
{
    public $name;
}
PHP;

        $after = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(parameters: ['name' => new QueryParameter(filter: new PartialSearchFilter(caseSensitive: true))])]
class DummyCar
{
    public $name;
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyCar', [
            new UpgradeApiFilterParameter(
                key: 'name',
                filterClass: 'ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter',
                caseSensitive: true,
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }

    public function testDateFilterEmitsFilterContextConstant(): void
    {
        $before = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiFilter(DateFilter::class, properties: ['dateIncludeNullAfter' => DateFilter::INCLUDE_NULL_AFTER])]
#[ApiResource]
class DummyDate
{
    public $dateIncludeNullAfter;
}
PHP;

        $after = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(parameters: ['dateIncludeNullAfter' => new QueryParameter(filter: new DateFilter(), filterContext: DateFilter::INCLUDE_NULL_AFTER)])]
class DummyDate
{
    public $dateIncludeNullAfter;
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\Entity\DummyDate', [
            new UpgradeApiFilterParameter(
                key: 'dateIncludeNullAfter',
                filterClass: 'ApiPlatform\Doctrine\Orm\Filter\DateFilter',
                filterContext: 'include_null_after',
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }

    public function testSurvivingFilterKeepsClassWithExplicitProperty(): void
    {
        $before = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiFilter(ExistsFilter::class, properties: ['nameConverted'])]
#[ApiResource]
class ConvertedString
{
    public $nameConverted;
}
PHP;

        $after = <<<'PHP'
<?php

namespace ApiPlatform\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(parameters: ['nameConverted' => new QueryParameter(filter: new ExistsFilter())])]
class ConvertedString
{
    public $nameConverted;
}
PHP;

        $visitor = new UpgradeApiFilterVisitor('ApiPlatform\Tests\Fixtures\TestBundle\Entity\ConvertedString', [
            new UpgradeApiFilterParameter(
                key: 'nameConverted',
                filterClass: 'ApiPlatform\Doctrine\Orm\Filter\ExistsFilter',
            ),
        ]);

        $this->assertSame($after, $this->transform($before, $visitor));
    }
}
