<?php
declare(strict_types=1);

namespace Sitegeist\Iconoclasm;


use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Package\Package as BasePackage;
use Neos\Media\Domain\Service\ThumbnailService;
use Sitegeist\Iconoclasm\Service\ThumbnailOptimizationService;

class Package extends BasePackage
{
    /**
     * @param Bootstrap $bootstrap The current bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $dispatcher = $bootstrap->getSignalSlotDispatcher();
        $dispatcher->connect(ThumbnailService::class, 'thumbnailRefreshed', ThumbnailOptimizationService::class, 'optimizeThumbnail');
    }
}
