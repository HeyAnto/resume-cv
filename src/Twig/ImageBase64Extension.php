<?php

namespace App\Twig;

use App\Service\ImageBase64Service;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImageBase64Extension extends AbstractExtension
{
  public function __construct(
    private ImageBase64Service $imageBase64Service
  ) {}

  public function getFunctions(): array
  {
    return [
      new TwigFunction('image_base64', [$this, 'getImageBase64']),
    ];
  }

  public function getImageBase64(string $imagePath): string
  {
    try {
      return $this->imageBase64Service->getImageAsBase64($imagePath);
    } catch (\Exception $e) {
      // In case of error, return empty string or a placeholder
      return '';
    }
  }
}
