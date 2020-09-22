<?php

namespace App\Controller\Movie;

use App\Controller\BaseController;
use App\Entity\Movie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Class MovieController
 *
 * @package App\Controller\Movie
 */
class MovieController extends BaseController
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
        $movieId = (integer)$request->getQueryParams()['id'];
        if (empty((integer)$movieId) or $movieId <= 0) {
            throw new HttpNotFoundException($request);
        }

        $movie = $this->fetchData($movieId);
        if (empty($movie)) {
            throw new HttpNotFoundException($request);
        }

        try {
            $data = $this->twig->render('movie/index.html.twig', [
                'movie' => $movie,
            ]);
        } catch (Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage(), $e);
        }

        $response->getBody()->write($data);

        return $response;
    }

    /**
     * @param int $id
     *
     * @return Movie
     */
    protected function fetchData(int $id): Movie
    {
        return $this->em->getRepository(Movie::class)->find($id);
    }
}
