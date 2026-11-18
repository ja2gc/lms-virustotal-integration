const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const events = {};
const deleted = [];
let unregistered = false;
vm.runInNewContext(fs.readFileSync('public/sw.js', 'utf8'), {
  self: {
    addEventListener: (name, handler) => { events[name] = handler; },
    skipWaiting: async () => {},
    registration: { unregister: async () => { unregistered = true; } },
  },
  caches: { keys: async () => ['doodle-v0.1-beta-static', 'doodle-v0.1-beta-runtime', 'other-app'],
    delete: async name => { deleted.push(name); } },
});
(async () => {
  assert.equal(events.fetch, undefined, 'Private responses must have no offline fallback');
  let finished;
  events.install({ waitUntil: work => { finished = work; } });
  await finished;
  events.activate({ waitUntil: work => { finished = work; } });
  await finished;
  assert.deepEqual(deleted, ['doodle-v0.1-beta-static', 'doodle-v0.1-beta-runtime']);
  assert.equal(unregistered, true);
  console.log('PASS service worker retires only Doodle caches and unregisters');
})().catch(error => { console.error(error); process.exitCode = 1; });
