# Documents et dossiers

Un document (`Document`) est un fichier stocké sur S3, avec une miniature. Un dossier (`Dossier`, ressource API `folder`) regroupe des documents et des sous-dossiers. Les deux appartiennent à un bénéficiaire, sont **privés** ou **partagés**, et implémentent `FolderableEntityInterface` et `ClientResourceInterface`.

Règles portées par les entités, le voter ou les managers partagés :

- **Extensions autorisées** : `Document::ALLOWED_FILE_EXTENSIONS`, vérifiées par `DocumentManager::uploadFile` (web et v3).
- **Visibilité à l'upload** : un document hérite de la visibilité de son dossier ; à la racine, il est privé si c'est le bénéficiaire qui l'envoie, partagé sinon (`DocumentManager::createDocumentFromFile`, web et v3).
- **Dossier privé** : tout élément déplacé dedans devient privé (`move()`), et un élément qu'il contient ne peut pas être rendu partagé (`canToggleVisibility()`).
- **Bascule d'un dossier** : elle s'applique à tous ses sous-dossiers et documents (`Dossier::toggleVisibility`).
- **Arborescence** : un dossier ne peut pas être déplacé dans un de ses descendants (`NoCircularDependency`).
- **Même bénéficiaire** : un document ou un sous-dossier ne peut être rangé que dans un dossier du même bénéficiaire (contrainte `SameBeneficiaryAsFolder` sur `Document::$dossier` et `Dossier::$dossierParent`, validée aussi à l'upload par `DocumentManager`). Côté web, `FolderDestinationVoter` vérifie en plus l'accès au dossier de destination.
- **Suppression réservée au bénéficiaire** : un pro ne peut supprimer ni document ni dossier (`PersonalDataVoter::canDelete`).
- **Nom** : nettoyé et suffixé par l'extension (`DocumentListener`).
- **Client Rosalie** : les documents envoyés par ce client vont dans un dossier dédié (`FolderManager::getOrCreateClientFolder`).

## 1. User stories

### Bénéficiaire

#### Web

Documents :

- **DOC-BENEF-WEB-01** : En tant que bénéficiaire, je peux accéder à « Mes documents » depuis la navigation et voir mes dossiers et documents de la racine, privés et partagés.
  - Refus : la liste d'un autre bénéficiaire m'est interdite.
- **DOC-BENEF-WEB-02** : En tant que bénéficiaire, je peux rechercher un document ou un dossier, à la racine ou dans un dossier.
- **DOC-BENEF-WEB-03** : En tant que bénéficiaire, je peux déposer un ou plusieurs fichiers, à la racine ou dans un dossier.
  - Refus : extension non autorisée (message par fichier) ; dossier inexistant (400) ; dossier d'un autre bénéficiaire (403).
- **DOC-BENEF-WEB-04** : En tant que bénéficiaire, je peux consulter le détail d'un document (aperçu, miniature).
  - Refus : document d'un autre bénéficiaire.
- **DOC-BENEF-WEB-05** : En tant que bénéficiaire, je peux télécharger un document.
  - Refus : document d'un autre bénéficiaire.
- **DOC-BENEF-WEB-06** : En tant que bénéficiaire, je peux renommer un document.
  - Refus : nom vide ; document d'un autre bénéficiaire.
- **DOC-BENEF-WEB-07** : En tant que bénéficiaire, je peux déplacer un document dans un dossier ou à la racine, via une arborescence.
  - Effet : il devient privé si le dossier de destination est privé.
  - Refus : document d'un autre bénéficiaire ; dossier de destination d'un autre bénéficiaire (403).
- **DOC-BENEF-WEB-08** : En tant que bénéficiaire, je peux basculer un document entre privé et partagé.
  - Refus : document situé dans un dossier privé ; document d'un autre bénéficiaire.
- **DOC-BENEF-WEB-09** : En tant que bénéficiaire, je peux envoyer un document par email, à une adresse saisie ou à un de mes contacts. Le destinataire reçoit un lien de téléchargement.
  - Refus : document d'un autre bénéficiaire ; contact sans email.
- **DOC-BENEF-WEB-10** : En tant que bénéficiaire, je peux supprimer un document après confirmation (fichier et miniature supprimés du stockage).
  - Refus : document d'un autre bénéficiaire.

Dossiers :

- **FOLDER-BENEF-WEB-01** : En tant que bénéficiaire, je peux créer un dossier à la racine, privé par défaut, avec des noms suggérés (santé, logement, identité, impôts, travail).
  - Refus : nom vide.
- **FOLDER-BENEF-WEB-02** : En tant que bénéficiaire, je peux créer un sous-dossier.
  - Effet : il est privé si le dossier parent est privé.
  - Refus : dossier parent d'un autre bénéficiaire.
- **FOLDER-BENEF-WEB-03** : En tant que bénéficiaire, je peux ouvrir un dossier et voir son contenu.
  - Refus : dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-WEB-04** : En tant que bénéficiaire, je peux consulter le détail d'un dossier.
- **FOLDER-BENEF-WEB-05** : En tant que bénéficiaire, je peux renommer un dossier.
  - Refus : nom vide ; dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-WEB-06** : En tant que bénéficiaire, je peux déplacer un dossier dans un autre dossier ou à la racine.
  - Refus : déplacement dans un de ses sous-dossiers ; dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-WEB-07** : En tant que bénéficiaire, je peux basculer un dossier entre privé et partagé, avec tout son contenu.
  - Refus : sous-dossier d'un dossier privé ; dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-WEB-08** : En tant que bénéficiaire, je peux télécharger un dossier entier (archive).
  - Refus : dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-WEB-09** : En tant que bénéficiaire, je peux supprimer un dossier, avec ses sous-dossiers et documents.
  - Refus : dossier d'un autre bénéficiaire.

#### API v3 (password grant)

Documents :

- **DOC-BENEF-V3-01** : En tant que bénéficiaire, je peux lister tous mes documents (`GET /documents`, `GET /beneficiaries/{id}/documents`), éventuellement filtrés par dossier (`folderId`).
  - Refus : documents d'un autre bénéficiaire.
- **DOC-BENEF-V3-02** : En tant que bénéficiaire, je peux consulter un document, avec ses URL de téléchargement et de miniature (`GET /documents/{id}`).
  - Refus : document d'un autre bénéficiaire.
- **DOC-BENEF-V3-03** : En tant que bénéficiaire, je peux déposer un fichier, à la racine ou dans un de mes dossiers (`POST /documents`, multipart).
  - Le `beneficiary_id` envoyé est ignoré.
  - Refus : fichier absent ; extension non autorisée ; dossier inexistant ou d'un autre bénéficiaire.
- **DOC-BENEF-V3-04** : En tant que bénéficiaire, je peux renommer un document ou le déplacer (`PATCH /documents/{id}`, ou `PATCH /documents/{id}/move` avec `folder_id`).
  - Effet : il devient privé si le dossier de destination est privé.
  - Refus : dossier d'un autre bénéficiaire ; changement de bénéficiaire.
- **DOC-BENEF-V3-05** : En tant que bénéficiaire, je peux basculer un document entre privé et partagé (`PATCH /documents/{id}/toggle-visibility`).
  - Refus : document dans un dossier privé ; document d'un autre bénéficiaire.
- **DOC-BENEF-V3-06** : En tant que bénéficiaire, je peux envoyer un document par email (`POST /api/v3/documents/{id}/share`).
  - Refus : email absent (400) ; document d'un autre bénéficiaire.
- **DOC-BENEF-V3-07** : En tant que bénéficiaire, je peux supprimer un document (`DELETE /documents/{id}`).
  - Refus : document d'un autre bénéficiaire.

Dossiers :

- **FOLDER-BENEF-V3-01** : En tant que bénéficiaire, je peux lister tous mes dossiers (`GET /folders`, `GET /beneficiaries/{id}/folders`), éventuellement filtrés par dossier parent.
  - Refus : dossiers d'un autre bénéficiaire.
- **FOLDER-BENEF-V3-02** : En tant que bénéficiaire, je peux consulter un dossier (`GET /folders/{id}`).
  - Refus : dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-V3-03** : En tant que bénéficiaire, je peux créer un dossier ou un sous-dossier (`POST /folders`, avec `dossierParentId`).
  - Partagé par défaut si `bPrive` n'est pas envoyé ; privé si le parent est privé.
  - Refus : nom vide ; parent inexistant ou d'un autre bénéficiaire (400).
- **FOLDER-BENEF-V3-04** : En tant que bénéficiaire, je peux renommer ou déplacer un dossier (`PATCH /folders/{id}`).
  - Refus : déplacement dans un de ses sous-dossiers ; parent d'un autre bénéficiaire ; changement de bénéficiaire.
- **FOLDER-BENEF-V3-05** : En tant que bénéficiaire, je peux basculer un dossier et son contenu entre privé et partagé (`PATCH /folders/{id}/toggle-visibility`).
  - Refus : sous-dossier d'un dossier privé ; dossier d'un autre bénéficiaire.
- **FOLDER-BENEF-V3-06** : En tant que bénéficiaire, je peux supprimer un dossier et son contenu (`DELETE /folders/{id}`).
  - Refus : dossier d'un autre bénéficiaire.

#### API v1/v2

- **DOC-BENEF-LEGACY-01** : En tant que bénéficiaire, je peux lister mes documents, à la racine ou dans un dossier (`GET /beneficiaries/{id}/documents`, v1 : `/appli/beneficiaries/{id}/folders/{folderId}/documents`).
- **DOC-BENEF-LEGACY-02** : En tant que bénéficiaire, je peux consulter un document et en télécharger les différentes versions (`GET /documents/{id}/{version}` : original, miniatures).
- **DOC-BENEF-LEGACY-03** : En tant que bénéficiaire, je peux déposer un fichier (`POST /beneficiaries/{id}/documents`).
- **DOC-BENEF-LEGACY-04** : En tant que bénéficiaire, je peux renommer un document (`PATCH /documents/{id}/name`, v2 : aussi `PATCH /documents/{id}`).
- **DOC-BENEF-LEGACY-05** : En tant que bénéficiaire, je peux mettre un document dans un dossier ou l'en sortir (`PATCH /documents/{id}/folder/{dossierId}`, `PATCH /documents/{id}/get-out-from-folder`).
- **DOC-BENEF-LEGACY-06** : En tant que bénéficiaire, je peux basculer un document entre privé et partagé (`PATCH /documents/{id}/toggle-access`).
- **DOC-BENEF-LEGACY-07** : En tant que bénéficiaire, je peux envoyer un document par email (`POST /documents/{id}/send`, v2 : aussi `POST /documents/{id}/share`).
- **DOC-BENEF-LEGACY-08** : En tant que bénéficiaire, je peux supprimer un document (`DELETE /documents/{id}`).
- **FOLDER-BENEF-LEGACY-01** : En tant que bénéficiaire, je peux lister, créer, renommer (`PATCH /folders/{id}/name` ou `PATCH /folders/{id}`), basculer (`toggle-access`) et supprimer mes dossiers (`/api/v2/beneficiaries/{id}/folders`, `/api/v2/folders/{id}`).
- **FOLDER-BENEF-LEGACY-02** : En tant que bénéficiaire, je peux envoyer un dossier entier par email (`POST /api/v2/folders/{id}/send`).

Cas de refus v1/v2 : élément d'un autre bénéficiaire (voter legacy `DonneePersonnelleVoter`). [À VÉRIFIER] au cas par cas, ces contrôleurs n'étant pas génériques.

### Pro

#### Web

- **DOC-PRO-WEB-01** : En tant que pro, je peux voir les dossiers et documents partagés d'un bénéficiaire que je gère, et y rechercher.
  - Refus : bénéficiaire que je ne gère pas ; les éléments privés n'apparaissent pas.
- **DOC-PRO-WEB-02** : En tant que pro, je peux déposer des fichiers pour un bénéficiaire que je gère, partagés par défaut (ou privés si le dossier de destination l'est).
  - Refus : extension non autorisée ; bénéficiaire que je ne gère pas.
- **DOC-PRO-WEB-03** : En tant que pro, je peux consulter, télécharger et renommer un document partagé.
  - Refus : document privé ; bénéficiaire que je ne gère pas.
- **DOC-PRO-WEB-04** : En tant que pro, je peux déplacer un document partagé dans un dossier.
  - Refus : document privé.
- **DOC-PRO-WEB-05** : En tant que pro, je peux rendre privé un document partagé (j'en perds ensuite l'accès).
  - Refus : document privé ; document dans un dossier privé.
- **DOC-PRO-WEB-06** : En tant que pro, je peux envoyer un document partagé par email ou à un contact partagé du bénéficiaire.
  - Refus : document privé ; contact sans email.
- **DOC-PRO-WEB-07** : En tant que pro, je ne peux pas supprimer un document.
- **FOLDER-PRO-WEB-01** : En tant que pro, je peux créer un dossier ou un sous-dossier pour un bénéficiaire que je gère, partagé par défaut.
  - Refus : nom vide ; bénéficiaire que je ne gère pas ; dossier parent privé.
- **FOLDER-PRO-WEB-02** : En tant que pro, je peux ouvrir, consulter, renommer, déplacer et télécharger un dossier partagé.
  - Refus : dossier privé ; bénéficiaire que je ne gère pas ; déplacement circulaire.
- **FOLDER-PRO-WEB-03** : En tant que pro, je peux rendre privé un dossier partagé et son contenu.
  - Refus : dossier privé ; sous-dossier d'un dossier privé.
- **FOLDER-PRO-WEB-04** : En tant que pro, je ne peux pas supprimer un dossier.

#### API v3 (password grant)

- **DOC-PRO-V3-01** : En tant que pro, je peux lister les documents partagés de tous mes bénéficiaires ou d'un bénéficiaire géré (`GET /documents`, `GET /beneficiaries/{id}/documents`).
  - Refus : bénéficiaire que je ne gère pas ; les privés n'apparaissent pas.
- **DOC-PRO-V3-02** : En tant que pro, je peux consulter un document partagé (`GET /documents/{id}`).
  - Refus : document privé ; bénéficiaire que je ne gère pas.
- **DOC-PRO-V3-03** : En tant que pro, je peux déposer un fichier pour un bénéficiaire géré (`POST /documents` avec `beneficiary_id`, et éventuellement `folder_id`).
  - Refus : `beneficiary_id` manquant (400) ; bénéficiaire inexistant (404) ou non géré (403) ; dossier privé (403) ; dossier d'un autre bénéficiaire (400) ; extension non autorisée.
- **DOC-PRO-V3-04** : En tant que pro, je peux renommer ou déplacer un document partagé (`PATCH /documents/{id}`, `PATCH /documents/{id}/move`).
  - Refus : document privé ; dossier de destination privé (403) ou d'un autre bénéficiaire (400).
- **DOC-PRO-V3-05** : En tant que pro, je peux rendre privé un document partagé (`PATCH /documents/{id}/toggle-visibility`).
  - Refus : document privé ; document dans un dossier privé.
- **DOC-PRO-V3-06** : En tant que pro, je peux envoyer un document partagé par email (`POST /api/v3/documents/{id}/share`).
  - Refus : document privé ; email absent.
- **DOC-PRO-V3-07** : En tant que pro, je ne peux pas supprimer un document (`DELETE /documents/{id}` refusé).
- **FOLDER-PRO-V3-01** : En tant que pro, je peux lister et consulter les dossiers partagés de mes bénéficiaires (`GET /folders`, `GET /beneficiaries/{id}/folders`, `GET /folders/{id}`).
  - Refus : dossier privé ; bénéficiaire que je ne gère pas.
- **FOLDER-PRO-V3-02** : En tant que pro, je peux créer un dossier ou un sous-dossier pour un bénéficiaire géré (`POST /folders` avec `beneficiaireId`).
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire non géré (404) ; parent d'un autre bénéficiaire (400).
- **FOLDER-PRO-V3-03** : En tant que pro, je peux renommer, déplacer ou rendre privé un dossier partagé (`PATCH /folders/{id}`, `PATCH /folders/{id}/toggle-visibility`).
  - Refus : dossier privé ; déplacement circulaire ; sous-dossier d'un dossier privé.
- **FOLDER-PRO-V3-04** : En tant que pro, je ne peux pas supprimer un dossier (`DELETE /folders/{id}` refusé).

#### API v1/v2

- **DOC-PRO-LEGACY-01** : En tant que pro, je peux lister, consulter, déposer, renommer, classer, envoyer par email les documents partagés d'un bénéficiaire géré, et signaler un document (`report-abuse` ou `toggle-access`, qui le rend privé).
  - Refus : document privé (`ProPrivateDataAccessTest` couvre partage, miniatures).
- **DOC-PRO-LEGACY-02** : En tant que pro, je peux supprimer un document partagé. [À VÉRIFIER] le voter legacy autorise la suppression à un pro, contrairement au voter v2/v3.
- **FOLDER-PRO-LEGACY-01** : En tant que pro, je peux lister, créer, renommer, envoyer par email et signaler les dossiers partagés d'un bénéficiaire géré. [À VÉRIFIER] suppression.

### Client API (client credentials)

#### API v3

- **DOC-CLIENT-V3CC-01** : En tant que client avec le scope `documents_read`, je peux lister les documents partagés d'un bénéficiaire lié (`GET /beneficiaries/{id}/documents`).
  - Pas de `GET /documents` ni de `GET /documents/{id}` pour un client : ces opérations exigent `ROLE_USER`.
- **DOC-CLIENT-V3CC-02** : En tant que client avec le scope `documents_create`, je peux déposer un fichier pour un bénéficiaire lié, identifié par `distant_id` ou `beneficiary_id`, éventuellement dans un dossier (`POST /documents`).
  - Pour Rosalie, le document va dans le dossier dédié au client.
  - Refus : identifiant absent (400) ; bénéficiaire inconnu (404) ou non lié (403) ; dossier d'un autre bénéficiaire (400) ; scope absent.
- **DOC-CLIENT-V3CC-03** : En tant que client avec le scope `documents_update`, je peux rendre privé un document partagé (`PATCH /documents/{id}/toggle-visibility`).
  - Refus : document privé ; bénéficiaire non lié ; scope absent.
- **FOLDER-CLIENT-V3CC-01** : En tant que client avec le scope `documents_read`, je peux lister et consulter les dossiers partagés des bénéficiaires liés (`GET /folders`, `GET /folders/{id}`, `GET /beneficiaries/{id}/folders`).
  - Refus : dossier privé ; bénéficiaire non lié ; scope absent.
- **FOLDER-CLIENT-V3CC-02** : En tant que client avec le scope `folders_read`, je peux obtenir l'arborescence des dossiers partagés de premier niveau d'un bénéficiaire lié (`GET /beneficiaries/{id}/folders_tree`).
  - Refus : bénéficiaire non lié (réponse vide).
- **FOLDER-CLIENT-V3CC-03** : En tant que client avec le scope `documents_create`, je peux créer un dossier pour un bénéficiaire lié (`POST /folders` avec `beneficiaireId`).
- **FOLDER-CLIENT-V3CC-04** : En tant que client avec le scope `documents_update`, je peux rendre privé un dossier partagé (`PATCH /folders/{id}/toggle-visibility`).
- Pas de modification, de déplacement, de suppression ni d'envoi par email pour un client.

#### API v2

- **DOC-CLIENT-LEGACY-01** : En tant que client, je peux lister, consulter, déposer, renommer, classer et supprimer les documents d'un bénéficiaire lié, et les envoyer par email. [À VÉRIFIER] droits exacts et filtrage des privés.
- **FOLDER-CLIENT-LEGACY-01** : En tant que client, je peux gérer les dossiers d'un bénéficiaire lié (liste, création, renommage, envoi, bascule, suppression). [À VÉRIFIER] droits exacts.

### Différences d'implémentation à exploiter aux étapes 2 et 4

- **Sécurité du toggle v3**, trois formes : document `ROLE_USER ? TOGGLE_VISIBILITY : UPDATE` (client via `ApiVoter`) ; dossier `ROLE_OAUTH2_DOCUMENTS_UPDATE or (ROLE_USER and TOGGLE_VISIBILITY)` ; contact `UPDATE`.
- **Accès client aux documents** : un client ne peut pas lire `GET /documents` ni `GET /documents/{id}` (`ROLE_USER` seul), mais peut lister les documents via `/beneficiaries/{id}/documents` (scope `documents_read`). Les dossiers, eux, sont lisibles avec `documents_read`.
- **Envoi par email** : web `SharedDocumentManager::generateSharedDocumentAndSendEmail` ; v3 `DocumentApiController::shareDocument` appelle directement `SharedDocumentFactory` et `MailerService`.
- **Déplacement** : web `FolderableItemManager::move` (avec validation) ; v3 `PATCH /documents/{id}/move` passe aussi par `FolderableItemManager::move`, mais `PATCH /documents/{id}` et `PATCH /folders/{id}` passent par `FolderableItemStateProcessor` (appel direct à `move()`, validation par API Platform).
- **Téléchargement d'un dossier entier** : web uniquement (`FolderTreeDownloader`). La v2 propose l'envoi d'un dossier par email ; la v3 n'a ni l'un ni l'autre.

## 2. Écarts

Légende : ✅ disponible, ⚠️ disponible avec un comportement différent, ❌ absent, ➖ sans objet.

### Matrice bénéficiaire

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister dossiers et documents (privés compris) | ✅ DOC-WEB-01, FOLDER-WEB-03 | ✅ DOC-V3-01, FOLDER-V3-01 | ✅ |
| Rechercher | ✅ DOC-WEB-02 | ❌ (D5) | ❌ |
| Déposer un fichier | ✅ DOC-WEB-03 | ✅ DOC-V3-03 | ✅ |
| Consulter, télécharger un document | ✅ DOC-WEB-04, 05 | ✅ DOC-V3-02 (URL présignée) | ✅ |
| Renommer un document | ✅ DOC-WEB-06 | ✅ DOC-V3-04 | ✅ |
| Déplacer un document | ✅ DOC-WEB-07 | ✅ DOC-V3-04 (deux routes) | ✅ |
| Basculer un document | ✅ DOC-WEB-08 | ✅ DOC-V3-05 | ✅ |
| Envoyer un document par email | ✅ DOC-WEB-09 (adresse ou contact) | ✅ DOC-V3-06 (adresse) | ✅ |
| Supprimer un document | ✅ DOC-WEB-10 | ✅ DOC-V3-07 | ✅ |
| Créer un dossier | ✅ FOLDER-WEB-01, privé par défaut | ⚠️ FOLDER-V3-03, partagé par défaut (D4) | ⚠️ partagé par défaut [À VÉRIFIER] |
| Créer un sous-dossier | ✅ FOLDER-WEB-02 | ✅ FOLDER-V3-03 | [À VÉRIFIER] |
| Renommer, déplacer un dossier | ✅ FOLDER-WEB-05, 06 | ✅ FOLDER-V3-04 | ⚠️ renommage seul [À VÉRIFIER] |
| Basculer un dossier et son contenu | ✅ FOLDER-WEB-07 | ✅ FOLDER-V3-05 | ✅ |
| Télécharger un dossier entier | ✅ FOLDER-WEB-08 | ❌ (D6) | ❌ (envoi par email à la place) |
| Envoyer un dossier par email | ❌ | ❌ | ✅ FOLDER-LEGACY-02 |
| Supprimer un dossier et son contenu | ✅ FOLDER-WEB-09 | ✅ FOLDER-V3-06 | [À VÉRIFIER] |

### Matrice pro

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister et rechercher les éléments partagés d'un bénéficiaire géré | ✅ DOC-PRO-WEB-01 | ⚠️ DOC-PRO-V3-01, FOLDER-PRO-V3-01, sans recherche (D5) | ✅ |
| Lister les documents de tous mes bénéficiaires | ❌ | ✅ (D9) | ❌ |
| Déposer un fichier | ✅ DOC-PRO-WEB-02, dossier privé ou d'un autre bénéficiaire refusé | ✅ DOC-PRO-V3-03, dossier privé refusé | ✅ |
| Consulter, télécharger, renommer, déplacer un document partagé | ✅ DOC-PRO-WEB-03, 04 | ✅ DOC-PRO-V3-02, 04 | ✅ |
| Rendre privé un document ou un dossier partagé | ✅ DOC-PRO-WEB-05, FOLDER-PRO-WEB-03 | ✅ DOC-PRO-V3-05, FOLDER-PRO-V3-03 | ⚠️ via `report-abuse` |
| Envoyer un document par email | ✅ DOC-PRO-WEB-06 | ✅ DOC-PRO-V3-06 | ✅ |
| Créer, renommer, déplacer un dossier | ✅ FOLDER-PRO-WEB-01, 02 | ✅ FOLDER-PRO-V3-02, 03 | ✅ |
| Télécharger un dossier entier | ✅ FOLDER-PRO-WEB-02 | ❌ (D6) | ❌ |
| Supprimer un document ou un dossier | ➖ refusé | ➖ refusé | [À VÉRIFIER] |

### Matrice client API

| Fonctionnalité | API v3 (client credentials) | API v2 |
|---|---|---|
| Lister les documents partagés d'un bénéficiaire lié | ✅ DOC-CLIENT-V3CC-01 (`documents_read`) | ✅ |
| Lister tous les documents, consulter un document par son id | ❌ (D7) | ✅ |
| Lister, consulter les dossiers partagés | ✅ FOLDER-CLIENT-V3CC-01 (`documents_read`) | ✅ |
| Arborescence de premier niveau | ✅ FOLDER-CLIENT-V3CC-02 (`folders_read`) | ➖ |
| Déposer un fichier | ✅ DOC-CLIENT-V3CC-02 | ✅ |
| Créer un dossier | ✅ FOLDER-CLIENT-V3CC-03 | ✅ |
| Rendre privé un document ou un dossier | ✅ DOC-CLIENT-V3CC-03, FOLDER-CLIENT-V3CC-04 | ✅ |
| Renommer, déplacer, supprimer, envoyer par email | ❌ (D8) | ✅ |

### Liste des écarts

| # | Écart | Canaux | Gravité | Décision à prendre |
|---|---|---|---|---|
| D4 | Dossier créé par le bénéficiaire : privé par défaut sur le web, partagé par défaut en v3. Même cause que E1. Les documents, eux, suivent la même règle partout. | Web / v3 | Haute (confidentialité) | Même décision que E1. |
| D5 | Pas de recherche en v3 (seul un filtre par dossier existe). | Web / v3 | Moyenne | Même décision que E2. |
| D6 | Pas de téléchargement d'un dossier entier en v3 (ni d'envoi par email comme en v2). | Web / v3 | Moyenne | Besoin côté app ? |
| D7 | Un client v3 ne peut ni lister `GET /documents` ni lire `GET /documents/{id}`, alors qu'il peut lister les documents d'un bénéficiaire via la sous-ressource, et lire les dossiers. Le document est la seule donnée personnelle dans ce cas. | v3 client | Moyenne | Voulu (par exemple pour ne pas exposer d'URL présignée) ou oubli ? |
| D8 | Un client v3 ne peut ni renommer, ni déplacer, ni supprimer, ni envoyer un document ou un dossier, alors que la v2 le permettait. Même famille que E3. | v3 client / v2 | Moyenne | Même décision que E3. |
| D9 | Liste globale des documents de tous les bénéficiaires d'un pro, en v3 seulement. | Web / v3 | Basse | Même décision que E6. |
| D12 | Consultation d'un bénéficiaire par un pro enregistrée sur la liste web des documents, pas en v3. Même cause que E10. | Web / v3 | Moyenne | Même décision que E10. |

### Remarques hors écarts

- **Envoi à un contact** : le web propose de choisir un contact ; la v3 attend une adresse. C'est la même situation que E7 des contacts.

## 3. Couverture de tests

Légende : ✅ cas nominal et cas de refus couverts, 🟡 couverture partielle, ❌ non couvert.

Chemins abrégés : `WebD/` = `tests/v2/Controller/DocumentController/`, `WebF/` = `tests/v2/Controller/FolderController/`, `V3/` = `tests/v2/API/v3/PasswordGrant/PersonalData/`, `V3D/` = `tests/v2/API/v3/PasswordGrant/Document/`, `V3CC/` = `tests/v2/API/v3/ClientCredentials/`, `V2/` = `tests/v2/API/v2/PasswordGrant/`.

Ce domaine est nettement mieux testé que les précédents : les tests web vérifient les effets (visibilité après déplacement, bascule récursive, refus dans un dossier privé), et la v3 a des tests dédiés à l'upload, au déplacement et au partage.

### Bénéficiaire

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| DOC-WEB-01 | 🟡 | `WebD/ListTest::testRoute` | Contenu de la liste |
| DOC-WEB-02 | ❌ | | Recherche (racine et dossier) |
| DOC-WEB-03 | 🟡 | `WebD/CreateTest::testRoute`, `testUpload`, `testCanNotUploadIntoForbiddenFolder`, `testCanNotUploadIntoUnknownFolder` ; visibilité et contrainte : `tests/v2/Manager/DocumentManager/CreateDocumentFromFileTest` | Extension refusée ; upload nominal dans un dossier |
| DOC-WEB-04 | ✅ | `WebD/DetailTest::testRoute` | |
| DOC-WEB-05 | ❌ | | Téléchargement (`document_download`) |
| DOC-WEB-06 | ✅ | `WebD/RenameTest` (route, formulaire valide, nom vide) ; `tests/v2/Listener/DocumentListenerTest` (nettoyage du nom) | |
| DOC-WEB-07 | ✅ | `WebD/MoveToFolderTest` (dont `testMoveToWrongBeneficiary`, `testProCanNotMoveDocumentBetweenManagedBeneficiaries`, 4 combinaisons de visibilité), `WebD/TreeViewMoveTest` ; `tests/v2/Entity/DocumentConstraintsTest` | |
| DOC-WEB-08 | ✅ | `WebD/ToggleVisibilityTest`, `WebD/SwitchPrivateTest` (dont refus dans un dossier privé) | |
| DOC-WEB-09 | 🟡 | `tests/v2/Controller/SharedDocument/ShareWithContactTest` | Envoi à une adresse saisie (`document_share`) |
| DOC-WEB-10 | 🟡 | `WebD/DeleteTest::testRoute` | Suppression effective |
| FOLDER-WEB-01 | 🟡 | `WebF/CreateTest` (route, formulaire, nom vide) | Dossier privé par défaut (D4) |
| FOLDER-WEB-02 | ✅ | `WebF/CreateSubFolderTest` (dont `testCreateSubfolder`) | |
| FOLDER-WEB-03 | 🟡 | `WebF/ListTest::testRoute` | Contenu ; recherche dans un dossier |
| FOLDER-WEB-04 | ❌ | | Détail d'un dossier (`folder_detail`) |
| FOLDER-WEB-05 | ✅ | `WebF/RenameTest` | |
| FOLDER-WEB-06 | ✅ | `WebF/FolderMoveToFolderTest` (dont `testShouldNotMoveToOtherBeneficiary`, `testProCanNotMoveFolderBetweenManagedBeneficiaries`, `testCanNotMoveParentFolderIntoChild`), `WebF/TreeViewMoveTest` ; `tests/v2/Entity/FolderTest` (dépendance circulaire, parent d'un autre bénéficiaire) | |
| FOLDER-WEB-07 | ✅ | `WebF/ToggleVisibilityTest` (dont `testVisibilityIsToggledRecursively`, refus sous dossier privé), `WebF/SwitchPrivateTest` | |
| FOLDER-WEB-08 | ❌ | | Téléchargement d'un dossier (`folder_download`) |
| FOLDER-WEB-09 | 🟡 | `WebF/DeleteTest` (dont `testShouldRedirectToParentFolderAfterDelete`) | Suppression effective du contenu |
| DOC-V3-01 | ✅ | `V3/PersonalDataCollectionTest` (méthodes génériques) | Filtre `folderId` |
| DOC-V3-02 | ✅ | `V3/PersonalDataItemTest::testGetItem` | |
| DOC-V3-03 | ✅ | `V3D/UploadTest::testBeneficiaryUploadsPrivateDocumentForThemself`, `testBeneficiaryUploadsIntoOwnFolder`, `testRejectsUnsupportedFile` | Dossier d'un autre bénéficiaire |
| DOC-V3-04 | ✅ | `V3D/MoveTest` (6 tests), `V3/PatchTest` (renommage, déplacement, dossier d'un autre bénéficiaire, dossier privé) | |
| DOC-V3-05 | ✅ | `V3/ToggleVisibilityTest` (dont `testCanNotShareItemInsidePrivateFolder`) | |
| DOC-V3-06 | ✅ | `V3D/ShareTest` (envoi, sans email, refus) | |
| DOC-V3-07 | 🟡 | `V3/DeleteTest` | Suppression effective |
| FOLDER-V3-01 à 05 | ✅ | Tests génériques `V3/` (dont `testBeneficiaryCreatesSubFolderInheritingPrivacy`, `testCanNotCreateSubFolderInAnotherBeneficiaryFolder`, `testCanNotMoveFolderIntoAnotherBeneficiaryFolder`, `testMakingFolderPrivateAlsoHidesItsContent`) ; icônes : `tests/v2/API/v3/PasswordGrant/Folder/FolderIconTest` | Déplacement circulaire via l'API ; visibilité par défaut (D4) |
| FOLDER-V3-06 | 🟡 | `V3/DeleteTest` | Suppression effective du contenu |
| Legacy | 🟡 | `V2/Document/DocumentAccessTest`, `V2/Document/MobileDocumentTest`, `V2/Folder/MobileFolderTest` | Envoi d'un dossier par email |

### Pro

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| DOC-PRO-WEB-01 | 🟡 | `WebD/ListTest::testRoute` (200, 403) ; `tests/v2/Manager/FolderManager/getRootFoldersTest::testMemberShouldGetSharedRootFoldersOnly` | Documents privés absents de la liste ; recherche |
| DOC-PRO-WEB-02 | ✅ | `WebD/CreateTest::testRoute` (201), `testCanNotUploadIntoForbiddenFolder` (dossier privé, dossier d'un autre bénéficiaire géré) ; `CreateDocumentFromFileTest` (« always shared for pro ») | |
| DOC-PRO-WEB-03 | 🟡 | `WebD/DetailTest`, `WebD/RenameTest` (dont privé 403) | Téléchargement |
| DOC-PRO-WEB-04 | ✅ | `WebD/MoveToFolderTest` | |
| DOC-PRO-WEB-05 | ✅ | `WebD/ToggleVisibilityTest`, `WebD/SwitchPrivateTest` (refus dans un dossier privé) | |
| DOC-PRO-WEB-06 | 🟡 | `ShareWithContactTest` (pro 302) | Document privé refusé ; envoi à une adresse |
| DOC-PRO-WEB-07 | ✅ | `WebD/DeleteTest` (pro avec relais commun 403) | |
| FOLDER-PRO-WEB-01 | 🟡 | `WebF/CreateTest`, `WebF/CreateSubFolderTest` (route 200 pour le pro) | Soumission par un pro ; parent privé refusé |
| FOLDER-PRO-WEB-02 | 🟡 | `WebF/RenameTest`, `WebF/FolderMoveToFolderTest`, `WebF/ListTest` | Téléchargement ; détail |
| FOLDER-PRO-WEB-03 | ✅ | `WebF/ToggleVisibilityTest`, `WebF/SwitchPrivateTest` | |
| FOLDER-PRO-WEB-04 | ✅ | `WebF/DeleteTest` (pro 403) | |
| DOC-PRO-V3-01, 02 | ✅ | Tests génériques `V3/` | |
| DOC-PRO-V3-03 | ✅ | `V3D/UploadTest` (pro, `beneficiary_id` manquant, non géré, dossier privé) | |
| DOC-PRO-V3-04 | ✅ | `V3D/MoveTest::testProMovesSharedDocumentIntoSharedFolder`, `testProCanNotMoveDocumentIntoPrivateFolder`, `testCanNotMoveDocument` | |
| DOC-PRO-V3-05 | ✅ | `V3/ToggleVisibilityTest` | |
| DOC-PRO-V3-06 | ✅ | `V3D/ShareTest` (pro sur partagé, pro sur privé refusé) | |
| DOC-PRO-V3-07, FOLDER-PRO-V3-04 | ✅ | `V3/DeleteTest::testCanNotDelete` (« pro can not delete shared item » pour Document et Folder) | |
| FOLDER-PRO-V3-01 à 03 | 🟡 | Tests génériques `V3/` | Bénéficiaire non géré sur l'item ; déplacement circulaire |
| Legacy | 🟡 | `V2/Document/DocumentAccessTest` (pro sans relais, privé refusé sur 11 opérations), `V2/Security/ProPrivateDataAccessTest` | Suppression par un pro |

### Client API

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| DOC-CLIENT-V3CC-01 | ✅ | `V3CC/Document/DocumentAPIv3Test::testGetCollectionForBeneficiary`, `testCanNotGetCollectionForBeneficiary` | Bénéficiaire non lié |
| DOC-CLIENT-V3CC-02 | 🟡 | `V3CC/Document/UploadTest::testUpload` (scopes), `testRosalieUploadsInItsFolder` | Identifiant absent ; non lié ; dossier d'un autre bénéficiaire |
| DOC-CLIENT-V3CC-03 | ✅ | `V3CC/Document/DocumentAPIv3Test::testToggleVisibility`, `testCanNotToggleVisibility` | |
| FOLDER-CLIENT-V3CC-01 | ✅ | `V3CC/Folder/FolderApiV3Test` (collection, item, refus par scope) | Non lié |
| FOLDER-CLIENT-V3CC-02 | 🟡 | `V3CC/Folder/FolderApiV3Test::testGetCollectionTreeFromBeneficiary` | Non lié ; scope absent |
| FOLDER-CLIENT-V3CC-03, 04 | ✅ | `V3CC/Folder/FolderApiV3Test::testPost`, `testCanNotPost`, `testToggleVisibility`, `testCanNotToggleVisibility` | |
| Refus des opérations non ouvertes au client (D7, D8) | ❌ | | `GET /documents/{id}`, `PATCH`, `DELETE`, partage |

### Synthèse

| Acteur / canal | ✅ | 🟡 | ❌ |
|---|---|---|---|
| Bénéficiaire web (19 US) | 8 | 7 | 4 |
| Bénéficiaire v3 (13 US) | 11 | 2 | 0 |
| Pro web (11 US) | 6 | 5 | 0 |
| Pro v3 (11 US) | 8 | 3 | 0 |
| Client v3 (7 US + refus) | 5 | 2 | 1 |

### Constats sur les tests existants

- **Téléchargement non testé** : ni le téléchargement d'un document (`document_download`) ni celui d'un dossier (`folder_download`) n'ont de test web.
- **Libellés trompeurs** : dans `WebF/TreeViewMoveTest`, deux cas s'appellent « Private folder moved in … folder should be shared », alors que les valeurs attendues sont `true` (privé). Le test est juste, les libellés sont faux.
- **Test sauté** : `V3CC/Document/DocumentAPIv3Test::testPost` est marqué `markTestSkipped()` sans message ; l'upload client est couvert par `V3CC/Document/UploadTest`, ce test sauté est donc probablement obsolète.
- **Bonne pratique à généraliser** : `CreateDocumentFromFileTest` teste la règle de visibilité à l'upload au niveau du manager, une seule fois pour tous les canaux. C'est le modèle à suivre pour les règles centralisées des autres domaines.

## 4. Centralisation des règles métier (web et API v3)

Légende : ✅ une seule implémentation partagée, ⚠️ dupliquée, ❌ divergente ou absente d'un canal.

### Règles communes avec les autres données personnelles

Les règles R1 à R13 des [contacts](contacts.md#4-centralisation-des-règles-métier-web-et-api-v3) s'appliquent aussi ici : accès au bénéficiaire (R1, ✅), duplication PHP / DQL des bénéficiaires gérés (R2, ⚠️), masquage des privés dans les listes (R4, ⚠️ : `DocumentRepository::findByBeneficiary` et `FolderManager::getFolders` côté web, `FilterUserCollectionsExtension` côté v3), visibilité par défaut à la création d'un dossier (R5, ❌, D4), recherche (R10, ❌, D5), consultation (R13, ❌, D12).

### Règles propres aux documents et dossiers

| # | Règle | Web | API v3 | Statut |
|---|---|---|---|---|
| DR1 | Extensions autorisées, stockage, miniature, visibilité à l'upload | `DocumentManager::uploadFile` / `createDocumentFromFile` | Idem (appelé par `UploadDocumentController`) | ✅ |
| DR2 | Le dossier de destination appartient au même bénéficiaire et l'utilisateur y a accès | `FolderDestinationVoter` (upload, déplacements) ; contrainte `SameBeneficiaryAsFolder` sur les entités | Upload : `UploadDocumentController::getFolder`. `/move` : contrôle explicite dans `DocumentApiController::move`. `PATCH` : expression `securityPostDenormalize`. Sous-dossier : `PersonalDataStateProcessor`. Contrainte `SameBeneficiaryAsFolder` en filet de sécurité | ✅ (web) et ⚠️ (contrôles v3 encore dupliqués) |
| DR4 | Effet d'un déplacement (dossier privé ⇒ élément privé) et interdiction des cycles | `move()` de l'entité + validation par `FolderableItemManager::move` | `/move` : `FolderableItemManager::move`. `PATCH` : `FolderableItemStateProcessor` (appel direct à `move()`) + validation API Platform | ✅ pour la règle (entité), ⚠️ pour l'orchestration |
| DR5 | Sous-dossier créé privé si le parent est privé | `Dossier::createFromParent` | `PersonalDataStateProcessor` (affectation du parent et de `bPrive` à la main) | ⚠️ |
| DR6 | Interdiction de partager un élément d'un dossier privé ; bascule récursive d'un dossier | `canToggleVisibility()` et `Dossier::toggleVisibility()` (entité), vérifiés par `PersonalDataVoter::TOGGLE_VISIBILITY` | Idem, mais trois expressions de sécurité différentes selon la ressource | ✅ pour la règle, ⚠️ pour la sécurité des opérations |
| DR7 | Suppression réservée au bénéficiaire | `PersonalDataVoter::canDelete` | Idem | ✅ |
| DR8 | Envoi d'un document par email (lien de téléchargement) | `SharedDocumentManager::generateSharedDocumentAndSendEmail` | `DocumentApiController::shareDocument` appelle directement `SharedDocumentFactory` et `MailerService` | ⚠️ |
| DR9 | Nettoyage du nom de fichier | `DocumentListener` | Idem | ✅ |
| DR11 | Accès des clients API | ➖ | Documents et dossiers implémentent `ClientResourceInterface` (`ApiVoter`), mais les lectures de documents exigent `ROLE_USER` (D7) | ⚠️ |

### Détail

- **DR2** : sur le web, la règle est portée par `FolderDestinationVoter` (même bénéficiaire et `UPDATE` sur le dossier, réponse 403) et, en défense en profondeur, par la contrainte `SameBeneficiaryAsFolder` sur `Document::$dossier` et `Dossier::$dossierParent`. Cette contrainte s'applique partout où l'entité est validée (`FolderableItemManager::move`, opérations API Platform, formulaires) et à l'upload, où `DocumentManager` valide le document avant persistance et supprime le fichier déjà envoyé sur le stockage si la création échoue. Reste dupliqué : les quatre contrôles v3, qui pourraient s'appuyer sur le voter (en gardant leurs codes 400 actuels).
- **DR5** : la v3 pourrait réutiliser `Dossier::createFromParent` ou `move()`, au lieu de réimplémenter l'héritage dans un processor générique.
- **DR8** : même opération, deux orchestrations. Le service web ajoute des messages flash, ce qui explique sans doute que la v3 ne l'appelle pas. Il suffit de séparer l'envoi (réutilisable) des messages flash (propres au web).

### Plan de refacto

Les étapes des plans [contacts](contacts.md#plan-de-refacto), [notes](notes.md#plan-de-refacto) et [événements](evenements.md#plan-de-refacto) s'appliquent aussi aux documents et dossiers pour les règles communes. Étapes propres au domaine, par ordre de priorité :

1. **DR2** (`refactor:`) : faire s'appuyer les quatre contrôles v3 sur `FolderDestinationVoter` et la contrainte `SameBeneficiaryAsFolder`, sans changer leurs codes de retour (400 pour un dossier d'un autre bénéficiaire).
2. **DR8** (`refactor:`) : extraire l'envoi par email dans un service sans message flash, appelé par le web et par la v3.
3. **DR5** (`refactor:`) : faire passer la création de sous-dossier v3 par `Dossier::createFromParent` (ou `move()`).
4. **DR4 / DR6** (`refactor:`) : aligner les trois expressions de sécurité du toggle (documents, dossiers, contacts) sur une seule forme, en même temps que NR1 des notes. Faire passer `PATCH` et `/move` par le même chemin (`FolderableItemManager::move`).
