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
