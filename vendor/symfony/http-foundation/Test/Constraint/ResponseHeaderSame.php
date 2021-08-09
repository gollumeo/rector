<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace RectorPrefix20210809\Symfony\Component\HttpFoundation\Test\Constraint;

use RectorPrefix20210809\PHPUnit\Framework\Constraint\Constraint;
use RectorPrefix20210809\Symfony\Component\HttpFoundation\Response;
final class ResponseHeaderSame extends \RectorPrefix20210809\PHPUnit\Framework\Constraint\Constraint
{
    private $headerName;
    private $expectedValue;
    public function __construct(string $headerName, string $expectedValue)
    {
        $this->headerName = $headerName;
        $this->expectedValue = $expectedValue;
    }
    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return \sprintf('has header "%s" with value "%s"', $this->headerName, $this->expectedValue);
    }
    /**
     * @param Response $response
     *
     * {@inheritdoc}
     */
    protected function matches($response) : bool
    {
        return $this->expectedValue === $response->headers->get($this->headerName, null);
    }
    /**
     * @param Response $response
     *
     * {@inheritdoc}
     */
    protected function failureDescription($response) : string
    {
        return 'the Response ' . $this->toString();
    }
}
