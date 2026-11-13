---
paths:
  - "src/Entity/Document.php"
  - "src/Entity/DonneePersonnelle.php"
  - "config/validator/personal_data.yml"
  - "src/ListenerV2/DocumentListener.php"
  - "src/ManagerV2/DocumentManager.php"
  - "src/Provider/DocumentProvider.php"
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

## Où la règle s'applique (décision PO)
- **Oui** : le web (renommage, import) et l'app mobile Reconnect (API v1 `/appli`, v2 `/api/v2`).
- **Non** : les applications tierces (RP, Rosalie, tout client OAuth sans utilisateur) gardent le comportement d'avant, pour ne pas casser leur intégration.
  - API v3 / API Platform : la contrainte est hors du groupe `Default`, le seul validé par API Platform.
  - Autres API : `validateName()` ne fait rien quand `Oauth2Helper::isAuthenticatedAsClient()` (token sans utilisateur).
- Formulaire de renommage : `validation_groups` de `RenameDocumentType`.
- Points d'entrée sans formulaire : appeler `DocumentManager::validateName($name)`, qui lève une `UnprocessableEntityHttpException` (422).
  - L'appeler **après** le contrôle d'accès (403/404 avant 422), et **avant** l'envoi S3 (pas de fichier orphelin).
  - Pour un import de plusieurs fichiers, valider **tous** les noms avant d'enregistrer le premier.
  - Ne pas l'appeler dans `DocumentProvider::uploadFile` ni `DocumentManager::uploadFile` : ils sont partagés avec Rosalie, l'API v3 et l'import Nidus.

## Pièges
- `DocumentListener` (prePersist/preUpdate) nettoie le nom (slug) et **ajoute toujours l'extension du document** après la validation : `aaa` devient `aaa.pdf`. Une extension synonyme (même type MIME, ex. `jpeg` pour un document `jpg`) est retirée du nom ; une extension différente y reste (`rapport.docx` en PDF → `rapport_docx.pdf`).
- Le formulaire de renommage affiche le nom sans extension (`getter`) ; le listener la remet à l'enregistrement.
- Des documents existants dépassent la limite (15 en prod en octobre 2026, non migrés) : ils doivent être raccourcis au prochain renommage web ou mobile.
- Après une modification de la contrainte ou d'un attribut `#[ApiResource]`, vider le cache de test **non-debug** (`-e APP_DEBUG=0`), sinon les tests utilisent l'ancienne règle.

## Tests
- Web : `tests/v2/Controller/DocumentController/RenameTest.php`, `CreateTest.php`.
- App mobile (API v1/v2) : `tests/v2/API/v2/Document/DocumentNameLengthTest.php`.
- Applications tierces (API v3) : `tests/v2/API/v3/Document/DocumentAPIv3Test.php`.
- Nom enregistré : `tests/v2/Listener/DocumentListenerTest.php`.
