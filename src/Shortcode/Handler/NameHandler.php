<?php
namespace Neko\Framework\Shortcode\Handler;

use Neko\Framework\Shortcode\Shortcode\ShortcodeInterface;

/**
 * @author Tomasz Kowalczyk <tomasz@kowalczyk.cc>
 */
final class NameHandler
{
    /**
     * [name /]
     * [name]content is ignored[/name]
     *
     * @param ShortcodeInterface $shortcode
     *
     * @return string
     */
    public function __invoke(ShortcodeInterface $shortcode)
    {
        return $shortcode->getName();
    }
}
