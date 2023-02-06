<?php

namespace App\Controller;

use App\OtherClasses\Image;
use Imagine\Gd\Imagine;
use Liip\ImagineBundle\Imagine\Filter\Loader\ThumbnailFilterLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class REImage2Controller.
 */
class REImage2Controller extends AbstractController
{
    private Imagine $imagine;
    private string $upload_tmp_dir;

    private Packages $helper;

    public function __construct(Imagine $imagine, $upload_tmp_dir, Packages $helper)
    {
        $this->imagine = $imagine;
        $this->upload_tmp_dir = $upload_tmp_dir;
        $this->helper = $helper;
    }

    public function download(Request $request): Response
    {
        try {
            if ($request->files->has('files')) {
                $file = $request->files->get('files');
                $ext = str_replace('jpeg', 'jpg', $file->guessExtension());
                if (!preg_match('#(jpg|png|bmp|tif|svg|gif)#', strtolower($ext))) {
                    throw new \RuntimeException('bad file format');
                }

                if (!file_exists($this->upload_tmp_dir) && !mkdir($concurrentDirectory = $this->upload_tmp_dir) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }

                $this->cleanTmpFiles();

                do {
                    $filename = base64_encode(uniqid()).'.'.$ext;
                } while (file_exists($this->upload_tmp_dir.$filename));
                $file->move($this->upload_tmp_dir, $filename);
                if ('svg' !== strtolower($ext) && 'gif' !== strtolower($ext)) {
                    $this->redimenssionerImage($this->upload_tmp_dir.$filename, $request->get('width'), $request->get('height'));
                }

                return new Response(json_encode([
                    'assetpath' => $this->helper->getUrl($this->upload_tmp_dir.$filename),
                    'filepath' => $this->upload_tmp_dir.$filename,
                ]));
            }
        } catch (\Exception $e) {
            return new Response(json_encode(['error' => $e->getMessage()]));
        }

        return new Response(json_encode('No file received'));
    }

    private function cleanTmpFiles()
    {
        // Clean files
        foreach ((new Finder())->in($this->upload_tmp_dir) as $file) {
            if (time() - $file->getCTime() > 60) {
                unlink($file);
            }
        }
    }

    private function redimenssionerImage($path, $width, $height)
    {
        $image = $this->imagine->open($path);
        $image2 = new Image($image->getGdResource(), $image->palette(), $image->metadata());

        if (null !== $width && null === $height) {
            if ($width === $image2->getSize()->getWidth()) {
                return; // dont apply filter if same size
            }
            $height = $width * $image2->getSize()->getHeight() / $image2->getSize()->getWidth();
        } elseif (null !== $height && null === $width) {
            if ($height === $image2->getSize()->getHeight()) {
                return; // dont apply filter if same size
            }
            $width = $height * $image2->getSize()->getWidth() / $image2->getSize()->getHeight();
        }

        $thumbnailFilter = new ThumbnailFilterLoader();
        $newImage = $thumbnailFilter->load($image2, [
            'size' => [$width, $height],
            'allow_upscale' => true,
            'mode' => 'THUMBNAIL_INSET',
        ]);
        $newImage->save($path);
    }
}
