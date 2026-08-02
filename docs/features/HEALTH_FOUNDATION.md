# Health Foundation

## Player-visible outcome

Authenticated people can record, review, correct, and delete a private daily wellbeing observation at `/health`. A check-in contains a date, a neutral 1–5 energy observation, one bounded feeling word, and an optional private note.

## Trust boundary

- Health observations are private by default and are neither diagnostic nor medical advice.
- The experience has no streaks, rewards, failure states, or treatment recommendations.
- Explicit consent may publish only the observation date and a derived `low`, `steady`, or `full` energy band.
- Feeling words and private notes are excluded from the outbox event.
- Corrections and deletions leave lifecycle evidence without retaining deleted private note content.
- Account export and closure include the Health-owned records.

## Routes

- `GET /health`
- `POST /health/checkins`
- `GET /health/checkins/{id}`
- `POST /health/checkins/{id}`
- `POST /health/checkins/{id}/delete`

The unauthenticated `GET /health` and `GET /system/health` endpoints remain deployment probes. An authenticated request to `/health` opens the personal Health experience.

## Storage and interoperability

`health_wellbeing_checkins` owns the current observation. `health_checkin_revisions` records created, corrected, and deleted lifecycle actions. Opt-in interoperability emits `Health.WellbeingCheckInRecorded` version 1 with only `checkin_id`, `observed_on`, and `energy_band`.

## Validation

The release suite verifies the schema, private record lifecycle, correction, deletion evidence, minimized event payload, and explicit exclusion of feeling words and private notes.
