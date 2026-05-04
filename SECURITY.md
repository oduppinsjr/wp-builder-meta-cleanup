# Security policy

## Supported versions

Security fixes are applied to the **latest release line** in this repository (currently **2.x**). Older major lines may not receive backports unless explicitly stated in a security advisory.

| Version | Supported for security updates |
| ------- | ------------------------------ |
| 2.x (latest) | Yes |
| Below 2.0 | No |

## Reporting a vulnerability

Please **do not** open a public GitHub issue for undisclosed security vulnerabilities. Public issues can put sites at risk before a fix exists.

**Preferred:** Use [GitHub private vulnerability reporting](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing-information-about-vulnerabilities/privately-reporting-a-security-vulnerability) for this repository (available when enabled by the repository owner).

If private reporting is not available, contact the maintainer through a **private channel** only (for example a security contact published on [duppinstech.com](https://duppinstech.com), if listed). Do not include exploit details in public forums.

### What to include

- Affected versions or commit range, if known.
- Steps to reproduce, or a minimal proof of concept.
- Impact assessment (confidentiality / integrity / availability) if you can.
- Whether you would like attribution in release notes.

### What to expect

- An acknowledgment that the report was received (goal: within several business days; no guarantee).
- Coordinated disclosure: we aim to prepare a fix and release before publishing technical details, depending on severity and exploitability.

## Scope

This policy applies to this plugin’s **distributed code** in this repository. Third-party themes, other plugins, or hosting configuration issues are outside maintainers’ control but may be discussed responsibly in issues after sensitive details are handled.

## Safe use reminder

This plugin **deletes database rows** when operators choose cleanup actions. Always take a **database backup** before use, and only run cleanup when the relevant builder or addon is **inactive**, as documented in the plugin UI and readme.
