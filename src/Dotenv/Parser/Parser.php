<?php

declare(strict_types=1);

namespace Neko\Framework\Dotenv\Parser;

use Neko\Framework\Dotenv\Exception\InvalidFileException;
use Neko\Framework\Dotenv\Util\Regex;
use Neko\Framework\Dotenv\ResultType\Result;
use Neko\Framework\Dotenv\ResultType\Success;

final class Parser implements ParserInterface
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
    public function parse(string $content)
    {
        return Regex::split("/(\r\n|\n|\r)/", $content)->mapError(static function (){
            return 'Could not split into separate lines.';
        })->flatMap(static function (array $lines) {
            return self::process(Lines::process($lines));
        })->mapError(static function (string $error) use ($content)  {
            throw new InvalidFileException(\sprintf('Failed to parse dotenv file. %s', $error));
        })->success()->get();
    }

    /**
     * Convert the raw entries into proper entries.
     *
     * @param string[] $entries
     *
     * @return \Neko\Framework\Dotenv\ResultType\Result<\Neko\Framework\Dotenv\Parser\Entry[],string>
     */
    private static function process(array $entries)
    {
        return \array_reduce($entries, static function (Result $result, string $raw) {
            return $result->flatMap(static function (array $entries) use ($raw) {
                return EntryParser::parse($raw)->map(static function (Entry $entry) use ($entries) {
                    return \array_merge($entries, [$entry]);
                });
            });
        }, Success::create([]));
    }
}
