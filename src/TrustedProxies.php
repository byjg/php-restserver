<?php

namespace ByJG\RestServer;

use InvalidArgumentException;

/**
 * The proxies in front of the application (see Server::withTrustedProxies()). Any client can send a
 * forwarding header, so it is believed only when the connection comes from one of these proxies, and
 * only for the addresses they added: the header is read from the right, and the first address that is
 * not a trusted proxy is the client.
 */
final class TrustedProxies
{
    /** X-Forwarded-For: client, proxy1, proxy2 (HAProxy, nginx, AWS ELB, Cloudflare...) */
    public const string X_FORWARDED_FOR = 'X-Forwarded-For';

    /** Forwarded: for=client, for=proxy1 (RFC 7239) */
    public const string FORWARDED = 'Forwarded';

    /** @var list<array{string, int}> each range as its packed address and its prefix length */
    private array $ranges = [];

    /**
     * @param string[] $proxies IPv4 or IPv6 addresses or CIDR ranges, e.g. '10.0.0.0/8', '::1'
     * @param string $header the forwarding header these proxies write: X_FORWARDED_FOR or FORWARDED.
     *        Only that header is read: a client could send the other one, which no proxy would correct.
     */
    public function __construct(array $proxies, private readonly string $header = self::X_FORWARDED_FOR)
    {
        if ($header !== self::X_FORWARDED_FOR && $header !== self::FORWARDED) {
            throw new InvalidArgumentException("The forwarding header must be X-Forwarded-For or Forwarded, not '$header'");
        }

        foreach ($proxies as $proxy) {
            $parts = explode('/', $proxy, 2);
            $packed = @inet_pton($parts[0]);
            if ($packed === false) {
                throw new InvalidArgumentException("'$proxy' is not an IP address or a CIDR range");
            }
            $maxPrefix = strlen($packed) * 8;
            $prefix = $parts[1] ?? (string)$maxPrefix;
            if (!ctype_digit($prefix) || (int)$prefix > $maxPrefix) {
                throw new InvalidArgumentException("'$proxy' is not an IP address or a CIDR range");
            }
            $this->ranges[] = [$packed, (int)$prefix];
        }
    }

    /**
     * The client's address. $remoteAddr is the connection's address; unless it is a trusted proxy, it is
     * the client. Otherwise the forwarding header is read from the right while the addresses are trusted
     * proxies; an entry that is not an address ("unknown", an obfuscated identifier) ends it, and the
     * proxy that forwarded it is returned.
     */
    public function clientIp(string $remoteAddr, ?string $headerValue): string
    {
        if ($headerValue === null || !$this->isTrusted($remoteAddr)) {
            return $remoteAddr;
        }

        $client = $remoteAddr;
        foreach (array_reverse($this->addresses($headerValue)) as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP) === false) {
                break;
            }
            $client = $address;
            if (!$this->isTrusted($address)) {
                break;
            }
        }
        return $client;
    }

    /**
     * The key of the forwarding header in the server params
     */
    public function serverKey(): string
    {
        return $this->header === self::FORWARDED ? 'HTTP_FORWARDED' : 'HTTP_X_FORWARDED_FOR';
    }

    public function isTrusted(string $address): bool
    {
        $packed = @inet_pton($address);
        if ($packed === false) {
            return false;
        }
        foreach ($this->ranges as [$network, $prefix]) {
            if (strlen($network) !== strlen($packed)) {
                continue;
            }
            $bytes = intdiv($prefix, 8);
            if (substr($packed, 0, $bytes) !== substr($network, 0, $bytes)) {
                continue;
            }
            $bits = $prefix % 8;
            $mask = (0xFF << (8 - $bits)) & 0xFF;
            if ($bits === 0 || (ord($packed[$bytes]) & $mask) === (ord($network[$bytes]) & $mask)) {
                return true;
            }
        }
        return false;
    }

    /**
     * The addresses of the header, left to right, without quotes, brackets or ports
     *
     * @return string[]
     */
    private function addresses(string $headerValue): array
    {
        $addresses = [];
        foreach (explode(',', $headerValue) as $element) {
            $address = trim($element);
            if ($this->header === self::FORWARDED) {
                // for=192.0.2.60;proto=http;by=203.0.113.43 (an element without for= is an unknown hop)
                $address = '';
                foreach (explode(';', $element) as $pair) {
                    $nameValue = explode('=', trim($pair), 2);
                    if (strtolower($nameValue[0]) === 'for' && isset($nameValue[1])) {
                        $address = trim($nameValue[1], " \t\"");
                    }
                }
            }
            $addresses[] = self::withoutPort($address);
        }
        return $addresses;
    }

    private static function withoutPort(string $address): string
    {
        // [2001:db8::1]:4711 or [2001:db8::1]
        if (str_starts_with($address, '[')) {
            $end = strpos($address, ']');
            return $end === false ? $address : substr($address, 1, $end - 1);
        }
        // 192.0.2.43:47011; an IPv6 address without brackets has more than one ':'
        if (substr_count($address, ':') === 1) {
            return explode(':', $address)[0];
        }
        return $address;
    }
}
