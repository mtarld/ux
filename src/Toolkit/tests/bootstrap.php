<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\Filesystem\Filesystem;

require __DIR__.'/../vendor/autoload.php';

(new Filesystem())->remove(__DIR__.'/../var');

// @see https://github.com/symfony/symfony/issues/53812
ErrorHandler::register(null, false);
