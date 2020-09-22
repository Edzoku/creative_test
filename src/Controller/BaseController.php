<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Twig\Environment;

/**
 * Class BaseController
 *
 * @package App\Controller
 */
class BaseController
{
    /**
     * @var RouteCollectorInterface
     */
    protected $routeCollector;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * BaseController constructor.
     *
     * @param RouteCollectorInterface $routeCollector
     * @param Environment $twig
     * @param EntityManagerInterface $em
     */
    public function __construct(RouteCollectorInterface $routeCollector, Environment $twig, EntityManagerInterface $em)
    {
        $this->routeCollector = $routeCollector;
        $this->twig = $twig;
        $this->em = $em;
    }
}

