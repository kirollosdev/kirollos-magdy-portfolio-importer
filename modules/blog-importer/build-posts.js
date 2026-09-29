// Converts source/*.md into Gutenberg block HTML (posts/*.html) plus posts/manifest.json,
// and validates SEO rules. Run: node build-posts.js
const fs = require('fs');
const path = require('path');

const SRC = path.join(__dirname, 'source');
const OUT = path.join(__dirname, 'posts');
const SITE_PAGES = ['/contact/', '/portfolios/', '/about/', '/blog/', '/how-to-build-a-fast-wordpress-website-from-the-ground-up/'];

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
// Yoast-style match: every content word of the keyphrase appears, in any order.
const STOP = new Set(['a', 'an', 'the', 'in', 'for', 'to', 'of', 'and', 'with']);
const hasKw = (text, kw) => {
  const words = new Set(text.toLowerCase().match(/[a-z0-9]+/g) || []);
  return kw.toLowerCase().match(/[a-z0-9]+/g).filter((w) => !STOP.has(w)).every((w) => words.has(w));
};
const inline = (s) =>
  esc(s)
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');

function parse(file) {
  const raw = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
  const m = raw.match(/^---\n([\s\S]*?)\n---\n([\s\S]*)$/);
  if (!m) throw new Error(`${file}: missing front matter`);
  const meta = {};
  for (const line of m[1].split('\n')) {
    const i = line.indexOf(': ');
    if (i > 0) meta[line.slice(0, i).trim()] = line.slice(i + 2).trim();
  }
  meta.tags = (meta.tags || '').split(',').map((t) => t.trim()).filter(Boolean);
  return { meta, body: m[2].trim() };
}

function toBlocks(md) {
  const out = [];
  const lines = md.split('\n');
  let para = [];
  let list = null; // { ordered, items }
  const flushPara = () => {
    if (para.length) {
      out.push(`<!-- wp:paragraph -->\n<p>${inline(para.join(' '))}</p>\n<!-- /wp:paragraph -->`);
      para = [];
    }
  };
  const flushList = () => {
    if (!list) return;
    const tag = list.ordered ? 'ol' : 'ul';
    const attrs = list.ordered ? ' {"ordered":true}' : '';
    const items = list.items
      .map((i) => `<!-- wp:list-item -->\n<li>${inline(i)}</li>\n<!-- /wp:list-item -->`)
      .join('\n\n');
    out.push(`<!-- wp:list${attrs} -->\n<${tag} class="wp-block-list">${items}</${tag}>\n<!-- /wp:list -->`);
    list = null;
  };
  for (const line of lines) {
    let h;
    if ((h = line.match(/^(#{2,4}) (.+)$/))) {
      flushPara(); flushList();
      const level = h[1].length;
      const attrs = level === 2 ? '' : ` {"level":${level}}`;
      out.push(`<!-- wp:heading${attrs} -->\n<h${level} class="wp-block-heading">${inline(h[2])}</h${level}>\n<!-- /wp:heading -->`);
    } else if ((h = line.match(/^- (.+)$/)) || (h = line.match(/^\d+\. (.+)$/))) {
      flushPara();
      const ordered = /^\d+\./.test(line);
      if (list && list.ordered !== ordered) flushList();
      if (!list) list = { ordered, items: [] };
      list.items.push(h[1]);
    } else if (line.trim() === '') {
      flushPara(); flushList();
    } else {
      flushList();
      para.push(line.trim());
    }
  }
  flushPara(); flushList();
  return out.join('\n\n');
}

const files = fs.readdirSync(SRC).filter((f) => f.endsWith('.md')).sort();
const posts = files.map((f) => ({ file: f, ...parse(path.join(SRC, f)) }));
const slugs = new Set(posts.map((p) => `/${p.meta.slug}/`));
const errors = [];
const warnings = [];
const seenKw = new Map();

fs.mkdirSync(OUT, { recursive: true });
for (const f of fs.readdirSync(OUT)) if (f.endsWith('.html') || f.endsWith('.json')) fs.unlinkSync(path.join(OUT, f));

const manifest = posts.map((p, idx) => {
  const { meta, body, file } = p;
  for (const k of ['title', 'slug', 'focus_keyword', 'seo_title', 'meta_description', 'excerpt', 'category', 'cover_title']) {
    if (!meta[k]) errors.push(`${file}: missing ${k}`);
  }
  const all = JSON.stringify(meta) + body;
  if (/[–—]/.test(all)) errors.push(`${file}: contains an em or en dash`);
  if (meta.seo_title.length > 60) errors.push(`${file}: seo_title ${meta.seo_title.length} chars (>60)`);
  const md = meta.meta_description.length;
  if (md > 156 || md < 120) errors.push(`${file}: meta_description ${md} chars (want 120-156)`);
  const kw = meta.focus_keyword.toLowerCase();
  if (seenKw.has(kw)) errors.push(`${file}: focus keyword duplicates ${seenKw.get(kw)}`);
  seenKw.set(kw, file);
  const firstPara = body.split('\n\n')[0].toLowerCase();
  if (!hasKw(firstPara, kw)) errors.push(`${file}: focus keyword not in first paragraph`);
  if (!hasKw(meta.seo_title, kw)) warnings.push(`${file}: focus keyword not in SEO title`);
  if (!hasKw(meta.meta_description, kw)) warnings.push(`${file}: focus keyword not in meta description`);
  const links = [...body.matchAll(/\]\((\/[^)]*)\)/g)].map((m) => m[1]);
  for (const l of links) if (!slugs.has(l) && !SITE_PAGES.includes(l)) errors.push(`${file}: unknown internal link ${l}`);
  if (!links.some((l) => slugs.has(l) || l.startsWith('/how-to-build'))) warnings.push(`${file}: no internal link to another post`);
  const words = body.replace(/[#*\[\]()\-]/g, ' ').split(/\s+/).filter(Boolean).length;

  const html = toBlocks(body);
  const out = `${String(idx + 1).padStart(2, '0')}-${meta.slug}.html`;
  fs.writeFileSync(path.join(OUT, out), html + '\n');
  console.log(`${out}  ${words} words`);
  return {
    id: meta.slug,
    file: out,
    title: meta.title,
    slug: meta.slug,
    excerpt: meta.excerpt,
    category: meta.category,
    cover_title: meta.cover_title,
    cover: `covers/${meta.slug}.jpg`,
    cover_alt: meta.title,
    tags: meta.tags,
    focus_keyword: meta.focus_keyword,
    seo_title: meta.seo_title,
    meta_description: meta.meta_description,
  };
});

fs.writeFileSync(path.join(OUT, 'manifest.json'), JSON.stringify(manifest, null, 2) + '\n');
warnings.forEach((w) => console.log('WARN  ' + w));
if (errors.length) {
  errors.forEach((e) => console.error('ERROR ' + e));
  process.exit(1);
}
console.log(`OK: ${manifest.length} posts built`);
