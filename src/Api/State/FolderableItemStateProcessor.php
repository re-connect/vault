<?php

namespace App\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Document;
use App\Entity\Dossier;

/** @implements ProcessorInterface<Document|Dossier, Document|Dossier|void> */
readonly class FolderableItemStateProcessor implements ProcessorInterface
{
    public function __construct(private ProcessorInterface $persistProcessor)
    {
    }

    /** @param Document|Dossier $data */
    #[\Override]
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Setting the parent folder through an IRI bypasses move(), which keeps the items of a private folder private
        $parentFolder = $data instanceof Document ? $data->getDossier() : $data->getDossierParent();
        $data->move($parentFolder);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
