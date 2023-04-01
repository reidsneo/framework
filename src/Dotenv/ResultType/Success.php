<?php

declare(strict_types=1);

/*
 * This file is part of Result Type.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Neko\Framework\Dotenv\ResultType;

use Neko\Framework\Dotenv\PhpOption\None;
use Neko\Framework\Dotenv\PhpOption\Some;

/**
 * @template T
 * @template E
 * @extends \Neko\Framework\Dotenv\ResultType\Result<T,E>
 */
final class Success extends Result
{
    /**
     * @var T
     */
    private $value;

    /**
     * Internal constructor for a success value.
     *
     * @param T $value
     *
     * @return void
     */
    private function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Create a new error value.
     *
     * @template S
     *
     * @param S $value
     *
     * @return \Neko\Framework\Dotenv\ResultType\Result<S,E>
     */
    public static function create($value)
    {
        return new self($value);
    }

    /**
     * Get the success option value.
     *
     * @return \Neko\Framework\Dotenv\PhpOption\Option<T>
     */
    public function success()
    {
        return Some::create($this->value);
    }

    /**
     * Map over the success value.
     *
     * @template S
     *
     * @param callable(T):S $f
     *
     * @return \Neko\Framework\Dotenv\ResultType\Result<S,E>
     */
    public function map(callable $f)
    {
        return self::create($f($this->value));
    }

    /**
     * Flat map over the success value.
     *
     * @template S
     * @template F
     *
     * @param callable(T):\Neko\Framework\Dotenv\ResultType\Result<S,F> $f
     *
     * @return \Neko\Framework\Dotenv\ResultType\Result<S,F>
     */
    public function flatMap(callable $f)
    {
        return $f($this->value);
    }

    /**
     * Get the error option value.
     *
     * @return \Neko\Framework\Dotenv\PhpOption\Option<E>
     */
    public function error()
    {
        return None::create();
    }

    /**
     * Map over the error value.
     *
     * @template F
     *
     * @param callable(E):F $f
     *
     * @return \Neko\Framework\Dotenv\ResultType\Result<T,F>
     */
    public function mapError(callable $f)
    {
        return self::create($this->value);
    }
}
