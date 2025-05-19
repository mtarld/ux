<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Symfony\UX\TwigComponent\EventListener\TwigComponentLoggerListener;
use Symfony\UX\TwigComponent\MountedComponent;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class TwigComponentLoggerListenerTest extends TestCase
{
    public function testLoggerStoreEvents(): void
    {
        $logger = new TwigComponentLoggerListener();
        $this->assertSame([], $logger->getEvents());

        $mounted = new MountedComponent('foo', new \stdClass(), new ComponentAttributes([], new EscaperRuntime()));
        $eventA = new PreRenderEvent($mounted, new ComponentMetadata(['template' => 'bar']), []);
        $logger->onPreRender($eventA);
        $eventB = new PostRenderEvent($mounted);
        $logger->onPostRender($eventB);

        $this->assertSame([$eventA, $eventB], array_column($logger->getEvents(), 0));
    }

    public function testLoggerReset(): void
    {
        $logger = new TwigComponentLoggerListener();
        $escaper = new EscaperRuntime();

        $logger->onPreRender(new PreRenderEvent(new MountedComponent('foo', new \stdClass(), new ComponentAttributes([], $escaper)), new ComponentMetadata(['template' => 'bar']), []));
        $this->assertNotSame([], $logger->getEvents());

        $logger->reset();
        $this->assertSame([], $logger->getEvents());
    }
}
