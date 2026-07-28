# Foundational Decisions

Status: Initial consolidation baseline

These decisions reconcile contradictions discovered during the final document
review. They govern until replaced by an accepted architecture decision record
or an explicit canonical-document revision.

## FD-001: Clean implementation history

Koravik Final starts at Build 1. It inherits product concepts, not prior code,
schemas, migrations, routes, build numbers, or completion claims.

## FD-002: Durable person identity

An Account is the single durable identity. “Player” describes the Account
holder while experiencing a World and is not a second identity model.

Households, Organizations, Worlds, social participation, Companion, Arena, and
Creator capabilities are optional.

## FD-003: Organization ownership

The Organizations group module owns organizations, teams, membership, and group
roles.

Beacon owns public presentation, outreach, campaigns, and organization-facing
pages. Gather owns events and participation. Quests owns tasks, projects, and
responsibilities. References connect these records without transferring
ownership.

## FD-004: Household action ownership

Quests owns assignable, completable, repeatable, or tracked action state,
including actions associated with a Household. Hearth composes and displays
that information but does not own the task record or completion event.

## FD-005: Journey requires formal modeling

Journey is reserved as a curated, durable, cross-District record of meaningful
milestones. It must not be implemented until its ownership, entities, privacy
rules, and relationship to Chronicle and raw events are accepted in an ADR.

## FD-006: World state boundaries

NPC relationships, narrative flags, story progress, inventory, delayed
consequences, and World-specific achievements belong to one World installation.
They do not automatically transfer to unrelated Worlds.

Account-level achievements and explicitly portable rewards must use separate,
clearly defined models.

## FD-007: Initial World activation scope

The initial implementation supports Account-installed Worlds and one primary
active World per Account. Installed inactive Worlds may receive only explicitly
approved, bounded delayed processing.

Household-installed and Organization-installed Worlds are deferred until the
Account installation model is proven.

## FD-008: District facts and World interpretation

Districts own real-life records. After a successful commit, an owning District
may publish a minimized, versioned fact through the platform outbox.

Authorized Worlds interpret facts into independent World State. A World must
not edit the originating District record, and sensitive source data must not be
delivered when a derived condition is sufficient.

## FD-009: Companion authority

Companion may suggest, explain, summarize, and draft. Consequential actions
require explicit approval and must be performed by the owning District with an
audit record.

Companion memory must be sourced, visible, correctable, removable, and separated
between real-life and fictional contexts.

## FD-010: First proof

The first vertical slice must prove secure Account identity, one District-owned
real-life action, transactional event publication, Epic Ordinary
interpretation, independent and explainable World State, and safe resume after
leaving.

Broad District expansion must not precede this proof.
