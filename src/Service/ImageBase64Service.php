<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ImageBase64Service
{
  public function __construct(
    #[Autowire('%kernel.project_dir%')] private string $projectDir
  ) {}

  /**
   * Convert an image file to base64 data URI
   */
  public function getImageAsBase64(string $imagePath): string
  {
    // Remove leading slash if present
    $imagePath = ltrim($imagePath, '/');

    // Build full path to the image
    $fullPath = $this->projectDir . '/public/' . $imagePath;

    // Check if file exists
    if (!file_exists($fullPath)) {
      throw new \InvalidArgumentException("Image file not found: {$fullPath}");
    }

    // Get file content
    $imageData = file_get_contents($fullPath);
    if ($imageData === false) {
      throw new \RuntimeException("Failed to read image file: {$fullPath}");
    }

    // Get MIME type
    $mimeType = mime_content_type($fullPath);
    if ($mimeType === false) {
      // Fallback based on file extension
      $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
      $mimeType = match ($extension) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        default => 'image/png'
      };
    }

    // Encode to base64
    $base64 = base64_encode($imageData);

    // Return data URI
    return "data:{$mimeType};base64,{$base64}";
  }
}
