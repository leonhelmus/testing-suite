<?php
/**
 * Copyright MediaCT. All rights reserved.
 * https://www.mediact.nl
 */

namespace Mediact\TestingSuite\Composer\Installer;

use Composer\IO\IOInterface;
use Mediact\Composer\FileInstaller;
use Mediact\FileMapping\UnixFileMapping;
use Symfony\Component\Process\Process;

class PipelinesInstaller implements InstallerInterface
{
    /** @var FileInstaller */
    private $fileInstaller;

    /** @var IOInterface */
    private $io;

    /** @var string */
    private $destination;

    /** @var string */
    private $pattern = 'bitbucket.org';

    /** @var string */
    private $filename = 'bitbucket-pipelines.yml';

    /** @var array */
    private $types = [
        'mediact' => 'MediaCT pipelines script',
        'basic'   => 'Basic pipelines script'
    ];

    /**
     * Constructor.
     *
     * @param FileInstaller  $fileInstaller
     * @param IOInterface    $io
     * @param string|null    $destination
     * @param string|null    $pattern
     * @param string|null    $filename
     * @param array|null     $types
     */
    public function __construct(
        FileInstaller $fileInstaller,
        IOInterface $io,
        string $destination = null,
        string $pattern = null,
        string $filename = null,
        array $types = null
    ) {
        $this->fileInstaller  = $fileInstaller;
        $this->io             = $io;
        $this->destination    = $destination ?? getcwd();
        $this->pattern        = $pattern ?? $this->pattern;
        $this->filename       = $filename ?? $this->filename;
        $this->types          = $types ?? $this->types;
    }

    /**
     * Install.
     *
     * @return void
     */
    public function install()
    {
        if (file_exists($this->destination . '/' . $this->filename)
            || !$this->isBitbucket()
        ) {
            return;
        }


        $mapping = new UnixFileMapping(
            sprintf(
                __DIR__ . '/../../templates/files/pipelines-%s',
                $this->chooseMapping()
            ),
            $this->destination,
            $this->filename
        );

        $this->fileInstaller->installFile($mapping);
        $this->io->write(
            sprintf(
                '<info>Installed:</info> %s',
                $mapping->getRelativeDestination()
            )
        );
    }

    /**
     * Choose the mapping to install.
     *
     * @return string
     */
    private function chooseMapping():string
    {
        $labels = array_values($this->types);
        $keys   = array_keys($this->types);

        $selected = $this->io->select(
            'Bitbucket has been detected. Which pipelines script do you want to install?',
            $labels,
            key($labels)
        );

        return is_numeric($selected) ? $keys[$selected]
            : array_search($selected, $this->types);
    }

    /**
     * Check whether the project is on Bitbucket.
     *
     * @return bool
     */
    private function isBitbucket(): bool
    {
        $process = new Process('git remote -v');
        $process->run();

        return strpos($process->getOutput(), $this->pattern) !== false;
    }
}
