<?php

/*
 * This file is part of Chevere.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Chevere\Xr;

use function Chevere\Filesystem\filePhpReturnForPath;
use Chevere\Filesystem\Interfaces\DirInterface;
use function Chevere\Type\typeArray;
use Chevere\Xr\Interfaces\XrClientInterface;
use Chevere\Xr\Interfaces\XrCurlInterface;
use Chevere\Xr\Interfaces\XrInterface;
use Throwable;

final class Xr implements XrInterface
{
    private XrClientInterface $client;

    private DirInterface $configDir;

    private string $configFile = '';

    private array $configNames = ['xr.php'];

    private XrCurlInterface $curl;

    public function __construct(
        private bool $enable = true,
        private string $host = 'localhost',
        private int $port = 27420
    ) {
        $this->curl = new XrCurl();
        $this->setClient();
    }

    public function withConfigDir(DirInterface $configDir): XrInterface
    {
        $new = clone $this;
        $new->configDir = $configDir;
        $new->configFile = $new->getConfigFile();
        if ($new->configFile !== '') {
            $new->setConfigFromFile();
        }
        $new->setClient();

        return $new;
    }

    public function enable(): bool
    {
        return $this->enable;
    }

    public function client(): XrClientInterface
    {
        return $this->client;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    private function setConfigFromFile(): void
    {
        try {
            $return = filePhpReturnForPath($this->configFile)
                ->varType(typeArray());
            foreach (['enable', 'host', 'port'] as $prop) {
                $this->{$prop} = $return[$prop] ?? $this->{$prop};
            }
        }
        // @codeCoverageIgnoreStart
        catch (Throwable) {
            // Ignore to use defaults
        }
        // @codeCoverageIgnoreEnd
    }

    private function getConfigFile(): string
    {
        $configDirectory = $this->configDir->path()->__toString();
        while (is_dir($configDirectory)) {
            foreach ($this->configNames as $configName) {
                $configFullPath = $configDirectory . $configName;
                if (file_exists($configFullPath)) {
                    return $configFullPath;
                }
            }
            $parentDirectory = dirname($configDirectory) . DIRECTORY_SEPARATOR;
            if ($parentDirectory === $configDirectory) {
                return '';
            }
            $configDirectory = $parentDirectory;
        }

        return ''; // @codeCoverageIgnore
    }

    private function setClient(): void
    {
        $this->client = (new XrClient(
            host: $this->host,
            port: $this->port,
        ))->withCurl($this->curl);
    }
}
