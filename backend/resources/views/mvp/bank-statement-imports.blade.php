<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bankovn&#237; importy | TMS</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;color:#172033;font:14px Arial,sans-serif}.page{max-width:1440px;margin:auto;padding:28px}.top{display:flex;justify-content:space-between;align-items:center;gap:12px}.back{color:#334155;font-weight:700;text-decoration:none}h1{margin:18px 0 4px}.lead,.muted{color:#64748b}.notice{margin-top:16px;padding:13px 15px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:10px}.panel{background:#fff;border:1px solid #dbe3ee;border-radius:14px;padding:18px;margin-top:18px}.toolbar{display:flex;gap:12px;flex-wrap:wrap;align-items:end}.field{display:grid;gap:6px;min-width:220px;flex:1}label{font-weight:700}input,button{font:inherit;border:1px solid #cbd5e1;border-radius:8px;padding:10px}button{background:#173b72;color:#fff;border:0;font-weight:700;cursor:pointer}button.secondary{background:#e8eef7;color:#173b72}button:disabled{opacity:.55;cursor:not-allowed}.stats{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:10px;margin:16px 0}.stat{padding:14px;border-radius:10px;background:#f8fafc}.stat strong{display:block;font-size:22px;margin-top:4px}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}th{background:#f8fafc}.badge{display:inline-block;padding:4px 8px;border-radius:99px;background:#e2e8f0;font-weight:700}.accepted{background:#dcfce7}.duplicate_candidate,.completed_with_review{background:#fef3c7}.rejected{background:#fee2e2}.completed{background:#dbeafe}.hidden{display:none}.message{margin-top:12px;padding:10px;border-radius:8px;background:#eef2ff}.error{background:#fee2e2;color:#991b1b}.amount,.date,.account,.symbol{white-space:nowrap}.amount{font-weight:700}.status-cell>.badge{white-space:nowrap}.wrap{max-width:320px;white-space:normal;word-break:break-word}.candidates{display:grid;gap:5px}.candidate{padding:7px;background:#fffbeb;border-radius:7px}.candidate-actions{display:flex;gap:6px;flex-wrap:wrap;margin-top:7px}.candidate-actions button{padding:6px 8px;font-size:12px}.candidate-actions .dismiss{background:#e8eef7;color:#173b72}.resolution-note{margin-top:5px;color:#475569}.modal{position:fixed;inset:0;z-index:1000;display:grid;place-items:center;padding:20px}.modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58)}.modal-card{position:relative;width:min(520px,100%);background:#fff;border:1px solid #dbe3ee;border-radius:14px;padding:22px;box-shadow:0 24px 70px rgba(15,23,42,.28)}.modal-card h2{margin:0 0 8px}.modal-card textarea{width:100%;min-height:110px;resize:vertical;font:inherit;border:1px solid #cbd5e1;border-radius:8px;padding:10px}.modal-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}.modal-error{margin-top:8px;color:#991b1b;font-weight:700}.modal.hidden{display:none}.breakdown-button{margin-top:7px;padding:6px 8px;font-size:12px}.breakdown-card{width:min(760px,100%)}.breakdown-grid{display:grid;grid-template-columns:150px 1fr 150px auto;gap:8px;align-items:center;margin-top:8px}.breakdown-grid select,.breakdown-grid input{width:100%;font:inherit;border:1px solid #cbd5e1;border-radius:8px;padding:9px}.breakdown-summary{display:flex;justify-content:space-between;gap:12px;margin-top:14px;padding:12px;background:#f8fafc;border-radius:9px}.balance-ok{color:#166534}.balance-error{color:#991b1b}.breakdown-help{margin-top:8px;color:#64748b}.remove-component{background:#fee2e2;color:#991b1b;padding:7px 9px}@media(max-width:800px){.page{padding:16px}.stats{grid-template-columns:1fr 1fr}.table-wrap{overflow:auto}}
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
<div id="amountBreakdownModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="amountBreakdownTitle">
    <div class="modal-backdrop" data-breakdown-close></div>
    <div class="modal-card breakdown-card">
        <h2 id="amountBreakdownTitle">Rozpad bankovní částky</h2>
        <p class="muted">Rozdělte částku na DPH, spoluúčast a libovolné vlastní položky. Záporná částka slouží jako korekce.</p>
        <div id="amountBreakdownComponents"></div>
        <button type="button" class="secondary breakdown-button" id="addBreakdownComponent">Přidat položku</button>
        <div class="breakdown-summary"><span>Částka banky: <strong id="breakdownSourceAmount"></strong></span><span>Rozdíl: <strong id="breakdownDifference"></strong></span></div>
        <div class="field"><label for="amountBreakdownReason">Důvod úpravy</label><input id="amountBreakdownReason" maxlength="1000" required placeholder="Uveďte důvod rozpadu částky."></div>
        <div id="amountBreakdownError" class="modal-error hidden"></div>
        <div class="modal-actions">
            <button type="button" class="secondary" data-breakdown-close>Zrušit</button>
            <button type="button" id="saveBreakdownDraft">Uložit koncept</button>
            <button type="button" id="finalizeBreakdown">Finalizovat</button>
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
let amountBreakdownState=null;
const minorFromInput=value=>{const match=String(value??'').trim().replace(/[\s\u00a0\u202f]/g,'').replace(',','.').match(/^([+-]?)(\d+)(?:\.(\d{0,2}))?$/);if(!match)return null;const minor=Number(match[2])*100+Number((match[3]||'').padEnd(2,'0'));return match[1]==='-'?-minor:minor};
const minorToInput=minor=>`${minor<0?'-':''}${Math.floor(Math.abs(minor)/100)}.${String(Math.abs(minor)%100).padStart(2,'0')}`;
const componentTypeOptions=selected=>[['vat','DPH'],['deductible','Spoluúčast'],['custom','Vlastní položka']].map(([value,text])=>`<option value="${value}" ${selected===value?'selected':''}>${text}</option>`).join('');
function addBreakdownComponent(component={type:'custom',label:'',amount_minor:0,metadata:null}){amountBreakdownState.components.push(component);renderBreakdownComponents()}
function removeBreakdownComponent(index){amountBreakdownState.components.splice(index,1);renderBreakdownComponents()}
function renderBreakdownComponents(){const root=document.querySelector('#amountBreakdownComponents');root.innerHTML=amountBreakdownState.components.map((component,index)=>`<div class="breakdown-grid"><select data-component-type="${index}">${componentTypeOptions(component.type)}</select><input data-component-label="${index}" value="${escapeHtml(component.label||'')}" placeholder="Název položky"><input data-component-amount="${index}" inputmode="decimal" value="${escapeHtml(minorToInput(component.amount_minor||0))}" aria-label="Částka položky"><button type="button" class="remove-component" data-remove-component="${index}">Odebrat</button></div>`).join('');root.querySelectorAll('[data-component-type]').forEach(element=>element.onchange=()=>{amountBreakdownState.components[Number(element.dataset.componentType)].type=element.value;updateBreakdownBalance()});root.querySelectorAll('[data-component-label]').forEach(element=>element.oninput=()=>{amountBreakdownState.components[Number(element.dataset.componentLabel)].label=element.value});root.querySelectorAll('[data-component-amount]').forEach(element=>element.oninput=()=>{const minor=minorFromInput(element.value);amountBreakdownState.components[Number(element.dataset.componentAmount)].amount_minor=minor;updateBreakdownBalance()});root.querySelectorAll('[data-remove-component]').forEach(element=>element.onclick=()=>removeBreakdownComponent(Number(element.dataset.removeComponent)));updateBreakdownBalance()}
function updateBreakdownBalance(){if(!amountBreakdownState)return;const finalized=amountBreakdownState.finalized===true;document.querySelector('#amountBreakdownTitle').textContent=finalized?'Finalizovaný rozpad bankovní částky':'Rozpad bankovní částky';document.querySelector('#amountBreakdownReason').disabled=finalized;document.querySelector('#addBreakdownComponent').classList.toggle('hidden',finalized);document.querySelector('#saveBreakdownDraft').classList.toggle('hidden',finalized);document.querySelector('#finalizeBreakdown').classList.toggle('hidden',finalized);document.querySelectorAll('#amountBreakdownComponents select,#amountBreakdownComponents input,#amountBreakdownComponents button').forEach(element=>{element.disabled=finalized;if(element.hasAttribute('data-remove-component'))element.classList.toggle('hidden',finalized)});const valid=amountBreakdownState.components.every(item=>Number.isInteger(item.amount_minor)),allocated=valid?amountBreakdownState.components.reduce((sum,item)=>sum+item.amount_minor,0):0,difference=amountBreakdownState.sourceAmountMinor-allocated,balanced=valid&&difference===0;const output=document.querySelector('#breakdownDifference');output.textContent=valid?money(difference/100,amountBreakdownState.currency):'Neplatná částka';output.className=balanced?'balance-ok':'balance-error';document.querySelector('#saveBreakdownDraft').disabled=!balanced;document.querySelector('#finalizeBreakdown').disabled=!balanced}
async function openAmountBreakdown(evidencePublicId,amount,currency){const modal=document.querySelector('#amountBreakdownModal'),sourceMinor=minorFromInput(amount);amountBreakdownState={evidencePublicId,sourceAmountMinor:sourceMinor,currency,latestRevision:0,finalized:false,components:[{type:'custom',label:'Celá bankovní částka',amount_minor:sourceMinor,metadata:null}]};document.querySelector('#amountBreakdownReason').value='';document.querySelector('#amountBreakdownError').classList.add('hidden');try{const history=await api(`/api/v1/bank-transaction-evidence/${evidencePublicId}/amount-breakdowns`);amountBreakdownState.latestRevision=Number(history.latest_revision||0);const latest=(history.revisions||[]).at(-1);if(latest){amountBreakdownState.components=(latest.components||[]).map(item=>({type:item.type,label:item.label||'',amount_minor:Number(item.amount_minor),metadata:item.metadata||null}));document.querySelector('#amountBreakdownReason').value=latest.reason||'';amountBreakdownState.finalized=latest.status==='finalized'}}catch(error){document.querySelector('#amountBreakdownError').textContent=error.message;document.querySelector('#amountBreakdownError').classList.remove('hidden')}document.querySelector('#breakdownSourceAmount').textContent=money(sourceMinor/100,currency);renderBreakdownComponents();modal.classList.remove('hidden')}
function closeAmountBreakdown(){document.querySelector('#amountBreakdownModal').classList.add('hidden');amountBreakdownState=null}
async function submitAmountBreakdown(finalize){if(!amountBreakdownState)return;const reason=document.querySelector('#amountBreakdownReason').value.trim(),errorOutput=document.querySelector('#amountBreakdownError');if(!reason){errorOutput.textContent='Důvod úpravy je povinný.';errorOutput.classList.remove('hidden');return}const components=amountBreakdownState.components.map(item=>({type:item.type,label:item.label.trim()||null,amount:minorToInput(item.amount_minor),metadata:item.metadata}));if(components.some(item=>item.type==='custom'&&!item.label)){errorOutput.textContent='Vlastní položka musí mít název.';errorOutput.classList.remove('hidden');return}try{errorOutput.classList.add('hidden');await api(`/api/v1/bank-transaction-evidence/${amountBreakdownState.evidencePublicId}/amount-breakdowns`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({idempotency_key:crypto.randomUUID(),expected_revision:amountBreakdownState.latestRevision,finalize,reason,components})});closeAmountBreakdown();message(finalize?'Rozpad částky byl finalizován.':'Koncept rozpadu částky byl uložen.')}catch(error){errorOutput.textContent=error.message;errorOutput.classList.remove('hidden')}}
document.querySelector('#addBreakdownComponent').onclick=()=>addBreakdownComponent();
document.querySelector('#saveBreakdownDraft').onclick=()=>submitAmountBreakdown(false);
document.querySelector('#finalizeBreakdown').onclick=()=>submitAmountBreakdown(true);
document.querySelectorAll('[data-breakdown-close]').forEach(element=>element.onclick=closeAmountBreakdown);
async function load(){try{message('');const data=await api('/api/v1/bank-statement-imports'),items=data.items||[];document.querySelector('#batches').innerHTML=items.map(batch=>`<tr><td>${escapeHtml(batch.original_filename)}</td><td>${escapeHtml(batch.source_type==='csob_csv'?'\u010cSOB CSV':batch.source_type)}</td><td><span class="badge ${escapeHtml(batch.status)}">${escapeHtml(label(batch.status))}</span></td><td>${escapeHtml(batch.source_row_count||0)}</td><td>${escapeHtml(batch.accepted_row_count||0)}</td><td>${escapeHtml(batch.duplicate_candidate_row_count||0)}</td><td>${escapeHtml(batch.rejected_row_count||0)}</td><td><button type="button" data-batch="${escapeHtml(batch.public_id)}">Otev\u0159\u00edt</button></td></tr>`).join('')||'<tr><td colspan="8">Zat\u00edm nebyl nahr\u00e1n \u017e\u00e1dn\u00fd bankovn\u00ed v\u00fdpis.</td></tr>';document.querySelectorAll('[data-batch]').forEach(button=>button.onclick=()=>openBatch(button.dataset.batch))}catch(error){message(error.message,true)}}
async function openBatch(publicId){try{currentBatchPublicId=publicId;const batch=await api(`/api/v1/bank-statement-imports/${publicId}`),rows=batch.rows||[];document.querySelector('#detail').classList.remove('hidden');document.querySelector('#detailTitle').textContent=`Detail: ${batch.original_filename}`;document.querySelector('#detailMeta').textContent=`${batch.source_type==='csob_csv'?'\u010cSOB CSV':batch.source_type} \u00b7 parser ${batch.parser_version||'\u2014'}`;document.querySelector('#stats').innerHTML=[['Celkem',batch.source_row_count],['P\u0159ijato',batch.accepted_row_count],['Kandid\u00e1ti duplicity',batch.duplicate_candidate_row_count],['Odm\u00edtnuto',batch.rejected_row_count]].map(item=>`<div class="stat">${escapeHtml(item[0])}<strong>${escapeHtml(item[1]||0)}</strong></div>`).join('');document.querySelector('#rows').innerHTML=rows.map(row=>{const normalized=row.normalized_payload||{},evidencePublicId=row.transaction_evidence?.public_id||'',validation=(row.validation_messages||[]).map(escapeHtml).join('<br>'),duplicates=candidateHtml(row.duplicate_candidates);return `<tr><td>${escapeHtml(row.source_row)}</td><td class="date">${escapeHtml(normalized.booked_at||'\u2014')}</td><td>${escapeHtml(normalized.counterparty_name||'\u2014')}</td><td class="account">${escapeHtml(normalized.counterparty_account_identifier||'\u2014')}</td><td class="symbol">${escapeHtml(normalized.variable_symbol||'\u2014')}</td><td class="wrap">${escapeHtml(normalized.message||'\u2014')}</td><td class="amount">${escapeHtml(money(normalized.amount,normalized.currency))}</td><td class="status-cell"><span class="badge ${escapeHtml(row.status)}">${escapeHtml(label(row.status))}</span><div class="candidates">${validation||duplicates}</div>${evidencePublicId?`<button type="button" class="secondary breakdown-button" onclick="openAmountBreakdown('${escapeHtml(evidencePublicId)}','${escapeHtml(normalized.amount)}','${escapeHtml(normalized.currency)}')">Rozd\u011blit \u010d\u00e1stku</button>`:''}</td></tr>`}).join('')||'<tr><td colspan="8">D\u00e1vka neobsahuje \u017e\u00e1dn\u00e9 \u0159\u00e1dky.</td></tr>';document.querySelector('#detail').scrollIntoView({behavior:'smooth',block:'start'})}catch(error){message(error.message,true)}}
document.querySelector('#upload').onsubmit=async event=>{event.preventDefault();const file=document.querySelector('#file').files[0];if(!file){message('Vyberte CSV soubor.',true);return}const button=document.querySelector('#submit'),form=new FormData();form.append('idempotency_key',crypto.randomUUID());form.append('adapter','csob_csv');form.append('file',file);try{button.disabled=true;message('V\u00fdpis se zpracov\u00e1v\u00e1...');const batch=await api('/api/v1/bank-statement-imports',{method:'POST',body:form});message('Bankovn\u00ed v\u00fdpis byl bezpe\u010dn\u011b zpracov\u00e1n.');document.querySelector('#upload').reset();await load();await openBatch(batch.public_id)}catch(error){message(error.message,true)}finally{button.disabled=false}};
document.querySelector('#reload').onclick=load;
document.querySelector('#closeDetail').onclick=()=>document.querySelector('#detail').classList.add('hidden');
load();
</script>
</body>
</html>