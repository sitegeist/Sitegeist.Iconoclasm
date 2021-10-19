<?php
declare(strict_types=1);

namespace Sitegeist\Iconoclasm\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Flow\Utility\Environment;
use Neos\Utility;
use Psr\Log\LoggerInterface;

class ThumbnailOptimizationService
{
    /**
     * @var LoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @Flow\InjectConfiguration(path="command")
     * @var array
     */
    protected $command;

    /**
     * @Flow\InjectConfiguration(path="mediaTypes")
     * @var array
     */
    protected $configuration;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @param Thumbnail $asset
     */
    public function optimizeThumbnail(Thumbnail $thumbnail)
    {
        $resource = $thumbnail->getResource();
        if (!$resource) {
            return;
        }

        $mediaType = $resource->getMediaType();
        if (!array_key_exists($mediaType, $this->configuration)) {
            return;
        }
        if (
            !array_key_exists('enabled', $this->configuration[$mediaType])
            || $this->configuration[$mediaType]['enabled'] === false
        ) {
            return;
        }

        $temporaryInputPath = $this->createTemporaryPath('TemporaryThumbnailsToOptimize/');
        $temporaryOutputPath = $this->createTemporaryPath('TemporaryThumbnailsOptimized/');
        $temporaryFilename = $this->createTemporaryFilename($thumbnail->getResource());

        $tmpFileInput = $temporaryInputPath . $temporaryFilename;
        $tmpFileOptimized = $temporaryOutputPath . $temporaryFilename;

        $temporaryFileHandle = fopen($tmpFileInput, 'w');
        $resourceStream = $resource->getStream();
        stream_copy_to_stream($resourceStream, $temporaryFileHandle);
        fclose($resourceStream);
        fclose($temporaryFileHandle);

        $command = $this->command;
        $options = $this->configuration[$mediaType]['options'] ?? '';

        $shellCommand = $command . ' ' . escapeshellarg($tmpFileInput) . ' ' . $options . ' > ' . escapeshellarg($tmpFileOptimized);
        $output = [];
        exec($shellCommand, $output, $result);
        $failed = (int)$result !== 0;

        if ($failed) {
            $this->logger->error(sprintf('Optimizing image "%s" with command "%s" failed', $thumbnail->getOriginalAsset()->getLabel(), $shellCommand), $output);
            unlink($tmpFileInput);
            unlink($tmpFileOptimized);
            return;
        } else {
            $this->logger->info(sprintf('Optimized image "%s" with command "%s"', $thumbnail->getOriginalAsset()->getLabel(), $shellCommand));
        }

        $optimizedResource = $this->resourceManager->importResource($tmpFileOptimized, $resource->getCollectionName());
        $thumbnail->setResource($optimizedResource);
        $this->resourceManager->deleteResource($resource);

        #unlink($tmpFileInput);
        #unlink($tmpFileOptimized);
    }

    /**
     * @param string $postfix
     * @return string
     */
    protected function createTemporaryPath(string $postfix): string
    {
        $temporaryPath = $this->environment->getPathToTemporaryDirectory() . $postfix;
        Utility\Files::createDirectoryRecursively($temporaryPath);
        return $temporaryPath;
    }

    /**
     * @param PersistentResource $resource
     * @return string
     */
    protected function createTemporaryFilename(PersistentResource $resource): string
    {
        $filename = $resource->getSha1();
        $filename .= '-' . microtime(true);

        if (function_exists('posix_getpid')) {
            $filename .= '-' . str_pad((string) posix_getpid(), 10, '0', STR_PAD_LEFT);
        } else {
            $filename .= '-' . str_pad((string) getmypid(), 10, '0', STR_PAD_LEFT);
        }

        $filename .= '.' . $resource->getFileExtension();
        return $filename;
    }
}
