<?php
namespace Neko\Framework\Shortcode\EventHandler;

use Neko\Framework\Shortcode\Event\FilterShortcodesEvent;

/**
 * @author Tomasz Kowalczyk <tomasz@kowalczyk.cc>
 */
final class FilterRawEventHandler
{
    /** @var string[] */
    private $names = array();

    public function __construct(array $names)
    {
        foreach($names as $name) {
            if(false === is_string($name)) {
                throw new \InvalidArgumentException('Expected array of strings!');
            }

            $this->names[] = $name;
        }
    }

    public function __invoke(FilterShortcodesEvent $event)
    {
        $parent = $event->getParent();
        if($parent && \in_array($parent->getName(), $this->names, true)) {
            $event->setShortcodes(array());

            return;
        }
    }
}
