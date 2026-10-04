/** Agrupa subdomínios sob o domínio pai (sufixo mais longo). Principal primeiro, depois alfabético; órfãos no fim. */
export function groupSites(list) {
  const parents = list.filter((s) => s.vhostType !== 'subdomain')
    .sort((a, b) => (a.vhostType === 'main' ? -1 : b.vhostType === 'main' ? 1 : a.domain.localeCompare(b.domain)));
  const children = new Map(parents.map((p) => [p.domain, []]));
  const orphans = [];

  for (const sub of list.filter((s) => s.vhostType === 'subdomain')) {
    let best = null;
    for (const p of parents) {
      if (sub.domain.endsWith(`.${p.domain}`) && (!best || p.domain.length > best.length)) best = p.domain;
    }
    if (best) children.get(best).push(sub);
    else orphans.push(sub);
  }

  return [
    ...parents.map((site) => ({ site, children: children.get(site.domain).sort((a, b) => a.domain.localeCompare(b.domain)) })),
    ...orphans.sort((a, b) => a.domain.localeCompare(b.domain)).map((site) => ({ site, children: [] })),
  ];
}
