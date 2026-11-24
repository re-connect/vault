# Contacts

Un contact appartient à un bénéficiaire. Il est soit **privé** (`bPrive = true`, visible du bénéficiaire uniquement), soit **partagé** (visible des pros qui gèrent le bénéficiaire et des clients API liés).

Un pro « gère » un bénéficiaire quand ils ont au moins un relais en commun, et que le pro a la permission de gestion des bénéficiaires sur ce relais (`UserHelper::canUpdateBeneficiary`).

## 1. User stories

### Bénéficiaire

#### Web

- **CONTACT-BENEF-WEB-01** : En tant que bénéficiaire, je peux accéder à « Mes contacts » depuis la navigation pour retrouver mon carnet d'adresses.
- **CONTACT-BENEF-WEB-02** : En tant que bénéficiaire, je peux voir la liste de tous mes contacts, privés et partagés.
  - Refus : la liste des contacts d'un autre bénéficiaire m'est interdite.
- **CONTACT-BENEF-WEB-03** : En tant que bénéficiaire, je peux rechercher un contact par nom, prénom, téléphone ou email.
- **CONTACT-BENEF-WEB-04** : En tant que bénéficiaire, je peux consulter la fiche détaillée d'un contact.
  - Refus : le contact d'un autre bénéficiaire m'est interdit.
- **CONTACT-BENEF-WEB-05** : En tant que bénéficiaire, je peux ajouter un contact, privé par défaut.
  - Refus : nom vide, prénom vide, email invalide.
  - Refus : je ne peux pas ajouter un contact chez un autre bénéficiaire.
- **CONTACT-BENEF-WEB-06** : En tant que bénéficiaire, je peux modifier un contact, y compris son accès.
  - Refus : mêmes règles de validation qu'à la création ; contact d'un autre bénéficiaire interdit.
- **CONTACT-BENEF-WEB-07** : En tant que bénéficiaire, je peux supprimer un contact après confirmation.
  - Refus : contact d'un autre bénéficiaire interdit.
- **CONTACT-BENEF-WEB-08** : En tant que bénéficiaire, je peux basculer un contact entre privé et partagé depuis la liste.
  - Refus : contact d'un autre bénéficiaire interdit.
- **CONTACT-BENEF-WEB-09** : En tant que bénéficiaire, je peux envoyer un document à un de mes contacts.
  - Refus : contact sans email (bouton désactivé, message `contact_has_no_email`).

#### API v3 (password grant)

- **CONTACT-BENEF-V3-01** : En tant que bénéficiaire, je peux lister tous mes contacts, privés et partagés (`GET /contacts`, `GET /beneficiaries/{id}/contacts`).
  - Refus : `GET /beneficiaries/{id}/contacts` sur un autre bénéficiaire.
- **CONTACT-BENEF-V3-02** : En tant que bénéficiaire, je peux consulter un contact (`GET /contacts/{id}`).
  - Refus : contact d'un autre bénéficiaire.
- **CONTACT-BENEF-V3-03** : En tant que bénéficiaire, je peux créer un contact (`POST /contacts`), qui m'est toujours rattaché.
  - Le `beneficiaireId` envoyé est ignoré. Partagé par défaut si `bPrive` n'est pas envoyé.
  - Refus : prénom vide, email invalide.
- **CONTACT-BENEF-V3-04** : En tant que bénéficiaire, je peux modifier un contact (`PATCH /contacts/{id}`).
  - Refus : contact d'un autre bénéficiaire ; changement de bénéficiaire du contact.
- **CONTACT-BENEF-V3-05** : En tant que bénéficiaire, je peux supprimer un contact (`DELETE /contacts/{id}`).
  - Refus : contact d'un autre bénéficiaire.
- **CONTACT-BENEF-V3-06** : En tant que bénéficiaire, je peux basculer un contact entre privé et partagé (`PATCH /contacts/{id}/toggle-visibility`).
  - Refus : contact d'un autre bénéficiaire.

#### API v1/v2

- **CONTACT-BENEF-LEGACY-01** : En tant que bénéficiaire, je peux lister tous mes contacts, privés et partagés (`GET /beneficiaries/{id}/contacts`).
  - Refus : la liste des contacts d'un autre bénéficiaire m'est interdite (`GESTION_BENEFICIAIRE`, contrôlé par défaut dans `BeneficiaireProvider`).
- **CONTACT-BENEF-LEGACY-02** : En tant que bénéficiaire, je peux consulter un contact (`GET /contacts/{id}`).
  - Refus : contact d'un autre bénéficiaire.
- **CONTACT-BENEF-LEGACY-03** : En tant que bénéficiaire, je peux créer un contact (`POST /beneficiaries/{id}/contacts`), partagé si `b_prive` n'est pas envoyé.
  - Refus : prénom vide, email invalide ; création chez un autre bénéficiaire.
- **CONTACT-BENEF-LEGACY-04** : En tant que bénéficiaire, je peux modifier un contact (`PUT /contacts/{id}`).
  - Refus : contact d'un autre bénéficiaire.
- **CONTACT-BENEF-LEGACY-05** : En tant que bénéficiaire, je peux supprimer un contact (`DELETE /contacts/{id}`).
  - Refus : contact d'un autre bénéficiaire.
- **CONTACT-BENEF-LEGACY-06** : En tant que bénéficiaire, je peux basculer un contact entre privé et partagé (`PATCH /contacts/{id}/toggle-access`).
  - Refus : contact d'un autre bénéficiaire.

### Pro

#### Web

- **CONTACT-PRO-WEB-01** : En tant que pro, je peux voir la liste des contacts partagés d'un bénéficiaire que je gère.
  - Refus : bénéficiaire que je ne gère pas ; les contacts privés n'apparaissent pas.
- **CONTACT-PRO-WEB-02** : En tant que pro, je peux rechercher un contact partagé d'un bénéficiaire par nom, prénom, téléphone ou email.
- **CONTACT-PRO-WEB-03** : En tant que pro, je peux consulter la fiche d'un contact partagé.
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-WEB-04** : En tant que pro, je peux ajouter un contact à un bénéficiaire que je gère, partagé par défaut.
  - Refus : prénom vide, email invalide ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-WEB-05** : En tant que pro, je peux modifier un contact partagé.
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-WEB-06** : En tant que pro, je peux supprimer un contact partagé après confirmation.
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-WEB-07** : En tant que pro, je peux rendre privé un contact partagé (j'en perds ensuite l'accès).
  - Refus : contact déjà privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-WEB-08** : En tant que pro, je peux envoyer un document d'un bénéficiaire à un de ses contacts partagés.
  - Refus : contact sans email ; contact privé.

#### API v3 (password grant)

- **CONTACT-PRO-V3-01** : En tant que pro, je peux lister les contacts partagés de tous les bénéficiaires que je gère (`GET /contacts`).
- **CONTACT-PRO-V3-02** : En tant que pro, je peux lister les contacts partagés d'un bénéficiaire que je gère (`GET /beneficiaries/{id}/contacts`).
  - Refus : bénéficiaire que je ne gère pas ; les contacts privés n'apparaissent pas.
- **CONTACT-PRO-V3-03** : En tant que pro, je peux consulter un contact partagé (`GET /contacts/{id}`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-V3-04** : En tant que pro, je peux créer un contact pour un bénéficiaire que je gère (`POST /contacts` avec `beneficiaireId`), partagé par défaut.
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire que je ne gère pas (404) ; prénom vide, email invalide.
- **CONTACT-PRO-V3-05** : En tant que pro, je peux modifier un contact partagé (`PATCH /contacts/{id}`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas ; changement de bénéficiaire du contact.
- **CONTACT-PRO-V3-06** : En tant que pro, je peux supprimer un contact partagé (`DELETE /contacts/{id}`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-V3-07** : En tant que pro, je peux rendre privé un contact partagé (`PATCH /contacts/{id}/toggle-visibility`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas.

#### API v1/v2

- **CONTACT-PRO-LEGACY-01** : En tant que pro, je peux lister les contacts partagés d'un bénéficiaire (`GET /beneficiaries/{id}/contacts`).
  - Refus : bénéficiaire que je ne gère pas (`GESTION_BENEFICIAIRE`) ; les contacts privés n'apparaissent pas.
- **CONTACT-PRO-LEGACY-02** : En tant que pro, je peux consulter un contact partagé (`GET /contacts/{id}`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-LEGACY-03** : En tant que pro, je peux créer un contact pour un bénéficiaire (`POST /beneficiaries/{id}/contacts`). Si je le crée privé, la réponse ne renvoie pas le contact.
  - Refus : bénéficiaire que je ne gère pas ; prénom vide, email invalide.
- **CONTACT-PRO-LEGACY-04** : En tant que pro, je peux modifier un contact partagé (`PUT /contacts/{id}`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-LEGACY-05** : En tant que pro, je peux supprimer un contact partagé (`DELETE /contacts/{id}`).
  - Refus : contact privé ; bénéficiaire que je ne gère pas.
- **CONTACT-PRO-LEGACY-06** : En tant que pro, je peux signaler un contact partagé, ce qui le rend privé (`PATCH /contacts/{id}/toggle-access` passe par `reportAbuse`).
  - Refus : contact privé.

### Client API (client credentials)

#### API v3

- **CONTACT-CLIENT-V3CC-01** : En tant que client avec le scope `contacts_read`, je peux lister les contacts partagés des bénéficiaires liés à mon client (`GET /contacts`).
  - Un client avec tous les privilèges voit les contacts partagés de tous les bénéficiaires.
- **CONTACT-CLIENT-V3CC-02** : En tant que client avec le scope `contacts_read`, je peux lister les contacts partagés d'un bénéficiaire lié (`GET /beneficiaries/{id}/contacts`).
  - Refus : bénéficiaire non lié à mon client.
- **CONTACT-CLIENT-V3CC-03** : En tant que client avec le scope `contacts_read`, je peux consulter un contact partagé (`GET /contacts/{id}`).
  - Refus : contact privé (404) ; bénéficiaire non lié ; scope absent.
- **CONTACT-CLIENT-V3CC-04** : En tant que client avec le scope `contacts_create`, je peux créer un contact pour un bénéficiaire lié (`POST /contacts` avec `beneficiaireId`), partagé par défaut.
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire non lié (404) ; scope absent ; prénom vide, email invalide.
- **CONTACT-CLIENT-V3CC-05** : En tant que client avec le scope `contacts_update`, je peux rendre privé un contact partagé (`PATCH /contacts/{id}/toggle-visibility`).
  - Refus : contact privé (404) ; bénéficiaire non lié ; scope absent.
- Pas de modification (`PATCH /contacts/{id}`) ni de suppression (`DELETE /contacts/{id}`) : les deux exigent `ROLE_USER`.

#### API v2

- **CONTACT-CLIENT-LEGACY-01** : En tant que client, je peux lister les contacts partagés d'un bénéficiaire via son identifiant distant (`GET /api/v2/beneficiaries/{distantId}/contacts`).
  - Refus : bénéficiaire inconnu pour mon client ; lien pas encore accepté par le bénéficiaire.
- **CONTACT-CLIENT-LEGACY-02** : En tant que client, je peux consulter un contact d'un bénéficiaire lié (`GET /api/v2/contacts/{id}`). [À VÉRIFIER] filtrage des contacts privés.
- **CONTACT-CLIENT-LEGACY-03** : En tant que client, je peux créer un contact pour un bénéficiaire lié (`POST /api/v2/beneficiaries/{distantId}/contacts`). Le client est enregistré comme créateur du contact.
  - Refus : bénéficiaire non lié ; lien non accepté ; prénom vide, email invalide.
- **CONTACT-CLIENT-LEGACY-04** : En tant que client, je peux modifier un contact partagé d'un bénéficiaire lié (`PUT /api/v2/contacts/{id}`).
  - Refus : contact privé ; bénéficiaire non lié.
- **CONTACT-CLIENT-LEGACY-05** : En tant que client, je peux supprimer un contact d'un bénéficiaire lié (`DELETE /api/v2/contacts/{id}`). [À VÉRIFIER] refus sur contact privé.
- **CONTACT-CLIENT-LEGACY-06** : En tant que client, je peux signaler un contact, ce qui le rend privé (`PATCH /api/v2/contacts/{id}/toggle-access`). [À VÉRIFIER] droits exacts.

## 2. Écarts

Légende : ✅ disponible, ⚠️ disponible avec un comportement différent, ❌ absent, ➖ sans objet.

### Matrice bénéficiaire

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister tous mes contacts (privés et partagés) | ✅ WEB-01, 02 | ✅ V3-01 | ✅ LEGACY-01 |
| Rechercher un contact | ✅ WEB-03 | ❌ (E2) | ❌ |
| Consulter un contact | ✅ WEB-04 | ✅ V3-02 | ✅ LEGACY-02 |
| Créer un contact | ✅ WEB-05, privé par défaut | ⚠️ V3-03, partagé par défaut (E1) | ⚠️ LEGACY-03, partagé par défaut (E1) |
| Modifier un contact | ✅ WEB-06 | ✅ V3-04 | ✅ LEGACY-04 |
| Supprimer un contact | ✅ WEB-07 | ✅ V3-05 | ✅ LEGACY-05 |
| Basculer privé / partagé | ✅ WEB-08 | ✅ V3-06 | ✅ LEGACY-06 |
| Envoyer un document à un contact | ✅ WEB-09 | ⚠️ email libre via `POST /documents/{id}/share` (E7) | ❌ |
| Ordre de la liste | création décroissante | non défini (E5) | id décroissant |

### Matrice pro

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister les contacts partagés d'un bénéficiaire géré | ✅ WEB-01 | ✅ V3-02 | ✅ LEGACY-01 |
| Lister les contacts partagés de tous mes bénéficiaires | ❌ | ✅ V3-01 (E6) | ❌ |
| Rechercher un contact | ✅ WEB-02 | ❌ (E2) | ❌ |
| Consulter un contact partagé | ✅ WEB-03 | ✅ V3-03 | ✅ LEGACY-02 |
| Créer un contact | ✅ WEB-04, partagé par défaut | ✅ V3-04, partagé par défaut | ✅ LEGACY-03, partagé par défaut |
| Modifier un contact partagé | ✅ WEB-05 | ✅ V3-05 | ✅ LEGACY-04 |
| Supprimer un contact partagé | ✅ WEB-06 | ✅ V3-06 | ✅ LEGACY-05 |
| Rendre privé un contact partagé | ✅ WEB-07 | ✅ V3-07 | ⚠️ LEGACY-06, via `reportAbuse` (E9) |
| Envoyer un document à un contact | ✅ WEB-08 | ⚠️ email libre (E7) | ❌ |
| Accéder à un contact privé | ➖ refusé partout | ➖ refusé partout | ➖ refusé partout |

### Matrice client API

| Fonctionnalité | API v3 (client credentials) | API v2 |
|---|---|---|
| Lister les contacts partagés des bénéficiaires liés | ✅ V3CC-01, V3CC-02 | ✅ LEGACY-01 (un bénéficiaire à la fois) |
| Consulter un contact partagé | ✅ V3CC-03 | ✅ LEGACY-02 |
| Créer un contact | ✅ V3CC-04 | ✅ LEGACY-03 |
| Modifier un contact | ❌ (E3) | ✅ LEGACY-04 |
| Supprimer un contact | ❌ (E3) | ✅ LEGACY-05 |
| Rendre privé un contact | ✅ V3CC-05 | ⚠️ LEGACY-06 |

### Liste des écarts

Les écarts avec la v1/v2 seule ne sont pas à corriger (périmètre en extinction), sauf s'ils révèlent une fonctionnalité dont les clients v2 dépendent et qui manque en v3.

| # | Écart | Canaux | Gravité | Décision à prendre |
|---|---|---|---|---|
| E1 | Un contact créé par le bénéficiaire est **privé par défaut sur le web** mais **partagé par défaut en API** si `bPrive` n'est pas envoyé. Un contact saisi depuis l'app mobile peut donc être visible des pros sans que le bénéficiaire l'ait voulu, selon ce que l'app envoie. | Web / v3 | Haute (confidentialité) | Règle cible unique du défaut de visibilité selon l'auteur (bénéficiaire ou pro). [À VÉRIFIER] ce que l'app mobile envoie aujourd'hui. |
| E2 | Pas de recherche en v3 : aucun filtre API Platform sur `Contact`. | Web / v3 | Moyenne | Ajouter un filtre de recherche en v3, ou assumer une recherche côté app. |
| E3 | En client credentials v3, `PATCH` et `DELETE /contacts/{id}` exigent `ROLE_USER` : un client ne peut ni modifier ni supprimer un contact, alors qu'il le pouvait en v2 et qu'un scope `contacts_update` existe. | v3 client / v2 | Moyenne | Restriction voulue ou oubli ? Si voulue, le scope `contacts_update` ne sert qu'au toggle de visibilité. |
| E5 | Ordre de la liste différent : date de création décroissante sur le web, ordre non défini en v3 [À VÉRIFIER], id décroissant en v1/v2. | Web / v3 | Basse | Fixer un ordre par défaut en v3. |
| E6 | En v3, un pro peut lister les contacts partagés de tous ses bénéficiaires en un appel (`GET /contacts`) ; le web n'a pas d'équivalent. | Web / v3 | Basse | Probablement voulu pour l'app ; à confirmer. |
| E7 | Le web propose d'envoyer un document à un contact (avec contrôle « contact sans email ») ; la v3 accepte un email libre. Le choix du contact est donc à la charge de l'app. | Web / v3 | Basse | Acceptable si l'app gère la sélection ; à confirmer. |
| ~~E8~~ | Invalidé : `nom` porte bien une contrainte `NotBlank`, déclarée en YAML (`config/validator/personal_data.yml`) sur `DonneePersonnelle`. Elle s'applique au web et à la v3. | ➖ | ➖ | Aucune. |
| E10 | Quand un pro consulte la liste des contacts d'un bénéficiaire sur le web, la consultation est enregistrée (`BeneficiaryConsultationEvent`). Rien n'est enregistré en v3. Découvert à l'étape 4. | Web / v3 | Moyenne | La consultation depuis l'app doit-elle compter ? |
| E9 | En v1/v2, le toggle d'un pro ne bascule pas : il passe par `reportAbuse` et rend le contact privé. Même effet final que la v3, logique différente. | v1/v2 | Info | Aucune (v1/v2 hors périmètre). |


## 3. Couverture de tests

Légende : ✅ cas nominal et cas de refus couverts, 🟡 couverture partielle, ❌ non couvert.

Chemins abrégés : `Web/` = `tests/v2/Controller/ContactController/`, `V3/` = `tests/v2/API/v3/PasswordGrant/PersonalData/`, `V3CC/` = `tests/v2/API/v3/ClientCredentials/Contact/`, `V2/` = `tests/v2/API/v2/PasswordGrant/`, `V1/` = `tests/v1/Controller/Rest/`.

Les tests v3 password grant et v2 sont génériques (un data provider par type de donnée personnelle) : ils couvrent les contacts via leur ligne `Contact`.

### Bénéficiaire

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| BENEF-WEB-01 | ❌ | | Présence de l'entrée « Mes contacts » dans la navigation |
| BENEF-WEB-02 | 🟡 | `Web/ListTest::testRoute` (200, autre bénéficiaire 403) | Les contacts privés figurent bien dans la liste |
| BENEF-WEB-03 | ❌ | | Recherche |
| BENEF-WEB-04 | ✅ | `Web/DetailTest::testRoute` (200, autre bénéficiaire 403) | |
| BENEF-WEB-05 | 🟡 | `Web/CreateTest::testRoute`, `testFormIsValid`, `testFormIsNotValid` (nom, prénom, email) | Contact privé par défaut (E1) ; persistance en base [À VÉRIFIER] |
| BENEF-WEB-06 | ✅ | `Web/EditTest::testRoute`, `testFormIsValid`, `testFormIsNotValid` | |
| BENEF-WEB-07 | 🟡 | `Web/DeleteTest::testRoute` (302, autre bénéficiaire 403) | Suppression effective en base [À VÉRIFIER] |
| BENEF-WEB-08 | 🟡 | `Web/ToggleVisibilityTest::testRoute` (AJAX), `Web/SwitchPrivateTest::testRoute` (redirection) | Changement effectif de `bPrive` [À VÉRIFIER] |
| BENEF-WEB-09 | ✅ | `tests/v2/Controller/SharedDocument/ShareWithContactTest::testRoute`, `testSendEmailToContact` (avec et sans email) | |
| BENEF-V3-01 | ✅ | `V3/PersonalDataCollectionTest::testBeneficiaryListsOwnPersonalData`, `testBeneficiaryListsOwnPersonalDataFromBeneficiaryEndpoint`, `testCanNotListPersonalDataOfAnUnmanagedBeneficiary` | |
| BENEF-V3-02 | ✅ | `V3/PersonalDataItemTest::testGetItem` (propre contact privé 200, autre bénéficiaire 403) | |
| BENEF-V3-03 | 🟡 | `V3/CreateTest::testBeneficiaryCreatesItemForThemself` | Validation (nom, prénom, email) ; visibilité par défaut (E1) |
| BENEF-V3-04 | 🟡 | `V3/PatchTest::testBeneficiaryRenamesItem`, `testBeneficiaryCanNotGiveItemToAnotherBeneficiary` | Modification d'un contact d'un autre bénéficiaire ; validation |
| BENEF-V3-05 | ✅ | `V3/DeleteTest::testDelete` (propre contact privé), `testCanNotDelete` (autre bénéficiaire) | |
| BENEF-V3-06 | ✅ | `V3/ToggleVisibilityTest::testBeneficiaryTogglesOwnItem` (deux sens), `testAnotherBeneficiaryCanNotToggleItem` | |
| BENEF-LEGACY-01 | ✅ | `V2/PersonalData/MobilePersonalDataListTest::testBeneficiaryListsAllPersonalData`, `testCanNotListPersonalDataOfAnotherBeneficiary` | |
| BENEF-LEGACY-02 | 🟡 | `V2/PersonalData/PersonalDataAccessTest::testAuthorizedUserCanReadPersonalData` | Contact d'un autre bénéficiaire |
| BENEF-LEGACY-03 à 06 | ❌ | | Création, modification, suppression, bascule par un bénéficiaire |

### Pro

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| PRO-WEB-01 | 🟡 | `Web/ListTest::testRoute` (200, pro sans relais commun 403) | Les contacts privés sont absents de la liste |
| PRO-WEB-02 | ❌ | | Recherche |
| PRO-WEB-03 | ✅ | `Web/DetailTest::testRoute` (200, 403, contact privé 403) | |
| PRO-WEB-04 | 🟡 | `Web/CreateTest::testRoute` (200, 403) | Soumission du formulaire par un pro ; contact partagé par défaut |
| PRO-WEB-05 | 🟡 | `Web/EditTest::testRoute` (200, 403, contact privé 403) | Soumission du formulaire par un pro |
| PRO-WEB-06 | ✅ | `Web/DeleteTest::testRoute` (302, 403, contact privé 403) | |
| PRO-WEB-07 | 🟡 | `Web/ToggleVisibilityTest::testRoute`, `Web/SwitchPrivateTest::testRoute` (200/302, 403, contact privé 403) | Changement effectif de `bPrive` |
| PRO-WEB-08 | 🟡 | `ShareWithContactTest::testRoute` (302, 403) | Envoi vers un contact privé refusé |
| PRO-V3-01 | ✅ | `V3/PersonalDataCollectionTest::testMemberListsSharedPersonalDataOfManagedBeneficiaries` | |
| PRO-V3-02 | ✅ | `V3/PersonalDataCollectionTest::testMemberListsSharedPersonalDataFromBeneficiaryEndpoint`, `testCanNotListPersonalDataOfAnUnmanagedBeneficiary` | |
| PRO-V3-03 | 🟡 | `V3/PersonalDataItemTest::testGetItem` (partagé 200, privé 403) | Bénéficiaire non géré |
| PRO-V3-04 | 🟡 | `V3/CreateTest::testProCreatesItemForManagedBeneficiary`, `testProCanNotCreateItemForUnmanagedBeneficiary` (404), `testProMustGiveBeneficiary` (400) | Validation ; visibilité par défaut |
| PRO-V3-05 | 🟡 | `V3/PatchTest::testProCanNotMoveItemBetweenManagedBeneficiaries` | Modification nominale d'un contact partagé ; contact privé ; bénéficiaire non géré |
| PRO-V3-06 | 🟡 | `V3/DeleteTest::testDelete` (partagé), `testCanNotDelete` (privé) | Bénéficiaire non géré |
| PRO-V3-07 | 🟡 | `V3/ToggleVisibilityTest::testProMakesSharedItemPrivate`, `testProCanNotToggleAPrivateItem` | Bénéficiaire non géré |
| PRO-LEGACY-01 | ✅ | `V2/PersonalData/MobilePersonalDataListTest::testMemberListsOnlySharedPersonalData`, `testCanNotListPersonalDataOfAnotherBeneficiary` | |
| PRO-LEGACY-02 | ✅ | `V2/PersonalData/PersonalDataAccessTest` (lecture d'un partagé, refus d'un privé), `V2/Security/ProPrivateDataAccessTest::testProCanNotAccessPrivateItem` | |
| PRO-LEGACY-03 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testAffiliateABeneficiary` (création) | Bénéficiaire non géré ; validation |
| PRO-LEGACY-04 | 🟡 | `V2/PersonalData/PersonalDataAccessTest::testMemberWithRelayInCommonCannotAccessPrivatePersonalData` (refus sur privé) | Modification nominale |
| PRO-LEGACY-05 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testAffiliateABeneficiary` (nominal), `PersonalDataAccessTest` (refus sur privé) | Bénéficiaire non géré |
| PRO-LEGACY-06 | 🟡 | `PersonalDataAccessTest` (refus sur privé) | Signalement nominal (le contact devient privé) |

### Client API

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| CLIENT-V3CC-01 | ✅ | `V3CC/ContactApiV3Test::testGetCollection`, `testCanNotGetCollection` (scopes) | |
| CLIENT-V3CC-02 | 🟡 | `V3CC/ContactApiV3Test::testGetCollectionForBeneficiary`, `testCanNotGetCollectionForBeneficiary` (scopes) | Bénéficiaire non lié |
| CLIENT-V3CC-03 | 🟡 | `V3CC/ContactApiV3Test::testGetOne`, `testCanNotGetOne` (scopes) | Contact privé (404) ; bénéficiaire non lié |
| CLIENT-V3CC-04 | ❌ | | Création (nominal, `beneficiaireId` manquant, bénéficiaire non lié, scope absent, validation) |
| CLIENT-V3CC-05 | ❌ | | Toggle de visibilité |
| PATCH / DELETE refusés | ❌ | | Refus de modification et de suppression par un client (E3) |
| CLIENT-LEGACY-01 | ❌ | | Liste par identifiant distant ; lien non accepté |
| CLIENT-LEGACY-02 à 06 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testReconnectPro` (création, lecture, modification, passage en privé, suppression) | Tous les cas de refus |

### Synthèse

| Acteur / canal | ✅ | 🟡 | ❌ |
|---|---|---|---|
| Bénéficiaire web | 3 | 4 | 2 |
| Bénéficiaire v3 | 4 | 2 | 0 |
| Bénéficiaire v1/v2 | 1 | 1 | 4 |
| Pro web | 2 | 5 | 1 |
| Pro v3 | 2 | 5 | 0 |
| Pro v1/v2 | 2 | 4 | 0 |
| Client v3 | 1 | 2 | 3 |
| Client v2 | 0 | 5 | 1 |

### Constats sur les tests existants

- **Tests web limités au statut HTTP** : la plupart des tests web vérifient le code de réponse, pas l'effet (contenu de la liste, contact supprimé, `bPrive` changé). [À VÉRIFIER] au cas par cas dans `testRoute`.
- **Doublon** : `Web/ToggleVisibilityTest` et `Web/SwitchPrivateTest` testent la même route (`/contact/{id}/toggle-visibility`), l'un en AJAX, l'autre en redirection. Ils pourraient être fusionnés.
- **Risque de test instable** : `ContactFactory` tire `bPrive` au hasard (`faker()->boolean()`). `V3CC/ContactApiV3Test::testGetOne` appelle `findOrCreate` sans fixer `bPrive` ; si le contact obtenu est privé, le client devrait recevoir 404 au lieu de 200. [À VÉRIFIER]
- **Écarts sans test** : aucun test ne fige la visibilité par défaut à la création (E1), sur aucun canal.

## 4. Centralisation des règles métier (web et API v3)

Légende : ✅ une seule implémentation partagée, ⚠️ dupliquée (deux implémentations de la même règle), ❌ divergente ou absente d'un canal.

### Inventaire des règles

| # | Règle | Web | API v3 | Statut |
|---|---|---|---|---|
| R1 | Un utilisateur peut-il accéder à un bénéficiaire donné ? | `BeneficiaryVoter::UPDATE` → `UserHelper::canUpdateBeneficiary` | Idem (sécurité du `Link` de la sous-ressource, `PersonalDataStateProcessor`) | ✅ |
| R2 | Quels bénéficiaires un pro gère-t-il ? (version requête, pour les collections) | ➖ (la liste web passe par un bénéficiaire, donc par R1) | `BeneficiaireRepository::addProAccessConditions` (DQL), appelé par `FilterUserCollectionsExtension` | ⚠️ |
| R3 | Un utilisateur peut-il lire, modifier, supprimer ou basculer un contact ? | `PersonalDataVoter` (`UPDATE`, `DELETE`, `TOGGLE_VISIBILITY`) | `PersonalDataVoter`, mais le toggle v3 vérifie `UPDATE` au lieu de `TOGGLE_VISIBILITY` | ✅ (⚠️ mineur) |
| R4 | Les contacts privés sont masqués aux non-propriétaires dans les listes | `ContactRepository::findByBeneficiary($isOwner)`, avec `isOwner` calculé par `UserAwareTrait::isLoggedInUser` | `FilterUserCollectionsExtension::filterPersonalData` (`bPrive = false` si l'utilisateur n'est pas bénéficiaire) | ⚠️ |
| R5 | Visibilité par défaut à la création | Contrôleur : option `private` de `BeneficiaryPersonalDataController::getCreateForm` | Valeur par défaut de l'entité (`DonneePersonnelle::PARTAGE`) ou payload | ❌ (E1) |
| R6 | Rattachement du contact au bénéficiaire à la création | Paramètre de route + `IsGranted('UPDATE', beneficiary)` | `PersonalDataStateProcessor::getBeneficiary` (via R1) | ✅ |
| R7 | Interdiction de changer le bénéficiaire d'un contact | Structurel : le formulaire n'expose pas le champ | `securityPostDenormalize` sur `Patch` | ✅ |
| R8 | Validation des champs (nom, prénom, email) | Contraintes de l'entité (`Assert` + `config/validator/personal_data.yml`) | Idem | ✅ |
| R9 | Effet de la bascule de visibilité | `Contact::toggleVisibility()` via `ContactManager::toggleVisibility` | `Contact::toggleVisibility()` via `PersonalDataStateProcessor` | ✅ |
| R10 | Recherche (champs cherchés) | `ContactRepository::findByBeneficiary` (nom, prénom, téléphone, email) | Absente | ❌ (E2) |
| R11 | Ordre de la liste | `ContactRepository::findByBeneficiary` (création décroissante) | Non défini | ❌ (E5) |
| R12 | Journalisation et créateur de la donnée | Listeners Doctrine `PersonalDataListener` et `CreatorListener` | Idem | ✅ |
| R13 | Enregistrement de la consultation d'un bénéficiaire par un pro | `BeneficiaryPersonalDataController::listContacts` déclenche `BeneficiaryConsultationEvent` | Absent | ❌ (E10) |

### Détail des duplications

- **R2** : la règle « un pro gère un bénéficiaire » existe deux fois, en PHP (`UserHelper::canUpdateBeneficiary`) et en DQL (`addProAccessConditions`). Elles ne sont pas strictement équivalentes, et toute évolution de la règle est à faire deux fois.
- **R3** : pour un contact, `UPDATE` et `TOGGLE_VISIBILITY` donnent le même résultat aujourd'hui. Mais si la règle de `canToggleVisibility` évolue, la v3 ne suivra pas.
- **R4** : deux implémentations de « les privés sont réservés au propriétaire », avec deux définitions du propriétaire. Le web compare l'utilisateur connecté à l'utilisateur du bénéficiaire ; la v3 regarde seulement si l'utilisateur connecté est un bénéficiaire, puis restreint à son propre bénéficiaire. Le résultat est le même aujourd'hui, mais toute évolution est à faire deux fois.
- **R5** : la règle n'existe pas dans le domaine. Elle est codée dans un contrôleur web, et la v3 retombe sur la valeur par défaut de l'entité.

### Plan de refacto

Chaque étape correspond à un commit et ne change pas le comportement, sauf mention contraire. Les étapes 5 à 7 dépendent de décisions métier (E1, E2, E5, E10) : elles sont à faire à l'étape 5 du projet, une fois les règles cibles fixées.

1. **Tests de caractérisation** (`test:`) : avant tout refacto, figer le comportement actuel sur les trous de l'étape 3 qui touchent ces règles. Concrètement : contenu des listes web (privés visibles du bénéficiaire, absents pour le pro), visibilité par défaut à la création sur web et v3, et cas « bénéficiaire non géré » en v3.
2. **R3** (`refactor:`) : en v3, faire vérifier `TOGGLE_VISIBILITY` (et non `UPDATE`) sur `/contacts/{id}/toggle-visibility`, comme le web. À appliquer aux autres données personnelles en même temps, puisque la ressource est générique.
3. **R4** (`refactor:`) : extraire la condition « privés réservés au propriétaire » dans une seule méthode de requête, réutilisée par `ContactRepository::findByBeneficiary` et `FilterUserCollectionsExtension::filterPersonalData`. Par exemple une méthode statique sur le modèle de `addProAccessConditions`, qui prend l'utilisateur connecté. Le nom et l'emplacement sont à valider.
4. **R2** (`refactor:` ou `fix:` selon la décision) : aligner les deux implémentations. PHP et DQL ne pouvant pas partager le même code, ajouter un test de contrat qui vérifie, sur les fixtures, que `UserHelper::canUpdateBeneficiary` et `addProAccessConditions` renvoient les mêmes bénéficiaires.
5. **R5** (`fix:`, dépend de E1) : sortir la règle du contrôleur web dans un service du domaine, appelé par le formulaire web et par `PersonalDataStateProcessor`. Point d'attention : en v3, il faut distinguer « `bPrive` absent du payload » de « `bPrive` envoyé à `false` ».
6. **R10 / R11** (`feat:`, dépend de E2 et E5) : définir une seule fois les champs de recherche et l'ordre par défaut, puis les utiliser dans `ContactRepository` et dans un filtre API Platform. Je ne sais pas encore si un filtre natif (`SearchFilter`) suffit pour un « OU » sur quatre champs ; un filtre maison sera peut-être nécessaire. À vérifier dans la doc API Platform.
7. **R13** (`feat:`, dépend de E10) : si la consultation depuis l'app doit compter, déclencher `BeneficiaryConsultationEvent` depuis la v3, à un seul endroit (par exemple un listener sur les opérations de la sous-ressource `/beneficiaries/{id}/...`) plutôt qu'endpoint par endpoint.

Ce plan touche des classes génériques (`PersonalDataVoter`, `FilterUserCollectionsExtension`, `PersonalDataStateProcessor`), partagées avec les documents, dossiers, notes et événements. Il vaudra mieux l'exécuter après l'audit de ces domaines, pour regrouper les changements.
