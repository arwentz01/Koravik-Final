# Builds 098–107 — Accessibility Personalization

## Outcome

Koravik now carries a person’s reading and interaction preferences across the
signed-in application instead of limiting accessibility controls to contrast
and motion.

- **098:** durable accessibility preference schema and safe defaults;
- **099:** standard, large, and larger text scales;
- **100:** optional readable sans-serif typeface;
- **101:** relaxed content spacing;
- **102:** persistent underlined-link emphasis;
- **103:** enhanced keyboard-focus visibility;
- **104:** narrow reading-width option;
- **105:** dedicated accessibility settings surface and account-menu access;
- **106:** preference preview, reset, validation, and audit records;
- **107:** global visual-system integration, automated coverage, CI, and health checkpoint.

Preferences are account-owned, low risk, reversible, and applied as additive
CSS classes. Browser and operating-system accessibility preferences remain
authoritative when they request stronger behavior.

Open `/settings/accessibility` to review or change them.
