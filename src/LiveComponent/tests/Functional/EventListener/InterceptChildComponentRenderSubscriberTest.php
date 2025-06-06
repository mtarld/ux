<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;

final class InterceptChildComponentRenderSubscriberTest extends KernelTestCase
{
    use HasBrowser;
    use LiveComponentTestHelper;

    // The actual ids and fingerprints that will happen inside todo_list.html.twig
    // if you pass in 3 "items" with data that matches what's used by default
    // in buildUrlForTodoListComponent
    private static array $actualTodoItemFingerprints = [
        AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX.'0' => 'dSQ4+SgsF3QWeK4ngSOM1ROM50s6N1kWAK6bYW2JjZU=',
        AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX_EMBEDDED.'0' => 'sMvvf7q68tz/Cuk+vDeisDiq+7YPWzT+WZFzI37dGHY=',
        AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX.'1' => '8AooEz36WYQyxj54BCaDm/jKbcdDdPDLaNO4/49bcQk=',
    ];

    public function testItAllowsFullChildRenderOnMissingFingerprints(): void
    {
        $this->browser()
            ->visit($this->buildUrlForTodoListComponent([]))
            ->assertSuccessful()
            ->assertHtml()
            ->assertElementCount('ul li', 3)
            ->assertContains('todo item: wake up')
            ->assertContains('todo item: high five a friend')
        ;
    }

    public function testItRendersEmptyElementOnMatchingFingerprintBasic(): void
    {
        $this->browser()
            ->visit($this->buildUrlForTodoListComponent(self::$actualTodoItemFingerprints))
            ->assertSuccessful()
            ->assertHtml()
            // we correctly know to render an li
            ->assertElementCount('ul li', 3)
            ->assertNotContains('todo item')
        ;
    }

    public function testItRendersEmptyElementOnMatchingFingerprintWithCustomDataLiveId(): void
    {
        $fingerPrintsWithCustomLiveId = [];
        foreach (array_values(self::$actualTodoItemFingerprints) as $key => $fingerprintValue) {
            // creating fingerprints to match todo_list.html.twig
            $fingerPrintsWithCustomLiveId['todo-item-'.$key + 1] = $fingerprintValue;
        }

        $this->browser()
            ->visit($this->buildUrlForTodoListComponent($fingerPrintsWithCustomLiveId, true))
            ->assertSuccessful()
            ->assertHtml()
            ->assertElementCount('ul li', 3)
            ->assertNotContains('todo item')
        ;
    }

    public function testItRendersNewPropWhenFingerprintDoesNotMatch(): void
    {
        $fingerprints = self::$actualTodoItemFingerprints;
        $fingerprints[AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX_EMBEDDED.'0'] = 'wrong fingerprint';
        $fingerprints[AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX.'1'] = 'wrong fingerprint';

        $this->browser()
            ->visit($this->buildUrlForTodoListComponent($fingerprints))
            ->assertSuccessful()
            ->assertHtml()
            ->assertElementCount('ul li', 3)
            ->assertNotContains('todo item')
            ->use(function (AbstractBrowser $browser) {
                $content = $browser->getResponse()->getContent();

                // 1st renders empty
                // fingerprint changed in 2nd & 3rd, so it renders new fingerprint + props
                $this->assertStringContainsString(\sprintf(
                    '<li id="%s0" data-live-preserve></li>',
                    AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX
                ), $content);
                // new props are JUST the "textLength" + a checksum for it specifically
                $this->assertStringContainsString(\sprintf(
                    '<li data-live-name-value="todo_item" id="%s0" data-live-fingerprint-value="sMvvf7q68tz/Cuk+vDeisDiq+7YPWzT+WZFzI37dGHY=" data-live-props-updated-from-parent-value="{&quot;textLength&quot;:18,&quot;@checksum&quot;:&quot;LGxXa9fMKrJ6PelkUPfqmdwnfkk+LORgoJHXyPpS3Pw=&quot;}" data-live-preserve></li>',
                    AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX_EMBEDDED
                ), $content);
                $this->assertStringContainsString(\sprintf(
                    '<li data-live-name-value="todo_item" id="%s1" data-live-fingerprint-value="8AooEz36WYQyxj54BCaDm/jKbcdDdPDLaNO4/49bcQk=" data-live-props-updated-from-parent-value="{&quot;textLength&quot;:10,&quot;@checksum&quot;:&quot;BXUk7q6LI\/6Qx3c62Xiui6287YndmoK3QmVq6e5mcGk=&quot;}" data-live-preserve></li>',
                    AddLiveAttributesSubscriberTest::TODO_ITEM_DETERMINISTIC_PREFIX
                ), $content);
            });
    }

    public function testItUsesKeysToRenderChildrenLiveIds(): void
    {
        $fingerprintValues = array_values(self::$actualTodoItemFingerprints);
        $fingerprints = [];
        $i = 0;
        foreach ($fingerprintValues as $key => $fingerprintValue) {
            $prefix = 0 !== $i++ % 2 ? 'live-4172682817-the-key' : 'live-521026374-the-key';
            // creating fingerprints keys to match todo_list_with_keys.html.twig
            $fingerprints[$prefix.$key] = $fingerprintValue;
        }
        $fingerprints['live-4172682817-the-key1'] = 'wrong fingerprint';

        $urlSimple = $this->doBuildUrlForComponent('todo_list_with_keys', []);
        $urlWithChangedFingerprints = $this->doBuildUrlForComponent('todo_list_with_keys', $fingerprints);

        $templateName = 'components/todo_list_with_keys.html.twig';
        $obscuredName = '4726a6e348c4496094a4d3bf3693078e';
        $this->addTemplateMap($obscuredName, $templateName);

        $this->browser()
            ->visit($urlSimple)
            ->assertSuccessful()
            ->assertHtml()
            ->assertElementCount('ul li', 3)
            // check for the id we expect based on the key
            ->assertContains('id="live-521026374-the-key0"')
            ->assertNotContains('key="the-key0"')
            ->visit($urlWithChangedFingerprints)
            ->assertContains('<li id="live-521026374-the-key0" data-live-preserve></li>')
            // this one is changed, so it renders a full element
            ->assertContains('<li data-live-name-value="todo_item" id="live-4172682817-the-key1"')
        ;
    }

    private function buildUrlForTodoListComponent(array $childrenFingerprints, bool $includeLiveId = false): string
    {
        return $this->doBuildUrlForComponent('todo_list', $childrenFingerprints, [
            'includeDataLiveId' => $includeLiveId,
        ]);
    }

    private function doBuildUrlForComponent(string $componentName, array $childrenFingerprints, array $extraProps = []): string
    {
        $component = $this->mountComponent($componentName, array_merge([
            'items' => [
                ['text' => 'wake up'],
                ['text' => 'high five a friend'],
                ['text' => 'take a nap'],
            ],
        ], $extraProps));

        $dehydratedProps = $this->dehydrateComponent($component);

        $queryData = [
            'props' => json_encode($dehydratedProps->getProps()),
        ];

        if ($childrenFingerprints) {
            $children = [];
            foreach ($childrenFingerprints as $childId => $fingerprint) {
                $children[$childId] = [
                    'fingerprint' => $fingerprint,
                    'tag' => 'li',
                ];
            }
            $queryData['children'] = json_encode($children);
        }

        return \sprintf('/_components/%s?%s', $componentName, http_build_query($queryData));
    }
}
