<?php

declare(strict_types=1);

namespace Neko\Framework\Dotenv\Repository\Adapter;

interface ReaderInterface
{
    /**
     * Read an environment variable, if it exists.
     *
     * @param string $name
     *
     * @return \Neko\Framework\Dotenv\PhpOption\Option<string>
     */
    public function read(string $name);
}
