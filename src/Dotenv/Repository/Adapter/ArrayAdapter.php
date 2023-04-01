<?php

declare(strict_types=1);

namespace Neko\Framework\Dotenv\Repository\Adapter;

use Neko\Framework\Dotenv\PhpOption\Option;
use Neko\Framework\Dotenv\PhpOption\Some;

final class ArrayAdapter implements AdapterInterface
{
    /**
     * The variables and their values.
     *
     * @var array<string,string>
     */
    private $variables;

    /**
     * Create a new array adapter instance.
     *
     * @return void
     */
    private function __construct()
    {
        $this->variables = [];
    }

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \Neko\Framework\Dotenv\PhpOption\Option<\Neko\Framework\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create()
    {
        /** @var \Neko\Framework\Dotenv\PhpOption\Option<AdapterInterface> */
        return Some::create(new self());
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \Neko\Framework\Dotenv\PhpOption\Option<string>
     */
    public function read(string $name)
    {
        return Option::fromArraysValue($this->variables, $name);
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param string $name
     * @param string $value
     *
     * @return bool
     */
    public function write(string $name, string $value)
    {
        $this->variables[$name] = $value;

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param string $name
     *
     * @return bool
     */
    public function delete(string $name)
    {
        unset($this->variables[$name]);

        return true;
    }
}
