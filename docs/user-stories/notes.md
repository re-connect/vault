# Notes

Une note appartient à un bénéficiaire. Elle a un titre (`nom`), un contenu en texte enrichi (`contenu`, saisi avec l'éditeur Quill sur le web) et une visibilité : **privée** (`bPrive = true`, visible du bénéficiaire uniquement) ou **partagée** (visible des pros qui gèrent le bénéficiaire et des clients API liés).

Les règles d'accès sont les mêmes que pour les [contacts](contacts.md) : un pro « gère » un bénéficiaire quand ils ont un relais en commun et que le pro a la permission de gestion des bénéficiaires sur ce relais.

## 1. User stories

### Bénéficiaire

#### Web

- **NOTE-BENEF-WEB-01** : En tant que bénéficiaire, je peux accéder à « Mes notes » depuis la navigation.
- **NOTE-BENEF-WEB-02** : En tant que bénéficiaire, je peux voir la liste de toutes mes notes, privées et partagées (titre et date de création).
  - Refus : la liste des notes d'un autre bénéficiaire m'est interdite.
- **NOTE-BENEF-WEB-03** : En tant que bénéficiaire, je peux rechercher une note par titre ou contenu.
- **NOTE-BENEF-WEB-04** : En tant que bénéficiaire, je peux consulter une note (titre, contenu mis en forme, date d'ajout).
  - Le contenu HTML est nettoyé à l'affichage (`sanitize_html('app.note_sanitizer')`).
  - Refus : la note d'un autre bénéficiaire m'est interdite.
- **NOTE-BENEF-WEB-05** : En tant que bénéficiaire, je peux ajouter une note, privée par défaut.
  - Refus : titre vide, contenu vide ; ajout chez un autre bénéficiaire.
- **NOTE-BENEF-WEB-06** : En tant que bénéficiaire, je peux modifier une note, y compris son accès.
  - Refus : titre vide, contenu vide ; note d'un autre bénéficiaire.
- **NOTE-BENEF-WEB-07** : En tant que bénéficiaire, je peux supprimer une note après confirmation.
  - Refus : note d'un autre bénéficiaire.
- **NOTE-BENEF-WEB-08** : En tant que bénéficiaire, je peux basculer une note entre privée et partagée depuis la liste.
  - Refus : note d'un autre bénéficiaire.

#### API v3 (password grant)

- **NOTE-BENEF-V3-01** : En tant que bénéficiaire, je peux lister toutes mes notes, privées et partagées (`GET /notes`, `GET /beneficiaries/{id}/notes`).
  - Refus : `GET /beneficiaries/{id}/notes` sur un autre bénéficiaire.
- **NOTE-BENEF-V3-02** : En tant que bénéficiaire, je peux consulter une note (`GET /notes/{id}`).
  - Le contenu renvoyé a été nettoyé à l'enregistrement (`SanitizeNoteContentListener`).
  - Refus : note d'un autre bénéficiaire.
- **NOTE-BENEF-V3-03** : En tant que bénéficiaire, je peux créer une note (`POST /notes`), qui m'est toujours rattachée.
  - Le `beneficiaireId` envoyé est ignoré. Partagée par défaut si `bPrive` n'est pas envoyé.
  - Refus : titre vide, contenu vide.
- **NOTE-BENEF-V3-04** : En tant que bénéficiaire, je peux modifier une note (`PATCH /notes/{id}`).
  - Refus : note d'un autre bénéficiaire ; changement de bénéficiaire de la note.
- **NOTE-BENEF-V3-05** : En tant que bénéficiaire, je peux supprimer une note (`DELETE /notes/{id}`).
  - Refus : note d'un autre bénéficiaire.
- **NOTE-BENEF-V3-06** : En tant que bénéficiaire, je peux basculer une note entre privée et partagée (`PATCH /notes/{id}/toggle-visibility`).
  - Refus : note d'un autre bénéficiaire.

#### API v1/v2

- **NOTE-BENEF-LEGACY-01** : En tant que bénéficiaire, je peux lister toutes mes notes, privées et partagées (`GET /beneficiaries/{id}/notes`).
  - Refus : notes d'un autre bénéficiaire (`GESTION_BENEFICIAIRE`).
- **NOTE-BENEF-LEGACY-02** : En tant que bénéficiaire, je peux consulter une note (`GET /notes/{id}`).
  - Refus : note d'un autre bénéficiaire.
- **NOTE-BENEF-LEGACY-03** : En tant que bénéficiaire, je peux créer une note (`POST /beneficiaries/{id}/notes`), partagée si `b_prive` n'est pas envoyé.
  - Refus : titre vide, contenu vide ; création chez un autre bénéficiaire.
- **NOTE-BENEF-LEGACY-04** : En tant que bénéficiaire, je peux modifier une note (`PUT /notes/{id}`).
  - Refus : note d'un autre bénéficiaire.
- **NOTE-BENEF-LEGACY-05** : En tant que bénéficiaire, je peux supprimer une note (`DELETE /notes/{id}`).
  - Refus : note d'un autre bénéficiaire.
- **NOTE-BENEF-LEGACY-06** : En tant que bénéficiaire, je peux basculer une note entre privée et partagée (`PATCH /notes/{id}/toggle-access`).
  - Refus : note d'un autre bénéficiaire.

### Pro

#### Web

- **NOTE-PRO-WEB-01** : En tant que pro, je peux voir la liste des notes partagées d'un bénéficiaire que je gère.
  - Refus : bénéficiaire que je ne gère pas ; les notes privées n'apparaissent pas.
- **NOTE-PRO-WEB-02** : En tant que pro, je peux rechercher une note partagée par titre ou contenu.
- **NOTE-PRO-WEB-03** : En tant que pro, je peux consulter une note partagée.
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-WEB-04** : En tant que pro, je peux ajouter une note à un bénéficiaire que je gère, partagée par défaut.
  - Refus : titre vide, contenu vide ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-WEB-05** : En tant que pro, je peux modifier une note partagée.
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-WEB-06** : En tant que pro, je peux supprimer une note partagée après confirmation.
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-WEB-07** : En tant que pro, je peux rendre privée une note partagée (j'en perds ensuite l'accès).
  - Refus : note déjà privée ; bénéficiaire que je ne gère pas.

#### API v3 (password grant)

- **NOTE-PRO-V3-01** : En tant que pro, je peux lister les notes partagées de tous les bénéficiaires que je gère (`GET /notes`).
- **NOTE-PRO-V3-02** : En tant que pro, je peux lister les notes partagées d'un bénéficiaire que je gère (`GET /beneficiaries/{id}/notes`).
  - Refus : bénéficiaire que je ne gère pas ; les notes privées n'apparaissent pas.
- **NOTE-PRO-V3-03** : En tant que pro, je peux consulter une note partagée (`GET /notes/{id}`).
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-V3-04** : En tant que pro, je peux créer une note pour un bénéficiaire que je gère (`POST /notes` avec `beneficiaireId`), partagée par défaut.
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire que je ne gère pas (404) ; titre vide, contenu vide.
- **NOTE-PRO-V3-05** : En tant que pro, je peux modifier une note partagée (`PATCH /notes/{id}`).
  - Refus : note privée ; bénéficiaire que je ne gère pas ; changement de bénéficiaire de la note.
- **NOTE-PRO-V3-06** : En tant que pro, je peux supprimer une note partagée (`DELETE /notes/{id}`).
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-V3-07** : En tant que pro, je peux rendre privée une note partagée (`PATCH /notes/{id}/toggle-visibility`).
  - Refus : note privée ; bénéficiaire que je ne gère pas.

#### API v1/v2

- **NOTE-PRO-LEGACY-01** : En tant que pro, je peux lister les notes partagées d'un bénéficiaire (`GET /beneficiaries/{id}/notes`).
  - Refus : bénéficiaire que je ne gère pas (`GESTION_BENEFICIAIRE`) ; les notes privées n'apparaissent pas.
- **NOTE-PRO-LEGACY-02** : En tant que pro, je peux consulter une note partagée (`GET /notes/{id}`).
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-LEGACY-03** : En tant que pro, je peux créer une note pour un bénéficiaire (`POST /beneficiaries/{id}/notes`). Si je la crée privée, la réponse ne renvoie pas la note.
  - Refus : bénéficiaire que je ne gère pas ; titre vide, contenu vide.
- **NOTE-PRO-LEGACY-04** : En tant que pro, je peux modifier une note partagée (`PUT /notes/{id}`).
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-LEGACY-05** : En tant que pro, je peux supprimer une note partagée (`DELETE /notes/{id}`).
  - Refus : note privée ; bénéficiaire que je ne gère pas.
- **NOTE-PRO-LEGACY-06** : En tant que pro, je peux signaler une note partagée, ce qui la rend privée (`PATCH /notes/{id}/toggle-access` passe par `reportAbuse`).
  - Refus : note privée.

### Client API (client credentials)

#### API v3

- **NOTE-CLIENT-V3CC-01** : En tant que client avec le scope `notes_read`, je peux lister les notes partagées des bénéficiaires liés à mon client (`GET /notes`).
  - Un client avec tous les privilèges voit les notes partagées de tous les bénéficiaires.
- **NOTE-CLIENT-V3CC-02** : En tant que client avec le scope `notes_read`, je peux lister les notes partagées d'un bénéficiaire lié (`GET /beneficiaries/{id}/notes`).
  - Refus : bénéficiaire non lié à mon client.
- **NOTE-CLIENT-V3CC-03** : En tant que client avec le scope `notes_read`, je peux consulter une note partagée (`GET /notes/{id}`).
  - Refus : note privée (404) ; bénéficiaire non lié (404) ; scope absent.
- **NOTE-CLIENT-V3CC-04** : En tant que client avec le scope `notes_create`, je peux créer une note pour un bénéficiaire lié (`POST /notes` avec `beneficiaireId`), partagée par défaut.
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire non lié (404) ; scope absent ; titre vide, contenu vide.
- **NOTE-CLIENT-V3CC-05** : En tant que client avec le scope `notes_update`, je peux rendre privée une note partagée (`PATCH /notes/{id}/toggle-visibility`).
  - Refus : note privée (404) ; bénéficiaire non lié (404) ; scope absent.
- Pas de modification (`PATCH /notes/{id}`) ni de suppression (`DELETE /notes/{id}`) : les deux exigent `ROLE_USER`.

#### API v2

- **NOTE-CLIENT-LEGACY-01** : En tant que client, je peux lister les notes partagées d'un bénéficiaire via son identifiant distant (`GET /api/v2/beneficiaries/{distantId}/notes`).
  - Refus : bénéficiaire inconnu pour mon client ; lien pas encore accepté par le bénéficiaire.
- **NOTE-CLIENT-LEGACY-02** : En tant que client, je peux consulter une note d'un bénéficiaire lié (`GET /api/v2/notes/{id}`). [À VÉRIFIER] filtrage des notes privées.
- **NOTE-CLIENT-LEGACY-03** : En tant que client, je peux créer une note pour un bénéficiaire lié (`POST /api/v2/beneficiaries/{distantId}/notes`). Le client est enregistré comme créateur de la note.
  - Refus : bénéficiaire non lié ; lien non accepté ; titre vide, contenu vide.
- **NOTE-CLIENT-LEGACY-04** : En tant que client, je peux modifier une note partagée d'un bénéficiaire lié (`PUT /api/v2/notes/{id}`).
  - Refus : note privée ; bénéficiaire non lié.
- **NOTE-CLIENT-LEGACY-05** : En tant que client, je peux supprimer une note d'un bénéficiaire lié (`DELETE /api/v2/notes/{id}`). [À VÉRIFIER] refus sur note privée.
- **NOTE-CLIENT-LEGACY-06** : En tant que client, je peux signaler une note, ce qui la rend privée (`PATCH /api/v2/notes/{id}/toggle-access`). [À VÉRIFIER] droits exacts.

### Différences d'implémentation avec les contacts (à exploiter aux étapes 2 et 4)

- `Note` n'implémente pas `ClientResourceInterface`, contrairement à `Contact` : `ApiVoter` ne s'applique pas aux notes. Pour un client, l'accès à une note passe uniquement par le scope et par le filtrage de `ClientResourceCollectionExtension`.
- La sécurité du toggle v3 diffère : `ROLE_OAUTH2_NOTES_UPDATE or (ROLE_USER and UPDATE)` pour les notes, `UPDATE` seul pour les contacts.
- Les routes v1/v2 `beneficiaries/{beneficiaryId}/notes` déclarent une contrainte sur `id` au lieu de `beneficiaryId` : l'identifiant du bénéficiaire n'est pas contraint à des chiffres.

## 2. Écarts

Légende : ✅ disponible, ⚠️ disponible avec un comportement différent, ❌ absent, ➖ sans objet.

Les identifiants entre parenthèses renvoient aux écarts des [contacts](contacts.md#2-écarts) quand la cause est la même.

### Matrice bénéficiaire

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister toutes mes notes (privées et partagées) | ✅ WEB-01, 02 | ✅ V3-01 | ✅ LEGACY-01 |
| Rechercher une note (titre, contenu) | ✅ WEB-03 | ❌ (N2) | ❌ |
| Consulter une note | ✅ WEB-04 | ✅ V3-02 | ✅ LEGACY-02 |
| Créer une note | ✅ WEB-05, privée par défaut | ⚠️ V3-03, partagée par défaut (N1) | ⚠️ LEGACY-03, partagée par défaut (N1) |
| Modifier une note | ✅ WEB-06 | ✅ V3-04 | ✅ LEGACY-04 |
| Supprimer une note | ✅ WEB-07 | ✅ V3-05 | ✅ LEGACY-05 |
| Basculer privée / partagée | ✅ WEB-08 | ✅ V3-06 | ✅ LEGACY-06 |
| Ordre de la liste | création décroissante | non défini (N5) | id décroissant |

### Matrice pro

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister les notes partagées d'un bénéficiaire géré | ✅ WEB-01 | ✅ V3-02 | ✅ LEGACY-01 |
| Lister les notes partagées de tous mes bénéficiaires | ❌ | ✅ V3-01 (N7) | ❌ |
| Rechercher une note | ✅ WEB-02 | ❌ (N2) | ❌ |
| Consulter une note partagée | ✅ WEB-03 | ✅ V3-03 | ✅ LEGACY-02 |
| Créer une note | ✅ WEB-04, partagée par défaut | ✅ V3-04, partagée par défaut | ✅ LEGACY-03, partagée par défaut |
| Modifier une note partagée | ✅ WEB-05 | ✅ V3-05 | ✅ LEGACY-04 |
| Supprimer une note partagée | ✅ WEB-06 | ✅ V3-06 | ✅ LEGACY-05 |
| Rendre privée une note partagée | ✅ WEB-07 | ✅ V3-07 | ⚠️ LEGACY-06, via `reportAbuse` |
| Accéder à une note privée | ➖ refusé partout | ➖ refusé partout | ➖ refusé partout |

### Matrice client API

| Fonctionnalité | API v3 (client credentials) | API v2 |
|---|---|---|
| Lister les notes partagées des bénéficiaires liés | ✅ V3CC-01, V3CC-02 | ✅ LEGACY-01 (un bénéficiaire à la fois) |
| Consulter une note partagée | ✅ V3CC-03 | ✅ LEGACY-02 |
| Créer une note | ✅ V3CC-04 | ✅ LEGACY-03 |
| Modifier une note | ❌ (N3) | ✅ LEGACY-04 |
| Supprimer une note | ❌ (N3) | ✅ LEGACY-05 |
| Rendre privée une note | ✅ V3CC-05 | ⚠️ LEGACY-06 |

### Liste des écarts

| # | Écart | Canaux | Gravité | Décision à prendre |
|---|---|---|---|---|
| N1 | Une note créée par le bénéficiaire est **privée par défaut sur le web** mais **partagée par défaut en API** si `bPrive` n'est pas envoyé. Même cause que E1 des contacts (`getCreateForm` côté web, valeur par défaut de l'entité côté API). | Web / v3 | Haute (confidentialité) | Même décision que E1, à prendre une fois pour toutes les données personnelles. |
| N2 | Pas de recherche en v3. | Web / v3 | Moyenne | Même décision que E2. |
| N3 | En client credentials v3, pas de modification ni de suppression (`ROLE_USER` exigé), alors que le scope `notes_update` existe et que la v2 le permettait. | v3 client / v2 | Moyenne | Même décision que E3. |
| N5 | Ordre de la liste non défini en v3 [À VÉRIFIER]. | Web / v3 | Basse | Même décision que E5. |
| ~~N6~~ | Invalidé : le contenu est nettoyé à l'enregistrement par le listener Doctrine `SanitizeNoteContentListener` (`prePersist`, `preUpdate`), donc sur tous les canaux. Le web le nettoie une seconde fois à l'affichage. | ➖ | ➖ | Aucune. |
| N7 | En v3, un pro peut lister les notes partagées de tous ses bénéficiaires en un appel ; le web n'a pas d'équivalent. | Web / v3 | Basse | Même décision que E6. |
| N8 | Quand un pro consulte la liste des notes sur le web, la consultation est enregistrée (`BeneficiaryConsultationEvent`) ; rien en v3. | Web / v3 | Moyenne | Même décision que E10. |

### Remarques hors écarts

- **Implémentations différentes, même effet** : la sécurité du toggle v3 et l'absence de `ClientResourceInterface` sur `Note` (voir fin de la section 1) ne créent pas d'écart fonctionnel visible aujourd'hui. Pour un client, le résultat est le même que pour les contacts (scope + bénéficiaire lié + note partagée). Ce point est à traiter à l'étape 4.

## 3. Couverture de tests

Légende : ✅ cas nominal et cas de refus couverts, 🟡 couverture partielle, ❌ non couvert.

Chemins abrégés : `Web/` = `tests/v2/Controller/NoteController/`, `V3/` = `tests/v2/API/v3/PasswordGrant/PersonalData/`, `V3CC/` = `tests/v2/API/v3/ClientCredentials/Note/`, `V2/` = `tests/v2/API/v2/PasswordGrant/`, `V1/` = `tests/v1/Controller/Rest/`.

Les tests web ont la même structure que ceux des contacts. Les tests v3 password grant et v2 sont les mêmes tests génériques (ligne `Note` des data providers).

### Bénéficiaire

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| BENEF-WEB-01 | ❌ | | Présence de « Mes notes » dans la navigation |
| BENEF-WEB-02 | 🟡 | `Web/ListTest::testRoute` (200, autre bénéficiaire 403) | Les notes privées figurent dans la liste |
| BENEF-WEB-03 | ❌ | | Recherche |
| BENEF-WEB-04 | ✅ | `Web/DetailTest::testRoute` (200, autre bénéficiaire 403) | |
| BENEF-WEB-05 | 🟡 | `Web/CreateTest::testRoute`, `testFormIsValid`, `testFormIsNotValid` (titre, contenu) | Note privée par défaut (N1) |
| BENEF-WEB-06 | ✅ | `Web/EditTest::testRoute`, `testFormIsValid`, `testFormIsNotValid` | |
| BENEF-WEB-07 | 🟡 | `Web/DeleteTest::testRoute` (302, autre bénéficiaire 403) | Suppression effective [À VÉRIFIER] |
| BENEF-WEB-08 | 🟡 | `Web/ToggleVisibilityTest::testRoute`, `Web/SwitchPrivateTest::testRoute` | Changement effectif de `bPrive` [À VÉRIFIER] |
| BENEF-V3-01 | ✅ | `V3/PersonalDataCollectionTest` (mêmes méthodes que pour les contacts) | |
| BENEF-V3-02 | ✅ | `V3/PersonalDataItemTest::testGetItem` | |
| BENEF-V3-03 | 🟡 | `V3/CreateTest::testBeneficiaryCreatesItemForThemself` | Validation (titre, contenu) ; visibilité par défaut (N1) |
| BENEF-V3-04 | 🟡 | `V3/PatchTest::testBeneficiaryRenamesItem`, `testBeneficiaryCanNotGiveItemToAnotherBeneficiary` | Note d'un autre bénéficiaire ; validation |
| BENEF-V3-05 | ✅ | `V3/DeleteTest::testDelete`, `testCanNotDelete` | |
| BENEF-V3-06 | ✅ | `V3/ToggleVisibilityTest::testBeneficiaryTogglesOwnItem`, `testAnotherBeneficiaryCanNotToggleItem` | |
| BENEF-LEGACY-01 | ✅ | `V2/PersonalData/MobilePersonalDataListTest::testBeneficiaryListsAllPersonalData`, `testCanNotListPersonalDataOfAnotherBeneficiary` | |
| BENEF-LEGACY-02 | 🟡 | `V2/PersonalData/PersonalDataAccessTest::testAuthorizedUserCanReadPersonalData` | Note d'un autre bénéficiaire |
| BENEF-LEGACY-03 à 06 | ❌ | | Création, modification, suppression, bascule par un bénéficiaire |

### Pro

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| PRO-WEB-01 | 🟡 | `Web/ListTest::testRoute` (200, 403) | Les notes privées sont absentes de la liste |
| PRO-WEB-02 | ❌ | | Recherche |
| PRO-WEB-03 | ✅ | `Web/DetailTest::testRoute` (200, 403, note privée 403) | |
| PRO-WEB-04 | 🟡 | `Web/CreateTest::testRoute` (200, 403) | Soumission du formulaire par un pro ; note partagée par défaut |
| PRO-WEB-05 | 🟡 | `Web/EditTest::testRoute` (200, 403, note privée 403) | Soumission du formulaire par un pro |
| PRO-WEB-06 | ✅ | `Web/DeleteTest::testRoute` (302, 403, note privée 403) | |
| PRO-WEB-07 | 🟡 | `Web/ToggleVisibilityTest::testRoute`, `Web/SwitchPrivateTest::testRoute` (200/302, 403, note privée 403) | Changement effectif de `bPrive` |
| PRO-V3-01 | ✅ | `V3/PersonalDataCollectionTest::testMemberListsSharedPersonalDataOfManagedBeneficiaries` | |
| PRO-V3-02 | ✅ | `V3/PersonalDataCollectionTest::testMemberListsSharedPersonalDataFromBeneficiaryEndpoint`, `testCanNotListPersonalDataOfAnUnmanagedBeneficiary` | |
| PRO-V3-03 | 🟡 | `V3/PersonalDataItemTest::testGetItem` (partagée 200, privée 403) | Bénéficiaire non géré |
| PRO-V3-04 | 🟡 | `V3/CreateTest::testProCreatesItemForManagedBeneficiary`, `testProCanNotCreateItemForUnmanagedBeneficiary`, `testProMustGiveBeneficiary` | Validation ; visibilité par défaut |
| PRO-V3-05 | 🟡 | `V3/PatchTest::testProCanNotMoveItemBetweenManagedBeneficiaries` | Modification nominale ; note privée ; bénéficiaire non géré |
| PRO-V3-06 | 🟡 | `V3/DeleteTest::testDelete` (partagée), `testCanNotDelete` (privée) | Bénéficiaire non géré |
| PRO-V3-07 | 🟡 | `V3/ToggleVisibilityTest::testProMakesSharedItemPrivate`, `testProCanNotToggleAPrivateItem` | Bénéficiaire non géré |
| PRO-LEGACY-01 | ✅ | `V2/PersonalData/MobilePersonalDataListTest::testMemberListsOnlySharedPersonalData`, `testCanNotListPersonalDataOfAnotherBeneficiary` | |
| PRO-LEGACY-02 | ✅ | `V2/PersonalData/PersonalDataAccessTest`, `V2/Security/ProPrivateDataAccessTest::testProCanNotAccessPrivateItem` | |
| PRO-LEGACY-03 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testAffiliateABeneficiary` | Bénéficiaire non géré ; validation |
| PRO-LEGACY-04 | 🟡 | `PersonalDataAccessTest` (refus sur privée) | Modification nominale |
| PRO-LEGACY-05 | 🟡 | `testAffiliateABeneficiary` (nominal), `PersonalDataAccessTest` et `ProPrivateDataAccessTest` (refus sur privée) | Bénéficiaire non géré |
| PRO-LEGACY-06 | 🟡 | `PersonalDataAccessTest` (refus sur privée) | Signalement nominal |

### Client API

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| CLIENT-V3CC-01 | ✅ | `V3CC/NoteApiV3Test::testGetCollection`, `testNotGetCollection` (scopes) | |
| CLIENT-V3CC-02 | 🟡 | `V3CC/NoteApiV3Test::testGetCollectionFromBeneficiary`, `testCanNotGetCollectionFromBeneficiary` (scopes) | Bénéficiaire non lié |
| CLIENT-V3CC-03 | 🟡 | `V3CC/NoteApiV3Test::testGetOne`, `testCanNotGetOne` (scopes) | Note privée ; bénéficiaire non lié |
| CLIENT-V3CC-04 | 🟡 | `V3CC/NoteApiV3Test::testPost`, `testCanNotPost` (scopes) | `beneficiaireId` manquant ; bénéficiaire non lié ; validation |
| CLIENT-V3CC-05 | ✅ | `V3CC/NoteApiV3Test::testToggleVisibility` (partagée → privée 200, puis 404 sur la note devenue privée), `testCanNotToggleVisibility` (scopes) | Bénéficiaire non lié |
| PATCH / DELETE refusés | ❌ | Voir constat ci-dessous | Refus de modification et de suppression par un client (N3) |
| CLIENT-LEGACY-01 | ❌ | | Liste par identifiant distant ; lien non accepté |
| CLIENT-LEGACY-02 à 06 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testReconnectPro` | Tous les cas de refus |

### Règle transverse : nettoyage du contenu

| Règle | Statut | Couvert par | Manque |
|---|---|---|---|
| Le contenu HTML est nettoyé à la création et à la modification | 🟡 | `tests/v2/Listener/SanitizeNoteContentListenerTest::testSanitizeNoteOnCreate`, `testSanitizeNoteOnUpdate` | Les cas testés portent sur les attributs `style` ; aucun cas de balise dangereuse (`<script>`, `onerror`…) |

### Synthèse

| Acteur / canal | ✅ | 🟡 | ❌ |
|---|---|---|---|
| Bénéficiaire web | 2 | 4 | 2 |
| Bénéficiaire v3 | 4 | 2 | 0 |
| Bénéficiaire v1/v2 | 1 | 1 | 4 |
| Pro web | 2 | 4 | 1 |
| Pro v3 | 2 | 5 | 0 |
| Pro v1/v2 | 2 | 4 | 0 |
| Client v3 | 2 | 3 | 1 |
| Client v2 | 0 | 5 | 1 |

### Constats sur les tests existants

- **Tests obsolètes et contradictoires** : `V3CC/NoteApiV3Test::testPut`, `testPatch` et `testDelete` sont marqués `markTestSkipped('Notes api ressource is currently disabled')`. Ils attendent qu'un client (`rosalie`) puisse modifier et supprimer une note, alors que la ressource est active et que ces opérations exigent aujourd'hui `ROLE_USER` (N3). Ils sont à supprimer ou à réécrire en tests de refus, selon la décision prise sur N3.
- **Les tests client des notes sont plus complets que ceux des contacts** : création et toggle y sont testés. Ils peuvent servir de modèle pour combler CONTACT-CLIENT-V3CC-04 et 05.
- **Doublon** : `ToggleVisibilityTest` et `SwitchPrivateTest` testent la même route, comme pour les contacts.
- **Aucun test ne fige la visibilité par défaut à la création** (N1).

## 4. Centralisation des règles métier (web et API v3)

Légende : ✅ une seule implémentation partagée, ⚠️ dupliquée, ❌ divergente ou absente d'un canal.

Les règles R1 à R13 des [contacts](contacts.md#4-centralisation-des-règles-métier-web-et-api-v3) s'appliquent aux notes à l'identique : mêmes classes génériques (`BeneficiaryVoter`, `PersonalDataVoter`, `FilterUserCollectionsExtension`, `PersonalDataStateProcessor`, listeners Doctrine). Seules les différences sont détaillées ici.

### Règles communes avec les contacts

| # | Règle | Statut pour les notes | Remarque |
|---|---|---|---|
| R1 | Accès à un bénéficiaire | ✅ | |
| R2 | Bénéficiaires gérés par un pro (version requête) | ⚠️ | Même duplication PHP / DQL |
| R3 | Accès à une note (lecture, modification, suppression, bascule) | ✅ (⚠️ toggle) | Voir NR1 |
| R4 | Notes privées masquées dans les listes | ⚠️ | `NoteRepository::findByBeneficiary` (web) et `FilterUserCollectionsExtension` (v3) |
| R5 | Visibilité par défaut à la création | ❌ | N1, même cause que E1 |
| R6 à R9 | Rattachement, bénéficiaire non modifiable, validation, effet de la bascule | ✅ | |
| R10 / R11 | Recherche, ordre de la liste | ❌ | N2, N5 |
| R12 | Journalisation et créateur | ✅ | |
| R13 | Consultation d'un bénéficiaire par un pro | ❌ | N8 |

### Règles propres aux notes

| # | Règle | Web | API v3 | Statut |
|---|---|---|---|---|
| NR1 | Un client API peut-il agir sur une note d'un bénéficiaire lié ? | ➖ | Scope vérifié dans l'expression de sécurité (`ROLE_OAUTH2_NOTES_UPDATE` sur le toggle), lien vérifié par `ClientResourceCollectionExtension`. Pour les contacts, le lien est vérifié par `ApiVoter` (via `ClientResourceInterface`) **et** par l'extension. | ⚠️ |
| NR2 | Nettoyage du contenu HTML | `SanitizeNoteContentListener` à l'enregistrement, puis `sanitize_html('app.note_sanitizer')` à l'affichage | `SanitizeNoteContentListener` | ✅ |

### Détail

- **NR1** : la même règle (« un client agit seulement sur les données partagées de ses bénéficiaires liés, avec le bon scope ») est exprimée de deux façons selon la ressource. Pour les contacts, `ApiVoter` et `ClientResourceCollectionExtension` la vérifient en double. Pour les notes, seule l'extension la vérifie, avec le scope écrit en dur dans l'expression. Le résultat est le même aujourd'hui, mais une évolution de la règle (par exemple dans `Oauth2Helper::canClientAccessRessource`) ne s'appliquerait qu'aux contacts. [À VÉRIFIER] sur les documents, dossiers et événements, pour savoir quelle forme est majoritaire.
- **NR2** : la règle est centralisée dans un listener Doctrine, ce qui couvre tous les canaux, v1/v2 compris. Le second nettoyage à l'affichage web est redondant mais sans risque ; il protège les notes enregistrées avant l'ajout du listener. Je ne sais pas si de telles notes existent encore en base.
- **Petite divergence interne au web** : `ContactManager::getContacts` renvoie une liste vide si le bénéficiaire n'a pas d'utilisateur, alors que `NoteManager::getNotes` appelle `isLoggedInUser($beneficiary->getUser())` sans vérifier ce cas. Ce n'est pas un écart web/v3, mais c'est un symptôme de la duplication `ContactManager` / `NoteManager` (et des repositories), qui ne diffèrent que par les champs de recherche.

### Plan de refacto

Les étapes 1 à 7 du [plan des contacts](contacts.md#plan-de-refacto) couvrent aussi les notes, puisqu'elles portent sur les classes génériques. Il faut les exécuter une seule fois pour toutes les données personnelles. Étapes propres aux notes :

1. **Test du nettoyage** (`test:`) : ajouter à `SanitizeNoteContentListenerTest` des cas de balises et d'attributs dangereux (`<script>`, `onerror`, `javascript:`), pour figer la protection XSS et pas seulement le traitement des `style`.
2. **NR1** (`refactor:`) : choisir une seule façon d'exprimer l'accès des clients. Deux options :
   - faire implémenter `ClientResourceInterface` à `Note` et écrire la sécurité de ses opérations comme celle de `Contact` (`ApiVoter`) ;
   - à l'inverse, retirer `ApiVoter` des contacts et s'appuyer partout sur l'extension et le scope dans l'expression.

   Je recommande la première : la règle reste dans un voter, testable seule, et l'extension ne sert plus qu'à filtrer les collections. À trancher après l'audit des autres données personnelles.
3. **Duplication des managers et repositories web** (`refactor:`) : fusionner `ContactManager` / `NoteManager` et les méthodes `findByBeneficiary` en une implémentation générique paramétrée par les champs de recherche. Cela corrige au passage le cas du bénéficiaire sans utilisateur. Ce point rejoint l'étape 3 du plan des contacts (R4) : à faire dans le même commit.
4. **Tests sautés** (`test:`, dépend de N3) : supprimer `NoteApiV3Test::testPut`, `testPatch` et `testDelete`, ou les réécrire en tests de refus selon la décision prise sur N3.
