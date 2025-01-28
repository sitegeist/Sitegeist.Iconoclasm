<?php

declare(strict_types=1);

namespace Sitegeist\Iconoclasm\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
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
     * @var string
     */
    protected $command;

    /**
     * @Flow\InjectConfiguration(path="mediaTypes")
     * @var array<string, array{enabled:bool, command?:string}>
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
     * Optimize the given thumbnails using local copies that later replace the original resource
     */
    public function optimizeThumbnail(Thumbnail $thumbnail): void
    {
        /**
         * @var null|PersistentResource $resource
         */
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

        $temporaryInputPath = $this->createTemporaryPath('iconoclasm/input/');
        $temporaryOutputPath = $this->createTemporaryPath('iconoclasm/output/');
        $temporaryFilename = $this->createTemporaryFilename($thumbnail->getResource());

        $tmpFileInput = $temporaryInputPath . $temporaryFilename;
        $tmpFileOptimized = $temporaryOutputPath . $temporaryFilename;

        $temporaryFileHandle = fopen($tmpFileInput, 'wb');
        if (!$temporaryFileHandle) {
            return;
        }
        /**
         * @var resource|false $resourceStream
         */
        $resourceStream = $resource->getStream();
        if (!$resourceStream) {
            return;
        }
        stream_copy_to_stream($resourceStream, $temporaryFileHandle);
        fclose($resourceStream);
        fclose($temporaryFileHandle);

        $filesizeOriginal = filesize($tmpFileInput);
        if ((bool)$filesizeOriginal === false) {
            $this->logger->error(sprintf('Creating temporary copy of original image "%s" resulted in empty file', $thumbnail->getOriginalAsset()->getResource()->getFilename()), LogEnvironment::fromMethodName(__METHOD__));
            unlink($tmpFileInput);
            unlink($tmpFileOptimized);
            return;
        }

        $shellCommand = str_replace(
            ['{input}', '{output}'],
            [escapeshellarg($tmpFileInput), escapeshellarg($tmpFileOptimized)],
            $this->configuration[$mediaType]['command'] ?? $this->command
        );

        $output = [];
        exec($shellCommand, $output, $result);
        $failed = (int)$result !== 0;

        if ($failed) {
            $this->logger->error(sprintf('Optimizing image "%s" with command "%s" failed', $thumbnail->getOriginalAsset()->getResource()->getFilename(), $shellCommand), LogEnvironment::fromMethodName(__METHOD__) + $output);
            unlink($tmpFileInput);
            unlink($tmpFileOptimized);
            return;
        }

        $filesizeOptimized = filesize($tmpFileOptimized);
        if ($filesizeOptimized === false || $filesizeOptimized === 0) {
            $this->logger->error(sprintf('Optimizing image "%s" with command "%s" resulted in empty files', $thumbnail->getOriginalAsset()->getResource()->getFilename(), $shellCommand), LogEnvironment::fromMethodName(__METHOD__) + $output);
            unlink($tmpFileInput);
            unlink($tmpFileOptimized);
            return;
        }

        if ($filesizeOptimized >= $filesizeOriginal) {
            $this->logger->warning(sprintf(
                'Optimizing image "%s" with command "%s" yielded no size reduction %s > %s bytes',
                $thumbnail->getOriginalAsset()->getResource()->getFilename(),
                $shellCommand,
                Utility\Files::bytesToSizeString($filesizeOriginal, 2),
                Utility\Files::bytesToSizeString($filesizeOptimized, 2)
            ));
            unlink($tmpFileInput);
            unlink($tmpFileOptimized);
            return;
        }

        $this->logger->info(sprintf(
            'Optimized image "%s" with command "%s" reduction %s > %s bytes - reduction %s %%',
            $thumbnail->getOriginalAsset()->getResource()->getFilename(),
            $shellCommand,
            Utility\Files::bytesToSizeString($filesizeOriginal, 2),
            Utility\Files::bytesToSizeString($filesizeOptimized, 2),
            number_format(100.0 - ($filesizeOptimized / $filesizeOriginal * 100), 2)
        ));
        $optimizedResource = $this->resourceManager->importResource($tmpFileOptimized, $resource->getCollectionName());
        $optimizedResource->setFilename($resource->getFilename());
        $thumbnail->setResource($optimizedResource);
        $this->resourceManager->deleteResource($resource);

        unlink($tmpFileInput);
        unlink($tmpFileOptimized);
    }

    /**
     * Create a temporary path with the given postfix and return the result
     */
    protected function createTemporaryPath(string $postfix): string
    {
        $temporaryPath = $this->environment->getPathToTemporaryDirectory() . $postfix;
        Utility\Files::createDirectoryRecursively($temporaryPath);
        return $temporaryPath;
    }

    /**
     * Create a temporary name for the image that will be optimized. The name has to be unique to avoid collisions
     * in case of two processes try to optimize the same file
     *
     * @see: Neos\Flow\Classes\ResourceManagement\PersistentResource->createTemporaryLocalCopy
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
