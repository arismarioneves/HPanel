import { test } from 'node:test';
import assert from 'node:assert/strict';
import { groupSites } from '../../assets/js/sites.js';

test('subdomínio fica sob o pai de sufixo mais longo; principal primeiro; órfão no fim', () => {
  const groups = groupSites([
    { domain: 'zeta.com', vhostType: 'addon' },
    { domain: 'api.loja.com', vhostType: 'subdomain' },
    { domain: 'loja.com', vhostType: 'main' },
    { domain: 'v2.api.loja.com', vhostType: 'subdomain' },
    { domain: 'api.loja.com.br', vhostType: 'addon' },
    { domain: 'x.sozinho.net', vhostType: 'subdomain' },
  ]);
  assert.deepEqual(groups.map((g) => [g.site.domain, g.children.map((c) => c.domain)]), [
    ['loja.com', ['api.loja.com', 'v2.api.loja.com']],
    ['api.loja.com.br', []],
    ['zeta.com', []],
    ['x.sozinho.net', []],
  ]);
});

test('lista vazia', () => {
  assert.deepEqual(groupSites([]), []);
});
