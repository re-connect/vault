# Admin Sonata (CFN)

## Contrôle d'accès

- `ROLE_ADMIN` : tout `/admin` (`config/packages/security.yaml`, `access_control`).
- `ROLE_SUPER_ADMIN` : écrans du groupe « Outils SuperAdmin » (`sonata.admin.admin`, `sonata.admin.feature_toggle`,
  `sonata.admin.folder_icon`, `reo_auth.admin.client`), protégés par une règle `access_control` sur leur URL.
- `roles` d'un groupe du dashboard (`config/packages/sonata_admin.yaml`) **cache seulement le menu** : il ne protège pas les URL.
- Une action réservée aux Superadmins dans un écran Admin se teste dans l'admin : `isGranted('ROLE_SUPER_ADMIN')` (ex. export dans `BeneficiaireAdmin`).
- Tests d'accès : `tests/v2/Controller/Admin/AdminUserAdminTest.php` (`AdminFixture::ADMIN_MAIL`, `SuperAdminFixture::SUPER_ADMIN_MAIL`).

## URL des admins

- Pas de `generateBaseRoutePattern()` surchargé : l'URL vient du FQCN de l'entité.
  `App\Entity\FeatureFlag` → `/admin/app/featureflag`, route `admin_app_featureflag_*`.
- **Renommer ou déplacer une entité change l'URL** : mettre à jour les règles `access_control` et les tests
  (le passage de `App\Entity\Attributes\*` à `App\Entity\*` avait rendu la règle Superadmin inopérante).
- Les admins de liaison externe (`reo_auth.admin.client_*`, URL `/admin/app/clientbeneficiaire`…) servent aux admins
  depuis les fiches Bénéficiaire et Professionnel : une règle sur `/admin/app/client` doit s'arrêter à `(/|$)`.
- Vérifier la route réelle : `symfony php bin/console debug:router <route>` (default `_sonata_admin` = code de l'admin servi).

## Pièges

- `sonata.admin.user_simple` et `sonata.admin.admin` portent tous deux sur `App\Entity\User` : mêmes routes
  `admin_app_user_*`, le dernier déclaré (`sonata.admin.admin`) les écrase. `/admin/app/user/{id}/edit` ouvre donc
  `AdminUserAdmin` pour n'importe quel utilisateur. `user_simple` n'est utilisé qu'en formulaire intégré (`admin_code`).
- `AdminUserAdmin::configureQuery()` ne filtre que la liste, pas l'édition par id.
- `User::setTypeUser()` remplace tous les rôles (`setRoles([$typeUser])`).

## Collections inline et bouton « + Ajouter »

- Le bouton envoie **tout le formulaire parent** en AJAX vers `/admin/core/append-form-field-element`
  (`AppendFormFieldElementAction` de Sonata → `AdminHelper::appendFormFieldElement()`).
- En création, pas d'`objectId` : Sonata part de `getNewInstance()`, soumet les données postées (`handleRequest`),
  puis reconstruit le formulaire pour afficher la nouvelle ligne. Conséquences pour `configureFormFields()` :
  - appelée deux fois par clic : pas d'effet de bord ;
  - `isCurrentRoute('create')` et `isCurrentRoute('edit')` valent `false` pendant cet appel (route `sonata_admin_append_form_element`) ;
  - l'entité de la ligne est neuve : toute propriété lue par son `*CentreAdmin` doit être initialisée (`?Type $x = null`),
    sinon « must not be accessed before initialization » et le clic renvoie une 500.
- Modèle : `MembreCentre` (`?Membre $membre = null`, `?Centre $centre = null`). `BeneficiaireCentre` a été corrigé
  de la même façon, avec `#[Assert\NotNull]` pour continuer à refuser une ligne incomplète.
- Un setter qui reçoit `null` à la soumission doit correspondre au type de sa propriété : un champ vidé qui arrive
  sur une propriété non nullable renvoie aussi une 500.

## Tests fonctionnels de formulaires

- Poster ce que le navigateur envoie (exemple : `tests/v2/Controller/Admin/BeneficiaireAdminTest.php`) :
  - fixer le nom du formulaire avec `?uniqid=<valeur>` : les champs deviennent `<uniqid>[user][nom]` ;
  - lire le jeton CSRF dans `input[name="<uniqid>[_token]"]` sur la page en GET ;
  - envoyer aussi les champs préremplis : un champ absent arrive à `null` dans les setters et fait échouer le test pour une autre raison ;
  - `BirthdayType` est en `single_text` : envoyer `Y-m-d` (`1990-01-01`).
- Couvrir « + Ajouter » en création (sans `objectId`) et en modification (avec `objectId`).

## Dette connue

- `MembreAdmin::preUpdate()` utilise `Query::HINT_FORCE_PARTIAL_LOAD` (retiré dans Doctrine ORM 3) et concatène l'id
  dans le DQL : ne pas recopier, utiliser un paramètre lié.
- `MembreAdmin` passe l'option `admin_code_external_links`, qui n'est pas une option Sonata.
