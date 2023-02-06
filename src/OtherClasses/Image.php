<?php

namespace App\OtherClasses;

use Imagine\Draw\AlphaBlendingAwareDrawerInterface;
use Imagine\Draw\DrawerInterface;
use Imagine\Effects\EffectsInterface;
use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\OutOfBoundsException;
use Imagine\Exception\RuntimeException;
use Imagine\Gd\Drawer;
use Imagine\Gd\Effects;
use Imagine\Gd\Layers;
use Imagine\Image\AbstractImage;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\Fill\FillInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\LayersInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\Color\RGB as RGBColor;
use Imagine\Image\Palette\PaletteInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Image\PointInterface;
use Imagine\Image\ProfileInterface;

final class Image extends AbstractImage
{
    public function thumbnail(BoxInterface $size, $settings = ManipulatorInterface::THUMBNAIL_INSET, $filter = ImageInterface::FILTER_UNDEFINED): static
    {
        if (ManipulatorInterface::THUMBNAIL_INSET !== $settings &&
            ManipulatorInterface::THUMBNAIL_OUTBOUND !== $settings) {
            throw new InvalidArgumentException('Invalid mode specified');
        }

        $imageSize = $this->getSize();
        $ratios = [
            $size->getWidth() / $imageSize->getWidth(),
            $size->getHeight() / $imageSize->getHeight(),
        ];

        $thumbnail = $this->copy();

        $thumbnail->usePalette($this->palette());
        $thumbnail->strip();

        if (ManipulatorInterface::THUMBNAIL_INSET === $settings) {
            $ratio = min($ratios);
        } else {
            $ratio = max($ratios);
        }
        if (ManipulatorInterface::THUMBNAIL_OUTBOUND === $settings) {
            $imageSize = $thumbnail->getSize()->scale($ratio);
            $thumbnail->resize($imageSize, $filter);
            $thumbnail->crop(new Point(
                max(0, round(($imageSize->getWidth() - $size->getWidth()) / 2)),
                max(0, round(($imageSize->getHeight() - $size->getHeight()) / 2))
            ), $size);
        } else {
            if (!$imageSize->contains($size)) {
                $imageSize = $imageSize->scale($ratio);
                $thumbnail->resize($imageSize, $filter);
            } else {
                $imageSize = $thumbnail->getSize()->scale($ratio);
                $thumbnail->resize($imageSize, $filter);
            }
        }

        return $thumbnail;
    }

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var Layers|null
     */
    private $layers;

    /**
     * @var PaletteInterface
     */
    private $palette;

    /**
     * Constructs a new Image instance.
     *
     * @param resource $resource
     */
    public function __construct($resource, PaletteInterface $palette, MetadataBag $metadata)
    {
        $this->metadata = $metadata;
        $this->palette = $palette;
        $this->resource = $resource;
    }

    /**
     * Makes sure the current image resource is destroyed.
     */
    public function __destruct()
    {
        if (is_resource($this->resource) && 'gd' === get_resource_type($this->resource)) {
            imagedestroy($this->resource);
        }
    }

    /**
     * Returns Gd resource.
     *
     * @return resource
     */
    public function getGdResource()
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function copy()
    {
        $size = $this->getSize();
        $copy = $this->createImage($size, 'copy');

        if (false === imagecopy($copy, $this->resource, 0, 0, 0, 0, $size->getWidth(), $size->getHeight())) {
            throw new RuntimeException('Image copy operation failed');
        }

        return new Image($copy, $this->palette, $this->metadata);
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function crop(PointInterface $start, BoxInterface $size)
    {
        if (!$start->in($this->getSize())) {
            throw new OutOfBoundsException('Crop coordinates must start at minimum 0, 0 position from top  left corner, crop height and width must be positive integers and must not exceed the current image borders');
        }

        $width = $size->getWidth();
        $height = $size->getHeight();

        $dest = $this->createImage($size, 'crop');

        if (false === imagecopy($dest, $this->resource, 0, 0, $start->getX(), $start->getY(), $width, $height)) {
            throw new RuntimeException('Image crop operation failed');
        }

        imagedestroy($this->resource);

        $this->resource = $dest;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function paste(ImageInterface $image, PointInterface $start, $alpha = 100)
    {
        if (!$image instanceof self) {
            throw new InvalidArgumentException(sprintf('Gd\Image can only paste() Gd\Image instances, %s given', get_class($image)));
        }

        $size = $image->getSize();
        if (!$this->getSize()->contains($size, $start)) {
            throw new OutOfBoundsException('Cannot paste image of the given size at the specified position, as it moves outside of the current image\'s box');
        }

        imagealphablending($this->resource, true);
        imagealphablending($image->resource, true);

        if (false === imagecopy($this->resource, $image->resource, $start->getX(), $start->getY(), 0, 0, $size->getWidth(), $size->getHeight())) {
            throw new RuntimeException('Image paste operation failed');
        }

        imagealphablending($this->resource, false);
        imagealphablending($image->resource, false);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function resize(BoxInterface $size, $filter = ImageInterface::FILTER_UNDEFINED)
    {
        if (ImageInterface::FILTER_UNDEFINED !== $filter) {
            throw new InvalidArgumentException('Unsupported filter type, GD only supports ImageInterface::FILTER_UNDEFINED filter');
        }

        $width = $size->getWidth();
        $height = $size->getHeight();

        $dest = $this->createImage($size, 'resize');

        imagealphablending($this->resource, true);
        imagealphablending($dest, true);

        if (false === imagecopyresampled($dest, $this->resource, 0, 0, 0, 0, $width, $height, imagesx($this->resource), imagesy($this->resource))) {
            throw new RuntimeException('Image resize operation failed');
        }

        imagealphablending($this->resource, false);
        imagealphablending($dest, false);

        imagedestroy($this->resource);

        $this->resource = $dest;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function rotate($angle, ColorInterface $background = null)
    {
        $color = $background ? $background : $this->palette->color('fff');
        $resource = imagerotate($this->resource, -1 * $angle, $this->getColor($color));

        if (false === $resource) {
            throw new RuntimeException('Image rotate operation failed');
        }

        imagedestroy($this->resource);
        $this->resource = $resource;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function save($path = null, array $options = [])
    {
        $path = null === $path ? (isset($this->metadata['filepath']) ? $this->metadata['filepath'] : $path) : $path;

        if (null === $path) {
            throw new RuntimeException('You can omit save path only if image has been open from a file');
        }

        if (isset($options['format'])) {
            $format = $options['format'];
        } elseif ('' !== $extension = pathinfo($path, \PATHINFO_EXTENSION)) {
            $format = $extension;
        } else {
            $originalPath = isset($this->metadata['filepath']) ? $this->metadata['filepath'] : null;
            $format = pathinfo($originalPath, \PATHINFO_EXTENSION);
        }

        $this->saveOrOutput($format, $options, $path);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    public function show($format, array $options = [])
    {
        header('Content-type: '.$this->getMimeType($format));

        $this->saveOrOutput($format, $options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get($format, array $options = []): string
    {
        ob_start();
        $this->saveOrOutput($format, $options);

        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->get('png');
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function flipHorizontally()
    {
        $size = $this->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();
        $dest = $this->createImage($size, 'flip');

        for ($i = 0; $i < $width; ++$i) {
            if (false === imagecopy($dest, $this->resource, $i, 0, ($width - 1) - $i, 0, 1, $height)) {
                throw new RuntimeException('Horizontal flip operation failed');
            }
        }

        imagedestroy($this->resource);

        $this->resource = $dest;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function flipVertically()
    {
        $size = $this->getSize();
        $width = $size->getWidth();
        $height = $size->getHeight();
        $dest = $this->createImage($size, 'flip');

        for ($i = 0; $i < $height; ++$i) {
            if (false === imagecopy($dest, $this->resource, 0, $i, 0, ($height - 1) - $i, $width, 1)) {
                throw new RuntimeException('Vertical flip operation failed');
            }
        }

        imagedestroy($this->resource);

        $this->resource = $dest;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    final public function strip()
    {
        // GD strips profiles and comment, so there's nothing to do here
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function draw(): DrawerInterface|AlphaBlendingAwareDrawerInterface
    {
        return new Drawer($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function effects(): EffectsInterface
    {
        return new Effects($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): BoxInterface
    {
        return new Box(imagesx($this->resource), imagesy($this->resource));
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    public function applyMask(ImageInterface $mask)
    {
        if (!$mask instanceof self) {
            throw new InvalidArgumentException('Cannot mask non-gd images');
        }

        $size = $this->getSize();
        $maskSize = $mask->getSize();

        if ($size != $maskSize) {
            throw new InvalidArgumentException(sprintf('The given mask doesn\'t match current image\'s size, Current mask\'s dimensions are %s, while image\'s dimensions are %s', $maskSize, $size));
        }

        for ($x = 0, $width = $size->getWidth(); $x < $width; ++$x) {
            for ($y = 0, $height = $size->getHeight(); $y < $height; ++$y) {
                $position = new Point($x, $y);
                $color = $this->getColorAt($position);
                $maskColor = $mask->getColorAt($position);
                $round = (int) round(max($color->getAlpha(), (100 - $color->getAlpha()) * $maskColor->getRed() / 255));

                if (false === imagesetpixel($this->resource, $x, $y, $this->getColor($color->dissolve($round - $color->getAlpha())))) {
                    throw new RuntimeException('Apply mask operation failed');
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return ImageInterface
     */
    public function fill(FillInterface $fill)
    {
        $size = $this->getSize();

        for ($x = 0, $width = $size->getWidth(); $x < $width; ++$x) {
            for ($y = 0, $height = $size->getHeight(); $y < $height; ++$y) {
                if (false === imagesetpixel($this->resource, $x, $y, $this->getColor($fill->getColor(new Point($x, $y))))) {
                    throw new RuntimeException('Fill operation failed');
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mask(): static
    {
        $mask = $this->copy();

        if (false === imagefilter($mask->resource, IMG_FILTER_GRAYSCALE)) {
            throw new RuntimeException('Mask operation failed');
        }

        return $mask;
    }

    /**
     * {@inheritdoc}
     */
    public function histogram(): array
    {
        $size = $this->getSize();
        $colors = [];

        for ($x = 0, $width = $size->getWidth(); $x < $width; ++$x) {
            for ($y = 0, $height = $size->getHeight(); $y < $height; ++$y) {
                $colors[] = $this->getColorAt(new Point($x, $y));
            }
        }

        return array_unique($colors);
    }

    /**
     * {@inheritdoc}
     */
    public function getColorAt(PointInterface $point): ColorInterface
    {
        if (!$point->in($this->getSize())) {
            throw new RuntimeException(sprintf('Error getting color at point [%s,%s]. The point must be inside the image of size [%s,%s]', $point->getX(), $point->getY(), $this->getSize()->getWidth(), $this->getSize()->getHeight()));
        }

        $index = imagecolorat($this->resource, $point->getX(), $point->getY());
        $info = imagecolorsforindex($this->resource, $index);

        return $this->palette->color([$info['red'], $info['green'], $info['blue']], max(min(100 - (int) round($info['alpha'] / 127 * 100), 100), 0));
    }

    /**
     * {@inheritdoc}
     */
    public function layers(): LayersInterface
    {
        if (null === $this->layers) {
            $this->layers = new Layers($this, $this->palette, $this->resource);
        }

        return $this->layers;
    }

    /**
     * {@inheritdoc}
     **/
    public function interlace($scheme): static
    {
        static $supportedInterlaceSchemes = [
            ImageInterface::INTERLACE_NONE => 0,
            ImageInterface::INTERLACE_LINE => 1,
            ImageInterface::INTERLACE_PLANE => 1,
            ImageInterface::INTERLACE_PARTITION => 1,
        ];

        if (!array_key_exists($scheme, $supportedInterlaceSchemes)) {
            throw new InvalidArgumentException('Unsupported interlace type');
        }

        imageinterlace($this->resource, $supportedInterlaceSchemes[$scheme]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function palette(): PaletteInterface
    {
        return $this->palette;
    }

    /**
     * {@inheritdoc}
     */
    public function profile(ProfileInterface $profile): static
    {
        throw new RuntimeException('GD driver does not support color profiles');
    }

    /**
     * {@inheritdoc}
     */
    public function usePalette(PaletteInterface $palette): static
    {
        if (!$palette instanceof RGB) {
            throw new RuntimeException('GD driver only supports RGB palette');
        }

        $this->palette = $palette;

        return $this;
    }

    /**
     * Internal.
     *
     * Performs save or show operation using one of GD's image... functions
     *
     * @param string $format
     * @param string $filename
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function saveOrOutput($format, array $options, $filename = null)
    {
        $format = $this->normalizeFormat($format);

        if (!$this->supported($format)) {
            throw new InvalidArgumentException(sprintf('Saving image in "%s" format is not supported, please use one of the following extensions: "%s"', $format, implode('", "', $this->supported())));
        }

        $save = 'image'.$format;
        $args = [&$this->resource, $filename];

        // Preserve BC until version 1.0
        if (isset($options['quality']) && !isset($options['png_compression_level'])) {
            $options['png_compression_level'] = round((100 - $options['quality']) * 9 / 100);
        }
        if (isset($options['filters']) && !isset($options['png_compression_filter'])) {
            $options['png_compression_filter'] = $options['filters'];
        }

        $options = $this->updateSaveOptions($options);

        if ('jpeg' === $format && isset($options['jpeg_quality'])) {
            $args[] = $options['jpeg_quality'];
        }

        if ('png' === $format) {
            if (isset($options['png_compression_level'])) {
                if ($options['png_compression_level'] < 0 || $options['png_compression_level'] > 9) {
                    throw new InvalidArgumentException('png_compression_level option should be an integer from 0 to 9');
                }
                $args[] = $options['png_compression_level'];
            } else {
                $args[] = -1; // use default level
            }

            if (isset($options['png_compression_filter'])) {
                if (~PNG_ALL_FILTERS & $options['png_compression_filter']) {
                    throw new InvalidArgumentException('png_compression_filter option should be a combination of the PNG_FILTER_XXX constants');
                }
                $args[] = $options['png_compression_filter'];
            }
        }

        if (('wbmp' === $format || 'xbm' === $format) && isset($options['foreground'])) {
            $args[] = $options['foreground'];
        }

        $this->setExceptionHandler();

        if (false === call_user_func_array($save, $args)) {
            throw new RuntimeException('Save operation failed');
        }

        $this->resetExceptionHandler();
    }

    /**
     * Internal.
     *
     * Generates a GD image
     *
     * @param string the operation initiating the creation
     *
     * @return resource
     *
     * @throws RuntimeException
     */
    private function createImage(BoxInterface $size, $operation)
    {
        $resource = imagecreatetruecolor($size->getWidth(), $size->getHeight());

        if (false === $resource) {
            throw new RuntimeException('Image '.$operation.' failed');
        }

        if (false === imagealphablending($resource, false) || false === imagesavealpha($resource, true)) {
            throw new RuntimeException('Image '.$operation.' failed');
        }

        if (function_exists('imageantialias')) {
            imageantialias($resource, true);
        }

        $transparent = imagecolorallocatealpha($resource, 255, 255, 255, 127);
        imagefill($resource, 0, 0, $transparent);
        imagecolortransparent($resource, $transparent);

        return $resource;
    }

    /**
     * Internal.
     *
     * Generates a GD color from Color instance
     *
     * @return int A color identifier
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    private function getColor(ColorInterface $color)
    {
        if (!$color instanceof RGBColor) {
            throw new InvalidArgumentException('GD driver only supports RGB colors');
        }

        $index = imagecolorallocatealpha($this->resource, $color->getRed(), $color->getGreen(), $color->getBlue(), round(127 * (100 - $color->getAlpha()) / 100));

        if (false === $index) {
            throw new RuntimeException(sprintf('Unable to allocate color "RGB(%s, %s, %s)" with transparency of %d percent', $color->getRed(), $color->getGreen(), $color->getBlue(), $color->getAlpha()));
        }

        return $index;
    }

    /**
     * Internal.
     *
     * Normalizes a given format name
     *
     * @param string $format
     *
     * @return string
     */
    private function normalizeFormat($format)
    {
        $format = strtolower($format);

        if ('jpg' === $format || 'pjpeg' === $format) {
            $format = 'jpeg';
        }

        return $format;
    }

    /**
     * Internal.
     *
     * Checks whether a given format is supported by GD library
     *
     * @param string $format
     *
     * @return bool
     */
    private function supported($format = null)
    {
        $formats = ['gif', 'jpeg', 'png', 'wbmp', 'xbm'];

        if (null === $format) {
            return $formats;
        }

        return in_array($format, $formats);
    }

    private function setExceptionHandler()
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (0 === error_reporting()) {
                return;
            }

            throw new RuntimeException($errstr, $errno, new \ErrorException($errstr, 0, $errno, $errfile, $errline));
        }, E_WARNING | E_NOTICE);
    }

    private function resetExceptionHandler()
    {
        restore_error_handler();
    }

    /**
     * Internal.
     *
     * Get the mime type based on format.
     *
     * @param string $format
     *
     * @return string mime-type
     *
     * @throws RuntimeException
     */
    private function getMimeType($format)
    {
        $format = $this->normalizeFormat($format);

        if (!$this->supported($format)) {
            throw new RuntimeException('Invalid format');
        }

        static $mimeTypes = [
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'wbmp' => 'image/vnd.wap.wbmp',
            'xbm' => 'image/xbm',
        ];

        return $mimeTypes[$format];
    }
}
