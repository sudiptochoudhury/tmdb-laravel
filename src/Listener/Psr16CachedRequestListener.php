<?php
namespace Tmdb\Laravel\Listener;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tmdb\Event\BeforeRequestEvent;
use Tmdb\Event\ResponseEvent;

class Psr16CachedRequestListener implements EventSubscriberInterface
{
    private CacheInterface $cache;
    private ?int $defaultTtl;
    private ResponseFactoryInterface $responseFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        CacheInterface $cache,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        ?int $defaultTtl = null
    )
    {
        $this->cache = $cache;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->defaultTtl = $defaultTtl;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeRequestEvent::class => 'onBeforeRequest',
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onBeforeRequest(BeforeRequestEvent $event): void
    {
        $cacheKey = $this->getCacheKey($event->getRequest());

        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            $response = $this->responseFactory->createResponse($cached['status']);

            foreach ($cached['headers'] as $name => $values) {
                foreach ($values as $value) {
                    $response = $response->withAddedHeader($name, $value);
                }
            }

            $stream = $this->streamFactory->createStream($cached['body']);
            $response = $response->withBody($stream);

            $event->setResponse($response);
            $event->stopPropagation();
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $cacheKey = $this->getCacheKey($event->getRequest());

        $ttl = $this->getTtlFromResponse($response) ?? $this->defaultTtl;

        $body = (string) $response->getBody();

        $cached = [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => $body,
        ];

        $this->cache->set($cacheKey, $cached, $ttl);
    }

    private function getCacheKey(RequestInterface $request): string
    {
        return ':tmdb:' . sha1((string) $request->getUri());
    }

    private function getTtlFromResponse(ResponseInterface $response): ?int
    {
        if ($response->hasHeader('Cache-Control') &&
            preg_match('/max-age=(\d+)/', $response->getHeaderLine('Cache-Control'), $m)) {
            return (int) $m[1];
        }

        if ($response->hasHeader('Expires')) {
            $expires = strtotime($response->getHeaderLine('Expires'));
            if ($expires !== false) {
                return $expires - time();
            }
        }

        return null;
    }
}
