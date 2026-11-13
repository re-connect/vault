---
paths:
  - "src/Entity/Document.php"
  - "src/Entity/DonneePersonnelle.php"
  - "config/validator/personal_data.yml"
  - "src/ListenerV2/DocumentListener.php"
  - "src/ManagerV2/DocumentManager.php"
  - "src/FormV2/RenameDocumentType.php"
  - "src/ControllerV2/DocumentController.php"
  - "src/Controller/Api/UploadDocumentController.php"
  - "src/Controller/Rest/*.php"
---

# Nom des documents : 128 caractères maximum, hors extension

## Règle
- Le nom d'un document (`DonneePersonnelle::$nom`) est limité à `Document::NAME_MAX_LENGTH` (128) caractères, **extension non comprise**, comme sur RP.
- La contrainte est dans `config/validator/personal_data.yml` : `Length` sur `nom`, avec `normalizer: Document::removeExtension`, dans le groupe `Document::NAME_VALIDATION_GROUP` (`document_name`).
- Elle ne s'applique qu'aux documents. Notes, contacts, événements et dossiers restent limités par la colonne (255), sans validation de longueur.
- Message : clé `document_name_too_long` (domaine `validators`, `{{ limit }}`), traduite sur Crowdin.

## Où la règle s'applique (décision PO, novembre 2026)
- **Oui** : le web du coffre (CFN), pour un pro ou un bénéficiaire.
  - Renommage : `validation_groups` de `RenameDocumentType`.
  - Import : `DocumentManager::uploadFiles()`, qui valide chaque nom avant l'envoi S3 (pas de fichier orphelin) et affiche un message flash.
- **Pas encore** : l'app mobile (API v1 `/appli`, v2 `/api/v2`) et les applications tierces (API v3, Rosalie). Elles gardent le comportement d'avant ; le PO décidera plus tard.
  - API v3 / API Platform : la contrainte est hors du groupe `Default`, le seul validé par API Platform.
  - Ne pas valider le nom dans `DocumentProvider::uploadFile` ni `DocumentManager::uploadFile` : ils sont partagés avec ces canaux et l'import Nidus.

## Pièges
- `DocumentListener` (prePersist/preUpdate) nettoie le nom après la validation :
  - il le translittère (slug), ce qui peut l'allonger (`中文` devient `zhong_wen`), puis le tronque pour tenir dans la colonne (`DonneePersonnelle::NAME_COLUMN_LENGTH`) ;
  - il **ajoute toujours l'extension du document** (`aaa` devient `aaa.pdf`) ; une extension synonyme (même type MIME, ex. `jpeg` pour un document `jpg`) est retirée du nom, une extension différente y reste (`rapport.docx` en PDF → `rapport_docx.pdf`).
- La validation porte donc sur le nom saisi, pas sur le nom enregistré.
- Le formulaire de renommage affiche le nom sans extension (`getter`) ; le listener la remet à l'enregistrement.
- Des documents existants dépassent la limite (non migrés) : ils doivent être raccourcis au prochain renommage web.

## Tests
- Web : `tests/v2/Controller/DocumentController/RenameTest.php`, `CreateTest.php`.
- Applications tierces (API v3) : `tests/v2/API/v3/Document/DocumentAPIv3Test.php`.
- Nom enregistré : `tests/v2/Listener/DocumentListenerTest.php`.
- Extension : `tests/v2/Entity/DocumentTest.php`.
