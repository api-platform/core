<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Action;

use Symfony\Component\HttpFoundation\Response;

/**
 * Displays the documentation in Swagger UI.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class SwaggerUiAction
{
    private $twig;
    private $title;
    private $description;

    public function __construct(\Twig_Environment $twig, string $title, string $description)
    {
        $this->twig = $twig;
        $this->title = $title;
        $this->description = $description;
    }

    public function __invoke()
    {
        return new Response($this->twig->render(
            'ApiPlatformBundle:SwaggerUi:index.html.twig',
            ['title' => $this->title, 'description' => $this->description]
        ));
    }
}
