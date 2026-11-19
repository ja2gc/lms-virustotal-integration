const assert = require('node:assert/strict');
const {display, evidenceLabel} = require('../public/js/scan-status.js');
assert.equal(display({processingState:'queued',advisoryStatus:'Flagged'}), 'Queued');
assert.equal(display({processingState:'not_applicable',advisoryStatus:null}), 'Not applicable');
assert.equal(display({processingState:'completed',advisoryStatus:'No Detections Reported'}), 'No Detections Reported');
assert.equal(display({processingState:'alien',advisoryStatus:'No Detections Reported'}), 'Unresolved');
assert.equal(display({processingState:'completed',advisoryStatus:'Safe'}), 'Unresolved');
assert.equal(display({processingState:'failed',advisoryStatus:'No Detections Reported'}), 'Unresolved');
assert.match(evidenceLabel({evidenceMode:'fixture'}),/Offline fixture/);
assert.equal(evidenceLabel({}), '');
console.log('PASS scan labels keep processing and advisories separate; unknown evidence remains Unresolved');

const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require.resolve('../public/js/scan-status.js'), 'utf8');
const flush = async () => { for (let i=0;i<10;i++) await Promise.resolve(); };
function setup(responses, teacher=false) {
  const timers = new Map(), events = {}, submissions = [];
  let timerId=0;
  const element = () => ({textContent:'',hidden:false,disabled:false,style:{},append(){},replaceChildren(){},removeAttribute(name){delete this[name];}});
  const nodes = Object.fromEntries(['label','reason','time','error','download','history-list','evidence'].map(name=>[`[data-scan-${name}]`,element()]));
  const token = {value:''}, button = element();
  const form = teacher ? {action:'/rescan',querySelector:selector=>selector==='button'?button:token,addEventListener:(_,callback)=>{events.submit=callback;}} : null;
  const panel = {dataset:{scanStatus:'/status',scanFile:'1',scanDownloadBase:'/download/'},querySelector:selector=>selector==='[data-scan-rescan]'?form:nodes[selector]};
  const document = {hidden:false,querySelectorAll:()=>[panel],addEventListener:(name,callback)=>{events[name]=callback;},createElement:element,createTextNode:value=>value};
  const fetch = async (url, options) => {
    if (options.method==='POST') submissions.push(options.body.get('requestToken'));
    const next=responses.shift();
    if (next instanceof Error) throw next;
    if (!next) throw new Error('Unexpected request '+url);
    return {ok:true,status:options.method==='POST'?202:200,json:async()=>next};
  };
  class FormData { constructor(){this.values={requestToken:token.value};} set(k,v){this.values[k]=v;} get(k){return this.values[k];} }
  vm.runInNewContext(source,{document,fetch,FormData,AbortController,module:{exports:{}},setTimeout:(callback,delay)=>{timers.set(++timerId,{callback,delay});return timerId;},clearTimeout:id=>timers.delete(id)});
  return {document,events,nodes,timers,token,button,submissions};
}
const status = (state, fileId=1, extra={}) => ({contractVersion:1,processingState:state,advisoryStatus:null,fileId,reason:'scan_queued',checkedAt:null,...extra});
(async()=>{
  const page=setup([status('waiting'),status('completed',2,{advisoryStatus:'No Detections Reported',evidenceMode:'fixture'})]);
  await flush();
  assert.equal([...page.timers.values()][0].delay,15000);
  page.document.hidden=true; page.events.visibilitychange();
  assert.equal(page.timers.size,0,'Hidden page cancels polling');
  page.document.hidden=false; page.events.visibilitychange(); await flush();
  assert.equal(page.nodes['[data-scan-label]'].textContent,'No Detections Reported');
  assert.match(page.nodes['[data-scan-evidence]'].textContent,/Offline fixture/);
  assert.equal(page.nodes['[data-scan-download]'].href,'/download/2','Replacement updates private file identity');
  assert.equal(page.timers.size,0,'Terminal results stop polling');
  const failed=setup([new Error('offline'),status('queued')]); await flush();
  assert.equal([...failed.timers.values()][0].delay,30000,'Failed initial refresh backs off');
  assert.match(failed.nodes['[data-scan-error]'].textContent,/Last displayed advisory remains visible/);
  const teacher=setup([status('completed',1,{rescanToken:'token-a'}),new Error('lost POST response'),{contractVersion:1,attemptId:7,processingState:'queued',created:false},status('queued',1,{rescanToken:'token-b'})],true);
  await flush();
  await teacher.events.submit({preventDefault(){}}); await flush();
  assert.equal(teacher.token.value,'token-a','Uncertain request preserves idempotency token');
  await teacher.events.submit({preventDefault(){}}); await flush();
  assert.deepEqual(teacher.submissions,['token-a','token-a'],'Retry reuses original token');
  assert.equal(teacher.token.value,'token-b','Confirmed request refreshes next server token');
  console.log('PASS scan polling pauses hidden tabs, backs off failures, stops terminal, tracks file identity and replays uncertain rescans');
})().catch(error=>{console.error(error);process.exitCode=1;});
