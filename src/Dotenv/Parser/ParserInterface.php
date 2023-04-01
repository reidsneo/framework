<?php

declare(strict_types=1);

namespace Neko\Framework\Dotenv\Parser;

interface ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     *
     * @throws \Neko\Framework\Dotenv\Exception\InvalidFileException
     *
     * @return \Neko\Framework\Dotenv\Parser\Entry[]
     */
    public function parse(string $content);
}
