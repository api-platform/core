| Q             | A
| ------------- | ---
| Branch?       | 5.0 for bug fixes / main for features <!-- see below -->
| Tickets       | Closes #..., closes #... <!-- link related issues here -->
| License       | MIT
| Doc PR        | api-platform/docs#... <!-- required for new features -->
<!--
Replace this notice with two things:

1. One or two sentences that say what is broken, or what the feature does.
2. The functional test that proves it. Paste the test here, or point to the test file
   in this pull request.

The test is the part we read first. A test we can run tells us more than any prose, so
keep the prose short. Do not paste the output of an AI assistant in place of the test.
A reviewer cannot run it, and it hides what the patch changes.

Branch:
 - 5.0 is stable. Bug fixes go here.
 - main is development. New features, deprecations and legacy code removals go here.
 - 4.4 is old-stable. It takes security fixes only.

Maintainers merge 5.0 up into main, so open the fix once, against 5.0.
See https://api-platform.com/docs/extra/releases/ for the maintenance policy.

For security issues please email contact@les-tilleuls.coop.

Additionally:
 - Always add tests and make sure that they pass.
 - Never break backward compatibility (see https://symfony.com/bc).
 - Update the CHANGELOG.md file.
 - Follow the [Conventional Commits specification](https://www.conventionalcommits.org/).
-->
