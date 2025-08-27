<?php
namespace Tmdb\Laravel\Listener;

use Psr\SimpleCache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tmdb\Event\BeforeRequestEvent;
use Tmdb\Event\ResponseEvent;

class Psr16CachedRequestListener implements EventSubscriberInterface
{
    private CacheInterface $cache;
    private ?int $defaultTtl;

    public function __construct(CacheInterface $cache, ?int $defaultTtl = null)
    {
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeRequestEvent::class => 'onBeforeRequest',
            ResponseEvent::class      => 'onResponse',
        ];
    }

    public function onBeforeRequest(BeforeRequestEvent $event): void
    {
        $request = $event->getRequest();
        $cacheKey = $this->getCacheKey($request);

        $cachedResponse = $this->cache->get($cacheKey);
        if ($cachedResponse instanceof \Psr\Http\Message\ResponseInterface) {
            $event->setResponse($cachedResponse);
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $cacheKey = $this->getCacheKey($request);

        $ttl = $this->getTtlFromResponse($response) ?? $this->defaultTtl;
        $this->cache->set($cacheKey, $response, $ttl);
    }

    private function getCacheKey(\Psr\Http\Message\RequestInterface $request): string
    {
        return sha1((string) $request->getUri());
    }

    private function getTtlFromResponse(\Psr\Http\Message\ResponseInterface $response): ?int
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
