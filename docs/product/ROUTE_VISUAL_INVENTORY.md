# Route Visual Inventory

**Status:** Approved
**Version:** 1.1
**Effective build:** 023

Every player-facing route has one owner, one visual home, one template family, and one primary question.

| Route family | Visual home | Owner | Template | Primary action | Required states |
|---|---|---|---|---|---|
| `/hearth` | Hearth | Hearth | orientation/composition | Open one relevant next step | first use, ordinary day, return, partial failure |
| `/quests*` | Quests | Quests | list/detail/editor | Create, begin, resume, or complete | empty, validation, missing, archived, guarded completion |
| `/chronicle*` | Chronicle | Chronicle | timeline/detail/editor | Open or preserve an entry | empty, read-only provenance, archived, validation |
| `/worlds` | Worlds | World Catalog | catalog | Review a World | empty, unavailable package, installed status |
| `/worlds/{world}` | Worlds | World Installation | detail/trust review | Install, resume, or continue | incompatible, suspended, unavailable, permission-revoked |
| `/worlds/epic-ordinary/play*` | Worlds | Epic Ordinary Runtime | story home/scene | Continue one fictional scene | support choice missing, chapter ready, objective active, objective complete |
| `/companion*` | Companion | Companion/destination owner | request/review/activity | Review a specific proposal | empty, expired, dismissed, failed, executed |
| `/search` | Utility | Search | grouped results | Open a source result | guidance, no results, unavailable |
| `/notifications*` | Utility | Notifications | attention list/preferences | Open source or change preference | empty, read, dismissed, suppressed |
| `/settings*` | Account/trust | Settings/Security/Data | settings/trust control | Save or review consequence | validation, confirmation, unavailable dependency |
| `/privacy`, `/audit` | Account/trust | Consent/Audit | trust control/activity | Review or revoke | empty history, revoked, retained evidence |
| `/login`, `/recover`, `/reset-password` | Authentication | Authentication | focused auth | Sign in or recover | invalid, locked, expired, unavailable |

## Template families

- **List:** bounded groups, one obvious create/open action, useful empty state.
- **Detail:** owner, status, context, provenance, and next action.
- **Editor:** labels, validation summary, consequence language, cancel path.
- **Review:** proposed action, source, destination, consequence, approval state.
- **Story home:** current chapter, one primary continuation, objective, relationship context, recent explained change, and leave-to-Hearth action.
- **Trust control:** purpose, source, recipient, last use, revocation or retention effect.
- **State panel:** empty, validation, authorization, missing, expired, partial failure, and safe retry.

Contextual previews may appear elsewhere, but full lifecycle controls remain with the owner.