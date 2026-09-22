# Interview — plugin maintainer, 2026-09-22

Captured during an `om-discover` session. The participant is the plugin's author and maintainer; this is their own account of the product's situation, not an account from an independent user.

- Recent situation or task the person described: integrating version 1.4.0 with a partner theme on a classic checkout, where a phone number containing a non-breaking space was rejected by WooCommerce core before the plugin's validation ran (recorded in detail in issue #23).
- What they did, step by step: not described in this session beyond what issue #23 already records; the account here adds context around it, not new reproduction steps.
- What worked, what did not, and any consequences they described: a theme-side workaround (two `add_filter()` calls) fixes the case today. Asked what this session should help decide, they answered that the point is "so as not to build an extra workaround in the future".
- Other approaches they tried and how those worked: not described.
- The outcome they were trying to achieve and what happened: not described beyond the above.
- Verbatim quotes worth keeping (mark each as quote):
  - quote: "tak, żeby na przyszłość nie robić obejścia dodatkowego" — on what the session should decide.
  - quote: "dwa sklepy pod moją opieką - docelowo ma być w katalogu wtyczek" — on who runs the plugin today and where it is headed.
  - quote: "na stałe - ma sprawdzać tylko poprawność" — on whether confirming ownership of a number could ever be in scope.
  - quote: "nie, lista jest póki co teoretyczna" — on whether any of the four never-browser-verified items from issue #11 has caused a real problem.
- What they explicitly did not care about: confirming that a phone number belongs to the customer (SMS or one-time code). Stated as permanently out of scope, not deferred.
- Interviewer's own remarks (kept separate from what was said):
  - Both current installations are operated by the participant. This session therefore produced no account from a user who is independent of the maintainer, and nothing here can support a claim about store operators or shoppers in general.
  - "The list is still theoretical" establishes that no failure has been observed. It does not establish that those four paths work; they remain unchecked.
  - The WordPress.org plugin directory was named as the destination. That is a stated intention; this session collected no evidence of demand for the plugin in that directory.
