<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bankovn&#237; importy | TMS</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#172033;font:14px Arial,sans-serif}.page{max-width:1440px;margin:auto;padding:28px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px}.back{color:#334155;font-weight:700;text-decoration:none}h1{margin:18px 0 4px}.lead,.muted{color:#64748b}.notice{margin-top:16px;padding:13px 15px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:10px}.panel{background:#fff;border:1px solid #dbe3ee;border-radius:14px;padding:18px;margin-top:18px}.toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:end}.field{display:grid;gap:6px;min-width:220px;flex:1}label{font-weight:700}input,button{font:inherit;border:1px solid #cbd5e1;border-radius:8px;padding:10px}button{background:#173b72;color:#fff;border:0;font-weight:700;cursor:pointer}button.secondary{background:#e8eef7;color:#173b72}button:disabled{opacity:.55;cursor:not-allowed}.stats{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;margin:16px 0}.stat{padding:14px;border-radius:10px;background:#f8fafc}.stat strong{display:block;font-size:22px;margin-top:4px}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}th{background:#f8fafc}.badge{display:inline-block;padding:4px 8px;border-radius:99px;background:#e2e8f0;font-weight:700}.accepted{background:#dcfce7}.duplicate_candidate,.completed_with_review{background:#fef3c7}.rejected{background:#fee2e2}.completed{background:#dbeafe}.hidden{display:none}.message{margin-top:12px;padding:10px;border-radius:8px;background:#eef2ff}.error{background:#fee2e2;color:#991b1b}.amount,.date,.account,.symbol{white-space:nowrap}.amount{font-weight:700}.status-cell>.badge{white-space:nowrap}.wrap{max-width:320px;white-space:normal;word-break:break-word}.candidates{display:grid;gap:5px}.candidate{padding:7px;background:#fffbeb;border-radius:7px}.candidate-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:7px}.candidate-actions button{padding:6px 8px;font-size:12px}.candidate-actions .dismiss{background:#e8eef7;color:#173b72}.resolution-note{margin-top:5px;color:#475569}.modal{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:20px}.modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58)}.modal-card{position:relative;width:min(520px,100%);background:#fff;border:1px solid #dbe3ee;border-radius:14px;padding:22px;box-shadow:0 24px 70px rgba(15,23,42,.28)}.modal-card h2{margin:0 0 8px}.modal-card textarea{width:100%;min-height:110px;resize:vertical;font:inherit;border:1px solid #cbd5e1;border-radius:8px;padding:10px}.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}.modal-error{margin-top:8px;color:#991b1b;font-weight:700}.modal.hidden{display:none}@media(max-width:800px){.page{padding:16px}.stats{grid-template-columns:1fr 1fr}.table-wrap{overflow:auto}}
</style>
</head>
<body>
<main class="page">
<div class="top"><a class="back" href="/app">&#8592; Zp&#283;t do aplikace</a><button class="secondary" id="reload" type="button">Obnovit</button></div>
<h1>Bankovn&#237; importy</h1>
<p class="lead">Nahr&#225;n&#237; v&#253;pisu &#268;SOB a kontrola importovan&#253;ch bankovn&#237;ch pohyb&#367;.</p>
<div class="notice"><strong>Bezpe&#269;n&#225; hranice:</strong> import pouze zaznamen&#225; bankovn&#237; evidenci. Neprov&#225;d&#237; automatick&#233; p&#225;rov&#225;n&#237;, neozna&#269;uje platby jako uhrazen&#233; a nem&#283;n&#237; faktury.</div>

<section class="panel">
<h2>Nahr&#225;t v&#253;pis &#268;SOB</h2>
<form id="upload" class="toolbar">
<div class="field"><label for="file">CSV soubor z internetov&#233;ho bankovnictv&#237; &#268;SOB</label><input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required></div>
<button id="submit" type="submit">Nahr&#225;t a zpracovat</button>
</form>
<div id="message"></div>
</section>

<section class="panel">
<h2>Importn&#237; d&#225;vky</h2>
<div class="table-wrap"><table><thead><tr><th>Soubor</th><th>Typ</th><th>Stav</th><th>Celkem</th><th>P&#345;ijato</th><th>Duplicity</th><th>Odm&#237;tnuto</th><th></th></tr></thead><tbody id="batches"></tbody></table></div>
</section>

<section class="panel hidden" id="detail">
<div class="top"><div><h2 id="detailTitle">Detail d&#225;vky</h2><div id="detailMeta" class="muted"></div></div><button class="secondary" id="closeDetail" type="button">Zav&#345;&#237;t detail</button></div>
<div class="stats" id="stats"></div>
<div class="table-wrap"><table><thead><tr><th>&#344;&#225;dek</th><th>Datum</th><th>Protistrana</th><th>Proti&#250;&#269;et</th><th>VS</th><th>Zpr&#225;va</th><th>&#268;&#225;stka</th><th>Stav a kontrola</th></tr></thead><tbody id="rows"></tbody></table></div>
</section>
</main>
<div id="duplicateResolutionModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="duplicateResolutionTitle">
    <div class="modal-backdrop" data-resolution-close></div>
    <div class="modal-card">
        <h2 id="duplicateResolutionTitle">Rozhodnutí o kandidátovi duplicity</h2>
        <p id="duplicateResolutionDescription" class="muted"></p>
        <label for="duplicateResolutionReason">Důvod rozhodnutí</label>
        <textarea id="duplicateResolutionReason" maxlength="1000" required placeholder="Uveďte konkrétní důvod rozhodnutí."></textarea>
        <div id="duplicateResolutionError" class="modal-error hidden">Důvod rozhodnutí je povinný.</div>
        <div class="modal-actions">
            <button type="button" class="secondary" data-resolution-close>Zrušit</button>
            <button type="button" id="duplicateResolutionSubmit">Uložit rozhodnutí</button>
        </div>
    </div>
</div>
<script>
const token=sessionStorage.getItem('tms_mvp_token')||'';
const organizationId=sessionStorage.getItem('tms_mvp_organization_id')||'1';
const api=async(path,options={})=>{const headers=new Headers(options.headers||{});headers.set('Accept','application/json');headers.set('X-Organization-ID',organizationId);if(token)headers.set('Authorization',`Bearer ${token}`);const response=await fetch(path,{...options,headers});const body=await response.json().catch(()=>({}));if(!response.ok){const error=Object.values(body.errors||{}).flat().join(' ')||body.message||`HTTP ${response.status}`;throw new Error(error)}return body.data};
const escapeHtml=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
const labels={completed:'Dokon\u010deno',completed_with_review:'Vy\u017eaduje kontrolu',processing:'Zpracov\u00e1v\u00e1 se',accepted:'P\u0159ijato',duplicate_candidate:'Kandid\u00e1t duplicity',rejected:'Odm\u00edtnuto',unresolved:'Nevy\u0159e\u0161eno',confirmed_duplicate:'Potvrzen\u00e1 duplicita',dismissed:'Nen\u00ed duplicita'};
const label=value=>labels[value]||value||'\u2014';
const message=(text,error=false)=>{document.querySelector('#message').innerHTML=text?`<div class="message ${error?'error':''}">${escapeHtml(text)}</div>`:''};
const money=(amount,currency)=>amount===null||amount===undefined?'\u2014':`${Number(amount).toLocaleString('cs-CZ',{minimumFractionDigits:2,maximumFractionDigits:2})} ${currency||''}`;
let currentBatchPublicId='';
const candidateHtml=items=>(items||[]).map(item=>{const resolution=item.resolution||null,effectiveDecision=resolution?.decision||item.decision,actions=resolution?'':`<div class="candidate-actions"><button type="button" onclick="openResolutionModal('${escapeHtml(item.public_id)}','confirmed_duplicate')">Potvrdit duplicitu</button><button type="button" class="dismiss" onclick="openResolutionModal('${escapeHtml(item.public_id)}','dismissed')">Nen\u00ed duplicita</button></div>`,note=resolution?`<div class="resolution-note">${escapeHtml(resolution.reason)}</div>`:'';return `<div class="candidate"><b>${escapeHtml(item.comparison_method)}</b> \u00b7 ${escapeHtml(Math.round(Number(item.confidence||0)*100))} % \u00b7 ${escapeHtml(label(effectiveDecision))}${note}${actions}</div>`}).join('');
let pendingDuplicateResolution=null;
function openResolutionModal(candidateId,decision){
    pendingDuplicateResolution={candidateId,decision};
    const modal=document.querySelector('#duplicateResolutionModal');
    const description=document.querySelector('#duplicateResolutionDescription');
    const reason=document.querySelector('#duplicateResolutionReason');
    const error=document.querySelector('#duplicateResolutionError');
    description.textContent=decision==='confirmed_duplicate'
        ?'Potvrďte, proč jde o stejnou bankovní transakci.'
        :'Uveďte, proč tato bankovní transakce není duplicitní.';
    reason.value='';
    error.textContent='Důvod rozhodnutí je povinný.';
    error.classList.add('hidden');
    modal.classList.remove('hidden');
    setTimeout(()=>reason.focus(),0);
}
function closeResolutionModal(){
    document.querySelector('#duplicateResolutionModal').classList.add('hidden');
    pendingDuplicateResolution=null;
}
async function submitDuplicateResolution(){
    if(!pendingDuplicateResolution)return;
    const reasonElement=document.querySelector('#duplicateResolutionReason');
    const errorElement=document.querySelector('#duplicateResolutionError');
    const submit=document.querySelector('#duplicateResolutionSubmit');
    const reason=reasonElement.value.trim();
    if(!reason){
        errorElement.textContent='Důvod rozhodnutí je povinný.';
        errorElement.classList.remove('hidden');
        reasonElement.focus();
        return;
    }
    errorElement.classList.add('hidden');
    submit.disabled=true;
    try{
        const {candidateId,decision}=pendingDuplicateResolution;
        await api(`/api/v1/bank-statement-import-duplicate-candidates/${candidateId}/resolution`,{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                idempotency_key:crypto.randomUUID(),
                decision,
                reason
            })
        });
        closeResolutionModal();
        message('Rozhodnutí bylo uloženo.');
        await openBatch(currentBatchPublicId);
    }catch(error){
        errorElement.textContent=error.message;
        errorElement.classList.remove('hidden');
    }finally{
        submit.disabled=false;
    }
}
document.querySelectorAll('[data-resolution-close]').forEach(element=>{
    element.addEventListener('click',closeResolutionModal);
});
document.querySelector('#duplicateResolutionSubmit').addEventListener('click',submitDuplicateResolution);
document.querySelector('#duplicateResolutionReason').addEventListener('input',()=>{
    document.querySelector('#duplicateResolutionError').classList.add('hidden');
});
document.addEventListener('keydown',event=>{
    const modal=document.querySelector('#duplicateResolutionModal');
    if(event.key==='Escape'&&!modal.classList.contains('hidden')){
        closeResolutionModal();
    }
});
async function load(){try{message('');const data=await api('/api/v1/bank-statement-imports'),items=data.items||[];document.querySelector('#batches').innerHTML=items.map(batch=>`<tr><td>${escapeHtml(batch.original_filename)}</td><td>${escapeHtml(batch.source_type==='csob_csv'?'\u010cSOB CSV':batch.source_type)}</td><td><span class="badge ${escapeHtml(batch.status)}">${escapeHtml(label(batch.status))}</span></td><td>${escapeHtml(batch.source_row_count||0)}</td><td>${escapeHtml(batch.accepted_row_count||0)}</td><td>${escapeHtml(batch.duplicate_candidate_row_count||0)}</td><td>${escapeHtml(batch.rejected_row_count||0)}</td><td><button type="button" data-batch="${escapeHtml(batch.public_id)}">Otev\u0159\u00edt</button></td></tr>`).join('')||'<tr><td colspan="8">Zat\u00edm nebyl nahr\u00e1n \u017e\u00e1dn\u00fd bankovn\u00ed v\u00fdpis.</td></tr>';document.querySelectorAll('[data-batch]').forEach(button=>button.onclick=()=>openBatch(button.dataset.batch))}catch(error){message(error.message,true)}}
async function openBatch(publicId){try{currentBatchPublicId=publicId;const batch=await api(`/api/v1/bank-statement-imports/${publicId}`),rows=batch.rows||[];document.querySelector('#detail').classList.remove('hidden');document.querySelector('#detailTitle').textContent=`Detail: ${batch.original_filename}`;document.querySelector('#detailMeta').textContent=`${batch.source_type==='csob_csv'?'\u010cSOB CSV':batch.source_type} \u00b7 parser ${batch.parser_version||'\u2014'}`;document.querySelector('#stats').innerHTML=[['Celkem',batch.source_row_count],['P\u0159ijato',batch.accepted_row_count],['Kandid\u00e1ti duplicity',batch.duplicate_candidate_row_count],['Odm\u00edtnuto',batch.rejected_row_count]].map(item=>`<div class="stat">${escapeHtml(item[0])}<strong>${escapeHtml(item[1]||0)}</strong></div>`).join('');document.querySelector('#rows').innerHTML=rows.map(row=>{const normalized=row.normalized_payload||{},validation=(row.validation_messages||[]).map(escapeHtml).join('<br>'),duplicates=candidateHtml(row.duplicate_candidates);return `<tr><td>${escapeHtml(row.source_row)}</td><td class="date">${escapeHtml(normalized.booked_at||'\u2014')}</td><td>${escapeHtml(normalized.counterparty_name||'\u2014')}</td><td class="account">${escapeHtml(normalized.counterparty_account_identifier||'\u2014')}</td><td class="symbol">${escapeHtml(normalized.variable_symbol||'\u2014')}</td><td class="wrap">${escapeHtml(normalized.message||'\u2014')}</td><td class="amount">${escapeHtml(money(normalized.amount,normalized.currency))}</td><td class="status-cell"><span class="badge ${escapeHtml(row.status)}">${escapeHtml(label(row.status))}</span><div class="candidates">${validation||duplicates}</div></td></tr>`}).join('')||'<tr><td colspan="8">D\u00e1vka neobsahuje \u017e\u00e1dn\u00e9 \u0159\u00e1dky.</td></tr>';document.querySelector('#detail').scrollIntoView({behavior:'smooth',block:'start'})}catch(error){message(error.message,true)}}
document.querySelector('#upload').onsubmit=async event=>{event.preventDefault();const file=document.querySelector('#file').files[0];if(!file){message('Vyberte CSV soubor.',true);return}const button=document.querySelector('#submit'),form=new FormData();form.append('idempotency_key',crypto.randomUUID());form.append('adapter','csob_csv');form.append('file',file);try{button.disabled=true;message('V\u00fdpis se zpracov\u00e1v\u00e1...');const batch=await api('/api/v1/bank-statement-imports',{method:'POST',body:form});message('Bankovn\u00ed v\u00fdpis byl bezpe\u010dn\u011b zpracov\u00e1n.');document.querySelector('#upload').reset();await load();await openBatch(batch.public_id)}catch(error){message(error.message,true)}finally{button.disabled=false}};
document.querySelector('#reload').onclick=load;
document.querySelector('#closeDetail').onclick=()=>document.querySelector('#detail').classList.add('hidden');
load();
</script>
</body>
</html>