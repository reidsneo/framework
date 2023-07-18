<?php
namespace Neko\Framework\Shortcode\Handler;

use Neko\Framework\Shortcode\Shortcode\ShortcodeInterface;

/**
 * @author Tomasz Kowalczyk <tomasz@kowalczyk.cc>
 */
final class NullHandler
{
    /**
     * Special shortcode to discard any input and return empty text
     *
     * @param ShortcodeInterface $shortcode
     *
     * @return null
     */
    public function __invoke(ShortcodeInterface $shortcode)
    {
        return null;
    }
}
