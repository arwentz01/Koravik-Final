# Complete Chronicle Entry Lifecycle

**Status:** Implemented vertical slice

Chronicle entries now expose their complete lifecycle and trust context in one detail experience: authored content and tags, provenance, private-by-default ownership, editability, archive or restore, and explicit permanent deletion for editable records. Generated historical entries remain read-only and direct correction back to their source owner.

All operations are account-scoped and consequential changes are CSRF-protected and audited. Archive is reversible; deletion is deliberately confirmed and is unavailable for source-generated historical evidence.
