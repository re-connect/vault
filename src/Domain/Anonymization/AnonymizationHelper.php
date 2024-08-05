<?php

namespace App\Domain\Anonymization;

class AnonymizationHelper
{
    final public const string ANONYMIZED_SUBJECT = 'Titre anonymisé';
    final public const string ANONYMIZED_DOCUMENT = 'Document anonymisé';
    final public const string ANONYMIZED_DOCUMENT_EXTENSION = 'png';
    final public const string ANONYMIZED_DOCUMENT_OBJECT_KEY = 'anonymous.png';
    final public const string ANONYMIZED_DOCUMENT_THUMBNAIL_KEY = 'anonymous-thumbnail.png';
    final public const string ANONYMIZED_CONTENT = 'Ce contenu a été anonymisé pour protéger les données personnelles qui pourraient être présentes';
    final public const string ANONYMIZED_EMAIL = 'anonymized@yopmail.com';
    final public const string ANONYMIZED_SECRET_ANSWER = 'Réponse secrète anonymisée';
}
