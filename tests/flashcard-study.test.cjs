// Optional developer check: node tests/flashcard-study.test.cjs (no packages).
const { readFileSync } = require('node:fs');
const { runInNewContext } = require('node:vm');
const assert = require('node:assert/strict');
const template = readFileSync(`${__dirname}/../app/views/flashcards/study.php`, 'utf8');
const script = template.split('<script>')[1].split('</script>')[0]
  .replace(/<\?=[\s\S]*?\?>/g, expression => expression.includes('json_encode')
    ? JSON.stringify([{ front: 'Question', back: 'Answer', review_id: 1 }]) : 'fixture');

function session(fetch) {
  const nodes = {};
  const document = {
    getElementById(id) {
      return nodes[id] ??= { textContent: id === 'progress' ? '0' : '', style: {},
        classList: { remove() {}, toggle() {}, contains() { return true; } } };
    },
    querySelectorAll() { return []; },
  };
  const context = { document, fetch, console };
  runInNewContext(script, context);
  return { context, nodes };
}
const flush = () => new Promise(resolve => setImmediate(resolve));

(async () => {
  for (const response of [
    { ok: false, json: async () => ({ ok: false }) },
    { ok: true, json: async () => ({ ok: false }) },
    { ok: true, json: async () => { throw new Error('Malformed response'); } },
  ]) {
    const { context, nodes } = session(async () => response);
    context.rateCard(3);
    await flush();
    assert.equal(String(nodes.progress?.textContent ?? '0'), '0', 'Failed review must not advance progress');
    assert.ok(nodes.reviewError?.textContent, 'Failed review must show a retry message');
  }
  const failed = session(async () => { throw new Error('Disconnected'); });
  failed.context.rateCard(3); await flush();
  assert.equal(String(failed.nodes.progress?.textContent ?? '0'), '0');
  assert.ok(failed.nodes.reviewError?.textContent);

  let calls = 0, finish;
  const { context, nodes } = session(() => { calls++; return new Promise(resolve => { finish = resolve; }); });
  context.rateCard(3); context.rateCard(3);
  assert.equal(calls, 1, 'Repeated clicks must send one pending review');
  finish({ ok: true, json: async () => ({ ok: true }) });
  await flush();
  assert.equal(String(nodes.progress.textContent), '1');
  assert.equal(nodes.sessionComplete.style.display, 'block');
  console.log('PASS flashcard review failures preserve progress; repeat clicks are blocked; success completes session');
})().catch(error => { console.error(error.message); process.exitCode = 1; });
