# Builds 088–097 — Release Verification

## Outcome

Koravik now has one executable release suite instead of relying only on syntax
checks and source-text assertions.

- **088:** unified PHP test runner with readable pass/fail output;
- **089:** repository-to-ledger migration and critical schema verification;
- **090:** password and CSRF security primitive regression checks;
- **091:** Organization contextual capability tests with rolled-back fixtures;
- **092:** Household contextual capability tests with rolled-back fixtures;
- **093:** Gather authorization source-contract checks;
- **094:** live login, semantic accessibility, asset, form, and subdirectory tests;
- **095:** Platform Mail and lifecycle recovery schema checks;
- **096:** bounded-worker and health diagnostics checks;
- **097:** combined PHP 8.3 lint, migrations, HTTP smoke, and executable release gate.

Authorization fixtures run inside transactions and are always rolled back.
The suite never deletes or rewrites user records.

Run:

```bash
php tools/test.php
```
