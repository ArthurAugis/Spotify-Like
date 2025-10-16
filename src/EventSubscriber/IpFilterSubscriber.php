<?php
namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * IP filter subscriber (whitelist + auto-block)
 * - Use `ip_filter.*` parameters to configure behaviour.
 * - Uses PSR-6 cache (inject cache.app) for counters and blocked flags.
 */
final class IpFilterSubscriber implements EventSubscriberInterface
{
    private ParameterBagInterface $params;
    private CacheItemPoolInterface $cache;
    private ?LoggerInterface $logger;

    public function __construct(ParameterBagInterface $params, CacheItemPoolInterface $cache, ?LoggerInterface $logger = null)
    {
        $this->params = $params;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 255]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $settings = [
            'enabled' => $this->params->get('ip_filter.enabled'),
            'whitelist' => $this->params->get('ip_filter.whitelist'),
            'blacklist_file' => $this->params->get('ip_filter.blacklist_file'),
            'threshold' => $this->params->get('ip_filter.auto_block.threshold'),
            'window' => $this->params->get('ip_filter.auto_block.window_seconds'),
            'duration' => $this->params->get('ip_filter.auto_block.duration_seconds'),
        ];

        if (!$settings['enabled']) {
            return;
        }

        $request = $event->getRequest();
        $clientIp = $request->getClientIp();
        if (!$clientIp) {
            return;
        }

        // Whitelist
        if (in_array($clientIp, $settings['whitelist'], true)) {
            return;
        }

        // Blacklist file check (fast, only if file exists)
        $blacklistFile = $settings['blacklist_file'] ?? null;
        if ($blacklistFile && is_readable($blacklistFile)) {
            $lines = @file($blacklistFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines) && in_array($clientIp, $lines, true)) {
                $this->deny($event, 'IP blocked (blacklist)');
                return;
            }
        }

        // Check cache for temporary block
        $blockedKey = 'ip_blocked_' . md5($clientIp);
        $blockedItem = $this->cache->getItem($blockedKey);
        if ($blockedItem->isHit()) {
            $this->log('info', sprintf('IP %s served: blocked by cache', $clientIp));
            $this->deny($event, 'IP temporarily blocked');
            return;
        }

        // Increment counter and evaluate threshold
        $counterKey = 'ip_count_' . md5($clientIp);
        $counterItem = $this->cache->getItem($counterKey);
        $count = $counterItem->isHit() ? (int)$counterItem->get() : 0;
        $count++;
        $counterItem->set($count);
        $counterItem->expiresAfter((int)$settings['window']);
        $this->cache->save($counterItem);

        if ($count >= (int)$settings['threshold']) {
            // mark blocked for duration
            $blockedItem->set(true);
            $blockedItem->expiresAfter((int)$settings['duration']);
            $this->cache->save($blockedItem);

            // persist to blacklist file for longer-term blocks
            if ($blacklistFile) {
                @file_put_contents($blacklistFile, $clientIp . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            $this->log('warning', sprintf('IP %s auto-blocked after %d requests', $clientIp, $count));
            $this->deny($event, 'IP auto-blocked');
            return;
        }
    }

    private function deny(RequestEvent $event, string $message): void
    {
        $response = new Response($message, Response::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }

    private function log(string $level, string $message): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message);
        }
    }
}
