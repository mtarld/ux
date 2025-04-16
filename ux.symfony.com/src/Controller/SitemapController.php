<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Service\LiveDemoRepository;
use App\Service\Toolkit\ToolkitService;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    public function __construct(
        private readonly UxPackageRepository $uxPackageRepository,
        private readonly LiveDemoRepository $liveDemoRepository,
        private readonly ToolkitService $toolkitService,
    ) {
    }

    #[Route(path: '/sitemap.xml', name: 'app_sitemap')]
    public function __invoke(Request $request): Response
    {
        $response = $this->render('sitemap.xml.twig', [
            'urls' => $this->getSitemapUrls(),
        ]);
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }

    /**
     * @return iterable<string>
     */
    private function getSitemapUrls(): iterable
    {
        // Static pages
        yield $this->generateAbsoluteUrl('app_homepage');
        yield $this->generateAbsoluteUrl('app_packages');
        yield $this->generateAbsoluteUrl('app_icons');
        yield $this->generateAbsoluteUrl('app_demos');
        yield $this->generateAbsoluteUrl('app_cookbook');
        yield $this->generateAbsoluteUrl('app_documentation');
        yield $this->generateAbsoluteUrl('app_changelog');
        yield $this->generateAbsoluteUrl('app_support');

        // UX Packages
        foreach ($this->uxPackageRepository->findAll() as $package) {
            yield $this->generateAbsoluteUrl($package->getRoute());
        }

        // Live Demos
        foreach ($this->liveDemoRepository->findAll() as $demo) {
            yield $this->generateAbsoluteUrl($demo->getRoute());
        }

        // Toolkit kits
        foreach ($this->toolkitService->getKits() as $kitId => $kit) {
            yield $this->generateAbsoluteUrl('app_toolkit_kit', ['kitId' => $kitId]);

            foreach ($this->toolkitService->getDocumentableComponents($kit) as $component) {
                yield $this->generateAbsoluteUrl('app_toolkit_component', ['kitId' => $kitId, 'componentName' => $component->name]);
            }
        }
    }

    private function generateAbsoluteUrl(string $route, array $parameters = []): string
    {
        return $this->generateUrl($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
