<?php

namespace RectorPrefix20220206\React\Socket;

use RectorPrefix20220206\React\Dns\Resolver\ResolverInterface;
use RectorPrefix20220206\React\Promise;
use RectorPrefix20220206\React\Promise\CancellablePromiseInterface;
final class DnsConnector implements \RectorPrefix20220206\React\Socket\ConnectorInterface
{
    private $connector;
    private $resolver;
    public function __construct(\RectorPrefix20220206\React\Socket\ConnectorInterface $connector, \RectorPrefix20220206\React\Dns\Resolver\ResolverInterface $resolver)
    {
        $this->connector = $connector;
        $this->resolver = $resolver;
    }
    public function connect($uri)
    {
        $original = $uri;
        if (\strpos($uri, '://') === \false) {
            $uri = 'tcp://' . $uri;
            $parts = \parse_url($uri);
            if (isset($parts['scheme'])) {
                unset($parts['scheme']);
            }
        } else {
            $parts = \parse_url($uri);
        }
        if (!$parts || !isset($parts['host'])) {
            return \RectorPrefix20220206\React\Promise\reject(new \InvalidArgumentException('Given URI "' . $original . '" is invalid (EINVAL)', \defined('SOCKET_EINVAL') ? \SOCKET_EINVAL : 22));
        }
        $host = \trim($parts['host'], '[]');
        $connector = $this->connector;
        // skip DNS lookup / URI manipulation if this URI already contains an IP
        if (@\inet_pton($host) !== \false) {
            return $connector->connect($original);
        }
        $promise = $this->resolver->resolve($host);
        $resolved = null;
        return new \RectorPrefix20220206\React\Promise\Promise(function ($resolve, $reject) use(&$promise, &$resolved, $uri, $connector, $host, $parts) {
            // resolve/reject with result of DNS lookup
            $promise->then(function ($ip) use(&$promise, &$resolved, $uri, $connector, $host, $parts) {
                $resolved = $ip;
                return $promise = $connector->connect(\RectorPrefix20220206\React\Socket\Connector::uri($parts, $host, $ip))->then(null, function (\Exception $e) use($uri) {
                    if ($e instanceof \RuntimeException) {
                        $message = \preg_replace('/^(Connection to [^ ]+)[&?]hostname=[^ &]+/', '$1', $e->getMessage());
                        $e = new \RuntimeException('Connection to ' . $uri . ' failed: ' . $message, $e->getCode(), $e);
                        // avoid garbage references by replacing all closures in call stack.
                        // what a lovely piece of code!
                        $r = new \ReflectionProperty('Exception', 'trace');
                        $r->setAccessible(\true);
                        $trace = $r->getValue($e);
                        // Exception trace arguments are not available on some PHP 7.4 installs
                        // @codeCoverageIgnoreStart
                        foreach ($trace as &$one) {
                            if (isset($one['args'])) {
                                foreach ($one['args'] as &$arg) {
                                    if ($arg instanceof \Closure) {
                                        $arg = 'Object(' . \get_class($arg) . ')';
                                    }
                                }
                            }
                        }
                        // @codeCoverageIgnoreEnd
                        $r->setValue($e, $trace);
                    }
                    throw $e;
                });
            }, function ($e) use($uri, $reject) {
                $reject(new \RuntimeException('Connection to ' . $uri . ' failed during DNS lookup: ' . $e->getMessage(), 0, $e));
            })->then($resolve, $reject);
        }, function ($_, $reject) use(&$promise, &$resolved, $uri) {
            // cancellation should reject connection attempt
            // reject DNS resolution with custom reason, otherwise rely on connection cancellation below
            if ($resolved === null) {
                $reject(new \RuntimeException('Connection to ' . $uri . ' cancelled during DNS lookup (ECONNABORTED)', \defined('SOCKET_ECONNABORTED') ? \SOCKET_ECONNABORTED : 103));
            }
            // (try to) cancel pending DNS lookup / connection attempt
            if ($promise instanceof \RectorPrefix20220206\React\Promise\CancellablePromiseInterface) {
                // overwrite callback arguments for PHP7+ only, so they do not show
                // up in the Exception trace and do not cause a possible cyclic reference.
                $_ = $reject = null;
                $promise->cancel();
                $promise = null;
            }
        });
    }
}
