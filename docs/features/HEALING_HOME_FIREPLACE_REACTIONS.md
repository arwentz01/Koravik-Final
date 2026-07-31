# Healing Home Fireplace Reactions

**Status:** Implemented vertical product slice

## User workflow

The Fireplace room now explains World reactions directly inside Healing Home. A person can open `/home/rooms/fireplace`, review why the house noticed something, see the approved minimized fact and World rule, understand what private data was deliberately excluded, and mark the reaction as reviewed without leaving the room.

The experience answers one question: why did this fictional change happen?

## Routes

- `GET /home/rooms/fireplace`
- `POST /home/rooms/fireplace/reactions/{reactionId}/review`

## Ownership and privacy

- World reactions remain owned by Worlds.
- Review state reuses the existing account-scoped World reaction review service.
- Healing Home composes only reactions owned by the signed-in account.
- Fireplace explanation explicitly excludes Quest notes, Chronicle prose, Companion memory, Health records, and unrelated private data.

## Accessibility and states

- The Fireplace has an empty state when no reaction exists.
- Each reaction has labeled explanation fields: what changed, approved fact, World rule, deliberately excluded data, and interpreted timestamp.
- Unreviewed reactions render a normal CSRF-protected button.
- Reviewed reactions render visible reviewed status text.

## Verification

Automated coverage proves account-scoped reaction composition, explainability copy, privacy exclusions, review persistence, and no cross-account leakage.
