# Audit des user stories par domaine fonctionnel

## Objectif

Pour chaque domaine fonctionnel :

1. Lister les user stories par canal et par acteur.
2. Comparer les listes et repérer les écarts.
3. Vérifier que chaque user story (cas de refus compris) est couverte par un test automatisé.
4. Vérifier que les règles métier du web et de l'API v3 sont centralisées (pas de duplication de logique), sinon proposer un plan de refacto. Les API v1/v2 sont hors périmètre de cette étape, car amenées à disparaître.
5. Corriger les écarts.

## Canaux et acteurs

| Canal | Acteurs |
|---|---|
| Web (v2) | Bénéficiaire, Pro |
| API v3, password grant | Bénéficiaire, Pro |
| API v3, client credentials | Client API (tous scopes) |
| API v1/v2 (`/api/`, `/api/v2/`) | Bénéficiaire, Pro, Client API |

## Conventions

- Identifiant : `<DOMAINE>-<ACTEUR>-<CANAL>-<NN>`, avec `ACTEUR` parmi `BENEF`, `PRO`, `CLIENT` et `CANAL` parmi `WEB`, `V3`, `V3CC`, `LEGACY`.
- Les cas de refus sont listés sous chaque user story.
- `[À VÉRIFIER]` signale un comportement déduit du code mais non confirmé.

## Domaines

| Domaine | Fichier | 1. US | 2. Écarts | 3. Tests | 4. Centralisation |
|---|---|---|---|---|---|
| Contacts | [contacts.md](contacts.md) | ✅ | ✅ | ✅ | ✅ |
| Documents et dossiers | [documents-dossiers.md](documents-dossiers.md) | ✅ | ✅ | ✅ | ✅ |
| Notes | [notes.md](notes.md) | ✅ | ✅ | ✅ | ✅ |
| Événements | [evenements.md](evenements.md) | ✅ | ✅ | ✅ | ✅ |
| Authentification et mot de passe | | | | | |
| Compte utilisateur | | | | | |
| Relais | | | | | |
| Gestion des bénéficiaires par un pro | | | | | |
| Gestion des pros | | | | | |
| Interopérabilité (Rosalie, SI-SIAO) | | | | | |
