<?php

namespace App\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class REImageListener
{
    private string $upload_tmp_dir;

    public function __construct($upload_tmp_dir)
    {
        $this->upload_tmp_dir = $upload_tmp_dir;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        foreach (get_class_methods($entity) as $method) {
            if (preg_match('#get(.+)2ImagePath#', $method, $m)) {
                $property = substr(strtolower($m[1]), 0, 1).substr($m[1], 1);
                if (array_key_exists($property, $args->getEntityChangeSet())) {
                    // Remove old image
                    $supposedPath = $args->getEntityChangeSet()[$property][0];
                    if ($supposedPath && file_exists($supposedPath)) {
                        unlink($supposedPath);
                    }
                }
            }
        }
        $this->correctAndSetImageToFolder($args->getObject());
    }

    private function correctAndSetImageToFolder($entity)
    {
        foreach (get_class_methods($entity) as $method) {
            if (preg_match('#get(.+)2ImagePath#', $method, $m)) {
                $property = substr(strtolower($m[1]), 0, 1).substr($m[1], 1);
                $getMethod = 'get'.ucfirst($property);
                $setMethod = 'set'.ucfirst($property);
                $uploadDir = $this->upload_tmp_dir;

                if (null !== $entity->$getMethod()) {
                    if (!file_exists($entity->$getMethod())) {
                        continue;
                    }

                    $tmpFile = new File($entity->$getMethod());
                    if (preg_match('#'.$uploadDir.'#', $tmpFile->getRealPath())) {
                        $newPath = $entity->$method().'.'.preg_replace('#jpeg#', 'jpg', $tmpFile->guessExtension());
                        preg_match('#^(.+)/(.+?)\.(.+?)$#', $newPath, $m);

                        // Get olds image and delete them
                        if (!file_exists($m[1])) {
                            mkdir($m[1]);
                        }
                        foreach ((new Finder())->in($m[1])->name($m[2].'*')->files() as $file) {
                            unlink($file);
                        }

                        $tmpFile->move($m[1].'/', $m[2].'.'.$m[3]);
                        $entity->$setMethod($newPath);
                    }
                }
            }
        }
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->correctAndSetImageToFolder($args->getObject());
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        foreach (get_class_methods($entity) as $method) {
            if (preg_match('#get(.+)2ImagePath#', $method, $m)) {
                $property = substr(strtolower($m[1]), 0, 1).substr($m[1], 1);
                $getMethod = 'get'.ucfirst($property);
                $filePath = $entity->$getMethod();
                if ($filePath && file_exists($filePath)) {
                    unlink($filePath);
                }
            }
        }
    }
}
