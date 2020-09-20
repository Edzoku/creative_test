<?php
/**
 * 2019-06-28.
 */

declare(strict_types=1);

namespace App\Command;

use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class FetchDataCommand.
 */
class FetchDataCommand extends Command
{
    private const DEFAULT_IMPORT_LIMIT = 10;
    private const DEFAULT_SOURCE = "https://trailers.apple.com/trailers/home/rss/newtrailers.rss";

    /**
     * @var string
     */
    protected static $defaultName = 'fetch:trailers';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $source;

    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var integer
     */
    private $importLimit;

    /**
     * FetchDataCommand constructor.
     *
     * @param ClientInterface        $httpClient
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $em
     * @param string|null            $name
     */
    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, EntityManagerInterface $em, string $name = null)
    {
        parent::__construct($name);
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->doctrine = $em;
        $this->source = getenv("MOVIES_IMPORT_URL") ?: self::DEFAULT_SOURCE;
        $this->importLimit = (integer) getenv("MOVIES_IMPORT_LIMIT") ?: self::DEFAULT_IMPORT_LIMIT;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Fetch data from iTunes Movie Trailers')
            ->addArgument('source', InputArgument::OPTIONAL, 'Overwrite source')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info(sprintf('Start %s at %s', __CLASS__, (string) date_create()->format(DATE_ATOM)));
        $source = $this->source;
        if ($input->getArgument('source')) {
            $source = $input->getArgument('source');
        }
        if (!is_string($source) or $source === "") {
            throw new RuntimeException('The source parameter is not correct. Check /.env file or set argument "source"');
        }

        $io = new SymfonyStyle($input, $output);
        $io->title(sprintf('Fetch data from %s', $source));

        try {
            $response = $this->httpClient->sendRequest(new Request('GET', $source));
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException($e->getMessage());
        }
        if (($status = $response->getStatusCode()) !== 200) {
            throw new RuntimeException(sprintf('Response status is %d, expected %d', $status, 200));
        }
        $data = $response->getBody()->getContents();
        $this->processXml($data);

        $this->logger->info(sprintf('End %s at %s', __CLASS__, (string) date_create()->format(DATE_ATOM)));

        return 0;
    }

    /**
     * @param string $data
     *
     * @throws \Exception
     */
    protected function processXml(string $data): void
    {
        $xml = (new \SimpleXMLElement($data))->children();
//        $namespace = $xml->getNamespaces(true)['content'];
//        dd((string) $xml->channel->item[0]->children($namespace)->encoded);

        if (!property_exists($xml, 'channel')) {
            throw new RuntimeException('Could not find \'channel\' element in feed');
        }

        $importLimit = (integer) $this->importLimit;
        if (!is_int($importLimit) or $importLimit <= 0) {
            throw new RuntimeException('The limit parameter is not correct. Check /.env file.');
        }
        $startPosition = 0;
        $endPosition = $this->importLimit--;
        $data = $xml->channel->xpath("//item[position()>= $startPosition and not(position() > $endPosition)]");
        foreach ($data as $item) {
            $trailer = $this->getMovie((string) $item->title)
                ->setTitle((string) $item->title)
                ->setDescription((string) $item->description)
                ->setLink((string) $item->link)
                ->setPubDate($this->parseDate((string) $item->pubDate))
            ;

            $this->doctrine->persist($trailer);
        }

        $this->doctrine->flush();
    }

    /**
     * @param string $date
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    protected function parseDate(string $date): \DateTime
    {
        return new \DateTime($date);
    }

    /**
     * @param string $title
     *
     * @return Movie
     */
    protected function getMovie(string $title): Movie
    {
        $item = $this->doctrine->getRepository(Movie::class)->findOneBy(['title' => $title]);

        if ($item === null) {
            $this->logger->info('Create new Movie', ['title' => $title]);
            $item = new Movie();
        } else {
            $this->logger->info('Move found', ['title' => $title]);
        }

        if (!($item instanceof Movie)) {
            throw new RuntimeException('Wrong type!');
        }

        return $item;
    }
}
