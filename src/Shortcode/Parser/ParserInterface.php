<?php
namespace Neko\Framework\Shortcode\Parser;

use Neko\Framework\Shortcode\Shortcode\ParsedShortcodeInterface;

/**
 * @author Tomasz Kowalczyk <tomasz@kowalczyk.cc>
 */
interface ParserInterface
{
    /**
     * Parse single shortcode match into object
     *
     * @param string $text
     *
     * @return ParsedShortcodeInterface[]
     */
    public function parse($text);
}
