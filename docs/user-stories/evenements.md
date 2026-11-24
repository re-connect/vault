# Événements

Un événement (entité `Evenement`, ressource API `Event`) appartient à un bénéficiaire. Il a un titre (`nom`), une date, un fuseau horaire, un lieu, un commentaire, des **rappels SMS** (entité `Rappel`) et une visibilité : **privé** ou **partagé**, avec les mêmes règles d'accès que les [contacts](contacts.md).

Règles propres aux événements, valables sur tous les canaux car portées par les validateurs de l'entité (`App\Validator\Constraints\Evenement`, `App\Validator\Constraints\Rappel`) :

- la date de l'événement ne peut pas être antérieure à « maintenant moins 12 h 05 » ;
- un rappel ne peut pas être postérieur à l'événement ;
- un nouveau rappel ne peut pas être antérieur à « maintenant moins 12 h 05 » ;
- un rappel déjà envoyé ne peut pas changer de date ;
- un rappel exige que le bénéficiaire ait un numéro de téléphone.

Les rappels SMS sont envoyés par la commande planifiée `SendRemindersSmsCommand` (`EventManager::sendReminders`).

## 1. User stories

### Bénéficiaire

#### Web

- **EVENT-BENEF-WEB-01** : En tant que bénéficiaire, je peux accéder à « Mes rappels » (mes événements) depuis la navigation.
- **EVENT-BENEF-WEB-02** : En tant que bénéficiaire, je peux voir la liste de mes événements à venir, privés et partagés, du plus proche au plus lointain.
  - Les événements passés n'apparaissent pas.
  - Refus : la liste des événements d'un autre bénéficiaire m'est interdite.
- **EVENT-BENEF-WEB-03** : En tant que bénéficiaire, je peux rechercher un événement par titre, commentaire ou date.
- **EVENT-BENEF-WEB-04** : En tant que bénéficiaire, je peux consulter un événement (date dans son fuseau horaire, lieu, commentaire, rappels).
  - Un événement passé reste consultable par son URL.
  - Refus : l'événement d'un autre bénéficiaire m'est interdit.
- **EVENT-BENEF-WEB-05** : En tant que bénéficiaire, je peux créer un événement, privé par défaut, avec une date proposée à « maintenant + 1 h » et le fuseau horaire de mon navigateur.
  - Refus : titre vide ; date passée ; ajout chez un autre bénéficiaire.
- **EVENT-BENEF-WEB-06** : En tant que bénéficiaire, je peux ajouter un ou plusieurs rappels SMS à un événement, à la création ou à la modification.
  - Refus : rappel après l'événement ; rappel passé ; pas de numéro de téléphone sur mon compte.
- **EVENT-BENEF-WEB-07** : En tant que bénéficiaire, je peux modifier un événement, y compris son accès et ses rappels.
  - Un rappel retiré est supprimé ; un rappel déjà envoyé garde sa date d'origine.
  - Refus : mêmes règles de validation qu'à la création ; événement d'un autre bénéficiaire.
- **EVENT-BENEF-WEB-08** : En tant que bénéficiaire, je peux supprimer un événement après confirmation.
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-WEB-09** : En tant que bénéficiaire, je peux basculer un événement entre privé et partagé depuis la liste.
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-WEB-10** : En tant que bénéficiaire, je reçois un SMS à la date de chaque rappel. (Commun à tous les canaux.)

#### API v3 (password grant)

- **EVENT-BENEF-V3-01** : En tant que bénéficiaire, je peux lister mes événements à venir, privés et partagés (`GET /events`, `GET /beneficiaries/{id}/events`).
  - Les événements passés ne sont pas listés.
  - Refus : `GET /beneficiaries/{id}/events` sur un autre bénéficiaire.
- **EVENT-BENEF-V3-02** : En tant que bénéficiaire, je peux consulter un événement, même passé (`GET /events/{id}`).
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-V3-03** : En tant que bénéficiaire, je peux créer un événement avec ses rappels (`POST /events`), qui m'est toujours rattaché.
  - Le `beneficiaireId` envoyé est ignoré. Partagé par défaut si `bPrive` n'est pas envoyé. Le fuseau horaire n'est pas exposé en v3 : `Europe/Paris` est appliqué par défaut.
  - Refus : titre vide ; date passée ; rappel après l'événement ou passé ; pas de numéro de téléphone.
- **EVENT-BENEF-V3-04** : En tant que bénéficiaire, je peux modifier un événement et ses rappels (`PATCH /events/{id}`).
  - Rappels : ceux renvoyés avec leur `id` sont conservés, ceux sans `id` sont créés, ceux absents sont archivés s'ils ont déjà été envoyés par SMS, supprimés sinon (`EventStateProcessor`).
  - Refus : validation ; événement d'un autre bénéficiaire ; changement de bénéficiaire.
- **EVENT-BENEF-V3-05** : En tant que bénéficiaire, je peux supprimer un événement (`DELETE /events/{id}`).
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-V3-06** : En tant que bénéficiaire, je peux basculer un événement entre privé et partagé (`PATCH /events/{id}/toggle-visibility`).
  - Refus : événement d'un autre bénéficiaire.

#### API v1/v2

- **EVENT-BENEF-LEGACY-01** : En tant que bénéficiaire, je peux lister mes événements à venir, privés et partagés (`GET /beneficiaries/{id}/events`).
  - Refus : événements d'un autre bénéficiaire (`GESTION_BENEFICIAIRE`).
- **EVENT-BENEF-LEGACY-02** : En tant que bénéficiaire, je peux consulter un événement (`GET /events/{id}`).
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-LEGACY-03** : En tant que bénéficiaire, je peux créer un événement avec ses rappels et son fuseau horaire (`POST /beneficiaries/{id}/events`), partagé si `b_prive` n'est pas envoyé.
  - Refus : validation (mêmes règles) ; création chez un autre bénéficiaire.
- **EVENT-BENEF-LEGACY-04** : En tant que bénéficiaire, je peux modifier un événement et ses rappels (`PUT /events/{id}`), avec la même gestion des rappels qu'en v3 (archivage des rappels envoyés).
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-LEGACY-05** : En tant que bénéficiaire, je peux supprimer un événement (`DELETE /events/{id}`).
  - Refus : événement d'un autre bénéficiaire.
- **EVENT-BENEF-LEGACY-06** : En tant que bénéficiaire, je peux basculer un événement entre privé et partagé (`PATCH /events/{id}/toggle-access`).
  - Refus : événement d'un autre bénéficiaire.

### Pro

#### Web

- **EVENT-PRO-WEB-01** : En tant que pro, je peux voir la liste des événements partagés à venir d'un bénéficiaire que je gère.
  - Refus : bénéficiaire que je ne gère pas ; les événements privés n'apparaissent pas.
- **EVENT-PRO-WEB-02** : En tant que pro, je peux rechercher un événement partagé par titre, commentaire ou date.
- **EVENT-PRO-WEB-03** : En tant que pro, je peux consulter un événement partagé.
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-WEB-04** : En tant que pro, je peux créer un événement pour un bénéficiaire que je gère, partagé par défaut, avec des rappels SMS.
  - Refus : validation (titre, date, rappels, téléphone du bénéficiaire) ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-WEB-05** : En tant que pro, je peux modifier un événement partagé et ses rappels.
  - Refus : événement privé ; bénéficiaire que je ne gère pas ; validation.
- **EVENT-PRO-WEB-06** : En tant que pro, je peux supprimer un événement partagé après confirmation.
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-WEB-07** : En tant que pro, je peux rendre privé un événement partagé (j'en perds ensuite l'accès).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.

#### API v3 (password grant)

- **EVENT-PRO-V3-01** : En tant que pro, je peux lister les événements partagés à venir de tous les bénéficiaires que je gère (`GET /events`).
- **EVENT-PRO-V3-02** : En tant que pro, je peux lister les événements partagés à venir d'un bénéficiaire que je gère (`GET /beneficiaries/{id}/events`).
  - Refus : bénéficiaire que je ne gère pas ; les événements privés n'apparaissent pas.
- **EVENT-PRO-V3-03** : En tant que pro, je peux consulter un événement partagé (`GET /events/{id}`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-V3-04** : En tant que pro, je peux créer un événement avec rappels pour un bénéficiaire que je gère (`POST /events` avec `beneficiaireId`), partagé par défaut.
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire que je ne gère pas (404) ; validation.
- **EVENT-PRO-V3-05** : En tant que pro, je peux modifier un événement partagé et ses rappels (`PATCH /events/{id}`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas ; changement de bénéficiaire ; validation.
- **EVENT-PRO-V3-06** : En tant que pro, je peux supprimer un événement partagé (`DELETE /events/{id}`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-V3-07** : En tant que pro, je peux rendre privé un événement partagé (`PATCH /events/{id}/toggle-visibility`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.

#### API v1/v2

- **EVENT-PRO-LEGACY-01** : En tant que pro, je peux lister les événements partagés à venir d'un bénéficiaire (`GET /beneficiaries/{id}/events`).
  - Refus : bénéficiaire que je ne gère pas (`GESTION_BENEFICIAIRE`) ; les événements privés n'apparaissent pas.
- **EVENT-PRO-LEGACY-02** : En tant que pro, je peux consulter un événement partagé (`GET /events/{id}`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-LEGACY-03** : En tant que pro, je peux créer un événement avec rappels pour un bénéficiaire (`POST /beneficiaries/{id}/events`). Si je le crée privé, la réponse ne renvoie pas l'événement.
  - Refus : bénéficiaire que je ne gère pas ; validation.
- **EVENT-PRO-LEGACY-04** : En tant que pro, je peux modifier un événement partagé et ses rappels (`PUT /events/{id}`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-LEGACY-05** : En tant que pro, je peux supprimer un événement partagé (`DELETE /events/{id}`).
  - Refus : événement privé ; bénéficiaire que je ne gère pas.
- **EVENT-PRO-LEGACY-06** : En tant que pro, je peux signaler un événement partagé, ce qui le rend privé (`PATCH /events/{id}/toggle-access` passe par `reportAbuse`).
  - Refus : événement privé.

### Client API (client credentials)

#### API v3

- **EVENT-CLIENT-V3CC-01** : En tant que client avec le scope `events_read`, je peux lister les événements partagés à venir des bénéficiaires liés à mon client (`GET /events`).
  - Un client avec tous les privilèges voit les événements partagés de tous les bénéficiaires.
- **EVENT-CLIENT-V3CC-02** : En tant que client avec le scope `events_read`, je peux lister les événements partagés d'un bénéficiaire lié (`GET /beneficiaries/{id}/events`).
  - Refus : bénéficiaire non lié à mon client.
- **EVENT-CLIENT-V3CC-03** : En tant que client avec le scope `events_read`, je peux consulter un événement partagé et à venir (`GET /events/{id}`).
  - Refus : événement privé (404) ; événement passé (404, filtre de `ClientResourceCollectionExtension` appliqué aussi à l'item) ; bénéficiaire non lié (404) ; scope absent.
- **EVENT-CLIENT-V3CC-04** : En tant que client avec le scope `events_create`, je peux créer un événement avec rappels pour un bénéficiaire lié (`POST /events` avec `beneficiaireId`), partagé par défaut.
  - Refus : `beneficiaireId` manquant (400) ; bénéficiaire non lié (404) ; scope absent ; validation.
- **EVENT-CLIENT-V3CC-05** : En tant que client avec le scope `events_update`, je peux rendre privé un événement partagé (`PATCH /events/{id}/toggle-visibility`).
  - Refus : événement privé ou passé (404) ; bénéficiaire non lié (404) ; scope absent.
- Pas de modification (`PATCH /events/{id}`) ni de suppression (`DELETE /events/{id}`) : les deux exigent `ROLE_USER`.

#### API v2

- **EVENT-CLIENT-LEGACY-01** : En tant que client, je peux lister les événements partagés à venir d'un bénéficiaire via son identifiant distant (`GET /api/v2/beneficiaries/{distantId}/events`).
  - Refus : bénéficiaire inconnu pour mon client ; lien pas encore accepté.
- **EVENT-CLIENT-LEGACY-02** : En tant que client, je peux consulter un événement d'un bénéficiaire lié (`GET /api/v2/events/{id}`). [À VÉRIFIER] filtrage des événements privés.
- **EVENT-CLIENT-LEGACY-03** : En tant que client, je peux créer un événement avec rappels pour un bénéficiaire lié (`POST /api/v2/beneficiaries/{distantId}/events`). Le client est enregistré comme créateur.
  - Refus : bénéficiaire non lié ; lien non accepté ; validation.
- **EVENT-CLIENT-LEGACY-04** : En tant que client, je peux modifier un événement partagé et ses rappels (`PUT /api/v2/events/{id}`).
  - Refus : événement privé ; bénéficiaire non lié.
- **EVENT-CLIENT-LEGACY-05** : En tant que client, je peux supprimer un événement d'un bénéficiaire lié (`DELETE /api/v2/events/{id}`). [À VÉRIFIER] refus sur événement privé.
- **EVENT-CLIENT-LEGACY-06** : En tant que client, je peux signaler un événement, ce qui le rend privé (`PATCH /api/v2/events/{id}/toggle-access`). [À VÉRIFIER] droits exacts.

### Différences d'implémentation à exploiter aux étapes 2 et 4

- **Gestion des rappels à la modification, trois implémentations** :
  - web : `EventManager::updateReminders` supprime tout rappel retiré, **même déjà envoyé**, et restaure la date d'origine d'un rappel envoyé ;
  - v3 : `EventStateProcessor::updateReminders` **archive** un rappel retiré déjà envoyé, supprime les autres ;
  - v1/v2 : `EvenementProvider::populate`, même logique que la v3 (le processor v3 indique l'avoir reprise).
- **« Événement à venir », quatre définitions** :
  - web, liste : requête `date > maintenant − 12 h 05` dans `EvenementRepository::findFutureEventsByBeneficiary`, puis filtre PHP `date ≥ maintenant` (UTC) dans `EventManager::getEvents` ;
  - v3 utilisateur, collection : `date > maintenant` dans `FilterUserCollectionsExtension::filterEvents` ;
  - v3 client, collection et item : `date > maintenant` dans `ClientResourceCollectionExtension` (le paramètre s'appelle `today` mais vaut `new \DateTime()`) ;
  - v1/v2 : `date > maintenant` dans `Beneficiaire::getEvenements`.
- **Fuseau horaire** : exposé en v1/v2 (`read-personal-data-v2`, `write-personal-data-v2`) et saisi automatiquement sur le web, mais absent des groupes v3. Un événement créé depuis l'app est interprété en `Europe/Paris`.
- **Sécurité du toggle v3** : même forme que pour les notes (`ROLE_OAUTH2_EVENTS_UPDATE or (ROLE_USER and UPDATE)`) ; `Evenement` n'implémente pas `ClientResourceInterface`.
- **Création v3** : `POST /events` passe par `PersonalDataStateProcessor`, pas par `EventStateProcessor`. Les rappels sont alors créés par la dénormalisation, sans la logique du processor.

## 2. Écarts

Légende : ✅ disponible, ⚠️ disponible avec un comportement différent, ❌ absent, ➖ sans objet.

Les identifiants E1 à E10 renvoient aux [écarts des contacts](contacts.md#2-écarts), quand la cause est la même.

### Matrice bénéficiaire

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister mes événements à venir | ✅ WEB-02 | ✅ V3-01 | ✅ LEGACY-01 |
| Rechercher un événement | ✅ WEB-03 | ❌ (EV2) | ❌ |
| Consulter un événement, même passé | ✅ WEB-04 | ✅ V3-02 | ✅ LEGACY-02 |
| Créer un événement | ✅ WEB-05, privé par défaut, fuseau du navigateur | ⚠️ V3-03, partagé par défaut (EV1), fuseau forcé à `Europe/Paris` (EV9) | ⚠️ LEGACY-03, partagé par défaut (EV1) |
| Ajouter des rappels SMS | ✅ WEB-06 | ✅ V3-03, V3-04 | ✅ LEGACY-03, 04 |
| Retirer un rappel déjà envoyé | ⚠️ supprimé (EV8) | ✅ archivé | ✅ archivé |
| Modifier un événement | ✅ WEB-07 | ✅ V3-04 | ✅ LEGACY-04 |
| Supprimer un événement | ✅ WEB-08 | ✅ V3-05 | ✅ LEGACY-05 |
| Basculer privé / partagé | ✅ WEB-09 | ✅ V3-06 | ✅ LEGACY-06 |
| Recevoir les rappels par SMS | ✅ WEB-10 (commun) | ✅ | ✅ |
| Ordre de la liste | date croissante | non défini (EV5) | date croissante |

### Matrice pro

| Fonctionnalité | Web | API v3 | API v1/v2 |
|---|---|---|---|
| Lister les événements partagés à venir d'un bénéficiaire géré | ✅ WEB-01 | ✅ V3-02 | ✅ LEGACY-01 |
| Lister ceux de tous mes bénéficiaires | ❌ | ✅ V3-01 (EV6) | ❌ |
| Rechercher | ✅ WEB-02 | ❌ (EV2) | ❌ |
| Consulter un événement partagé | ✅ WEB-03 | ✅ V3-03 | ✅ LEGACY-02 |
| Créer un événement avec rappels | ✅ WEB-04, partagé par défaut | ⚠️ V3-04, fuseau forcé (EV9) | ✅ LEGACY-03 |
| Modifier un événement partagé et ses rappels | ⚠️ WEB-05, rappel envoyé supprimé (EV8) | ✅ V3-05 | ✅ LEGACY-04 |
| Supprimer un événement partagé | ✅ WEB-06 | ✅ V3-06 | ✅ LEGACY-05 |
| Rendre privé un événement partagé | ✅ WEB-07 | ✅ V3-07 | ⚠️ LEGACY-06, via `reportAbuse` |

### Matrice client API

| Fonctionnalité | API v3 (client credentials) | API v2 |
|---|---|---|
| Lister les événements partagés à venir des bénéficiaires liés | ✅ V3CC-01, V3CC-02 | ✅ LEGACY-01 |
| Consulter un événement partagé | ⚠️ V3CC-03, événement passé refusé (EV10) | ✅ LEGACY-02 |
| Créer un événement avec rappels | ⚠️ V3CC-04, fuseau forcé (EV9) | ✅ LEGACY-03 |
| Modifier un événement | ❌ (EV3) | ✅ LEGACY-04 |
| Supprimer un événement | ❌ (EV3) | ✅ LEGACY-05 |
| Rendre privé un événement | ✅ V3CC-05 | ⚠️ LEGACY-06 |

### Liste des écarts

| # | Écart | Canaux | Gravité | Décision à prendre |
|---|---|---|---|---|
| EV1 | Événement créé par le bénéficiaire : privé par défaut sur le web, partagé par défaut en API. | Web / v3 | Haute (confidentialité) | Même décision que E1. |
| EV2 | Pas de recherche en v3. | Web / v3 | Moyenne | Même décision que E2. |
| EV3 | Un client v3 ne peut ni modifier ni supprimer un événement (`ROLE_USER` exigé), alors que `events_update` existe et que la v2 le permettait. | v3 client / v2 | Moyenne | Même décision que E3. |
| EV5 | Ordre non défini en v3 [À VÉRIFIER] ; date croissante sur le web et en v1/v2. Pour un agenda, l'ordre chronologique a un sens métier. | Web / v3 | Moyenne | Fixer l'ordre par date croissante en v3. |
| EV6 | Liste globale des événements de tous les bénéficiaires d'un pro, en v3 seulement. | Web / v3 | Basse | Même décision que E6. |
| EV7 | Consultation d'un bénéficiaire par un pro enregistrée sur le web (`BeneficiaryConsultationEvent`), pas en v3. | Web / v3 | Moyenne | Même décision que E10. |
| EV8 | **Rappel retiré alors qu'il a déjà été envoyé** : le web le supprime (`EventManager::updateReminders`) ; la v3 et la v1/v2 l'archivent pour garder la trace du SMS. Le critère « déjà envoyé » diffère aussi : `bEnvoye` sur le web, présence d'un `SMS` lié en v3. [À VÉRIFIER] ce que devient l'enregistrement `SMS` lié quand le web supprime le rappel (contrainte de clé étrangère ou orphelin). | Web / v3 | Moyenne à haute (perte d'historique, erreur SQL possible) | Une seule règle : archiver, comme la v3. |
| EV9 | **Fuseau horaire absent de la v3** (ni lecture ni écriture) : tout événement créé ou modifié depuis l'app est interprété en `Europe/Paris`. Pour un utilisateur dans un autre fuseau (outre-mer par exemple), l'heure de l'événement et des rappels SMS est décalée. Le web et la v1/v2 gèrent le fuseau. | Web / v3 | Haute (rappels envoyés à la mauvaise heure) | Exposer `timezone` en v3. [À VÉRIFIER] ce que l'app envoie et affiche aujourd'hui. |
| EV10 | Un client v3 ne peut pas consulter un événement passé par son id (le filtre « à venir » de `ClientResourceCollectionExtension` s'applique aussi à l'item) ; un utilisateur le peut sur tous les canaux. | v3 client / v3 utilisateur | Basse | Voulu ou effet de bord ? |
| EV11 | La création v3 (`POST /events`) ne passe pas par `EventStateProcessor` : les rappels sont créés par la dénormalisation seule. Sans effet visible à la création (aucun rappel existant à traiter), mais un rappel envoyé avec un `id` dans un `POST` n'est pas traité comme en `PATCH`. [À VÉRIFIER] | v3 | Basse | Faire passer `POST` et `PATCH` par la même logique. |

### Remarques hors écarts

- **« À venir »** : les quatre implémentations relevées à l'étape 1 donnent le même résultat à l'usage (date postérieure à maintenant). Le web passe par un détour : la requête prend « maintenant moins 12 h 05 », puis un filtre PHP retire les événements passés. Aucune ne tient compte du fuseau propre de l'événement. C'est un sujet de centralisation (étape 4) plus qu'un écart fonctionnel.
- **Tolérance de 12 h 05** : les validateurs acceptent un événement ou un rappel jusqu'à 12 h 05 dans le passé, sur tous les canaux. C'est cohérent entre canaux, mais je ne connais pas la raison métier de cette valeur.

## 3. Couverture de tests

Légende : ✅ cas nominal et cas de refus couverts, 🟡 couverture partielle, ❌ non couvert.

Chemins abrégés : `Web/` = `tests/v2/Controller/EventController/`, `V3/` = `tests/v2/API/v3/PasswordGrant/PersonalData/`, `V3E/` = `tests/v2/API/v3/PasswordGrant/Event/`, `V3CC/` = `tests/v2/API/v3/ClientCredentials/Event/`, `V2/` = `tests/v2/API/v2/PasswordGrant/`, `V1/` = `tests/v1/Controller/Rest/`.

Les tests web ont la même structure que ceux des contacts : codes HTTP, refus d'un autre bénéficiaire ou d'un pro sans relais commun, refus d'un pro sur un événement privé (`Edit`, `Detail`, `Delete`, `ToggleVisibility`, `SwitchPrivate`).

### Bénéficiaire

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| BENEF-WEB-01 | ❌ | | Présence de « Mes rappels » dans la navigation |
| BENEF-WEB-02 | 🟡 | `Web/ListTest::testRoute` (200, autre bénéficiaire 403) | Événements privés présents ; événements passés absents ; ordre |
| BENEF-WEB-03 | ❌ | | Recherche |
| BENEF-WEB-04 | ✅ | `Web/DetailTest::testRoute` | Consultation d'un événement passé (non bloquant) |
| BENEF-WEB-05 | 🟡 | `Web/CreateTest::testRoute`, `testFormIsValid`, `testFormIsValidWithDateLessThan12HoursInThePast`, `testFormIsNotValid` (nom vide, date à plus de 12 h dans le passé) | Événement privé par défaut (EV1) ; fuseau enregistré |
| BENEF-WEB-06 | ❌ | | Ajout de rappels ; refus : rappel après l'événement, rappel passé, pas de téléphone |
| BENEF-WEB-07 | 🟡 | `Web/EditTest::testRoute`, `testFormIsValid`, `testFormIsNotValid` (nom vide) | Retrait de rappels (`EventManager::updateReminders`, EV8) ; rappel déjà envoyé |
| BENEF-WEB-08 | 🟡 | `Web/DeleteTest::testRoute` | Suppression effective, y compris des rappels |
| BENEF-WEB-09 | 🟡 | `Web/ToggleVisibilityTest::testRoute`, `Web/SwitchPrivateTest::testRoute` | Changement effectif de `bPrive` |
| BENEF-WEB-10 | 🟡 | `tests/v2/Service/NotificationServiceTest::testSendSmsReminderSuccessfully`, `testSendSmsReminderErrorLog` | Sélection des rappels dus : `tests/v2/Manager/EventManager/SendRemindersTest::testSendSmsReminders` est **sauté** (`markTestSkipped`, « weird behavior in CI/CD ») |
| BENEF-V3-01 | ✅ | `V3/PersonalDataCollectionTest` (méthodes génériques), `testPastEventsAreNotListed` | Ordre |
| BENEF-V3-02 | ✅ | `V3/PersonalDataItemTest::testGetItem`, `testBeneficiaryGetsOwnPastEvent` | |
| BENEF-V3-03 | 🟡 | `V3/CreateTest::testBeneficiaryCreatesItemForThemself` (sans rappel) | Création avec rappels ; validation (titre, date passée, rappels, téléphone) ; visibilité par défaut ; fuseau (EV9) |
| BENEF-V3-04 | 🟡 | `V3/PatchTest::testBeneficiaryRenamesItem`, `testBeneficiaryCanNotGiveItemToAnotherBeneficiary` ; rappels : `V3E/EventRemindersTest` (4 tests : conservation, création, archivage des envoyés, suppression des autres, `PATCH` sans rappels) | Événement d'un autre bénéficiaire ; validation |
| BENEF-V3-05 | ✅ | `V3/DeleteTest::testDelete`, `testCanNotDelete` | |
| BENEF-V3-06 | ✅ | `V3/ToggleVisibilityTest` (méthodes génériques) | |
| BENEF-LEGACY-01 | ✅ | `V2/PersonalData/MobilePersonalDataListTest` (méthodes génériques) | |
| BENEF-LEGACY-02 | 🟡 | `V2/PersonalData/PersonalDataAccessTest::testAuthorizedUserCanReadPersonalData` | Événement d'un autre bénéficiaire |
| BENEF-LEGACY-03 à 06 | ❌ | | Création, modification (dont rappels), suppression, bascule par un bénéficiaire |

### Pro

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| PRO-WEB-01 | 🟡 | `Web/ListTest::testRoute` (200, 403) | Événements privés absents |
| PRO-WEB-02 | ❌ | | Recherche |
| PRO-WEB-03 | ✅ | `Web/DetailTest::testRoute` (200, 403, privé 403) | |
| PRO-WEB-04 | 🟡 | `Web/CreateTest::testRoute` (200, 403) | Soumission par un pro ; rappels ; téléphone du bénéficiaire absent |
| PRO-WEB-05 | 🟡 | `Web/EditTest::testRoute` (200, 403, privé 403) | Soumission par un pro ; rappels |
| PRO-WEB-06 | ✅ | `Web/DeleteTest::testRoute` (302, 403, privé 403) | |
| PRO-WEB-07 | 🟡 | `Web/ToggleVisibilityTest::testRoute`, `Web/SwitchPrivateTest::testRoute` (privé 403) | Changement effectif |
| PRO-V3-01 | ✅ | `V3/PersonalDataCollectionTest::testMemberListsSharedPersonalDataOfManagedBeneficiaries` | |
| PRO-V3-02 | ✅ | `V3/PersonalDataCollectionTest::testMemberListsSharedPersonalDataFromBeneficiaryEndpoint`, `testCanNotListPersonalDataOfAnUnmanagedBeneficiary` | |
| PRO-V3-03 | 🟡 | `V3/PersonalDataItemTest::testGetItem` | Bénéficiaire non géré |
| PRO-V3-04 | 🟡 | `V3/CreateTest::testProCreatesItemForManagedBeneficiary`, `testProCanNotCreateItemForUnmanagedBeneficiary`, `testProMustGiveBeneficiary` | Rappels ; validation |
| PRO-V3-05 | 🟡 | `V3/PatchTest::testProCanNotMoveItemBetweenManagedBeneficiaries` | Modification nominale par un pro, rappels compris ; privé ; non géré |
| PRO-V3-06 | 🟡 | `V3/DeleteTest::testDelete` (partagé), `testCanNotDelete` (privé) | Bénéficiaire non géré |
| PRO-V3-07 | 🟡 | `V3/ToggleVisibilityTest::testProMakesSharedItemPrivate`, `testProCanNotToggleAPrivateItem` | Bénéficiaire non géré |
| PRO-LEGACY-01 | ✅ | `V2/PersonalData/MobilePersonalDataListTest::testMemberListsOnlySharedPersonalData` | |
| PRO-LEGACY-02 | ✅ | `V2/PersonalData/PersonalDataAccessTest`, `V2/Security/ProPrivateDataAccessTest` (événement privé) | |
| PRO-LEGACY-03 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testAffiliateABeneficiary` | Bénéficiaire non géré ; validation |
| PRO-LEGACY-04 | 🟡 | `PersonalDataAccessTest` (refus sur privé) | Modification nominale |
| PRO-LEGACY-05 | 🟡 | `PersonalDataAccessTest` (refus sur privé) | Suppression nominale |
| PRO-LEGACY-06 | 🟡 | `PersonalDataAccessTest` (refus sur privé) | Signalement nominal |

### Client API

| US | Statut | Couvert par | Manque |
|---|---|---|---|
| CLIENT-V3CC-01 | ✅ | `V3CC/EventApiV3Test::testGetCollection`, `testCanNotGetCollection` (scopes) | Filtre « à venir » |
| CLIENT-V3CC-02 | 🟡 | `V3CC/EventApiV3Test::testGetCollectionFromBeneficiary`, `testCanNotGetCollectionFromBeneficiary` | Bénéficiaire non lié |
| CLIENT-V3CC-03 | 🟡 | `V3CC/EventApiV3Test::testGetOne`, `testCanNotGetOne` (scopes) | Événement privé ; événement passé (EV10) ; bénéficiaire non lié |
| CLIENT-V3CC-04 | 🟡 | `V3CC/EventApiV3Test::testPost`, `testCanNotPost` (scopes) | `beneficiaireId` manquant ; non lié ; validation ; rappels |
| CLIENT-V3CC-05 | 🟡 | `V3CC/EventApiV3Test::testToggleVisibility` (200 puis 404 une fois privé), `testCanNotToggleVisibility` (scopes) | Bénéficiaire non lié |
| PATCH / DELETE refusés | ❌ | | Refus de modification et de suppression par un client (EV3) |
| CLIENT-LEGACY-01 | ❌ | | Liste par identifiant distant |
| CLIENT-LEGACY-02 à 06 | 🟡 | `V1/BeneficiaireRestV2ControllerTest::testReconnectPro` | Tous les cas de refus |

### Règles transverses du domaine

| Règle | Statut | Couvert par | Manque |
|---|---|---|---|
| Date d'événement : pas plus de 12 h 05 dans le passé | 🟡 | Web : `CreateTest::testFormIsValidWithDateLessThan12HoursInThePast`, `testFormIsNotValid` | Aucun test en v3 ni en modification |
| Rappel postérieur à l'événement refusé | ❌ | | Aucun canal |
| Nouveau rappel passé refusé | ❌ | | Aucun canal |
| Rappel déjà envoyé : date non modifiable | ❌ | | Aucun canal ([À VÉRIFIER] `EventRemindersTest::testDoesNotRecreateSentReminderSentBackWithItsId` couvre le renvoi, pas le changement de date) |
| Rappel sans numéro de téléphone refusé | ❌ | | Aucun canal |
| Envoi des SMS de rappel | 🟡 | `NotificationServiceTest` (envoi unitaire) | Sélection des rappels dus : test sauté |
| Consultation enregistrée sur la liste web | ✅ | `tests/v2/Controller/BeneficiaryConsultation/BeneficiaryConsultationTest::testBeneficiaryConsultationRecord` (événements, contacts, notes, documents) | Côté v3 : rien à tester tant que EV7 n'est pas tranché |

### Synthèse

| Acteur / canal | ✅ | 🟡 | ❌ |
|---|---|---|---|
| Bénéficiaire web | 1 | 6 | 3 |
| Bénéficiaire v3 | 4 | 2 | 0 |
| Bénéficiaire v1/v2 | 1 | 1 | 4 |
| Pro web | 2 | 4 | 1 |
| Pro v3 | 2 | 5 | 0 |
| Pro v1/v2 | 2 | 4 | 0 |
| Client v3 | 1 | 4 | 1 |
| Client v2 | 0 | 5 | 1 |

### Constats sur les tests existants

- **Les validateurs des événements et des rappels sont presque absents des tests** : seule la date passée est testée, et seulement sur le web. Ce sont pourtant les règles métier les plus riches du domaine, et elles valent pour tous les canaux.
- **La logique des rappels est testée en v3 (`EventRemindersTest`) mais pas sur le web**, alors que c'est précisément là qu'elle diverge (EV8).
- **Test sauté** : `SendRemindersTest::testSendSmsReminders` est désactivé (« weird behavior in CI/CD »). La sélection des rappels à envoyer (`RappelRepository::getDueReminders`, fenêtre de −12 h 05 à +14 h) n'est donc pas testée. C'est le cœur de la fonctionnalité de rappel SMS.
- **Aucun test sur le fuseau horaire** (EV9), sur aucun canal.
- **La consultation est testée sur la liste web** : ce test couvre aussi les contacts et les notes (E10, N8), ce que je n'avais pas relevé dans leurs fichiers.

## 4. Centralisation des règles métier (web et API v3)

Légende : ✅ une seule implémentation partagée, ⚠️ dupliquée, ❌ divergente ou absente d'un canal.

### Règles communes avec les contacts et les notes

Les règles R1 à R13 des [contacts](contacts.md#4-centralisation-des-règles-métier-web-et-api-v3) s'appliquent à l'identique, avec le même statut (duplications R2 et R4, divergences R5, R10, R11, R13). L'accès des clients API suit la forme des notes (NR1 dans [notes.md](notes.md#4-centralisation-des-règles-métier-web-et-api-v3)) : `Evenement` n'implémente pas `ClientResourceInterface`.

### Règles propres aux événements

| # | Règle | Web | API v3 | Statut |
|---|---|---|---|---|
| ER1 | Validation de la date de l'événement et des rappels (5 règles) | Validateurs de l'entité (`Evenement\EntityValidator`, `Rappel\EntityValidator`) | Idem | ✅ |
| ER2 | Mise à jour des rappels à la modification d'un événement | `EventManager::updateReminders` : supprime tout rappel retiré | `EventStateProcessor::updateReminders` (sur `PATCH` uniquement) : archive les rappels envoyés, supprime les autres. Copie de `EvenementProvider::populate` (v1/v2) | ❌ (EV8, EV11) |
| ER3 | Critère « rappel déjà envoyé » | `Rappel::getBEnvoye()` (dans `updateReminders` et dans le validateur) | `Rappel::getSms() !== null` (dans le processor) | ⚠️ |
| ER4 | Définition d'un événement « à venir » | `EvenementRepository::findFutureEventsByBeneficiary` (maintenant − 12 h 05) puis filtre PHP dans `EventManager::getEvents` (maintenant, UTC) | `FilterUserCollectionsExtension::filterEvents` (utilisateur) et `ClientResourceCollectionExtension` (client), maintenant | ⚠️ (4 implémentations avec v1/v2) |
| ER5 | Fuseau horaire de l'événement | Saisi par le formulaire (`TimezoneType`, `TimezoneListener`) ; interprété par `Evenement::getDate()` | Ni lu ni écrit : `Europe/Paris` par défaut via `Evenement::getDate()` | ❌ (EV9) |
| ER6 | Envoi des SMS de rappel | Commande planifiée `SendRemindersSmsCommand` → `EventManager::sendReminders` → `RappelRepository::getDueReminders` → `NotificationService::sendSmsReminder` | Idem (indépendant du canal) | ✅ |
| ER7 | Date proposée à la création (maintenant + 1 h) | `EventType` | ➖ (choix d'interface, l'app propose sa propre valeur) | ➖ |

### Détail

- **ER2** : c'est la duplication la plus coûteuse du domaine. Trois implémentations de la même opération, dont deux identiques (v3, v1/v2) et une divergente (web). En v3, la création n'y passe même pas, ce qui fait une quatrième variante implicite (la dénormalisation seule). Toute évolution de la gestion des rappels est aujourd'hui à faire à trois endroits.
- **ER3** : les deux critères coïncident presque toujours, puisque `NotificationService::sendSmsReminder` positionne `bEnvoye` puis crée le `SMS`. Ils divergent si l'envoi échoue entre les deux : `bEnvoye` vaut alors `true` sans `SMS` lié. Dans ce cas, le web considère le rappel comme envoyé, la v3 non.
- **ER4** : les implémentations donnent le même résultat, mais avec des détours. Le web filtre en deux temps (requête large puis PHP) et convertit en UTC. Aucune ne tient compte du fuseau propre de l'événement : un événement à 10 h en Guadeloupe est comparé comme un événement à 10 h à Paris. [À VÉRIFIER] que la date est bien stockée sans fuseau (heure locale de l'événement), ce que laisse penser `Evenement::getDate()`.
- **ER5** : le fuseau est une donnée métier (il conditionne l'heure d'envoi des SMS), mais la v3 ne le gère pas. Ce n'est pas une duplication : la règle est absente d'un canal.

### Plan de refacto

Les étapes du [plan des contacts](contacts.md#plan-de-refacto) et du [plan des notes](notes.md#plan-de-refacto) s'appliquent aussi aux événements. Étapes propres au domaine :

1. **Tests des validateurs** (`test:`) : tester les 5 règles d'ER1 au niveau de l'entité, avec le validateur Symfony et sans passer par un canal. Une seule suite couvre ainsi web, v3 et v1/v2.
2. **Test de sélection des rappels dus** (`test:`) : réactiver `SendRemindersTest` en figeant l'horloge. Le composant `symfony/clock` n'est pas une dépendance directe du projet ; je ne sais pas s'il est installé en dépendance transitive. À vérifier avant de choisir l'approche (`ClockInterface` injecté, ou dates relatives calculées dans le test).
3. **Test de caractérisation web des rappels** (`test:`) : figer le comportement actuel de `EventManager::updateReminders` (rappel retiré supprimé, date d'un rappel envoyé restaurée), pour que le changement de l'étape 5 soit visible dans le diff des tests.
4. **ER3** (`refactor:`) : une seule méthode « rappel déjà envoyé » sur `Rappel` (nom à valider, par exemple `isSent()`), utilisée par le validateur, le web et le processor. Le critère reste à choisir (`bEnvoye`, `SMS` lié, ou les deux).
5. **ER2** (`fix:`, dépend de EV8) : extraire la mise à jour des rappels dans un seul service du domaine, appelé par le web (à la place de `EventManager::updateReminders`) et par `EventStateProcessor`, en `POST` comme en `PATCH`. Le comportement retenu serait celui de la v3 (archivage des rappels envoyés). Point d'attention : le web reçoit la collection du formulaire (`LiveCollectionType`), la v3 un payload brut ; le service doit prendre en entrée une forme commune, par exemple la liste des rappels conservés et des nouvelles dates. `EvenementProvider` (v1/v2) peut l'appeler aussi, mais ce n'est pas obligatoire.
6. **ER4** (`refactor:`) : une seule condition de requête « à venir » (par exemple une méthode statique de `EvenementRepository`, sur le modèle de `addProAccessConditions`), utilisée par le repository web et par les deux extensions v3. Supprimer au passage le filtre PHP de `EventManager::getEvents`. La prise en compte du fuseau propre de l'événement dans cette condition est une évolution à décider avec EV9, pas un refacto.
7. **ER5** (`feat:`, dépend de EV9) : exposer `timezone` en lecture et en écriture en v3 (groupes `v3:event:read`, `v3:event:write`), avec une contrainte de validation sur l'entité (par exemple `Assert\Timezone`). La règle de validation profite alors aussi au web.
