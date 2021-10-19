<?php
namespace Sitegeist\Iconoclasm\Aspect;

use Neos\Eel\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Media\Domain\Model\Thumbnail;
use Sitegeist\Iconoclasm\Service\ThumbnailOptimizationService;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class ThumbnailAspect
{
    /**
     * @var ThumbnailOptimizationService
     * @Flow\Inject
     */
    protected $thumbnailOptimizationService;

    /**
     * After a thumbnail has been refreshed the resource is optimized, meaning the
     * image is only optimized once when created.
     *
     * A new resource is generated for every thumbnail, meaning the original is
     * never touched.
     *
     * Only local file system target is supported to keep it from being blocking.
     * It would however be possible to create a local copy of the resource,
     * process it, import it and set that as the thumbnail resource.
     *
     * @Flow\AfterReturning("method(Neos\Media\Domain\Model\Thumbnail->refresh())")
     * @param JoinPointInterface $joinPoint The current join point
     * @return void
     * @throws Exception
     * @throws UnknownPackageException
     */
    public function optimizeThumbnail(JoinPointInterface $joinPoint)
    {
        /** @var Thumbnail $thumbnail */
        $thumbnail = $joinPoint->getProxy();
        $this->thumbnailOptimizationService->optimizeThumbnail($thumbnail);
    }
}
