<?php

namespace App\Service;

use Imagick;

class PdfService
{
    /**
     * @param int $size
     * @param int $page
     *
     * @return bool|string
     *
     * @throws \ImagickException
     */
    public function genPdfThumbnail($source, $target, $size = 256, $page = 1)
    {
        if (file_exists($source) && !is_dir($source)) { // source path must be available and not be a directory
            if ('application/pdf' != \mime_content_type($source)) {
                return false;                // source is not a pdf file returns a failure
            }

            $sepa = '/';                // using '/' as file separation for nfs on linux.
            $target = dirname($source).$sepa.$target;
            $size = intval($size);            // only use as integer, default is 256
            $page = intval($page);            // only use as integer, default is 1

            --$page;                    // default page 1, must be treated as 0 hereafter
            if ($page < 0) {
                $page = 0;
            }            // we cannot have negative values

            $img = new \Imagick($source."[$page]"); // [0] = first page, [1] = second page

            $imH = $img->getImageHeight();
            $imW = $img->getImageWidth();
            if (0 == $imH) {
                $imH = 1;
            }            // if the pdf page has no height use 1 instead
            if (0 == $imW) {
                $imW = 1;
            }            // if the pdf page has no width use 1 instead

            $sizR = round($size * (min($imW, $imH) / max($imW, $imH))); // relative pixels of the shorter side

            $img->setImageColorspace(255);        // prevent image colors from inverting
            $img->setImageBackgroundColor('white');    // set background color and flatten
            $img = $img->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);            // prevents black zones on transparency in pdf
            $img->setimageformat('jpeg');

            if ($imH == $imW) {
                $img->thumbnailimage($size, $size);
            }    // square page
            if ($imH < $imW) {
                $img->thumbnailimage($size, $sizR);
            }    // landscape page orientation
            if ($imH > $imW) {
                $img->thumbnailimage($sizR, $size);
            }    // portrait page orientation

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            } // if not there make target directory

            $img->writeimage($target);

            $img->clear();
            $img->destroy();

            if (file_exists($target)) {
                return $target;
            } // return the path to the new file for further processing
        }

        return false;    // the source file was not available, or Imagick didn't create a file, so returns a failure
    }
}
