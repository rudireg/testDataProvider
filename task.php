<?php
namespace src\Integration;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Interface DataProviderInterface
 * @package src\Integration
 */
interface DataProviderInterface
{
	public function get(array $request): array;
}

/**
 * Class DataProvider
 * @package src\Integration
 */
class DataProvider implements DataProviderInterface
{
	/**
	 * @var string
	 */
	private $host;
	/**
	 * @var string
	 */
	private $user;
	/**
	 * @var string
	 */
	private $password;

	/**
	 * @param $host
	 * @param $user
	 * @param $password
	 */
	public function __construct(string $host, string $user, string $password)
	{
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
	}

	/**
	 * @param array $request
	 * @return array
	 */
	public function get(array $request): array
	{
		// returns a response from external service
	}
}

Class Manager
{
	/**
	 * Manager constructor.
	 * @param DataProviderInterface $dataProvider
	 */
	public function __construct(DataProviderInterface $dataProvider)
	{
		$this->dataProvider = $dataProvider;
	}

	/**
	 * @param CacheItemPoolInterface $cache
	 */
	public function setCache(CacheItemPoolInterface $cache)
	{
		$this->cache = $cache;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * @param array $input
	 * @return array
	 */
	public function getResponse(array $input): array
	{
		try {
			if ($this->cache) {
				$cacheItem = $this->cache->getItem($this->getCacheKey($input));
				if ($cacheItem->isHit()) {
					return $cacheItem->get();
				}
			}
			$result = $this->dataProvider->get($input);
			if ($this->cache) {
				$cacheItem
					->set($result)
					->expiresAt(
						(new DateTime())->modify('+1 day')
					);
			}
			return $result;
		} catch (Exception $e) {
			if ($this->logger) {
				$this->logger->critical($e->getMessage(), $e->getCode());
			}
		}
	}

	/**
	 * @param array $input
	 * @return string
	 */
	public function getCacheKey(array $input)
	{
		return sha1(json_encode($input));
	}

	/**
	 * @var DataProviderInterface
	 */
	protected $dataProvider;
	/**
	 * @var null|Psr\Cache\CacheItemPoolInterface
	 */
	protected $cache = NULL;
	/**
	 * @var null|Psr\Log\LoggerInterface 
	 */
	protected $logger = NULL;
}
