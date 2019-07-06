<?php

namespace Spatie\Php7to5;

use RuntimeException;
use Spatie\Php7to5\Exceptions\InvalidParameter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class DirectoryConverter
{
    /** @var string */
    protected $sourceDirectory;

    /** @var string */
    protected $copyNonPhpFiles = true;

    /** @var bool */
    protected $cleanDestinationDirectory = false;

    /** @var string[] */
    protected $extensions;

    /** @var null|string[] */
    protected $excludes;

    protected $logger;

    /**
     * @param string        $sourceDirectory
     * @param string[]      $extensions
     * @param string[]|null $excludes
     *
     * @throws InvalidParameter
     */
    public function __construct($sourceDirectory, array $extensions = ['php'], array $excludes = null)
    {
        if (!file_exists($sourceDirectory)) {
            throw InvalidParameter::directoryDoesNotExist($sourceDirectory);
        }

        if (!count($extensions)) {
            throw InvalidParameter::emptyExtensionList();
        }

        $this->sourceDirectory = $sourceDirectory;
        $this->extensions = array_map('mb_strtolower', $extensions);
        $this->excludes = $excludes;
    }

    public function setLogger(OutputInterface $output)
    {
        $this->logger = $output;
    }

    public function log($sourceFilePath, $targetFilePath)
    {
        if ($this->logger === null) {
            return;
        }

        $sourcePath = substr($sourceFilePath, strlen(getcwd()) + 1);
        $targetPath = substr($targetFilePath, strlen(getcwd()) + 1);

        if ($this->isPhpFile($sourceFilePath)) {
            $this->logger->writeln(
                "Converting <info>{$sourcePath}</info> to <info>{$targetPath}</info>."
            );
        } else {
            $this->logger->writeln(
                "Copying <comment>{$sourcePath}</comment> to <comment>{$targetPath}</comment>."
            );
        }
    }

    /**
     * @return $this
     */
    public function alsoCopyNonPhpFiles()
    {
        $this->copyNonPhpFiles = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function cleanDestinationDirectory()
    {
        $this->cleanDestinationDirectory = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function doNotCopyNonPhpFiles()
    {
        $this->copyNonPhpFiles = false;

        return $this;
    }

    /**
     * @param string $destinationDirectory
     *
     * @throws InvalidParameter
     */
    public function savePhp5FilesTo($destinationDirectory)
    {
        if ($destinationDirectory === '') {
            throw InvalidParameter::directoryIsRequired();
        }

        if ($this->cleanDestinationDirectory) {
            $this->removeDirectory($destinationDirectory);
        }

        $this->copyDirectory($this->sourceDirectory, $destinationDirectory);
    }

    /**
     * @param string $sourceDirectory
     * @param string $destinationDirectory
     *
     * @throws InvalidParameter
     */
    protected function copyDirectory($sourceDirectory, $destinationDirectory)
    {
        if (!$this->createDirectory($destinationDirectory)) {
            throw new RuntimeException('Unable to create a directory "'.$destinationDirectory.'".');
        }

        $finder = new Finder();
        $finder->in($sourceDirectory);
        if (!$this->copyNonPhpFiles) {
            foreach ($this->extensions as $extension) {
                $finder->name('*.'.$extension);
            }
        }

        if ($this->excludes) {
            $finder->notPath($this->excludes);
        }

        foreach ($finder as $item) {
            $targetRealPath = rtrim($destinationDirectory, DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR
                .$item->getRelativePathname();

            if (!$item->isFile()) {
                continue;
            }

            $isPhpFile = $this->isPhpFile($targetRealPath);

            if (!$isPhpFile && !$this->copyNonPhpFiles) {
                continue;
            }

            $targetDir = dirname($targetRealPath);

            if ($targetDir && !$this->createDirectory($targetDir)) {
                throw new RuntimeException('Unable to create a directory "'.$targetDir.'".');
            }

            $this->log($item->getRealPath(), $targetRealPath);

            if ($isPhpFile) {
                $this->convertToPhp5($item->getRealPath(), $targetRealPath);
            } else {
                copy($item->getRealPath(), $targetRealPath);
            }
        }
    }

    /**
     * @param string $path
     */
    protected function removeDirectory($path)
    {
        if (PHP_OS === 'Windows') {
            $command = 'rd /s /q %s';
        } else {
            $command = 'rm -rf %s';
        }

        exec(sprintf($command, escapeshellarg($path)));
    }

    /**
     * @param string $sourceFilePath
     * @param string $targetFilePath
     */
    protected function convertToPhp5($sourceFilePath, $targetFilePath)
    {
        $converter = new Converter($sourceFilePath);

        $converter->saveAsPhp5($targetFilePath);
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    protected function isPhpFile($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return in_array($extension, $this->extensions, true);
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    private function createDirectory($directory)
    {
        if (is_dir($directory)) {
            return true;
        }

        return mkdir($directory, 0755, true) && is_dir($directory);
    }
}
