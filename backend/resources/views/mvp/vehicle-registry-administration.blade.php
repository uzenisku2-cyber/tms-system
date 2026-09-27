<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registr vozidel | TMS</title>
    <style>
        :root{font-family:Arial,sans-serif;color:#0b2345;background:#f3f6fb}*{box-sizing:border-box}body{margin:0;background:#f3f6fb}.page-shell{max-width:1500px;margin:auto;padding:24px}a{color:#123f7a;font-weight:700;text-decoration:none}h1,h2,h3{color:#071f42}.folder,.panel,.card{margin-top:18px;border:1px solid #bfd0e8;border-radius:14px;background:#fff}.folder-head,.subtab-head{width:100%;display:flex;justify-content:space-between;align-items:center;border:0;background:#f7faff;color:#071f42;text-align:left}.folder-head{padding:18px;font-size:22px}.folder-body{padding:18px}.folder-body[hidden],.subtab-body[hidden]{display:none}.hint,.message{color:#53657e}.grid{display:grid;grid-template-columns:repeat(3,minmax(180px,1fr));gap:12px}.search-grid{display:grid;grid-template-columns:2fr 1fr auto;gap:12px;align-items:end}label{display:grid;gap:6px;font-size:14px;font-weight:700}input,select,textarea,button{min-height:42px;padding:9px 12px;border:1px solid #c6d3e5;border-radius:9px;font:inherit}button{background:#173f79;color:#fff;font-weight:700;cursor:pointer}.secondary{background:#e8eef7;color:#123f7a;border-color:#e8eef7}.wide{grid-column:1/-1}.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.actions button{min-height:34px;padding:6px 10px}table{width:100%;margin-top:14px;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #dce4ef;text-align:left;vertical-align:top}th{background:#f5f7fa;font-size:13px}.vehicle-overview{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:10px;margin:14px 0}.overview,.field{padding:11px;border-radius:9px;background:#f5f7fa}.overview span,.field dt{display:block;margin-bottom:5px;color:#5b6c84;font-size:12px;font-weight:700;text-transform:uppercase}.overview strong,.field dd{margin:0;font-weight:700;overflow-wrap:anywhere}.subtab{margin-top:10px;border:1px solid #bfd0e8;border-radius:10px;overflow:hidden}.subtab-head{padding:13px;font-size:18px}.subtab-body{padding:14px}.record{margin-top:9px;padding:12px;border:1px solid #dce4ef;border-radius:10px}.record-grid{display:grid;grid-template-columns:repeat(4,minmax(140px,1fr));gap:9px;margin:0}.badge{padding:3px 8px;border-radius:999px;background:#e5edf8;font-size:12px}.check-field{display:grid;grid-template-columns:auto 1fr;gap:8px;align-items:end}.check-field input[type=checkbox]{min-height:auto}.status-ok{color:#176b3a}.status-error{color:#9a251f}@media(max-width:800px){.grid,.search-grid,.vehicle-overview,.record-grid{grid-template-columns:1fr}.page-shell{padding:12px}table{display:block;overflow:auto}}
    </style>
</head>
<body><main class="page-shell" data-testid="vehicle-registry-administration">
<a href="/settings">&larr; Nastavení</a><h1>Registr vozidel</h1><p>Organizačně řízený přehled vozidel, vlastnictví, odpovědnosti a provozní složky.</p>
<section class="folder" data-registry-folder="create"><button class="folder-head" type="button"><b>Založit nové vozidlo</b><span>Minimálně registrační značka nebo VIN &nbsp; <b class="symbol">+</b></span></button><div class="folder-body" hidden>
<form id="createForm" class="grid"><label>Registrační značka<input name="registration_number" placeholder="např. 9A9 9703"></label><label>VIN<input name="vin" placeholder="lze doplnit později"></label><label>Výrobce<input name="manufacturer"></label><label>Model<input name="model"></label><label>Rok<input name="year" type="number"></label><label>Palivo<select name="fuel_type" data-fuel-select></select></label><label>Tachometr v km<input name="mileage" type="number" min="0"></label><label class="wide">Důvod založení<input name="reason" value="Postupné založení vozidla; chybějící údaje budou doplněny později." required></label><button type="submit">Založit vozidlo</button></form><p id="createMessage" class="message"></p>
</div></section>
<section class="folder" data-registry-folder="manage"><button class="folder-head" type="button"><b>Správa vozidel</b><span>Vyhledávání, seznam a detail vozidla &nbsp; <b class="symbol">−</b></span></button><div class="folder-body">
<div class="panel" style="padding:18px"><div class="search-grid"><label>Hledat<input id="vehicleSearch" placeholder="RZ, VIN, výrobce nebo model"></label><label>Stav<select id="vehicleStatus"><option value="">Všechny</option><option value="active">Aktivní</option><option value="temporarily_inactive">Dočasně neaktivní</option><option value="restricted">Omezené</option><option value="disposed">Vyřazené</option><option value="written_off">Odepsané</option><option value="archived">Archivované</option></select></label><button id="vehicleFilter">Filtrovat</button></div><p id="vehicleMessage"></p><table><thead><tr><th>Vozidlo</th><th>VIN</th><th>Palivo</th><th>Tachometr</th><th>Stav</th><th>Revize</th><th></th></tr></thead><tbody id="vehicleRows"></tbody></table></div>
<section id="vehicleCard" class="card" style="padding:18px" hidden><button id="vehicleCardClose" class="secondary" style="float:right">Zavřít</button><h2 id="vehicleCardTitle">Karta vozidla</h2><div id="vehicleSummary"></div><div id="vehicleSubtabs"></div></section>
</div></section></main>
<script>
const token=sessionStorage.getItem('tms_mvp_token'),organization=sessionStorage.getItem('tms_mvp_organization_id');let current=null;
const esc=v=>String(v??'—').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const fuelTypes=[['','nepovinné'],['diesel','Nafta'],['petrol','Benzín'],['electric','Elektřina'],['hybrid','Hybrid'],['plug_in_hybrid','Plug-in hybrid'],['lpg','LPG'],['cng','CNG'],['hydrogen','Vodík'],['other','Jiné']];
document.querySelectorAll('[data-fuel-select]').forEach(s=>s.innerHTML=fuelTypes.map(([v,l])=>`<option value="${v}">${l}</option>`).join(''));
const localized={active:'Aktivní',temporarily_inactive:'Dočasně neaktivní',restricted:'Omezené',disposed:'Vyřazené',written_off:'Odepsané',archived:'Archivované',vehicle_lifecycle_transitioned:'Změna stavu vozidla',ended:'Ukončená',cancelled:'Zrušená',verified:'Ověřeno',unverified:'Neověřeno',rejected:'Zamítnuto',pending_document:'Čeká na doklad',diesel:'Nafta',petrol:'Benzín',electric:'Elektřina',hybrid:'Hybrid',plug_in_hybrid:'Plug-in hybrid',hydrogen:'Vodík',other:'Jiné'};
const text=v=>v===null||v===undefined||v===''?'—':String(v),loc=v=>localized[v]||text(v).replaceAll('_',' '),date=v=>v?new Date(v).toLocaleDateString('cs-CZ'):'—';
async function api(path,options={}){const r=await fetch(path,{...options,headers:{Accept:'application/json','Content-Type':'application/json',Authorization:`Bearer ${token}`,'X-Organization-Id':organization,...options.headers}}),j=await r.json().catch(()=>({}));if(!r.ok){const error=new Error(j.message||Object.values(j.errors||{}).flat().join(' ')||`HTTP ${r.status}`);error.status=r.status;throw error}return j}
function values(form){return Object.fromEntries([...new FormData(form)].filter(([,v])=>v!==''))}
function folder(button,body,open){body.hidden=!open;button.querySelector('.symbol').textContent=open?'−':'+'}
document.querySelectorAll('[data-registry-folder]').forEach(section=>{const button=section.querySelector('.folder-head'),body=section.querySelector('.folder-body');button.onclick=()=>{const open=body.hidden;document.querySelectorAll('[data-registry-folder]').forEach(x=>folder(x.querySelector('.folder-head'),x.querySelector('.folder-body'),false));folder(button,body,open)}});
const field=(l,v)=>`<div class="field"><dt>${esc(l)}</dt><dd>${esc(loc(v))}</dd></div>`,record=fields=>`<article class="record"><dl class="record-grid">${fields.join('')}</dl></article>`;
const subtab=(key,title,summary,body)=>`<section class="subtab" data-detail-subtab="${key}"><button class="subtab-head" type="button"><b>${title}</b><span>${summary} &nbsp; <b class="symbol">+</b></span></button><div class="subtab-body" hidden>${body}</div></section>`;
const verifiedOptions=d=>(d.documents||[]).filter(x=>x.verification_status==='verified').map(x=>`<option value="${esc(x.public_id)}">${esc(x.title)}</option>`).join('');
function bindSubtabs(){document.querySelectorAll('[data-detail-subtab]>.subtab-head').forEach(button=>button.onclick=()=>{const body=button.nextElementSibling,open=body.hidden;document.querySelectorAll('[data-detail-subtab]').forEach(x=>folder(x.querySelector('.subtab-head'),x.querySelector('.subtab-body'),false));folder(button,body,open)})}
async function loadVehicles(){try{const q=new URLSearchParams({search:vehicleSearch.value,lifecycle_status:vehicleStatus.value}),d=await api(`/api/v1/vehicle-registry-administration?${q}`);vehicleRows.innerHTML=(d.items||[]).map(v=>`<tr><td><b>${esc(v.registration_number)}</b><br>${esc(v.manufacturer)} ${esc(v.model)}</td><td>${esc(v.vin)}</td><td>${esc(loc(v.fuel_type))}</td><td>${esc(text(v.mileage))} ${esc(text(v.odometer_unit))}</td><td>${esc(loc(v.lifecycle_status))}</td><td>${esc(v.revision)}</td><td><button data-vehicle="${esc(v.public_id)}">Detail</button></td></tr>`).join('')||'<tr><td colspan="7">Žádná vozidla.</td></tr>';document.querySelectorAll('[data-vehicle]').forEach(b=>b.onclick=()=>showVehicle(b.dataset.vehicle));vehicleMessage.textContent=`Celkem ${d.pagination?.total||0} vozidel`}catch(e){vehicleMessage.textContent=e.message}}
const historyGroups=[
['Kontroly','compliance_records',x=>[field('Typ kontroly',x.compliance_type),field('Identifikátor',x.identifier),field('Stav',x.status),field('Výsledek',x.result),field('Kontrola dne',date(x.inspected_at)),field('Platnost do',date(x.valid_until)),field('Tachometr',x.odometer==null?'—':`${x.odometer} km`),field('Vydal',x.issuer_name)]],
['Pojištění','insurance_policies',x=>[field('Pojišťovna',x.insurer_name),field('Číslo smlouvy',x.policy_number),field('Typ',x.policy_type),field('Stav',x.status),field('Platnost od',date(x.valid_from)),field('Platnost do',date(x.valid_until)),field('Limit',amount(x.coverage_amount,x.currency)),field('Spoluúčast',amount(x.deductible_amount,x.currency))]],
['Servis','service_records',x=>[field('Servis',x.summary),field('Typ',x.service_type),field('Stav',x.status),field('Zahájení',date(x.opened_at)),field('Dokončení',date(x.completed_at)),field('Další servis',date(x.next_service_on)),field('Tachometr',x.odometer==null?'—':`${x.odometer} km`),field('Poskytovatel',x.external_provider_name|| (x.provider_organization_id?`Organizace #${x.provider_organization_id}`:'—')),field('Popis',x.details)]],
['Incidenty','incidents',x=>[field('Typ',x.incident_type),field('Stav',x.status),field('Závažnost',x.severity),field('Datum události',date(x.occurred_at)),field('Místo',x.location),field('Pojistná událost',x.insurance_claim_reference),field('Policejní reference',x.police_reference),field('Popis',x.description)]],
['Financování','financing_agreements',x=>[field('Typ',x.financing_type),field('Financující strana',x.external_financier_name||(x.financier_organization_id?`Organizace #${x.financier_organization_id}`:'—')),field('Číslo smlouvy',x.agreement_number),field('Stav',x.status),field('Platnost od',date(x.effective_from)),field('Platnost do',date(x.effective_until)),field('Celková hodnota',amount(x.total_amount,x.currency)),field('Počáteční platba',amount(x.initial_payment_amount,x.currency)),field('Zůstatková hodnota',amount(x.residual_value_amount,x.currency))]],
['Auditní události','events',x=>[field('Událost',x.event_type),field('Datum',date(x.occurred_at)),field('Revize vozidla',x.vehicle_revision),field('Uživatel',x.actor_user_id?`Uživatel #${x.actor_user_id}`:'—'),field('Důvod',x.reason)]]
];
const amount=(value,currency)=>value==null?'—':new Intl.NumberFormat('cs-CZ',{style:'currency',currency:currency||'CZK'}).format(Number(value));
function history(d){return historyGroups.map(([name,key,render])=>{const items=d[key]||[];return `<h3>${name} <span class="badge">${items.length}</span></h3>${items.map(x=>record(render(x))).join('')||'<p class="hint">Žádné záznamy.</p>'}`}).join('')}
const lifecycleTargets={active:['temporarily_inactive','restricted'],temporarily_inactive:['active','disposed','written_off'],restricted:['active','disposed','written_off'],disposed:['archived'],written_off:['archived'],archived:[]};
function lifecyclePanel(d){
    const v=d.vehicle,targets=lifecycleTargets[v.lifecycle_status]||[];
    const details=`<p>Aktuální stav: <strong>${esc(loc(v.lifecycle_status))}</strong> · Revize ${esc(v.revision)}</p>`;
    if(!d.capabilities?.can_manage_vehicles)return details+'<p class="hint">Ke změně stavu nemáte oprávnění.</p>';
    if(!targets.length)return details+'<p class="hint">Archivované vozidlo nemá další dostupné přechody.</p>';
    return details+`<form id="lifecycleForm" class="grid"><label>Nový stav<select name="target_status" required>${targets.map(status=>`<option value="${status}">${esc(loc(status))}</option>`).join('')}</select></label><label class="wide">Důvod změny<textarea name="reason" rows="3" minlength="3" maxlength="1000" required></textarea></label><div class="actions wide"><button type="submit">Změnit stav</button></div></form><p class="hint">Změna stavu se zapíše do auditní historie. Při přidělené nebo zahájené jízdě nelze aktivní vozidlo vyřadit z provozu.</p>`;
}
async function submitLifecycle(e){
    e.preventDefault();
    const form=e.currentTarget,target=form.elements.target_status.value,reason=form.elements.reason.value.trim();
    if(!reason){vehicleMessage.textContent='Vyplňte důvod změny stavu.';return}
    if(!window.confirm(`Opravdu změnit stav vozidla na ${loc(target)}?`))return;
    const id=current.vehicle.public_id,revision=current.vehicle.revision,button=form.querySelector('button[type=submit]');
    button.disabled=true;
    try{
        await api(`/api/v1/vehicle-registry-administration/${id}/lifecycle`,{method:'PUT',body:JSON.stringify({expected_revision:revision,target_status:target,reason})});
        await loadVehicles();await showVehicle(id);
        vehicleMessage.className='status-ok';vehicleMessage.textContent=`Stav vozidla byl změněn na ${loc(target)}.`;
    }catch(error){
        if(error.status===409){await showVehicle(id);vehicleMessage.textContent=`${error.message} Údaje byly znovu načteny.`}
        else vehicleMessage.textContent=error.message;
        vehicleMessage.className='status-error';button.disabled=false;
    }
}
const complianceTypes=[['technical_inspection','Technická prohlídka'],['emissions','Emise'],['registration','Registrace'],['roadworthiness','Provozní způsobilost'],['other','Jiné']];
const complianceStatuses=[['pending','Čeká'],['valid','Platné'],['expired','Po platnosti'],['failed','Neúspěšné'],['waived','Výjimka']];
const complianceResults=[['','Neuvedeno'],['passed','Vyhovělo'],['failed','Nevyhovělo'],['conditional','Podmíněně'],['not_applicable','Nevztahuje se']];
const dayInput=value=>value?String(value).slice(0,10):'';
const complianceOptionList=(options,selected='')=>options.map(([value,label])=>`<option value="${esc(value)}" ${value===selected?'selected':''}>${esc(label)}</option>`).join('');
function compliancePanel(d){
    const records=d.compliance_records||[], latest=new Map(), versions=new Map(), documents=d.documents||[];
    records.forEach(item=>{
        const key=item.record_uid;
        if(!versions.has(key))versions.set(key,[]);
        versions.get(key).push(item);
        if(!latest.has(key)||Number(item.revision)>Number(latest.get(key).revision))latest.set(key,item);
    });
    const cards=[...latest.values()].map(item=>{
        const older=versions.get(item.record_uid).filter(x=>x.public_id!==item.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const source=documents.find(x=>Number(x.id)===Number(item.primary_document_id));
        const fields=[field('Typ',complianceTypes.find(x=>x[0]===item.compliance_type)?.[1]||item.compliance_type),
            field('Stav',complianceStatuses.find(x=>x[0]===item.status)?.[1]||item.status),
            field('Výsledek',complianceResults.find(x=>x[0]===item.result)?.[1]||item.result),
            field('Platnost od',date(item.valid_from)),field('Platnost do',date(item.valid_until)),
            field('Kontrola dne',date(item.inspected_at)),field('Identifikátor',item.identifier),
            field('Vydal',item.issuer_name),field('Tachometr',item.odometer==null?'—':`${item.odometer} km`),
            field('Doklad',source?.title||'—'),field('Revize záznamu',item.revision),field('Poznámka',item.notes)];
        const revisions=older.length?`<details><summary>Starší verze (${older.length})</summary>${older.map(old=>record([
            field('Revize',old.revision),field('Stav',complianceStatuses.find(x=>x[0]===old.status)?.[1]||old.status),
            field('Výsledek',complianceResults.find(x=>x[0]===old.result)?.[1]||old.result),
            field('Platnost od',date(old.valid_from)),field('Platnost do',date(old.valid_until)),
            field('Doklad',documents.find(x=>Number(x.id)===Number(old.primary_document_id))?.title||'—'),
            field('Poznámka',old.notes)])).join('')}</details>`:'';
        const action=d.capabilities?.can_manage_vehicles?`<div class="actions"><button type="button" class="secondary" data-revise-compliance="${esc(item.public_id)}">Opravit záznam</button></div>`:'';
        return `<article class="record"><dl class="record-grid">${fields.join('')}</dl>${action}${revisions}</article>`;
    }).join('')||'<p class="hint">Žádné záznamy technických kontrol.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const verified=verifiedOptions(d);
    const form=`<form id="complianceForm" class="grid">
        <h3 class="wide" id="complianceFormTitle">Zapsat kontrolu</h3>
        <label>Typ kontroly<select name="compliance_type" required>${complianceOptionList(complianceTypes)}</select></label>
        <label>Identifikátor<input name="identifier" maxlength="255"></label>
        <label>Datum kontroly<input name="inspected_at" type="date"></label>
        <label>Platnost od<input name="valid_from" type="date" required></label>
        <label>Platnost do<input name="valid_until" type="date"></label>
        <label>Stav<select name="status" required>${complianceOptionList(complianceStatuses)}</select></label>
        <label>Výsledek<select name="result">${complianceOptionList(complianceResults)}</select></label>
        <label>Tachometr v km<input name="odometer" type="number" min="0" step="1"></label>
        <label>Vydal<input name="issuer_name" maxlength="255"></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verified}</select></label>
        <label class="wide">Poznámka<textarea name="notes" rows="2" maxlength="10000"></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat kontrolu</button><button type="button" class="secondary" id="complianceCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Stav a platnost jsou evidované údaje. Zápis automaticky nerozhoduje o možnosti provozovat vozidlo.</p>`;
    return form+cards;
}
function resetComplianceForm(){
    const form=document.querySelector('#complianceForm');
    if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    document.querySelector('#complianceFormTitle').textContent='Zapsat kontrolu';
    form.querySelector('button[type=submit]').textContent='Zapsat kontrolu';
    document.querySelector('#complianceCancel').hidden=true;
}
function bindCompliance(){
    const form=document.querySelector('#complianceForm');
    if(!form)return;
    document.querySelector('#complianceCancel').onclick=resetComplianceForm;
    document.querySelectorAll('[data-revise-compliance]').forEach(button=>button.onclick=()=>{
        const item=(current.compliance_records||[]).find(x=>x.public_id===button.dataset.reviseCompliance);
        if(!item)return;
        resetComplianceForm();
        form.dataset.recordPublicId=item.public_id;
        form.dataset.recordRevision=String(item.revision);
        for(const key of ['compliance_type','identifier','inspected_at','valid_from','valid_until','status','result','odometer','issuer_name','notes']){
            const control=form.elements.namedItem(key);
            if(control)control.value=['inspected_at','valid_from','valid_until'].includes(key)?dayInput(item[key]):(item[key]??'');
        }
        const source=(current.documents||[]).find(x=>Number(x.id)===Number(item.primary_document_id)&&x.verification_status==='verified');
        form.elements.source_document_public_id.value=source?.public_id||'';
        document.querySelector('#complianceFormTitle').textContent='Opravit kontrolu · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#complianceCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();
        if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId,revision=current.vehicle.revision;
        const body={...values(form),expected_revision:revision};
        if(recordId)body.expected_compliance_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/compliance-records`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="compliance"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';
            vehicleMessage.textContent=recordId?'Oprava byla uložena jako nová verze.':'Kontrola byla zapsána.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="compliance"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';
            submit.disabled=false;
        }
    };
}
const insuranceTypes=[['compulsory_liability','Povinné ručení'],['casco','Havarijní'],['gap','GAP'],['assistance','Asistenční'],['other','Jiné']];
const insuranceStatuses=[['pending','Čeká'],['active','Aktivní'],['expired','Po platnosti'],['cancelled','Zrušené']];
function insurancePanel(d){
    const records=d.insurance_policies||[],latest=new Map(),versions=new Map(),documents=d.documents||[];
    records.forEach(item=>{
        const key=item.record_uid;
        if(!versions.has(key))versions.set(key,[]);
        versions.get(key).push(item);
        if(!latest.has(key)||Number(item.revision)>Number(latest.get(key).revision))latest.set(key,item);
    });
    const cards=[...latest.values()].map(item=>{
        const older=versions.get(item.record_uid).filter(x=>x.public_id!==item.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const source=documents.find(x=>Number(x.id)===Number(item.primary_document_id));
        const fields=[field('Typ',insuranceTypes.find(x=>x[0]===item.policy_type)?.[1]||item.policy_type),
            field('Pojišťovna',item.insurer_name),field('Číslo smlouvy',item.policy_number),
            field('Stav',insuranceStatuses.find(x=>x[0]===item.status)?.[1]||item.status),
            field('Platnost od',date(item.valid_from)),field('Platnost do',date(item.valid_until)),
            field('Limit krytí',amount(item.coverage_amount,item.currency)),
            field('Spoluúčast',amount(item.deductible_amount,item.currency)),
            field('Doklad',source?.title||'—'),field('Revize záznamu',item.revision),field('Poznámka',item.notes)];
        const revisions=older.length?`<details><summary>Starší verze (${older.length})</summary>${older.map(old=>record([
            field('Revize',old.revision),field('Pojišťovna',old.insurer_name),field('Číslo smlouvy',old.policy_number),
            field('Stav',insuranceStatuses.find(x=>x[0]===old.status)?.[1]||old.status),
            field('Platnost od',date(old.valid_from)),field('Platnost do',date(old.valid_until)),
            field('Limit krytí',amount(old.coverage_amount,old.currency)),
            field('Spoluúčast',amount(old.deductible_amount,old.currency)),
            field('Doklad',documents.find(x=>Number(x.id)===Number(old.primary_document_id))?.title||'—'),
            field('Poznámka',old.notes)])).join('')}</details>`:'';
        const action=d.capabilities?.can_manage_vehicles?`<div class="actions"><button type="button" class="secondary" data-revise-insurance="${esc(item.public_id)}">Opravit pojistku</button></div>`:'';
        return `<article class="record"><dl class="record-grid">${fields.join('')}</dl>${action}${revisions}</article>`;
    }).join('')||'<p class="hint">Žádné pojistné záznamy.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const form=`<form id="insuranceForm" class="grid">
        <h3 class="wide" id="insuranceFormTitle">Zapsat pojistku</h3>
        <label>Typ pojištění<select name="policy_type" required>${complianceOptionList(insuranceTypes)}</select></label>
        <label>Pojišťovna<input name="insurer_name" maxlength="255" required></label>
        <label>Číslo smlouvy<input name="policy_number" maxlength="255" required></label>
        <label>Platnost od<input name="valid_from" type="date" required></label>
        <label>Platnost do<input name="valid_until" type="date"></label>
        <label>Stav<select name="status" required>${complianceOptionList(insuranceStatuses)}</select></label>
        <label>Limit krytí<input name="coverage_amount" type="number" min="0" max="999999999999.99" step="0.01"></label>
        <label>Spoluúčast<input name="deductible_amount" type="number" min="0" max="999999999999.99" step="0.01"></label>
        <label>Měna (tři velká písmena)<input name="currency" value="CZK" pattern="[A-Z]{3}" maxlength="3"></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verifiedOptions(d)}</select></label>
        <label class="wide">Poznámka<textarea name="notes" rows="2" maxlength="10000"></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat pojistku</button><button type="button" class="secondary" id="insuranceCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Záznam slouží k evidenci smlouvy. Neověřuje rozsah krytí ani platbu pojistného.</p>`;
    return form+cards;
}
function resetInsuranceForm(){
    const form=document.querySelector('#insuranceForm');
    if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    document.querySelector('#insuranceFormTitle').textContent='Zapsat pojistku';
    form.querySelector('button[type=submit]').textContent='Zapsat pojistku';
    document.querySelector('#insuranceCancel').hidden=true;
}
function bindInsurance(){
    const form=document.querySelector('#insuranceForm');
    if(!form)return;
    document.querySelector('#insuranceCancel').onclick=resetInsuranceForm;
    document.querySelectorAll('[data-revise-insurance]').forEach(button=>button.onclick=()=>{
        const item=(current.insurance_policies||[]).find(x=>x.public_id===button.dataset.reviseInsurance);
        if(!item)return;
        resetInsuranceForm();
        form.dataset.recordPublicId=item.public_id;
        form.dataset.recordRevision=String(item.revision);
        for(const key of ['policy_type','insurer_name','policy_number','valid_from','valid_until','status','coverage_amount','deductible_amount','currency','notes']){
            const control=form.elements.namedItem(key);
            if(control)control.value=['valid_from','valid_until'].includes(key)?dayInput(item[key]):(item[key]??'');
        }
        const source=(current.documents||[]).find(x=>Number(x.id)===Number(item.primary_document_id)&&x.verification_status==='verified');
        form.elements.source_document_public_id.value=source?.public_id||'';
        document.querySelector('#insuranceFormTitle').textContent='Opravit pojistku · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#insuranceCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();
        if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId,revision=current.vehicle.revision;
        const body={...values(form),expected_revision:revision};
        if(recordId)body.expected_insurance_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/insurance-policies`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="insurance"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';
            vehicleMessage.textContent=recordId?'Oprava byla uložena jako nová verze.':'Pojistka byla zapsána.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="insurance"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';
            submit.disabled=false;
        }
    };
}
function detailHtml(d){const v=d.vehicle,verified=verifiedOptions(d),docs=(d.documents||[]).map(x=>record([field('Název',x.title),field('Typ',x.document_type),field('Ověření',x.verification_status),field('Revize',x.revision),field('Platnost do',date(x.valid_until)),`<div class="actions"><button data-review-document="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-state="verified">Ověřit</button><button data-review-document="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-state="rejected" class="secondary">Zamítnout</button></div>`])).join('');
const ownership=(d.ownerships||[]).map(x=>record([field('Vlastník',x.owner_type==='organization'?`Organizace #${x.owner_organization_id}`:x.external_owner_name||x.owner_type),field('Podíl',`${Number(x.ownership_share_basis_points)/100} %`),field('Ověření',x.verification_status),field('Revize',x.revision),`<div class="actions"><button data-review-ownership="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="verified">Ověřit</button><button data-review-ownership="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="rejected" class="secondary">Zamítnout</button></div>`])).join('');
const responsibilities=(d.responsibilities||[]).map(x=>record([field('Typ',x.responsibility_type),field('Strana',x.party_type==='organization'?`Organizace #${x.party_organization_id}`:x.external_party_name||x.party_type),field('Stav',x.status),field('Revize',x.revision),`<div class="actions"><button data-review-responsibility="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="ended">Ukončit</button><button data-review-responsibility="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="cancelled" class="secondary">Zrušit</button></div>`])).join('');
const completionFields=[['registration_number','Registrační značka','text'],['vin','VIN','text'],['manufacturer','Výrobce','text'],['model','Model','text'],['year','Rok','number'],['fuel_type','Palivo','fuel'],['mileage','Tachometr','number']];
const completion=completionFields.map(([key,label,type])=>`<label class="check-field"><input type="checkbox" name="selected" value="${key}"><span>${label}${type==='fuel'?`<select name="${key}" data-completion-fuel>${fuelTypes.map(([a,b])=>`<option value="${a}" ${a===(v[key]||'')?'selected':''}>${b}</option>`).join('')}</select>`:`<input name="${key}" type="${type}" value="${esc(v[key]||'')}">`}</span></label>`).join('');
return subtab('lifecycle','Životní cyklus',loc(v.lifecycle_status),lifecyclePanel(d))+subtab('compliance','Technické kontroly',`${new Set((d.compliance_records||[]).map(x=>x.record_uid)).size} záznamů`,compliancePanel(d))+subtab('insurance','Pojištění',`${new Set((d.insurance_policies||[]).map(x=>x.record_uid)).size} záznamů`,insurancePanel(d))+subtab('documents','Dokumenty',`${(d.documents||[]).length} dokladů`,`<form id="documentForm" class="grid"><input name="document_type" value="registration_certificate" placeholder="Typ dokladu" required><input name="title" placeholder="Název" required><input name="storage_reference" placeholder="Odkaz na uložení" required><input name="valid_from" type="date"><input name="valid_until" type="date"><select name="access_classification"><option value="operational">Provozní</option><option value="restricted">Omezený</option></select><input class="wide" name="reason" value="Doplnění dostupného dokladu k vozidlu." required><button>Registrovat doklad</button></form>${docs||'<p class="hint">Žádné doklady.</p>'}`)+subtab('ownership','Vlastnictví',`${(d.ownerships||[]).length} záznamů`,`<form id="ownershipForm" class="grid"><select name="owner_type"><option value="organization">Organizace</option><option value="external_party">Externí vlastník</option></select><input name="owner_organization_id" value="${esc(organization)}" placeholder="ID organizace"><input name="external_owner_name" placeholder="Externí vlastník"><input name="ownership_share_basis_points" type="number" value="10000"><input name="valid_from" type="date" required><input name="acquisition_basis" value="purchase"><select name="source_document_public_id" required><option value="">Ověřený zdrojový doklad</option>${verified}</select><input class="wide" name="reason" value="Evidence vlastnictví podle ověřeného dokladu." required><button>Registrovat vlastnictví</button></form>${ownership||'<p class="hint">Žádné vlastnické záznamy.</p>'}`)+subtab('responsibilities','Odpovědnosti',`${(d.responsibilities||[]).filter(x=>x.status==='active').length} aktivní`,`<form id="responsibilityForm" class="grid"><select name="responsibility_type"><option value="registered_operator">Provozovatel</option><option value="operational_organization">Provozní organizace</option><option value="custodian">Správce</option><option value="authorized_user">Oprávněný uživatel</option><option value="default_driver">Výchozí řidič</option></select><select name="party_type"><option value="organization">Organizace</option><option value="external_party">Externí strana</option></select><input name="party_organization_id" value="${esc(organization)}"><input name="external_party_name" placeholder="Externí strana"><input name="valid_from" type="date" required><select name="source_document_public_id" required><option value="">Ověřený zdrojový doklad</option>${verified}</select><input class="wide" name="reason" value="Evidence odpovědnosti podle ověřeného dokladu." required><button>Registrovat odpovědnost</button></form>${responsibilities||'<p class="hint">Žádné odpovědnosti.</p>'}`)+subtab('completion','Doplnění údajů',`${(d.completeness?.pending_count||0)} oblastí čeká`,`<form id="completionForm" class="grid">${completion}<input class="wide" name="reason" value="Doplnění nebo oprava údajů vozidla." required><button>Uložit vybrané údaje</button></form>`)+subtab('statuses','Stav doplnění',`${(d.completeness?.pending_count||0)} čeká`,(d.field_statuses||[]).map(x=>record([field('Oblast',x.field_key),field('Stav',x.status),field('Revize',x.revision),field('Důvod',x.reason)])).join(''))+subtab('history','Historie a auditní stopa',`${(d.events||[]).length} událostí`,history(d))}
async function showVehicle(id){try{const d=await api(`/api/v1/vehicle-registry-administration/${id}`);current=d;const v=d.vehicle;vehicleCard.hidden=false;vehicleCardTitle.textContent=`${text(v.registration_number)} · ${text(v.manufacturer)} ${text(v.model)}`;vehicleSummary.innerHTML=`<div class="vehicle-overview">${field('VIN',v.vin)}${field('Stav',v.lifecycle_status)}${field('Rok',v.year)}${field('Tachometr',v.mileage)}${field('Revize',v.revision)}</div>`;vehicleSubtabs.innerHTML=detailHtml(d);bindSubtabs();bindForms();vehicleCard.scrollIntoView({behavior:'smooth',block:'start'})}catch(e){vehicleMessage.textContent=e.message}}
async function mutate(path,method,payload){try{await api(path,{method,body:JSON.stringify(payload)});await loadVehicles();await showVehicle(current.vehicle.public_id)}catch(e){vehicleMessage.textContent=e.message;vehicleMessage.className='status-error'}}
function bindForms(){const lifecycleForm=document.querySelector('#lifecycleForm');if(lifecycleForm)lifecycleForm.onsubmit=submitLifecycle;bindCompliance();bindInsurance();documentForm.onsubmit=e=>{e.preventDefault();mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/documents`,'POST',{...values(e.target),expected_revision:current.vehicle.revision})};ownershipForm.onsubmit=e=>{e.preventDefault();mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/ownerships`,'POST',{...values(e.target),expected_revision:current.vehicle.revision})};responsibilityForm.onsubmit=e=>{e.preventDefault();mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/responsibilities`,'POST',{...values(e.target),expected_revision:current.vehicle.revision})};completionForm.onsubmit=e=>{e.preventDefault();const selected=[...e.target.querySelectorAll('[name=selected]:checked')].map(x=>x.value),all=values(e.target),fields=Object.fromEntries(selected.map(k=>[k,all[k]??null]));if(!selected.length){vehicleMessage.textContent='Vyberte alespoň jedno pole.';return}mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}`,'PATCH',{expected_revision:current.vehicle.revision,fields,reason:all.reason})};document.querySelectorAll('[data-review-document]').forEach(b=>b.onclick=()=>mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/documents/${b.dataset.reviewDocument}/verification`,'PUT',{expected_revision:current.vehicle.revision,expected_document_revision:Number(b.dataset.revision),verification_status:b.dataset.state,reason:`${b.dataset.state==='verified'?'Ověření':'Zamítnutí'} dokumentu.`}));document.querySelectorAll('[data-review-ownership]').forEach(b=>b.onclick=()=>mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/ownerships/${b.dataset.reviewOwnership}/verification`,'PUT',{expected_revision:current.vehicle.revision,expected_ownership_revision:Number(b.dataset.revision),source_document_public_id:b.dataset.document,verification_status:b.dataset.state,reason:`${b.dataset.state==='verified'?'Ověření':'Zamítnutí'} vlastnictví.`}));document.querySelectorAll('[data-review-responsibility]').forEach(b=>b.onclick=()=>mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/responsibilities/${b.dataset.reviewResponsibility}/status`,'PUT',{expected_revision:current.vehicle.revision,expected_responsibility_revision:Number(b.dataset.revision),source_document_public_id:b.dataset.document,status:b.dataset.state,reason:`${b.dataset.state==='ended'?'Ukončení':'Zrušení'} odpovědnosti.`}))}
createForm.onsubmit=async e=>{e.preventDefault();try{const d=await api('/api/v1/vehicle-registry-administration',{method:'POST',body:JSON.stringify(values(e.target))});createMessage.textContent='Vozidlo bylo založeno.';e.target.reset();await loadVehicles();folder(document.querySelector('[data-registry-folder=create] .folder-head'),document.querySelector('[data-registry-folder=create] .folder-body'),false);folder(document.querySelector('[data-registry-folder=manage] .folder-head'),document.querySelector('[data-registry-folder=manage] .folder-body'),true);await showVehicle(d.vehicle.public_id)}catch(err){createMessage.textContent=err.message}};
vehicleFilter.onclick=loadVehicles;vehicleCardClose.onclick=()=>vehicleCard.hidden=true;loadVehicles();
</script></body></html>
