# Verify Phone Number Shift64 — product brief

- Date: 2026-09-22
- Updated: 2026-10-06 — WordPress.org readiness audit (issue #31): D05 supersedes D02, D06 added, A01 refuted, Q03 closed, issue #32 and the folder matching of directory updates recorded under Problems. The block-checkout half of D03 already shipped in 1.4.2 (#24, `WhitespaceFilter` on `rest_pre_dispatch`); D03 and R02 have reached their review-by condition and are flagged for the owner.
- Mode: existing; Owner: Mateusz Zadorożny (maintainer); Pass: full
- Evidence basis: one tracker issue with a root cause quoted from WooCommerce core (#23), the WooCommerce 10.9.4 source installed on this machine, this repository, and one account from the maintainer recorded this session. Both current installations are operated by the maintainer, so no independent store operator or shopper contributed. There is no usage data and no support history. The belief most consequential to the current decision — that the problem was confined to classic checkout — was refuted from source during this session (A02). A01, which D02 had accepted untested, was refuted on 2026-10-06 from WordPress.org's own guideline text and a Plugin Check run on the release package. That update adds the directory's published rules, the Plugin Check run, a live check of the directory's update API and two tracker issues (#31, #32); none of them says anything about users.
- Coverage: 34 claims — 30 sourced (interview 9, data 0, document 14, product 7, benchmark 0), 0 synthetic, 4 assumed; 1 entry on the collection plan
- Synthetic hypotheses outside Coverage: 0
- Definition of Ready signed by: not yet signed; Problems have source support, Target group does not.
- Ready for: implementation planning of the Now scope. The condition that previously held this open (Q01) was answered from source during this session and is closed. The WordPress.org readiness work (issue #31) rests on D05 and D06, which follow from the directory's rules, not from evidence about users.
- Sources: `.ai/specs/research/interviews/2026-09-22-maintainer.md`, `.ai/specs/research/decisions/{D01,D02,D03,D04,D05,D06,N01}.md`, GitHub issues #23, #11, #25, #31, #32, `README.md`, `readme.txt`, `src/`, `BACKWARD_COMPATIBILITY.md`, WooCommerce 10.9.4 core, WordPress.org Detailed Plugin Guidelines (https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/, guideline 8, read 2026-10-06), a Plugin Check 2.1.0 run on the 1.4.2 release package (WordPress 7.1.2, WooCommerce 11.1.2, 2026-10-06; output not committed), and requests to WordPress.org's update API (https://api.wordpress.org/plugins/update-check/1.1/, 2026-10-06; recorded in D05)

## Decision summary

Agreed: input cleanup that would otherwise be written into a theme belongs in the plugin (D01).

The situation supporting it is recorded in issue #23. On classic checkout, a phone number containing a non-breaking space is rejected by `WC_Validation::is_phone()` inside `WC_Checkout::validate_posted_data()` — before `woocommerce_after_checkout_validation`, where this plugin validates. The shopper sees WooCommerce's own message, the plugin's normalizer never runs, and the order is blocked. The maintainer found this while integrating version 1.4.0 with a partner theme; a theme-side workaround fixes it there today.

This session established from WooCommerce source that block checkout has the same defect on a different path, and that the fix proposed in issue #23 cannot reach it (see Problems, A02). The maintainer chose to ship the classic fix first and treat block checkout separately (D03), knowingly leaving rule R02 unmet for this input class until the second change lands. The cleanup will also collapse whitespace on fields the plugin skips, which the maintainer accepted on condition that the contract wording is corrected in the same pull request (D04). Confirming that a number belongs to the customer stays permanently out of scope (N01).

What could most change this: nothing about the defect itself — it is read from source. The open risk is organisational: the R02 gap has no tracker item yet, so the second half can be forgotten (Q01-followup).

Next action: file the block-checkout issue so D03's deliberate gap is tracked. The maintainer owns it. No date set.

Update 2026-10-06, WordPress.org readiness (issue #31). The GitHub self-updater is removed before submission (D05, superseding D02): the directory's guideline 8 forbids serving updates from anywhere but WordPress.org and Plugin Check reports the updater as an error, so A01 is refuted now instead of at the first review. The name stays and the listing text says the plugin validates numbers and confirms nothing (D06); that closes Q03 through the listing, not through evidence about users. Both are agent recommendations the maintainer accepted. The same audit found that block-checkout messages fall back to English on WordPress 6.7+ (issue #32, see Problems); it is tracked separately and not part of the readiness change. Also recorded on 2026-10-06: the Next action above is overtaken, because the block-checkout half of D03 shipped in 1.4.2 together with the classic-checkout fix (#24). `src/Checkout/WhitespaceFilter.php` cleans `/wc/store/` requests on `rest_pre_dispatch`, and `tests/e2e/block-checkout-unicode-whitespace-phone.spec.ts` covers the block case. D03 and R02 have therefore reached their review-by condition ("when the block-checkout fix lands") and are flagged here for the owner; changing their status is the owner's call, so their rows are unchanged.

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
- Three behaviours have never been checked in a browser: block checkout, Polylang/WPML message language, and the HPOS screen. Issue #11 listed a fourth, one-click update from a non-default directory, which ceased to exist when D05 removed the updater. Issue #11 is closed, so this work currently has no open tracker item. `[DOCUMENT]` issue #11
- None of them had caused an observed problem on the two live installations when the maintainer was asked on 2026-09-22. This means no failure was seen; it does not mean the paths work. `[INTERVIEW]` `research/interviews/2026-09-22-maintainer.md`
- Found in the WordPress.org readiness audit, 2026-10-06: Detailed Plugin Guideline 8 lists "Serving updates or otherwise installing plugins, themes, or add-ons from servers other than WordPress.org's" among what a directory plugin may not do. The bundled `src/Admin/GitHubUpdater.php` did exactly that, so the plugin could not be listed with it. Acted on by D05. `[DOCUMENT]` https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- Found in the same audit: Plugin Check 2.1.0 on the 1.4.2 release package (WordPress 7.1.2, WooCommerce 11.1.2) reported 7 errors: the updater (`plugin_updater_detected`), development files in the package (`hidden_files`, `application_detected`), no `License` header, an outdated `Tested up to`, a PHP file without a direct-access guard, and `str_starts_with()`, which Plugin Check counts as newer than `Requires at least: 5.0`. The errors stand between the plugin and a directory listing; issue #31 tracks them. `[PRODUCT]` Plugin Check 2.1.0 run, 2026-10-06 (output not committed)
- Found in the same audit: on WordPress 6.7 and later, block-checkout error messages fall back to English on non-English sites, including when Polylang or WPML switches the language, because `BlockCheckoutValidator::reload_textdomain()` unloads the text domain in a way WordPress does not reload within the request. Observed with `wp eval-file` on WordPress 7.1.2 and WooCommerce 11.1.2 with the locale filtered to `pl_PL`, not in a browser checkout. It is the Polylang/WPML behaviour above, now known to fail; the readiness change does not fix it. `[DOCUMENT]` issue #32
- Found in the same audit: WordPress.org offers directory updates only to an install whose folder matches the plugin's slug. On 2026-10-06 its update API, asked with a `WordPress/7.1.2` User-Agent, offered an update to `query-monitor/query-monitor.php` but not to the same plugin in `query-monitor-master/`, `query-monitor-main/` or an arbitrary folder; wp-crontrol, user-switching, akismet, woocommerce and wordpress-seo behaved the same in `-master` folders, and the one exception, `classic-editor-master`, looked like a server-side alias. This plugin is not listed yet, so it was not checked itself. A copy in any folder other than `verify-phone-number-shift64`, for example `verify-phone-number-shift64-master`, gets no directory updates once D05 removes the updater and has to be reinstalled from the directory. `[PRODUCT]` WordPress.org update-check API 1.1, 2026-10-06, `research/decisions/D05.md`

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
- **Later:** the three remaining unverified behaviours from the closed issue #11, which also need a new issue; the block-checkout message language defect in issue #32; the sponsor link in issue #25. `[DOCUMENT]` issues #11, #25, #32
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
- Future state: the same paste is cleaned to single ASCII spaces as WooCommerce assembles posted data, so the number reaches the plugin's normalizer and is judged on whether it is a real number. The same paste on block checkout was to stay blocked until the second half of D03 landed; that half shipped in 1.4.2 together with the classic fix (#24, see the 2026-10-06 update in the Decision summary). `[DOCUMENT]` `research/decisions/D03.md`

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
| D02 | 2026-09-22 | Keep the GitHub self-updater as it is and find out at WordPress.org review | Alternatives offered were removing it at submission or disabling it for directory installs; chosen without an agent recommendation, because the current guideline was not established | Mateusz Zadorożny | superseded by D05 (2026-10-06) | first review response | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D02.md` | none |
| D03 | 2026-09-22 | Fix classic checkout first; block checkout ships as a separate change | Alternatives were covering both paths at once, or looking for a shared entry point first; chosen for a smaller first change, accepting that R02 is unmet for this input class in the meantime | Mateusz Zadorożny | active | when the block-checkout fix lands | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D03.md` | none |
| D04 | 2026-09-22 | A cleaned phone value may be stored even on fields the plugin skips | The alternative was restoring the original characters for storage when the plugin skips the field; chosen for the simpler implementation, on condition that the `should_validate` docblock and `BACKWARD_COMPATIBILITY.md` §3 are corrected in the same pull request | Mateusz Zadorożny | active | if a site reports depending on the exact stored characters of a skipped field | owner-approved superseding row | `[INTERVIEW]` `research/decisions/D04.md` | none |
| D05 | 2026-10-06 | Remove the GitHub self-updater before submitting to WordPress.org; updates come only from the directory | Guideline 8 forbids serving updates from servers other than WordPress.org's, and Plugin Check reports the updater as an error, so A01 is refuted. The alternatives considered were the other two options D02 had weighed: keeping it and learning at review, which would now lose a review round knowingly, and disabling it for directory installs, which still ships the code the guideline and Plugin Check reject. The agent recommended removal after reading the guideline; the maintainer accepted that recommendation | Mateusz Zadorożny | active | if the plugin leaves or is closed in the directory, or if guideline 8 changes | owner-approved superseding row | `[DOCUMENT]` `research/decisions/D05.md`, issue #31 | D02 |
| D06 | 2026-10-06 | Keep the name "Verify Phone Number Shift64"; the directory listing states that the plugin validates numbers, sends no SMS or one-time codes, and does not confirm that a number belongs to the customer | The slug WordPress.org derives from the name must stay `verify-phone-number-shift64`, the text domain `BACKWARD_COMPATIBILITY.md` §6 protects, so the naming ambiguity N01 records is addressed in the listing text instead. The alternative considered was a different display name with a slug request at review, at the risk that the granted slug differs from the text domain. Whether the name creates the expectation (Q03) was not measured. The agent recommended keeping the name; the maintainer accepted that recommendation | Mateusz Zadorożny | active | if reviews or support requests show users expecting SMS or code confirmation | owner-approved superseding row | `[DOCUMENT]` `research/decisions/D06.md`, issue #31 | none |

## Riskiest assumptions

| Id | Assumption | Importance | Evidence today | If false | Smallest test | Owner | By when | Result |
|---|---|---|---|---|---|---|---|---|
| A01 | WordPress.org will accept the plugin with its bundled GitHub self-updater `[ASSUMPTION]` origin: D02 | high | Refuted 2026-10-06 in the readiness audit (issue #31): guideline 8 forbids "Serving updates or otherwise installing plugins, themes, or add-ons from servers other than WordPress.org's", and Plugin Check 2.1.0 reports `src/Admin/GitHubUpdater.php` as `plugin_updater_detected` (ERROR) — see Problems. Until then no guideline text had been checked | Already acted on: D05 removes the updater before submission | none needed; reading the guideline settled it (D02 had declined that check in favour of learning at review) | Mateusz Zadorożny | 2026-10-06 | refuted |
| A02 | The non-breaking-space problem is confined to classic checkout `[ASSUMPTION]` origin: issue #23, which stated block checkout was not tested | high | Refuted from WooCommerce 10.9.4 source during this session — see the second entry under Problems. Block checkout is affected on a different path, and the fix proposed in issue #23 cannot reach it | Already acted on: the scope was re-decided as D03 | none needed; the source read settled it | Mateusz Zadorożny | 2026-09-22 | refuted |
| A03 | Cleaning whitespace in the plugin removes the need for theme-side workarounds for this class of input `[ASSUMPTION]` origin: D01 | medium | The theme-side workaround uses the same two filters the plugin would use; no other input class was examined | Themes keep carrying workarounds and D01 does not pay off | After the fix ships, remove the theme-side filters on the affected install and re-run the reported case | Mateusz Zadorożny | unknown | untested |

## Kill criteria

Not applicable as a product decision: this is a bounded correctness fix on an existing product, not an experiment with a stopping point. D02, which was to be reconsidered on the first WordPress.org review response, was superseded by D05 before submission. The decisions that could now be reversed on external input are D05, if guideline 8 changes or the plugin leaves the directory, and D06, if reviews or support requests show users expecting SMS or code confirmation.

## Hypotheses to test

None. No synthetic panel or persona walkthrough was run in this session.

## Open questions

| Id | Question | Blocking | Who can answer | Status |
|---|---|---|---|---|
| Q01 | Does a non-breaking space in the phone field also break block checkout through the Store API? | — | maintainer | closed 2026-09-22 — yes, established from WooCommerce 10.9.4 source; see Problems and A02 |
| Q02 | Do the three remaining never-browser-verified behaviours from issue #11 actually work? (The fourth, one-click update from a non-default directory, ceased to exist under D05; issue #32 shows the message language failing on WordPress 6.7+.) | no — nothing currently depends on the answer, but issue #11 is closed so the work is untracked | maintainer, by testing | open |
| Q03 | Does the plugin's name create an expectation of ownership confirmation in the directory listing, and is that worth addressing in the listing text? | no | maintainer | closed 2026-10-06 by D06 — addressed in the listing text, which states that the plugin validates numbers and does not send SMS or one-time codes or confirm ownership; whether the name creates the expectation was not measured |
| Q04 | Has any theme or integration developer outside the maintainer's two installations used the plugin's filters or hit this workaround? | no for Now; yes for any claim about who the plugin's users are | maintainer, or the people who installed it | open |

## Definition of Ready addendum (existing)

Problems have relevant support: issue #23 documents an observed failure with a root cause quoted from WooCommerce core, and the block-checkout half was read directly from WooCommerce source. Target group does not: both installations are operated by the maintainer, so nothing here establishes the situation of an independent store operator (Q04).

The Now scope is ready for implementation planning. Its decisions are confirmed (D01, D03, D04), its exclusions are owned (N01), and the question that previously blocked it (Q01) is closed from source. Two conditions ride with it: the contract corrections required by D04 ship in the same pull request, and the block-checkout gap accepted under D03 gets its own tracker item, or R02 is left quietly unmet.

Not ready for decisions that depend on who the users are — including anything about the WordPress.org directory beyond D05 and D06, which follow from the directory's rules rather than from evidence about users.

## Collection plan

### Has anyone outside the maintainer's own two installations used this plugin, its filters, or hit the workaround?

- What decision or work needs it: any claim about the plugin's users or their situation, and therefore the Target group section and the WordPress.org direction (Q04)
- Who can answer it: whoever installed the plugin from a release ZIP; the maintainer can say whether such installs are known at all
- How: check whether the GitHub releases show download counts, and whether any issue or message came from outside the two stores
- Owner and by when: Mateusz Zadorożny; no date set
- Template: not needed — the result is a short note recorded beside this brief
