<?php

namespace Tests;

use ByJG\RestServer\ErrorHandler;
use ByJG\RestServer\HttpRequest;
use ByJG\RestServer\HttpResponse;
use ByJG\RestServer\OutputProcessor\JsonOutputProcessor;
use ByJG\RestServer\Route\Route;
use ByJG\RestServer\Route\RouteList;
use ByJG\RestServer\Server;
use ByJG\RestServer\TrustedProxies;
use ByJG\RestServer\Writer\MemoryWriter;
use ByJG\Util\Uri;
use ByJG\WebRequest\Psr7\ServerRequest;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * HttpRequest::getRequestIp() believes a forwarding header only from the trusted proxies
 * (Server::withTrustedProxies()), and only for the addresses they added
 */
class TrustedProxiesTest extends TestCase
{
    #[Override]
    public function tearDown(): void
    {
        ErrorHandler::getInstance()->unregister();
    }

    private static function ip(array $server, ?TrustedProxies $trustedProxies): ?string
    {
        $request = new HttpRequest([], [], $server, [], []);
        $request->setTrustedProxies($trustedProxies);
        return $request->getRequestIp();
    }

    public static function clientIpProvider(): array
    {
        $proxies = new TrustedProxies(['10.0.0.0/8', '172.16.0.0/12', '192.168.1.10', '2001:db8::/32']);
        $forwarded = new TrustedProxies(['10.0.0.0/8', '2001:db8::/32'], TrustedProxies::FORWARDED);

        return [
            'no trusted proxies: the header is not read' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4'], null, '10.0.0.1'],
            'a connection not from a trusted proxy: its header is not read' => [
                ['REMOTE_ADDR' => '203.0.113.7', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4'], $proxies, '203.0.113.7'],
            'from a trusted proxy: the address it added' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'], $proxies, '198.51.100.20'],
            'the address the client wrote before the proxy\'s is not believed' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 198.51.100.20'], $proxies, '198.51.100.20'],
            'through two trusted proxies' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 198.51.100.20, 192.168.1.10'], $proxies, '198.51.100.20'],
            'every address a trusted proxy: the leftmost' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '10.1.1.1, 10.2.2.2'], $proxies, '10.1.1.1'],
            'an entry that is not an address ends the chain: the proxy that forwarded it' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => 'unknown, 10.2.2.2'], $proxies, '10.2.2.2'],
            'a single address, not a range' => [
                ['REMOTE_ADDR' => '192.168.1.10', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'], $proxies, '198.51.100.20'],
            'next to the single address: not trusted' => [
                ['REMOTE_ADDR' => '192.168.1.11', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'], $proxies, '192.168.1.11'],
            'a range not on a byte boundary: the last address in it' => [
                ['REMOTE_ADDR' => '172.31.255.255', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'], $proxies, '198.51.100.20'],
            'a range not on a byte boundary: the first address past it' => [
                ['REMOTE_ADDR' => '172.32.0.0', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'], $proxies, '172.32.0.0'],
            'an address with a port' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20:4711'], $proxies, '198.51.100.20'],
            'IPv6 proxy and client' => [
                ['REMOTE_ADDR' => '2001:db8::1', 'HTTP_X_FORWARDED_FOR' => '2001:4860::8888'], $proxies, '2001:4860::8888'],
            'IPv6 outside the range' => [
                ['REMOTE_ADDR' => '2001:db9::1', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20'], $proxies, '2001:db9::1'],
            'from a trusted proxy without the header' => [
                ['REMOTE_ADDR' => '10.0.0.1'], $proxies, '10.0.0.1'],
            'no REMOTE_ADDR (the CLI)' => [
                ['HTTP_X_FORWARDED_FOR' => '1.2.3.4'], $proxies, null],
            'Forwarded: the for= of each element' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_FORWARDED' => 'for=1.2.3.4, for=198.51.100.20;proto=https;by=10.0.0.1'], $forwarded, '198.51.100.20'],
            'Forwarded: a quoted IPv6 address with a port' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_FORWARDED' => 'For="[2001:4860::8888]:4711"'], $forwarded, '2001:4860::8888'],
            'Forwarded: an obfuscated identifier ends the chain' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_FORWARDED' => 'for=_hidden'], $forwarded, '10.0.0.1'],
            'Forwarded configured: X-Forwarded-For, which a client could send, is not read' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4'], $forwarded, '10.0.0.1'],
            'X-Forwarded-For configured: Forwarded is not read' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_FORWARDED' => 'for=1.2.3.4'], $proxies, '10.0.0.1'],
            'the other spoofable headers are not read' => [
                ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_CLIENT_IP' => '1.2.3.4', 'HTTP_CF_CONNECTING_IP' => '1.2.3.4', 'HTTP_X_FORWARDED' => '1.2.3.4'], $proxies, '10.0.0.1'],
        ];
    }

    #[DataProvider('clientIpProvider')]
    public function testClientIp(array $server, ?TrustedProxies $trustedProxies, ?string $expected): void
    {
        $this->assertSame($expected, self::ip($server, $trustedProxies));
    }

    public static function invalidProvider(): array
    {
        return [
            'not an address' => [['proxy.local'], TrustedProxies::X_FORWARDED_FOR],
            'IPv4 prefix past 32' => [['10.0.0.0/33'], TrustedProxies::X_FORWARDED_FOR],
            'IPv6 prefix past 128' => [['2001:db8::/129'], TrustedProxies::X_FORWARDED_FOR],
            'not a number' => [['10.0.0.0/x'], TrustedProxies::X_FORWARDED_FOR],
            'another header' => [['10.0.0.0/8'], 'X-Real-IP'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testRefusesAnInvalidConfiguration(array $proxies, string $header): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Server())->withTrustedProxies($proxies, $header);
    }

    /**
     * The Server gives its trusted proxies to the request it handles, also when the request object
     * was given before them
     */
    public function testTheServerAppliesItsTrustedProxies(): void
    {
        $routes = (new RouteList())->addRoute(Route::get('/ip')->withClosure(
            function (HttpResponse $response, HttpRequest $request) {
                $response->write(['ip' => $request->getRequestIp()]);
            }
        ));
        $request = (new ServerRequest(Uri::getInstance('http://localhost/ip'), ['REMOTE_ADDR' => '10.0.0.1']))
            ->withHeader('X-Forwarded-For', '1.2.3.4, 198.51.100.20');

        // Without trusted proxies, and with the proxy's range
        foreach ([[[], '10.0.0.1'], [['10.0.0.0/8'], '198.51.100.20']] as [$proxies, $expected]) {
            $writer = new MemoryWriter();
            (new Server())
                ->withDefaultOutputProcessor(JsonOutputProcessor::class)
                ->withWriter($writer)
                ->withRequestObject($request)
                ->withTrustedProxies($proxies)
                ->handle($routes, false);

            $this->assertEquals(['ip' => $expected], json_decode($writer->getData(), true), 'trusted: ' . implode(',', $proxies));
        }
    }
}
