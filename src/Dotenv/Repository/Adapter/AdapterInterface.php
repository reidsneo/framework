<?php

declare(strict_types=1);

namespace Neko\Framework\Dotenv\Repository\Adapter;

interface AdapterInterface extends ReaderInterface, WriterInterface
{
    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return \Neko\Framework\Dotenv\PhpOption\Option<\Neko\Framework\Dotenv\Repository\Adapter\AdapterInterface>
     */
    public static function create();
}
