# Politique de mot de passe (CFN)

## Trois mécanismes qui forcent un changement de mot de passe

| Mécanisme | Flag / règle | Qui | Web | Mobile (réponse OAuth) |
|---|---|---|---|---|
| Mise à niveau des règles | `User::hasPasswordWithLatestPolicy = false` | tous | `PasswordPolicySubscriber` → `improve_password` | `weak_password` |
| Renouvellement annuel | `GdprService::isPasswordExpired()` (1 an après `passwordUpdatedAt`) | pros, gestionnaires, admins (jamais les bénéficiaires) | `ExpiredPasswordSubscriber` → `app_update_password` | `expired_password` (si `APPLI_EXPIRE_PASSWORD`) |
| Mot de passe temporaire | `User::hasTemporaryPassword = true` | bénéficiaires créés depuis Reconnect Pro | `TemporaryPasswordSubscriber` → `app_update_password` | `weak_password` (l'app n'a pas d'écran dédié) |

- Les pages `app_update_password` et `improve_password` sont dans `ControllerV2/GdprController` et partagent `update_password_form.html.twig`
  (texte selon le flag). `app_update_password` refuse le mot de passe actuel.
- `UserManager::updatePassword()` (v2) est le point de passage unique : il recalcule `hasPasswordWithLatestPolicy` et remet
  `hasTemporaryPassword` à `false`. L'ancien `App\Manager\UserManager` (v1, côté pro) ne touche pas aux flags.
- `WeakPasswordUpgrader::markPasswordCompliant()` passe `hasPasswordWithLatestPolicy` à `true` à **chaque connexion** (web et OAuth)
  si le mot de passe est fort : ne pas s'en servir pour forcer un changement d'un mot de passe fort.

## Mot de passe temporaire (Reconnect Pro)

- RP crée le coffre par `POST /api/v3/beneficiaries` **sans** mot de passe : le CFN en génère un, le renvoie (`user.plain_password`),
  RP l'affiche au pro et le lui envoie par mail. Le flag est posé dans `Api/State/BeneficiaryStateProcessor::createBeneficiary()`
  quand le client est `Client::CLIENT_RECONNECT_PRO`.
- Hors périmètre à ce jour : création depuis le CFN pro ou via Rosalie, mot de passe redéfini par un pro ou un admin
  (`BeneficiaryResetPasswordController`, `BeneficiaireAdmin`) : ces chemins remettent le flag à `false` via `updatePassword()`.

## Ordre des écrans à la connexion

- **Web** : CGS (`CheckAcceptedTermsOfUseSubscriber`) puis mot de passe. Chaque subscriber de redirection autorise
  `AbstractWebUserSubscriber::FIRST_VISIT_ROUTES` ; celui du mot de passe temporaire autorise aussi `improve_password`
  pour éviter une boucle avec `PasswordPolicySubscriber`. Tout nouveau subscriber de redirection doit autoriser les pages
  des autres, sinon redirection infinie.
- **Mobile** : ordre des `elseif` de `EventSubscriber/Api/Oauth2ResponseSubscriber` : MFA, compte désactivé, en création,
  `weak_password`, `expired_password`, `cgs_not_accepted`. Mot de passe **avant** CGS, donc l'inverse du web.
- L'app répond à `weak_password` en se reconnectant avec `_new_password` ; `Oauth2TokenIssuedSubscriber` (`ACCESS_TOKEN_ISSUED`)
  met à jour le mot de passe **avant** `TOKEN_REQUEST_RESOLVE`, qui construit la réponse. `WeakPasswordUpgrader::checkUpdateWeakPassword()`
  ignore un mot de passe identique à l'actuel ou trop faible.

## Dette et risques

- `_new_password` et `_auth_code` (MFA) sont lus dans la **query string** (`QUERY_STRING`) : ils finissent dans les logs HTTP.
  À déplacer dans le corps de la requête (évolution app + API, en gardant la query pour les anciennes versions).
- Code app mobile : dépôt `re-connect/appli`, `src/services/authentication.ts` (`handlePartialAuth`).

## Tests

- Web : `tests/v2/EventSubscriber/{PasswordPolicy,TemporaryPassword}SubscriberTest.php`,
  `tests/v2/Controller/GdprController/{Update,Improve}PasswordTest.php`.
- OAuth mobile : `tests/v2/API/v2/Oauth/TemporaryPasswordTokenTest.php`. Manager : `tests/v2/Manager/UserManager/`.
- Fixtures : `BeneficiaryFixture::BENEFICIARY_PASSWORD_WEAK`, `BENEFICIARY_TEMPORARY_PASSWORD(_FIRST_VISIT)`,
  `MemberFixture::MEMBER_PASSWORD_WEAK`, `MEMBER_FIRST_VISIT_AND_EXPIRED_PASSWORD`.
