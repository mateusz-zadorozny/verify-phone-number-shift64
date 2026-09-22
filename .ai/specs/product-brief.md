# Verify Phone Number Shift64 — product brief

- Date: 2026-09-22
- Mode: existing; Owner: Mateusz Zadorożny (maintainer); Pass: full
- Evidence basis: one tracker issue with a root cause quoted from WooCommerce core (#23), the WooCommerce 10.9.4 source installed on this machine, this repository, and one account from the maintainer recorded this session. Both current installations are operated by the maintainer, so no independent store operator or shopper contributed. There is no usage data and no support history. The belief most consequential to the current decision — that the problem was confined to classic checkout — was refuted from source during this session (A02); what remains untested and accepted is A01.
- Coverage: 28 claims — 24 sourced (interview 9, data 0, document 10, product 5, benchmark 0), 0 synthetic, 4 assumed; 1 entry on the collection plan
- Synthetic hypotheses outside Coverage: 0
- Definition of Ready signed by: not yet signed; Problems have source support, Target group does not.
- Ready for: implementation planning of the Now scope. The condition that previously held this open (Q01) was answered from source during this session and is closed.
- Sources: `.ai/specs/research/interviews/2026-09-22-maintainer.md`, `.ai/specs/research/decisions/{D01,D02,D03,D04,N01}.md`, GitHub issues #23, #11, #25, `README.md`, `readme.txt`, `src/`, `BACKWARD_COMPATIBILITY.md`, and WooCommerce 10.9.4 core

## Decision summary

Agreed: input cleanup that would otherwise be written into a theme belongs in the plugin (D01).

The situation supporting it is recorded in issue #23. On classic checkout, a phone number containing a non-breaking space is rejected by `WC_Validation::is_phone()` inside `WC_Checkout::validate_posted_data()` — before `woocommerce_after_checkout_validation`, where this plugin validates. The shopper sees WooCommerce's own message, the plugin's normalizer never runs, and the order is blocked. The maintainer found this while integrating version 1.4.0 with a partner theme; a theme-side workaround fixes it there today.

This session established from WooCommerce source that block checkout has the same defect on a different path, and that the fix proposed in issue #23 cannot reach it (see Problems, A02). The maintainer chose to ship the classic fix first and treat block checkout separately (D03), knowingly leaving rule R02 unmet for this input class until the second change lands. The cleanup will also collapse whitespace on fields the plugin skips, which the maintainer accepted on condition that the contract wording is corrected in the same pull request (D04). Confirming that a number belongs to the customer stays permanently out of scope (N01).

What could most change this: nothing about the defect itself — it is read from source. The open risk is organisational: the R02 gap has no tracker item yet, so the second half can be forgotten (Q01-followup).

Next action: file the block-checkout issue so D03's deliberate gap is tracked. The maintainer owns it. No date set.

## Vision

Store operators running WooCommerce should be able to trust the phone numbers on their orders without adapting each theme — the direction the maintainer stated when naming the WordPress.org directory as the destination; this is intended direction, not a demonstrated benefit. `[INTERVIEW]` `research/interviews/2026-09-22-maintainer.md`

## Target group and stakeholders

- Intended adopter: WooCommerce store operators. Today there are two installations and the maintainer operates both, so this is a chosen target, not an observed market. Nobody pays for the plugin: it is GPL-2.0-or-later and the stated destination is the free WordPress.org directory. `[INTERVIEW]` `research/interviews/2026-09-22-maintainer.md`, `composer.json`
- User (uses): the shopper typing or pasting a phone number at checkout. The one documented situation is a number pasted with non-breaking spaces from a document. `[DOCUMENT]` issue #23
- Stakeholders (decides, blocks, operates): the developer integrating the plugin with a theme. One such integration is documented — carried out by the maintainer. No external consumer of the plugin's filters is recorded anywhere. `[DOCUMENT]` issue #23, issue #8
- Decider for scope decisions: Mateusz Zadorożny (maintainer)

## Problems, with evidence

- A phone number containing U+00A0 is rejected by WooCommerce core before the plugin validates, so the order is blocked and the plugin's error message and filters never apply. Root cause quoted from `class-wc-validation.php:33`, observed on WooCommerce 10.9.4 and PHP 8.3. One account, from the maintainer. `[DOCUMENT]` issue #23
- The same defect exists on block checkout. `StoreApi/Schemas/V1/AbstractAddressSchema.php:237-239` runs `wc_remove_non_displayable_chars()` and then the same `WC_Validation::is_phone()`; the strip list at `wc-formatting-functions.php:1657-1677` covers fifteen formatting characters and includes neither U+00A0, U+202F nor U+2007. The rejection precedes `woocommerce_store_api_checkout_update_order_from_request`, the hook registered at `src/Checkout/BlockCheckoutValidator.php:67`. Read from WooCommerce 10.9.4 source, not observed in a browser. `[PRODUCT]` WooCommerce 10.9.4
- That every classic-checkout installation hits this is the reporter's inference from the root cause, not an observed frequency. No count of affected orders exists. `[ASSUMPTION]` issue #23
- Four behaviours have never been checked in a browser: block checkout, Polylang/WPML message language, the HPOS screen, and one-click update from a non-default directory. Issue #11 is closed, so this work currently has no open tracker item. `[DOCUMENT]` issue #11
- None of those four has caused an observed problem on the two live installations. This means no failure was seen; it does not mean the paths work. `[INTERVIEW]` `research/interviews/2026-09-22-maintainer.md`

## Product and how it stands out

- What it is: a WooCommerce plugin that validates billing and shipping phone numbers against the address country using libphonenumber, and optionally rewrites valid ones into one stored format. `[PRODUCT]` `README.md`, `src/`
- What makes it different: no reference product was checked in this session; deferred, because the current decision does not depend on a comparison.

| reference | what it does well | where it falls short for our users | checked on | link |
|---|---|---|---|---|

## Goals and success criteria

- Business goal: reach the WordPress.org plugin directory. Stated by the maintainer as the destination; no evidence of demand there was collected. `[INTERVIEW]` `research/interviews/2026-09-22-maintainer.md`
- User outcome: a valid number pasted from a document is accepted at checkout instead of blocking the order. `[DOCUMENT]` issue #23
- Primary metric, baseline today, threshold, date: none. There is no usage or support data, so no baseline exists and no target was set.
- What must not get worse: the six protected surfaces in `BACKWARD_COMPATIBILITY.md` — the four `shift64_phone_validation_*` filters, the five options, the stored order phone, the public PHP API, the environment minimums, and the text domain. D04 changes the third of these deliberately and requires the document to be corrected in the same pull request. `[PRODUCT]` `BACKWARD_COMPATIBILITY.md`

## Scope

- **Now:** move whitespace cleanup for the billing and shipping phone fields into the plugin on classic checkout, so it runs before WooCommerce's own phone validation; correct the `should_validate` docblock and `BACKWARD_COMPATIBILITY.md` §3 in the same change (D01, D03, D04, R03). `[DOCUMENT]` `research/decisions/D03.md`
- **Later:** the equivalent fix for block checkout on a Store API entry point, which needs its own issue (D03); the four unverified behaviours from the closed issue #11, which also need a new issue; the sponsor link in issue #25. `[DOCUMENT]` issues #11, #25
- **Not doing:** see Non-goals

## Domain glossary

| Term | Meaning | Owned by | Visible to |
|---|---|---|---|
| Validate | Check that a number is a real, well-formed number for a country. What this plugin does. | maintainer | developers |
| Verify | Confirm the number belongs to the person entering it. What this plugin does **not** do (N01). | maintainer | developers, store operators |
| Normalization | Cleaning separators and whitespace out of input before parsing. Owned by `Validation\Normalizer`. | maintainer | developers |
| Default country | The country used as parsing context when a number has no `+` prefix. | maintainer | store operators |

## Key flows

- Current state: shopper pastes a number containing non-breaking spaces into the billing phone field on classic checkout, submits, and is blocked by WooCommerce's own validation message before the plugin runs. `[DOCUMENT]` issue #23
- Future state: the same paste is cleaned to single ASCII spaces as WooCommerce assembles posted data, so the number reaches the plugin's normalizer and is judged on whether it is a real number. The same paste on block checkout is still blocked until the Later work lands. `[DOCUMENT]` `research/decisions/D03.md`

## Business rules

| Id | Rule | Applies to | Source | Status | Review by | Required path to change | Owner | Supersedes |
|---|---|---|---|---|---|---|---|---|
| R01 | The shipping phone is optional. On classic checkout it is validated only when "ship to a different address" is set and the field is not empty; on block checkout there is no such gate and any non-empty shipping phone on the order is validated. | classic and block checkout | `[PRODUCT]` `src/Checkout/ShippingPhoneValidator.php:48-58`, `src/Checkout/BlockCheckoutValidator.php` | active | unknown | maintainer | Mateusz Zadorożny | none |
| R02 | Classic checkout and block checkout must reach the same verdict on the same input. Knowingly not met for non-breaking-space input between the two halves of D03. | validation behaviour | `[PRODUCT]` `CODE_REVIEW.md` | active | when the block-checkout fix lands | maintainer | Mateusz Zadorożny | none |
| R03 | Cleaning input must never change which numbers are judged valid — only which characters survive to be parsed. Under D04 it may change the characters stored on a skipped field. | normalization | `[DOCUMENT]` `research/decisions/D01.md`, `research/decisions/D04.md` | active | unknown | maintainer | Mateusz Zadorożny | none |

## Non-goals

| Id | We are not building | Why | Owner | Status | Review by | Required path to change | Source | Supersedes |
|---|---|---|---|---|---|---|---|---|
| N01 | Confirmation that a phone number belongs to the customer (SMS or one-time code) | The plugin checks correctness only; ownership confirmation is a different product with a gateway, per-message cost and personal-data handling | Mateusz Zadorożny | active | none — stated as permanent | owner-approved superseding row | `[INTERVIEW]` `research/decisions/N01.md` | none |

## Decisions

| Id | Date | Decision | Why | Owner | Status | Review by | Required path to change | Source | Supersedes |
|---|---|---|---|---|---|---|---|---|---|
| D01 | 2026-09-22 | Input cleanup that would otherwise live in a theme belongs in the plugin | The alternative considered was continuing to fix it per theme. The reporter's inference in issue #23 — that every classic-checkout install hits the same root cause — was the stated reason; it is reasoning from the root cause, not a measured frequency | Mateusz Zadorożny | active | if WooCommerce fixes `WC_Validation::is_phone()` upstream | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D01.md` | none |
| D02 | 2026-09-22 | Keep the GitHub self-updater as it is and find out at WordPress.org review | Alternatives offered were removing it at submission or disabling it for directory installs; chosen without an agent recommendation, because the current guideline was not established | Mateusz Zadorożny | active | first review response | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D02.md` | none |
| D03 | 2026-09-22 | Fix classic checkout first; block checkout ships as a separate change | Alternatives were covering both paths at once, or looking for a shared entry point first; chosen for a smaller first change, accepting that R02 is unmet for this input class in the meantime | Mateusz Zadorożny | active | when the block-checkout fix lands | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D03.md` | none |
| D04 | 2026-09-22 | A cleaned phone value may be stored even on fields the plugin skips | The alternative was restoring the original characters for storage when the plugin skips the field; chosen for the simpler implementation, on condition that the `should_validate` docblock and `BACKWARD_COMPATIBILITY.md` §3 are corrected in the same pull request | Mateusz Zadorożny | active | if a site reports depending on the exact stored characters of a skipped field | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D04.md` | none |

## Riskiest assumptions

| Id | Assumption | Importance | Evidence today | If false | Smallest test | Owner | By when | Result |
|---|---|---|---|---|---|---|---|---|
| A01 | WordPress.org will accept the plugin with its bundled GitHub self-updater `[ASSUMPTION]` origin: D02 | high | `src/Admin/GitHubUpdater.php` queries `api.github.com`; the directory serves its own updates. No guideline text was checked | A submission round is spent and the updater has to change | Reading the current directory guidelines would settle it; the owner declined that check under D02 in favour of learning at review | Mateusz Zadorożny | first submission | accepted untested (D02) |
| A02 | The non-breaking-space problem is confined to classic checkout `[ASSUMPTION]` origin: issue #23, which stated block checkout was not tested | high | Refuted from WooCommerce 10.9.4 source during this session — see the second entry under Problems. Block checkout is affected on a different path, and the fix proposed in issue #23 cannot reach it | Already acted on: the scope was re-decided as D03 | none needed; the source read settled it | Mateusz Zadorożny | 2026-09-22 | refuted |
| A03 | Cleaning whitespace in the plugin removes the need for theme-side workarounds for this class of input `[ASSUMPTION]` origin: D01 | medium | The theme-side workaround uses the same two filters the plugin would use; no other input class was examined | Themes keep carrying workarounds and D01 does not pay off | After the fix ships, remove the theme-side filters on the affected install and re-run the reported case | Mateusz Zadorożny | unknown | untested |

## Kill criteria

Not applicable as a product decision: this is a bounded correctness fix on an existing product, not an experiment with a stopping point. The one decision that could be reversed on external input is D02, reconsidered on the first WordPress.org review response.

## Hypotheses to test

None. No synthetic panel or persona walkthrough was run in this session.

## Open questions

| Id | Question | Blocking | Who can answer | Status |
|---|---|---|---|---|
| Q01 | Does a non-breaking space in the phone field also break block checkout through the Store API? | — | maintainer | closed 2026-09-22 — yes, established from WooCommerce 10.9.4 source; see Problems and A02 |
| Q02 | Do the four never-browser-verified behaviours from issue #11 actually work? | no — nothing currently depends on the answer, but issue #11 is closed so the work is untracked | maintainer, by testing | open |
| Q03 | Does the plugin's name create an expectation of ownership confirmation in the directory listing, and is that worth addressing in the listing text? | no | maintainer | open |
| Q04 | Has any theme or integration developer outside the maintainer's two installations used the plugin's filters or hit this workaround? | no for Now; yes for any claim about who the plugin's users are | maintainer, or the people who installed it | open |

## Definition of Ready addendum (existing)

Problems have relevant support: issue #23 documents an observed failure with a root cause quoted from WooCommerce core, and the block-checkout half was read directly from WooCommerce source. Target group does not: both installations are operated by the maintainer, so nothing here establishes the situation of an independent store operator (Q04).

The Now scope is ready for implementation planning. Its decisions are confirmed (D01, D03, D04), its exclusions are owned (N01), and the question that previously blocked it (Q01) is closed from source. Two conditions ride with it: the contract corrections required by D04 ship in the same pull request, and the block-checkout gap accepted under D03 gets its own tracker item, or R02 is left quietly unmet.

Not ready for decisions that depend on who the users are — including anything about the WordPress.org directory beyond D02.

## Collection plan

### Has anyone outside the maintainer's own two installations used this plugin, its filters, or hit the workaround?

- What decision or work needs it: any claim about the plugin's users or their situation, and therefore the Target group section and the WordPress.org direction (Q04)
- Who can answer it: whoever installed the plugin from a release ZIP; the maintainer can say whether such installs are known at all
- How: check whether the GitHub releases show download counts, and whether any issue or message came from outside the two stores
- Owner and by when: Mateusz Zadorożny; no date set
- Template: not needed — the result is a short note recorded beside this brief
