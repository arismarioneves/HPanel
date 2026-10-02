const SVG_NS = 'http://www.w3.org/2000/svg';

/**
 * Cria elementos sem HTML: strings viram text nodes.
 * props especiais: class, text, dataset {}, style {}, on { evento: fn }.
 */
export function h(tag, props = {}, ...children) {
  const el = document.createElement(tag);
  for (const [key, value] of Object.entries(props || {})) {
    if (value == null || value === false) continue;
    if (key === 'class') el.className = value;
    else if (key === 'text') el.textContent = value;
    else if (key === 'dataset') Object.assign(el.dataset, value);
    else if (key === 'style') Object.assign(el.style, value);
    else if (key === 'on') for (const [ev, fn] of Object.entries(value)) el.addEventListener(ev, fn);
    else el.setAttribute(key, value === true ? '' : String(value));
  }
  for (const child of children.flat(Infinity)) {
    if (child == null || child === false) continue;
    el.append(child instanceof Node ? child : document.createTextNode(String(child)));
  }
  return el;
}

export function icon(name, cls = 'i') {
  const svg = document.createElementNS(SVG_NS, 'svg');
  svg.setAttribute('class', cls);
  svg.setAttribute('aria-hidden', 'true');
  const use = document.createElementNS(SVG_NS, 'use');
  use.setAttribute('href', `assets/icons.svg#${name}`);
  svg.append(use);
  return svg;
}

export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];

export function clear(el) {
  el.replaceChildren();
  return el;
}

export function boot() {
  const el = document.getElementById('boot');
  return el ? JSON.parse(el.textContent || '{}') : {};
}

/** Delegação de cliques por data-action. */
export function on(root, action, fn) {
  root.addEventListener('click', (event) => {
    const target = event.target.closest(`[data-action="${action}"]`);
    if (target && root.contains(target)) fn(event, target);
  });
}
