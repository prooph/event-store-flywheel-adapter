<?php

$header = <<<EOF
This file is part of the prooph/event-store-flywheel-adapter.

(c) 2016 prooph software GmbH <contact@prooph.de>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(array(
        'header_comment',
        'ordered_use',
        'short_array_syntax',
    ))
    ->setUsingCache(true)
    ->setUsingLinter(false)
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()->in([__DIR__])
    )
;
