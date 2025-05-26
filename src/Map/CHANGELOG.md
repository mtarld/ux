# CHANGELOG

## 2.26

-  Add support for creating `Polygon` with holes, by passing an array of `array<Point>` as `points` parameter to the `Polygon` constructor, e.g.:
```php
// Draw a polygon with a hole in it, on the French map
$map->addPolygon(new Polygon(points: [
    // First path, the outer boundary of the polygon
    [
        new Point(48.117266, -1.677792), // Rennes
        new Point(50.629250, 3.057256), // Lille
        new Point(48.573405, 7.752111), // Strasbourg
        new Point(43.296482, 5.369780), // Marseille
        new Point(44.837789, -0.579180), // Bordeaux
    ],
    // Second path, it will make a hole in the previous one
    [
        new Point(45.833619, 1.261105), // Limoges
        new Point(45.764043, 4.835659), // Lyon
        new Point(49.258329, 4.031696), // Reims
        new Point(48.856613, 2.352222), // Paris
    ],
]));
```

## 2.25

-  Downgrade PHP requirement from 8.3 to 8.1

## 2.24

-  Installing the package in a Symfony app using Flex won't add the `@symfony/ux-map` dependency to the `package.json` file anymore.
-  Add `Icon` to customize a `Marker` icon (URL or SVG content)
-  Add parameter `id` to `Marker`, `Polygon` and `Polyline` constructors
-  Add method `Map::removeMarker(string|Marker $markerOrId)`
-  Add method `Map::removePolygon(string|Polygon $polygonOrId)`
-  Add method `Map::removePolyline(string|Polyline $polylineOrId)`

## 2.23

-  Add `DistanceUnit` to represent distance units (`m`, `km`, `miles`, `nmi`) and
   ease conversion between units.
-  Add `DistanceCalculatorInterface` interface and three implementations:
   `HaversineDistanceCalculator`, `SphericalCosineDistanceCalculator` and `VincentyDistanceCalculator`.
-  Add `CoordinateUtils` helper, to convert decimal coordinates (`43.2109`) in DMS (`56° 78' 90"`)

## 2.22

-   Add method `Symfony\UX\Map\Renderer\AbstractRenderer::tapOptions()`, to allow Renderer to modify options before rendering a Map.
-   Add `ux_map.google_maps.default_map_id` configuration to set the Google ``Map ID``
-   Add `ComponentWithMapTrait` to ease maps integration in [Live Components](https://symfony.com/bundles/ux-live-component/current/index.html)
-   Add `Polyline` support

## 2.20

-   Deprecate `render_map` Twig function (will be removed in 2.21). Use
    `ux_map` or the `<twig:ux:map />` Twig component instead.
-   Add `ux_map` Twig function (replaces `render_map` with a more flexible
    interface)
-   Add `<twig:ux:map />` Twig component
-   The importmap entry `@symfony/ux-map/abstract-map-controller` can be removed
    from your importmap, it is no longer needed.
-   Add `Polygon` support

## 2.19

-   Component added
