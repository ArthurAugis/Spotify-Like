<?php
namespace App\Tests\EventSubscriber;

use App\EventSubscriber\IpFilterSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Psr\Log\NullLogger;

class IpFilterSubscriberTest extends TestCase
{
    private function makeEvent(string $ip): RequestEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => $ip]);
        return new RequestEvent($kernel, $request, 1);
    }

    public function testWhitelistIsAllowed()
    {
        $params = new ParameterBag([
            'ip_filter.enabled' => true,
            'ip_filter.whitelist' => ['10.0.0.1'],
            'ip_filter.blacklist_file' => null,
            'ip_filter.auto_block.threshold' => 3,
            'ip_filter.auto_block.window_seconds' => 60,
            'ip_filter.auto_block.duration_seconds' => 300,
        ]);

        $cache = new ArrayAdapter();
        $logger = new NullLogger();

        $subscriber = new IpFilterSubscriber($params, $cache, $logger);

        $event = $this->makeEvent('10.0.0.1');
        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse(), 'Whitelisted IP should not receive a response (not blocked)');
    }

    public function testAutoBlockAfterThreshold()
    {
        $params = new ParameterBag([
            'ip_filter.enabled' => true,
            'ip_filter.whitelist' => [],
            'ip_filter.blacklist_file' => null,
            'ip_filter.auto_block.threshold' => 3,
            'ip_filter.auto_block.window_seconds' => 60,
            'ip_filter.auto_block.duration_seconds' => 300,
        ]);

        $cache = new ArrayAdapter();
        $logger = new NullLogger();
        $subscriber = new IpFilterSubscriber($params, $cache, $logger);

        $ip = '192.0.2.5';

        // send requests below threshold
        for ($i = 0; $i < 2; $i++) {
            $event = $this->makeEvent($ip);
            $subscriber->onKernelRequest($event);
            $this->assertNull($event->getResponse(), 'IP should not be blocked before threshold');
        }

        // third request should trigger auto-block and set response
        $event = $this->makeEvent($ip);
        $subscriber->onKernelRequest($event);
        $response = $event->getResponse();
        $this->assertNotNull($response, 'IP should be blocked at threshold');
        $this->assertEquals(403, $response->getStatusCode());

        // subsequent request should be blocked by cache
        $event2 = $this->makeEvent($ip);
        $subscriber->onKernelRequest($event2);
        $response2 = $event2->getResponse();
        $this->assertNotNull($response2, 'IP should remain blocked by temporary cache');
        $this->assertEquals(403, $response2->getStatusCode());
    }
}
