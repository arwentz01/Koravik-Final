# Installed World Lifecycle

**Status:** Approved  
**Effective build:** 025

## Visual home

Installed World management belongs beneath **Worlds → Installed Worlds → Manage**.

## Lifecycle meanings

- **Activate/resume:** make one World active while preserving every other World’s independent progress.
- **Suspend:** stop active narrative processing while retaining installation and state.
- **Uninstall and retain state:** remove active access while preserving recoverable account-specific progress.
- **Restart:** reset only the selected World’s account-specific chapter, scene, objectives, choices, relationships, reactions, and keepsakes.
- **Delete eligible World State:** remove the selected account’s eligible World data while retaining shared catalog/package definitions and lifecycle evidence.
- **Update package:** move to a compatible package version without silently erasing progress.

## Consequence boundary

World lifecycle operations never modify account identity, real-life Quests, Chronicle entries, Companion memory, another World’s state, shared package definitions, or retained audit evidence.

Restart and deletion require exact confirmation phrases. Repeated lifecycle submissions must be idempotent.