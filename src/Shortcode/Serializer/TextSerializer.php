<?php
namespace Neko\Framework\Shortcode\Serializer;

use Neko\Framework\Shortcode\Parser\RegexParser;
use Neko\Framework\Shortcode\Shortcode\Shortcode;
use Neko\Framework\Shortcode\Shortcode\ShortcodeInterface;
use Neko\Framework\Shortcode\Syntax\Syntax;
use Neko\Framework\Shortcode\Syntax\SyntaxInterface;

/**
 * @author Tomasz Kowalczyk <tomasz@kowalczyk.cc>
 */
final class TextSerializer implements SerializerInterface
{
    /** @var SyntaxInterface */
    private $syntax;

    public function __construct(SyntaxInterface $syntax = null)
    {
        $this->syntax = $syntax ?: new Syntax();
    }

    /** @inheritDoc */
    public function serialize(ShortcodeInterface $shortcode)
    {
        $open = $this->syntax->getOpeningTag();
        $close = $this->syntax->getClosingTag();
        $marker = $this->syntax->getClosingTagMarker();

        $parameters = $this->serializeParameters($shortcode->getParameters());
        $bbCode = null !== $shortcode->getBbCode()
            ? $this->serializeValue($shortcode->getBbCode())
            : '';
        $return = $open.$shortcode->getName().$bbCode.$parameters;

        return null === $shortcode->getContent()
            ? $return.' '.$marker.$close
            : $return.$close.(string)$shortcode->getContent().$open.$marker.$shortcode->getName().$close;
    }

    /**
     * @psalm-param array<string,string|null> $parameters
     *
     * @return string
     */
    private function serializeParameters(array $parameters)
    {
        // unfortunately array_reduce() does not support keys
        $return = '';
        foreach ($parameters as $key => $value) {
            $return .= ' '.$key.$this->serializeValue($value);
        }

        return $return;
    }

    /**
     * @param string|null $value
     *
     * @return string
     */
    private function serializeValue($value)
    {
        if (null === $value) {
            return '';
        }

        $delimiter = $this->syntax->getParameterValueDelimiter();
        $separator = $this->syntax->getParameterValueSeparator();

        return $separator.(preg_match('/^\w+$/u', $value)
            ? $value
            : $delimiter.$value.$delimiter);
    }

    public function unserialize($text)
    {
        $parser = new RegexParser();

        $shortcodes = $parser->parse($text);

        if (empty($shortcodes)) {
            $msg = 'Failed to unserialize shortcode from text %s!';
            throw new \InvalidArgumentException(sprintf($msg, $text));
        }
        if (count($shortcodes) > 1) {
            $msg = 'Provided text %s contains more than one shortcode!';
            throw new \InvalidArgumentException(sprintf($msg, $text));
        }

        /** @var ShortcodeInterface $parsed */
        $parsed = array_shift($shortcodes);

        $name = $parsed->getName();
        $parameters = $parsed->getParameters();
        $content = $parsed->getContent();
        $bbCode = $parsed->getBbCode();

        return new Shortcode($name, $parameters, $content, $bbCode);
    }
}
