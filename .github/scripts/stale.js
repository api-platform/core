module.exports = async ({ github, context, core }) => {
  const DAYS_UNTIL_STALE = 60;
  const DAYS_UNTIL_CLOSE = 7;
  const STALE_LABEL      = 'stale';
  const EXEMPT_LABELS    = new Set([
    'Hacktoberfest',
    'RFC',
    '⭐ EU-FOSSA Hackathon',
  ]);
  const EXEMPT_TYPES     = new Set(['Bug', 'Feature']);
  const STALE_COMMENT    = [
    'This issue has been automatically marked as stale because it has not had',
    'recent activity. It will be closed if no further activity occurs. Thank you',
    'for your contributions.',
  ].join(' ');
  const BOT_LOGINS       = new Set(['github-actions[bot]', 'github-actions']);

  const DRY_RUN            = /^(1|true|yes)$/i.test(process.env.DRY_RUN || '');
  const MAX_ACTIONS_PER_RUN = Number.parseInt(process.env.MAX_ACTIONS_PER_RUN || '25', 10);

  const { owner, repo } = context.repo;
  const now             = Date.now();
  const staleCutoff     = new Date(now - DAYS_UNTIL_STALE * 86400000);
  const closeCutoff     = new Date(now - DAYS_UNTIL_CLOSE * 86400000);

  let actionsTaken = 0;
  const budgetExhausted = () => actionsTaken >= MAX_ACTIONS_PER_RUN;

  async function* iterateOpenIssues() {
    let cursor = null;
    while (true) {
      const data = await github.graphql(`
        query($owner: String!, $name: String!, $cursor: String) {
          repository(owner: $owner, name: $name) {
            issues(first: 100, after: $cursor, states: OPEN, orderBy: {field: UPDATED_AT, direction: ASC}) {
              pageInfo { hasNextPage endCursor }
              nodes {
                number
                updatedAt
                issueType { name }
                labels(first: 50) { nodes { name } }
                timelineItems(last: 100, itemTypes: [LABELED_EVENT]) {
                  nodes {
                    ... on LabeledEvent {
                      createdAt
                      label { name }
                    }
                  }
                }
              }
            }
          }
        }`, { owner, name: repo, cursor });

      const page = data.repository.issues;
      for (const node of page.nodes) yield node;
      if (!page.pageInfo.hasNextPage) break;
      cursor = page.pageInfo.endCursor;
    }
  }

  async function hasNonBotActivitySince(issue_number, since) {
    const events = await github.paginate(
      github.rest.issues.listEventsForTimeline,
      { owner, repo, issue_number, per_page: 100 },
    );
    return events.some(e => {
      const ts = e.created_at || e.submitted_at;
      if (!ts) return false;
      if (new Date(ts) <= since) return false;
      const actor = e.actor?.login || e.user?.login;
      if (actor && BOT_LOGINS.has(actor)) return false;
      return true;
    });
  }

  function mostRecentStaleAt(issue) {
    let latest = null;
    for (const e of issue.timelineItems.nodes) {
      if (e?.label?.name !== STALE_LABEL) continue;
      const at = new Date(e.createdAt);
      if (!latest || at > latest) latest = at;
    }
    return latest;
  }

  async function addStale(issue_number) {
    if (DRY_RUN) {
      core.info(`DRY_RUN would stale #${issue_number}`);
      return;
    }
    await github.rest.issues.addLabels({ owner, repo, issue_number, labels: [STALE_LABEL] });
    await github.rest.issues.createComment({ owner, repo, issue_number, body: STALE_COMMENT });
  }

  async function close(issue_number) {
    if (DRY_RUN) {
      core.info(`DRY_RUN would close #${issue_number}`);
      return;
    }
    await github.rest.issues.update({
      owner, repo, issue_number, state: 'closed', state_reason: 'not_planned',
    });
  }

  async function unstale(issue_number) {
    if (DRY_RUN) {
      core.info(`DRY_RUN would unstale #${issue_number}`);
      return;
    }
    await github.rest.issues.removeLabel({
      owner, repo, issue_number, name: STALE_LABEL,
    }).catch(err => {
      if (err.status !== 404) throw err;
    });
  }

  const summary = { staled: 0, closed: 0, unstaled: 0, exempt: 0, scanned: 0, skipped: 0 };

  for await (const issue of iterateOpenIssues()) {
    summary.scanned++;
    const labels   = new Set(issue.labels.nodes.map(l => l.name));
    const typeName = issue.issueType?.name;
    const exempt   = (typeName && EXEMPT_TYPES.has(typeName))
                  || [...labels].some(l => EXEMPT_LABELS.has(l));
    const hasStale = labels.has(STALE_LABEL);

    if (hasStale) {
      const staleAt = mostRecentStaleAt(issue);
      if (!staleAt) continue;

      const interacted = await hasNonBotActivitySince(issue.number, staleAt);
      if (interacted) {
        if (budgetExhausted()) { summary.skipped++; continue; }
        await unstale(issue.number);
        summary.unstaled++;
        actionsTaken++;
      } else if (staleAt <= closeCutoff) {
        if (budgetExhausted()) { summary.skipped++; continue; }
        await close(issue.number);
        summary.closed++;
        actionsTaken++;
      }
      continue;
    }

    if (exempt) {
      summary.exempt++;
      continue;
    }

    if (new Date(issue.updatedAt) <= staleCutoff) {
      if (budgetExhausted()) { summary.skipped++; continue; }
      await addStale(issue.number);
      summary.staled++;
      actionsTaken++;
    }
  }

  const prefix = DRY_RUN ? 'DRY_RUN ' : '';
  core.info(
    `${prefix}scanned=${summary.scanned} staled=${summary.staled} ` +
    `closed=${summary.closed} unstaled=${summary.unstaled} ` +
    `exempt=${summary.exempt} skipped=${summary.skipped} ` +
    `budget=${MAX_ACTIONS_PER_RUN}`,
  );
};
