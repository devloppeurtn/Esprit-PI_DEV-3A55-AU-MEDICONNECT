<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Asset\Packages;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private Packages $assetPackages
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('absolute_asset', [$this, 'absoluteAsset']),
        ];
    }

    public function absoluteAsset(string $path): string
    {
        $url = $this->assetPackages->getUrl($path);
        $appUrl = $_ENV['APP_URL'] ?? getenv('APP_URL') ?: null;
        if ($appUrl) {
            return rtrim($appUrl, '/') . $url;
        }
        return $url;
    }
}
