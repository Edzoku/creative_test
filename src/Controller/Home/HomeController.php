<?php

namespace App\Controller\Home;

use App\Controller\BaseController;
use App\Entity\Movie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * Class HomeController
 *
 * @package App\Controller\Home
 */
class HomeController extends BaseController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     *
     * @throws HttpBadRequestException
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $data = $this->twig->render('home/index.html.twig', [
                'trailers' => $this->fetchData(10),
            ]);
        } catch (Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    /**
     * @param int $limit
     *
     * @return Collection
     */
    protected function fetchData(int $limit): Collection
    {
        $data = $this->em->getRepository(Movie::class)
            ->findBy([], ['pubDate' => 'DESC'], $limit);

        return new ArrayCollection($data);
    }
}

