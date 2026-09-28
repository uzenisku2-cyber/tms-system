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
const serviceTypes=[['scheduled','Plánovaný servis'],['repair','Oprava'],['inspection','Prohlídka'],['tyres','Pneumatiky'],['recall','Svolávací akce'],['other','Jiné']];
const serviceStatuses=[['planned','Plánovaný'],['in_progress','Probíhá'],['completed','Dokončený'],['cancelled','Zrušený']];
const localDateTime=value=>{
    if(!value)return '';
    const d=new Date(value),pad=n=>String(n).padStart(2,'0');
    return Number.isNaN(d.getTime())?'':`${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
function servicePanel(d){
    const records=d.service_records||[],latest=new Map(),versions=new Map(),documents=d.documents||[];
    records.forEach(item=>{
        const key=item.record_uid;
        if(!versions.has(key))versions.set(key,[]);
        versions.get(key).push(item);
        if(!latest.has(key)||Number(item.revision)>Number(latest.get(key).revision))latest.set(key,item);
    });
    const provider=item=>item.external_provider_name||(item.provider_organization_id?`Organizace #${item.provider_organization_id}`:'—');
    const cards=[...latest.values()].map(item=>{
        const older=versions.get(item.record_uid).filter(x=>x.public_id!==item.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const source=documents.find(x=>Number(x.id)===Number(item.primary_document_id));
        const fields=[field('Servis',item.summary),field('Typ',serviceTypes.find(x=>x[0]===item.service_type)?.[1]||item.service_type),
            field('Stav',serviceStatuses.find(x=>x[0]===item.status)?.[1]||item.status),
            field('Zahájení',date(item.opened_at)),field('Dokončení',date(item.completed_at)),
            field('Další servis',date(item.next_service_on)),field('Tachometr',item.odometer==null?'—':`${item.odometer} km`),
            field('Další servis při',item.next_service_odometer==null?'—':`${item.next_service_odometer} km`),
            field('Poskytovatel',provider(item)),field('Doklad',source?.title||'—'),
            field('Revize záznamu',item.revision),field('Popis',item.details)];
        const revisions=older.length?`<details><summary>Starší verze (${older.length})</summary>${older.map(old=>record([
            field('Revize',old.revision),field('Servis',old.summary),
            field('Typ',serviceTypes.find(x=>x[0]===old.service_type)?.[1]||old.service_type),
            field('Stav',serviceStatuses.find(x=>x[0]===old.status)?.[1]||old.status),
            field('Zahájení',date(old.opened_at)),field('Dokončení',date(old.completed_at)),
            field('Další servis',date(old.next_service_on)),field('Tachometr',old.odometer==null?'—':`${old.odometer} km`),
            field('Další servis při',old.next_service_odometer==null?'—':`${old.next_service_odometer} km`),
            field('Poskytovatel',provider(old)),
            field('Doklad',documents.find(x=>Number(x.id)===Number(old.primary_document_id))?.title||'—'),
            field('Popis',old.details)])).join('')}</details>`:'';
        const action=d.capabilities?.can_manage_vehicles?`<div class="actions"><button type="button" class="secondary" data-revise-service="${esc(item.public_id)}">Opravit záznam</button></div>`:'';
        return `<article class="record"><dl class="record-grid">${fields.join('')}</dl>${action}${revisions}</article>`;
    }).join('')||'<p class="hint">Žádné servisní záznamy.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const form=`<form id="serviceForm" class="grid">
        <h3 class="wide" id="serviceFormTitle">Zapsat servis</h3>
        <label>Typ servisu<select name="service_type" required>${complianceOptionList(serviceTypes)}</select></label>
        <label>Stav<select name="status" required>${complianceOptionList(serviceStatuses)}</select></label>
        <label>Stručný popis<input name="summary" maxlength="255" required></label>
        <label>Zahájení<input name="opened_at" type="datetime-local" required></label>
        <label>Dokončení<input name="completed_at" type="datetime-local"></label>
        <label>Další servis dne<input name="next_service_on" type="date"></label>
        <label>Tachometr v km<input name="odometer" type="number" min="0" step="1"></label>
        <label>Další servis při km<input name="next_service_odometer" type="number" min="0" step="1"></label>
        <label>Organizace poskytovatele<select name="provider_organization_id"><option value="">Neuvedeno</option><option value="${esc(organization)}">Aktivní organizace</option></select></label>
        <label>Externí poskytovatel<input name="external_provider_name" maxlength="255"></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verifiedOptions(d)}</select></label>
        <label class="wide">Podrobnosti<textarea name="details" rows="2" maxlength="10000"></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat servis</button><button type="button" class="secondary" id="serviceCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Servisní termíny jsou evidenční údaje. Tento formulář nevytváří objednávku ani platbu.</p>`;
    return form+cards;
}
function resetServiceForm(){
    const form=document.querySelector('#serviceForm');
    if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    document.querySelector('#serviceFormTitle').textContent='Zapsat servis';
    form.querySelector('button[type=submit]').textContent='Zapsat servis';
    document.querySelector('#serviceCancel').hidden=true;
}
function bindService(){
    const form=document.querySelector('#serviceForm');
    if(!form)return;
    document.querySelector('#serviceCancel').onclick=resetServiceForm;
    document.querySelectorAll('[data-revise-service]').forEach(button=>button.onclick=()=>{
        const item=(current.service_records||[]).find(x=>x.public_id===button.dataset.reviseService);
        if(!item)return;
        resetServiceForm();
        form.dataset.recordPublicId=item.public_id;
        form.dataset.recordRevision=String(item.revision);
        for(const key of ['service_type','status','summary','opened_at','completed_at','next_service_on','odometer','next_service_odometer','provider_organization_id','external_provider_name','details']){
            const control=form.elements.namedItem(key);
            if(control)control.value=['opened_at','completed_at'].includes(key)?localDateTime(item[key]):(key==='next_service_on'?dayInput(item[key]):(item[key]??''));
        }
        const source=(current.documents||[]).find(x=>Number(x.id)===Number(item.primary_document_id)&&x.verification_status==='verified');
        form.elements.source_document_public_id.value=source?.public_id||'';
        document.querySelector('#serviceFormTitle').textContent='Opravit servis · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#serviceCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();
        if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId,revision=current.vehicle.revision;
        const body={...values(form),expected_revision:revision};
        if(body.provider_organization_id&&body.external_provider_name){vehicleMessage.className='status-error';vehicleMessage.textContent='Vyberte organizaci, nebo zadejte externího poskytovatele.';return}
        for(const key of ['opened_at','completed_at'])if(body[key])body[key]=new Date(body[key]).toISOString();
        if(recordId)body.expected_service_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/service-records`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="service"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';
            vehicleMessage.textContent=recordId?'Oprava byla uložena jako nová verze.':'Servis byl zapsán.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="service"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';submit.disabled=false;
        }
    };
}
const incidentTypes=[['accident','Nehoda'],['damage','Poškození'],['theft','Krádež'],['vandalism','Vandalismus'],['breakdown','Porucha'],['other','Jiné']];
const incidentStatuses=[['reported','Nahlášený'],['investigating','V šetření'],['repair_in_progress','Oprava probíhá'],['resolved','Vyřešený'],['closed','Uzavřený'],['rejected','Zamítnutý']];
const incidentSeverities=[['minor','Nízká'],['major','Závažná'],['critical','Kritická'],['total_loss','Totální škoda']];
function incidentPanel(d){
    const records=d.incidents||[],latest=new Map(),versions=new Map(),documents=d.documents||[];
    records.forEach(item=>{
        const key=item.record_uid;
        if(!versions.has(key))versions.set(key,[]);
        versions.get(key).push(item);
        if(!latest.has(key)||Number(item.revision)>Number(latest.get(key).revision))latest.set(key,item);
    });
    const label=(choices,value)=>choices.find(x=>x[0]===value)?.[1]||value;
    const fields=item=>[
        field('Typ',label(incidentTypes,item.incident_type)),field('Stav',label(incidentStatuses,item.status)),
        field('Závažnost',label(incidentSeverities,item.severity)),field('Datum události',date(item.occurred_at)),
        field('Nahlášeno',date(item.reported_at)),field('Vyřešeno',date(item.resolved_at)),
        field('Řidič',item.driver_user_id?`Uživatel #${item.driver_user_id}`:'—'),
        field('Odpovědná organizace',item.responsible_organization_id?`Organizace #${item.responsible_organization_id}`:'—'),
        field('Místo',item.location),field('Policejní reference',item.police_reference),
        field('Pojistná reference',item.insurance_claim_reference),
        field('Doklad',documents.find(x=>Number(x.id)===Number(item.primary_document_id))?.title||'—'),
        field('Revize záznamu',item.revision),field('Popis',item.description)
    ];
    const cards=[...latest.values()].map(item=>{
        const older=versions.get(item.record_uid).filter(x=>x.public_id!==item.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const revisions=older.length?`<details><summary>Starší verze (${older.length})</summary>${older.map(old=>record(fields(old))).join('')}</details>`:'';
        const action=d.capabilities?.can_manage_vehicles?`<div class="actions"><button type="button" class="secondary" data-revise-incident="${esc(item.public_id)}">Opravit incident</button></div>`:'';
        return `<article class="record"><dl class="record-grid">${fields(item).join('')}</dl>${action}${revisions}</article>`;
    }).join('')||'<p class="hint">Žádné incidenty.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const form=`<form id="incidentForm" class="grid">
        <h3 class="wide" id="incidentFormTitle">Zapsat incident</h3>
        <label>Typ incidentu<select name="incident_type" required>${complianceOptionList(incidentTypes)}</select></label>
        <label>Stav<select name="status" required>${complianceOptionList(incidentStatuses)}</select></label>
        <label>Závažnost<select name="severity" required>${complianceOptionList(incidentSeverities)}</select></label>
        <label>Datum události<input name="occurred_at" type="datetime-local" required></label>
        <label>Datum nahlášení<input name="reported_at" type="datetime-local" required></label>
        <label>Datum vyřešení<input name="resolved_at" type="datetime-local"></label>
        <label>ID řidiče v aktivní organizaci<input name="driver_user_id" type="number" min="1" step="1"></label>
        <label>Odpovědná organizace<select name="responsible_organization_id"><option value="">Neuvedeno</option><option value="${esc(organization)}">Aktivní organizace</option></select></label>
        <label>Místo<input name="location" maxlength="255"></label>
        <label>Policejní reference<input name="police_reference" maxlength="255"></label>
        <label>Pojistná reference<input name="insurance_claim_reference" maxlength="255"></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verifiedOptions(d)}</select></label>
        <label class="wide">Popis<textarea name="description" rows="3" maxlength="10000" required></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat incident</button><button type="button" class="secondary" id="incidentCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Pojistná reference je evidenční údaj. Zápis nevytváří pojistný nárok ani platbu.</p>`;
    return form+cards;
}
function resetIncidentForm(){
    const form=document.querySelector('#incidentForm');
    if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    document.querySelector('#incidentFormTitle').textContent='Zapsat incident';
    form.querySelector('button[type=submit]').textContent='Zapsat incident';
    document.querySelector('#incidentCancel').hidden=true;
}
function bindIncident(){
    const form=document.querySelector('#incidentForm');
    if(!form)return;
    document.querySelector('#incidentCancel').onclick=resetIncidentForm;
    document.querySelectorAll('[data-revise-incident]').forEach(button=>button.onclick=()=>{
        const item=(current.incidents||[]).find(x=>x.public_id===button.dataset.reviseIncident);
        if(!item)return;
        resetIncidentForm();
        form.dataset.recordPublicId=item.public_id;
        form.dataset.recordRevision=String(item.revision);
        for(const key of ['incident_type','status','severity','occurred_at','reported_at','resolved_at','driver_user_id','responsible_organization_id','location','police_reference','insurance_claim_reference','description']){
            const control=form.elements.namedItem(key);
            if(control)control.value=['occurred_at','reported_at','resolved_at'].includes(key)?localDateTime(item[key]):(item[key]??'');
        }
        const source=(current.documents||[]).find(x=>Number(x.id)===Number(item.primary_document_id)&&x.verification_status==='verified');
        form.elements.source_document_public_id.value=source?.public_id||'';
        document.querySelector('#incidentFormTitle').textContent='Opravit incident · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#incidentCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();
        if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId;
        const body={...values(form),expected_revision:current.vehicle.revision};
        for(const key of ['occurred_at','reported_at','resolved_at'])if(body[key])body[key]=new Date(body[key]).toISOString();
        if(recordId)body.expected_incident_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/incidents`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="incidents"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';
            vehicleMessage.textContent=recordId?'Oprava incidentu byla uložena jako nová verze.':'Incident byl zapsán.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="incidents"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';submit.disabled=false;
        }
    };
}
const financingTypes=[['operating_lease','Operativní leasing'],['finance_lease','Finanční leasing'],['purchase_installment','Koupě na splátky'],['loan','Úvěr'],['other','Jiné']];
const financingStatuses=[['draft','Návrh'],['active','Aktivní'],['suspended','Pozastavená'],['completed','Dokončená'],['terminated','Ukončená'],['cancelled','Zrušená']];
function financingPanel(d){
    const records=d.financing_agreements||[],latest=new Map(),versions=new Map();
    records.forEach(item=>{
        const key=item.financing_uid;
        if(!versions.has(key))versions.set(key,[]);
        versions.get(key).push(item);
        if(!latest.has(key)||Number(item.revision)>Number(latest.get(key).revision))latest.set(key,item);
    });
    const label=(choices,value)=>choices.find(x=>x[0]===value)?.[1]||value;
    const fields=item=>[
        field('Typ',label(financingTypes,item.financing_type)),field('Stav',label(financingStatuses,item.status)),
        field('Číslo smlouvy',item.agreement_number),
        field('Financující strana',item.external_financier_name||(item.financier_organization_id?`Organizace #${item.financier_organization_id}`:'—')),
        field('Dlužník',item.debtor_type==='driver'?`Řidič #${item.debtor_user_id}`:`Organizace #${item.debtor_organization_id}`),
        field('Platnost od',date(item.effective_from)),field('Platnost do',date(item.effective_until)),
        field('Celková hodnota',amount(item.total_amount,item.currency)),
        field('Počáteční platba',amount(item.initial_payment_amount,item.currency)),
        field('Zůstatková hodnota',amount(item.residual_value_amount,item.currency)),
        field('Revize smlouvy',item.revision),field('Poznámky',item.notes)
    ];
    const cards=[...latest.values()].map(item=>{
        const older=versions.get(item.financing_uid).filter(x=>x.public_id!==item.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const revisions=older.length?`<details><summary>Starší verze (${older.length})</summary>${older.map(old=>record(fields(old))).join('')}</details>`:'';
        const action=d.capabilities?.can_manage_vehicles?`<div class="actions"><button type="button" class="secondary" data-revise-financing="${esc(item.public_id)}">Opravit smlouvu</button></div>`:'';
        return `<article class="record"><dl class="record-grid">${fields(item).join('')}</dl>${action}${revisions}</article>`;
    }).join('')||'<p class="hint">Žádné finanční smlouvy.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const form=`<form id="financingForm" class="grid">
        <h3 class="wide" id="financingFormTitle">Zapsat finanční smlouvu</h3>
        <label>Typ financování<select name="financing_type" required>${complianceOptionList(financingTypes)}</select></label>
        <label>Stav<select name="status" required>${complianceOptionList(financingStatuses)}</select></label>
        <label>Číslo smlouvy<input name="agreement_number" maxlength="255"></label>
        <label>Financující strana<select name="financier_organization_id"><option value="">Externí strana</option><option value="${esc(organization)}">Aktivní organizace</option></select></label>
        <label>Externí financující strana<input name="external_financier_name" maxlength="255"></label>
        <label>Dlužník<select name="debtor_type" required><option value="organization">Aktivní organizace</option><option value="driver">Řidič</option></select></label>
        <label>ID řidiče, je-li dlužníkem<input name="debtor_user_id" type="number" min="1" step="1"></label>
        <label>Platnost od<input name="effective_from" type="date" required></label>
        <label>Platnost do<input name="effective_until" type="date"></label>
        <label>Měna<input name="currency" value="CZK" maxlength="3" pattern="[A-Z]{3}" required></label>
        <label>Celková hodnota<input name="total_amount" type="number" min="0" step="0.01"></label>
        <label>Počáteční platba<input name="initial_payment_amount" type="number" min="0" step="0.01"></label>
        <label>Zůstatková hodnota<input name="residual_value_amount" type="number" min="0" step="0.01"></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verifiedOptions(d)}</select></label>
        <label class="wide">Poznámky<textarea name="notes" rows="2" maxlength="10000"></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat smlouvu</button><button type="button" class="secondary" id="financingCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Zapsaná hodnota ani plán splátek nepotvrzují zaplacení. Formulář nevytváří platbu ani účetní doklad.</p>`;
    return form+cards;
}
function resetFinancingForm(){
    const form=document.querySelector('#financingForm');
    if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    document.querySelector('#financingFormTitle').textContent='Zapsat finanční smlouvu';
    form.querySelector('button[type=submit]').textContent='Zapsat smlouvu';
    document.querySelector('#financingCancel').hidden=true;
}
function bindFinancing(){
    const form=document.querySelector('#financingForm');
    if(!form)return;
    document.querySelector('#financingCancel').onclick=resetFinancingForm;
    document.querySelectorAll('[data-revise-financing]').forEach(button=>button.onclick=()=>{
        const item=(current.financing_agreements||[]).find(x=>x.public_id===button.dataset.reviseFinancing);
        if(!item)return;
        resetFinancingForm();
        form.dataset.recordPublicId=item.public_id;
        form.dataset.recordRevision=String(item.revision);
        for(const key of ['financing_type','status','agreement_number','financier_organization_id','external_financier_name','debtor_type','debtor_user_id','effective_from','effective_until','currency','total_amount','initial_payment_amount','residual_value_amount','notes']){
            const control=form.elements.namedItem(key);
            if(control)control.value=['effective_from','effective_until'].includes(key)?dayInput(item[key]):(item[key]??'');
        }
        form.elements.source_document_public_id.value='';
        document.querySelector('#financingFormTitle').textContent='Opravit smlouvu · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#financingCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();
        if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId;
        const body={...values(form),expected_revision:current.vehicle.revision};
        if(Boolean(body.financier_organization_id)===Boolean(body.external_financier_name)){
            vehicleMessage.className='status-error';vehicleMessage.textContent='Vyberte právě jednu financující stranu.';return;
        }
        if(body.debtor_type==='organization'){
            if(body.debtor_user_id){vehicleMessage.className='status-error';vehicleMessage.textContent='Pro dlužnou organizaci nevyplňujte řidiče.';return;}
            body.debtor_organization_id=Number(organization);
        }else{
            if(!body.debtor_user_id){vehicleMessage.className='status-error';vehicleMessage.textContent='Vyplňte ID řidiče v aktivní organizaci.';return;}
        }
        if(recordId)body.expected_financing_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/financing-agreements`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="financing"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';
            vehicleMessage.textContent=recordId?'Oprava smlouvy byla uložena jako nová verze.':'Finanční smlouva byla zapsána.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="financing"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';submit.disabled=false;
        }
    };
}
const scheduleStatuses=[['draft','Návrh'],['active','Aktivní'],['replaced','Nahrazený'],['completed','Dokončený'],['cancelled','Zrušený']];
const scheduleFrequencies=[['weekly','Týdně'],['monthly','Měsíčně'],['quarterly','Čtvrtletně'],['annual','Ročně'],['custom','Vlastní']];
function schedulePanel(d){
    const agreements=d.financing_agreements||[],currentFinancing=new Map(),byId=new Map();
    agreements.forEach(item=>{
        byId.set(Number(item.id),item);
        if(!currentFinancing.has(item.financing_uid)||Number(item.revision)>Number(currentFinancing.get(item.financing_uid).revision))currentFinancing.set(item.financing_uid,item);
    });
    const schedules=d.installment_schedules||[],latest=new Map(),versions=new Map();
    schedules.forEach(item=>{
        if(!versions.has(item.schedule_uid))versions.set(item.schedule_uid,[]);
        versions.get(item.schedule_uid).push(item);
        if(!latest.has(item.schedule_uid)||Number(item.revision)>Number(latest.get(item.schedule_uid).revision))latest.set(item.schedule_uid,item);
    });
    const label=(choices,value)=>choices.find(x=>x[0]===value)?.[1]||value;
    const fields=item=>{
        const agreement=byId.get(Number(item.vehicle_financing_agreement_id));
        return [field('Smlouva',agreement?.agreement_number||agreement?.financing_uid||'—'),field('Revize smlouvy',agreement?.revision),
            field('Od',date(item.starts_on)),field('Do',date(item.ends_on)),field('Počet splátek',item.installment_count),
            field('Plánovaná hodnota',amount(item.planned_total_amount,item.currency)),field('Četnost',label(scheduleFrequencies,item.frequency)),
            field('Stav',label(scheduleStatuses,item.status)),field('Revize kalendáře',item.revision),field('Poznámky',item.notes)];
    };
    const cards=[...latest.values()].map(item=>{
        const previous=versions.get(item.schedule_uid).filter(x=>x.public_id!==item.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const older=previous.length?`<details><summary>Starší verze (${previous.length})</summary>${previous.map(x=>record(fields(x))).join('')}</details>`:'';
        const agreement=byId.get(Number(item.vehicle_financing_agreement_id));
        const hasInstallments=(d.installments||[]).some(x=>versions.get(item.schedule_uid).some(v=>Number(v.id)===Number(x.vehicle_installment_schedule_id)));
        const canRevise=agreement&&!hasInstallments&&currentFinancing.get(agreement.financing_uid)?.public_id===agreement.public_id;
        const action=d.capabilities?.can_manage_vehicles?(canRevise?`<div class="actions"><button type="button" class="secondary" data-revise-schedule="${esc(item.public_id)}">Opravit kalendář</button></div>`:(hasInstallments?'<p class="hint">Kalendář se zapsanými splátkami nelze opravit.</p>':'<p class="hint">Smlouva má novější revizi. Zapište nový kalendář k aktuální smlouvě.</p>')):'';
        return `<article class="record"><dl class="record-grid">${fields(item).join('')}</dl>${action}${older}</article>`;
    }).join('')||'<p class="hint">Žádné splátkové kalendáře.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const choices=[...currentFinancing.values()].map(x=>`<option value="${esc(x.public_id)}">${esc(x.agreement_number||x.financing_uid)} · revize ${esc(x.revision)} · ${esc(x.currency)}</option>`).join('');
    if(!choices)return '<p class="hint">Nejprve zapište finanční smlouvu.</p>'+cards;
    const form=`<form id="scheduleForm" class="grid">
        <h3 class="wide" id="scheduleFormTitle">Zapsat splátkový kalendář</h3>
        <label class="wide">Aktuální finanční smlouva<select name="financing_public_id" required><option value="">Vyberte smlouvu</option>${choices}</select></label>
        <label>Začátek<input name="starts_on" type="date" required></label>
        <label>Konec<input name="ends_on" type="date"></label>
        <label>Počet splátek<input name="installment_count" type="number" min="1" step="1" required></label>
        <label>Plánovaná hodnota<input name="planned_total_amount" type="number" min="0" step="0.01" required></label>
        <label>Měna<input name="currency" maxlength="3" pattern="[A-Z]{3}" readonly required></label>
        <label>Četnost<select name="frequency" required>${complianceOptionList(scheduleFrequencies)}</select></label>
        <label>Stav<select name="status" required>${complianceOptionList(scheduleStatuses)}</select></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verifiedOptions(d)}</select></label>
        <label class="wide">Poznámky<textarea name="notes" rows="2" maxlength="10000"></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat kalendář</button><button type="button" class="secondary" id="scheduleCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Plán splátek je evidenční údaj. Zápis nepotvrzuje zaplacení a nevytváří jednotlivé splátky ani platby. Kalendář se zapsanými splátkami nelze opravovat.</p>`;
    return form+cards;
}
function resetScheduleForm(){
    const form=document.querySelector('#scheduleForm');if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    form.elements.currency.value='';
    document.querySelector('#scheduleFormTitle').textContent='Zapsat splátkový kalendář';
    form.querySelector('button[type=submit]').textContent='Zapsat kalendář';
    document.querySelector('#scheduleCancel').hidden=true;
}
function bindSchedules(){
    const form=document.querySelector('#scheduleForm');if(!form)return;
    const financingById=new Map((current.financing_agreements||[]).map(x=>[x.public_id,x]));
    const syncCurrency=()=>{form.elements.currency.value=financingById.get(form.elements.financing_public_id.value)?.currency||''};
    form.elements.financing_public_id.onchange=syncCurrency;
    document.querySelector('#scheduleCancel').onclick=resetScheduleForm;
    document.querySelectorAll('[data-revise-schedule]').forEach(button=>button.onclick=()=>{
        const item=(current.installment_schedules||[]).find(x=>x.public_id===button.dataset.reviseSchedule);
        if(!item)return;
        const agreement=(current.financing_agreements||[]).find(x=>Number(x.id)===Number(item.vehicle_financing_agreement_id));
        if(!agreement)return;
        resetScheduleForm();
        form.dataset.recordPublicId=item.public_id;form.dataset.recordRevision=String(item.revision);
        form.elements.financing_public_id.value=agreement.public_id;
        for(const key of ['starts_on','ends_on','installment_count','planned_total_amount','frequency','status','notes']){
            const control=form.elements.namedItem(key);
            if(control)control.value=['starts_on','ends_on'].includes(key)?dayInput(item[key]):(item[key]??'');
        }
        syncCurrency();form.elements.source_document_public_id.value='';
        document.querySelector('#scheduleFormTitle').textContent='Opravit kalendář · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#scheduleCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId;
        const agreement=financingById.get(form.elements.financing_public_id.value);
        if(!agreement){vehicleMessage.className='status-error';vehicleMessage.textContent='Vyberte aktuální finanční smlouvu.';return}
        const body={...values(form),expected_revision:current.vehicle.revision,expected_financing_revision:Number(agreement.revision)};
        if(recordId)body.expected_schedule_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/installment-schedules`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="schedules"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';vehicleMessage.textContent=recordId?'Oprava kalendáře byla uložena jako nová verze.':'Splátkový kalendář byl zapsán.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="schedules"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';submit.disabled=false;
        }
    };
}
function installmentPanel(d){
    const schedules=d.installment_schedules||[],agreements=d.financing_agreements||[],items=d.installments||[];
    const schedulesById=new Map(schedules.map(x=>[Number(x.id),x]));
    const agreementById=new Map(agreements.map(x=>[Number(x.id),x]));
    const latestFinancing=new Map(),latestSchedule=new Map(),latest=new Map(),versions=new Map();
    agreements.forEach(x=>{if(!latestFinancing.has(x.financing_uid)||Number(x.revision)>Number(latestFinancing.get(x.financing_uid).revision))latestFinancing.set(x.financing_uid,x)});
    schedules.forEach(x=>{if(!latestSchedule.has(x.schedule_uid)||Number(x.revision)>Number(latestSchedule.get(x.schedule_uid).revision))latestSchedule.set(x.schedule_uid,x)});
    items.forEach(x=>{
        if(!versions.has(x.installment_uid))versions.set(x.installment_uid,[]);
        versions.get(x.installment_uid).push(x);
        if(!latest.has(x.installment_uid)||Number(x.revision)>Number(latest.get(x.installment_uid).revision))latest.set(x.installment_uid,x);
    });
    const eligible=[...latestSchedule.values()].filter(x=>{
        const financing=agreementById.get(Number(x.vehicle_financing_agreement_id));
        return ['draft','active'].includes(x.status)&&financing&&latestFinancing.get(financing.financing_uid)?.public_id===financing.public_id;
    });
    const eligibleIds=new Set(eligible.map(x=>x.public_id));
    const statusLabel={planned:'Plánovaná',cancelled:'Zrušená',replaced:'Nahrazená',waived:'Prominutá'};
    const fields=x=>{
        const schedule=schedulesById.get(Number(x.vehicle_installment_schedule_id));
        return [field('Kalendář',schedule?.schedule_uid||'—'),field('Pořadí',x.sequence_number),field('Splatnost',date(x.due_on)),
            field('Jistina',amount(x.principal_amount,x.currency)),field('Finanční náklad',amount(x.finance_charge_amount,x.currency)),
            field('Další částka',amount(x.other_amount,x.currency)),field('Celkem',amount(x.total_amount,x.currency)),
            field('Stav',statusLabel[x.status]||x.status),field('Revize splátky',x.revision),field('Poznámky',x.notes)];
    };
    const cards=[...latest.values()].sort((a,b)=>Number(a.sequence_number)-Number(b.sequence_number)).map(x=>{
        const schedule=schedulesById.get(Number(x.vehicle_installment_schedule_id));
        const previous=versions.get(x.installment_uid).filter(v=>v.public_id!==x.public_id).sort((a,b)=>Number(b.revision)-Number(a.revision));
        const older=previous.length?`<details><summary>Starší verze (${previous.length})</summary>${previous.map(v=>record(fields(v))).join('')}</details>`:'';
        const action=d.capabilities?.can_manage_vehicles&&schedule&&eligibleIds.has(schedule.public_id)
            ?`<div class="actions"><button type="button" class="secondary" data-revise-installment="${esc(x.public_id)}">Opravit splátku</button></div>`:'';
        return `<article class="record"><dl class="record-grid">${fields(x).join('')}</dl>${action}${older}</article>`;
    }).join('')||'<p class="hint">Žádné jednotlivé splátky.</p>';
    if(!d.capabilities?.can_manage_vehicles)return cards;
    const choices=eligible.map(x=>`<option value="${esc(x.public_id)}">${esc(x.schedule_uid)} · revize ${esc(x.revision)} · ${esc(x.installment_count)} splátek · ${esc(x.currency)}</option>`).join('');
    if(!choices)return '<p class="hint">Nejprve zapište aktuální splátkový kalendář ke stávající finanční smlouvě.</p>'+cards;
    const form=`<form id="installmentForm" class="grid">
        <h3 class="wide" id="installmentFormTitle">Zapsat jednotlivou splátku</h3>
        <label class="wide">Aktuální kalendář<select name="schedule_public_id" required><option value="">Vyberte kalendář</option>${choices}</select></label>
        <label>Pořadí<input name="sequence_number" type="number" min="1" step="1" required></label>
        <label>Splatnost<input name="due_on" type="date" required></label>
        <label>Jistina<input name="principal_amount" type="number" min="0" step="0.01" required></label>
        <label>Finanční náklad<input name="finance_charge_amount" type="number" min="0" step="0.01" value="0.00" required></label>
        <label>Další částka<input name="other_amount" type="number" min="0" step="0.01" value="0.00" required></label>
        <label>Celkem<input name="total_amount" type="number" min="0" step="0.01" readonly required></label>
        <label>Měna<input name="currency" maxlength="3" pattern="[A-Z]{3}" readonly required></label>
        <label>Stav<select name="status" required><option value="planned">Plánovaná</option><option value="cancelled">Zrušená</option><option value="replaced">Nahrazená</option><option value="waived">Prominutá</option></select></label>
        <label class="wide">Ověřený zdrojový doklad<select name="source_document_public_id" required><option value="">Vyberte doklad</option>${verifiedOptions(d)}</select></label>
        <label class="wide">Poznámky<textarea name="notes" rows="2" maxlength="10000"></textarea></label>
        <label class="wide">Důvod zápisu nebo opravy<textarea name="reason" rows="2" minlength="3" maxlength="1000" required></textarea></label>
        <div class="actions wide"><button type="submit">Zapsat splátku</button><button type="button" class="secondary" id="installmentCancel" hidden>Zrušit opravu</button></div>
    </form><p class="hint">Splátka je evidenční plán. Zápis nepotvrzuje úhradu a nevytváří platbu.</p>`;
    return form+cards;
}
function resetInstallmentForm(){
    const form=document.querySelector('#installmentForm');if(!form)return;
    form.reset();delete form.dataset.recordPublicId;delete form.dataset.recordRevision;
    form.elements.currency.value='';form.elements.total_amount.value='';form.elements.sequence_number.readOnly=false;
    document.querySelector('#installmentFormTitle').textContent='Zapsat jednotlivou splátku';
    form.querySelector('button[type=submit]').textContent='Zapsat splátku';
    document.querySelector('#installmentCancel').hidden=true;
}
function bindInstallments(){
    const form=document.querySelector('#installmentForm');if(!form)return;
    const scheduleByPublicId=new Map((current.installment_schedules||[]).map(x=>[x.public_id,x]));
    const syncSchedule=()=>{
        const schedule=scheduleByPublicId.get(form.elements.schedule_public_id.value);
        form.elements.currency.value=schedule?.currency||'';
        form.elements.sequence_number.max=schedule?.installment_count||'';
        form.elements.due_on.min=schedule?dayInput(schedule.starts_on):'';
        form.elements.due_on.max=schedule?.ends_on?dayInput(schedule.ends_on):'';
    };
    const syncTotal=()=>{
        const cents=['principal_amount','finance_charge_amount','other_amount'].reduce((sum,key)=>sum+Math.round(Number(form.elements[key].value||0)*100),0);
        form.elements.total_amount.value=(cents/100).toFixed(2);
    };
    form.elements.schedule_public_id.onchange=syncSchedule;
    for(const key of ['principal_amount','finance_charge_amount','other_amount'])form.elements[key].oninput=syncTotal;
    document.querySelector('#installmentCancel').onclick=resetInstallmentForm;
    document.querySelectorAll('[data-revise-installment]').forEach(button=>button.onclick=()=>{
        const item=(current.installments||[]).find(x=>x.public_id===button.dataset.reviseInstallment);
        if(!item)return;
        const schedule=(current.installment_schedules||[]).find(x=>Number(x.id)===Number(item.vehicle_installment_schedule_id));
        if(!schedule)return;
        resetInstallmentForm();
        form.dataset.recordPublicId=item.public_id;form.dataset.recordRevision=String(item.revision);
        form.elements.schedule_public_id.value=schedule.public_id;
        for(const key of ['sequence_number','due_on','principal_amount','finance_charge_amount','other_amount','status','notes']){
            const control=form.elements.namedItem(key);
            if(control)control.value=key==='due_on'?dayInput(item[key]):(item[key]??'');
        }
        syncSchedule();syncTotal();form.elements.sequence_number.readOnly=true;
        form.elements.source_document_public_id.value='';
        document.querySelector('#installmentFormTitle').textContent='Opravit splátku · revize '+item.revision;
        form.querySelector('button[type=submit]').textContent='Uložit novou verzi';
        document.querySelector('#installmentCancel').hidden=false;
        form.scrollIntoView({behavior:'smooth',block:'nearest'});
    });
    form.onsubmit=async event=>{
        event.preventDefault();syncTotal();if(!form.reportValidity())return;
        const id=current.vehicle.public_id,recordId=form.dataset.recordPublicId;
        const schedule=scheduleByPublicId.get(form.elements.schedule_public_id.value);
        if(!schedule){vehicleMessage.className='status-error';vehicleMessage.textContent='Vyberte aktuální kalendář.';return}
        const body={...values(form),expected_revision:current.vehicle.revision,expected_schedule_revision:Number(schedule.revision)};
        if(recordId)body.expected_installment_revision=Number(form.dataset.recordRevision);
        const url=`/api/v1/vehicle-registry-administration/${id}/installments`+(recordId?`/${recordId}/revisions`:'');
        const submit=form.querySelector('button[type=submit]');submit.disabled=true;
        try{
            await api(url,{method:recordId?'PUT':'POST',body:JSON.stringify(body)});
            await loadVehicles();await showVehicle(id);
            const panel=document.querySelector('[data-detail-subtab="installments"]');
            folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
            vehicleMessage.className='status-ok';vehicleMessage.textContent=recordId?'Oprava splátky byla uložena jako nová verze.':'Splátka byla zapsána.';
        }catch(error){
            if(error.status===409){
                await showVehicle(id);
                const panel=document.querySelector('[data-detail-subtab="installments"]');
                folder(panel.querySelector('.subtab-head'),panel.querySelector('.subtab-body'),true);
                vehicleMessage.textContent=`${error.message} Aktuální údaje byly znovu načteny.`;
            }else vehicleMessage.textContent=error.message;
            vehicleMessage.className='status-error';submit.disabled=false;
        }
    };
}
function detailHtml(d){const v=d.vehicle,verified=verifiedOptions(d),docs=(d.documents||[]).map(x=>record([field('Název',x.title),field('Typ',x.document_type),field('Ověření',x.verification_status),field('Revize',x.revision),field('Platnost do',date(x.valid_until)),`<div class="actions"><button data-review-document="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-state="verified">Ověřit</button><button data-review-document="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-state="rejected" class="secondary">Zamítnout</button></div>`])).join('');
const ownership=(d.ownerships||[]).map(x=>record([field('Vlastník',x.owner_type==='organization'?`Organizace #${x.owner_organization_id}`:x.external_owner_name||x.owner_type),field('Podíl',`${Number(x.ownership_share_basis_points)/100} %`),field('Ověření',x.verification_status),field('Revize',x.revision),`<div class="actions"><button data-review-ownership="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="verified">Ověřit</button><button data-review-ownership="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="rejected" class="secondary">Zamítnout</button></div>`])).join('');
const responsibilities=(d.responsibilities||[]).map(x=>record([field('Typ',x.responsibility_type),field('Strana',x.party_type==='organization'?`Organizace #${x.party_organization_id}`:x.external_party_name||x.party_type),field('Stav',x.status),field('Revize',x.revision),`<div class="actions"><button data-review-responsibility="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="ended">Ukončit</button><button data-review-responsibility="${esc(x.public_id)}" data-revision="${esc(x.revision)}" data-document="${esc(x.source_document_public_id)}" data-state="cancelled" class="secondary">Zrušit</button></div>`])).join('');
const completionFields=[['registration_number','Registrační značka','text'],['vin','VIN','text'],['manufacturer','Výrobce','text'],['model','Model','text'],['year','Rok','number'],['fuel_type','Palivo','fuel'],['mileage','Tachometr','number']];
const completion=completionFields.map(([key,label,type])=>`<label class="check-field"><input type="checkbox" name="selected" value="${key}"><span>${label}${type==='fuel'?`<select name="${key}" data-completion-fuel>${fuelTypes.map(([a,b])=>`<option value="${a}" ${a===(v[key]||'')?'selected':''}>${b}</option>`).join('')}</select>`:`<input name="${key}" type="${type}" value="${esc(v[key]||'')}">`}</span></label>`).join('');
return subtab('lifecycle','Životní cyklus',loc(v.lifecycle_status),lifecyclePanel(d))+subtab('compliance','Technické kontroly',`${new Set((d.compliance_records||[]).map(x=>x.record_uid)).size} záznamů`,compliancePanel(d))+subtab('insurance','Pojištění',`${new Set((d.insurance_policies||[]).map(x=>x.record_uid)).size} záznamů`,insurancePanel(d))+subtab('service','Servis',`${new Set((d.service_records||[]).map(x=>x.record_uid)).size} záznamů`,servicePanel(d))+subtab('incidents','Incidenty',`${new Set((d.incidents||[]).map(x=>x.record_uid)).size} záznamů`,incidentPanel(d))+subtab('financing','Financování',`${new Set((d.financing_agreements||[]).map(x=>x.financing_uid)).size} smluv`,financingPanel(d))+subtab('schedules','Splátkové kalendáře',`${new Set((d.installment_schedules||[]).map(x=>x.schedule_uid)).size} kalendářů`,schedulePanel(d))+subtab('installments','Jednotlivé splátky',`${new Set((d.installments||[]).map(x=>x.installment_uid)).size} splátek`,installmentPanel(d))+subtab('documents','Dokumenty',`${(d.documents||[]).length} dokladů`,`<form id="documentForm" class="grid"><input name="document_type" value="registration_certificate" placeholder="Typ dokladu" required><input name="title" placeholder="Název" required><input name="storage_reference" placeholder="Odkaz na uložení" required><input name="valid_from" type="date"><input name="valid_until" type="date"><select name="access_classification"><option value="operational">Provozní</option><option value="restricted">Omezený</option></select><input class="wide" name="reason" value="Doplnění dostupného dokladu k vozidlu." required><button>Registrovat doklad</button></form>${docs||'<p class="hint">Žádné doklady.</p>'}`)+subtab('ownership','Vlastnictví',`${(d.ownerships||[]).length} záznamů`,`<form id="ownershipForm" class="grid"><select name="owner_type"><option value="organization">Organizace</option><option value="external_party">Externí vlastník</option></select><input name="owner_organization_id" value="${esc(organization)}" placeholder="ID organizace"><input name="external_owner_name" placeholder="Externí vlastník"><input name="ownership_share_basis_points" type="number" value="10000"><input name="valid_from" type="date" required><input name="acquisition_basis" value="purchase"><select name="source_document_public_id" required><option value="">Ověřený zdrojový doklad</option>${verified}</select><input class="wide" name="reason" value="Evidence vlastnictví podle ověřeného dokladu." required><button>Registrovat vlastnictví</button></form>${ownership||'<p class="hint">Žádné vlastnické záznamy.</p>'}`)+subtab('responsibilities','Odpovědnosti',`${(d.responsibilities||[]).filter(x=>x.status==='active').length} aktivní`,`<form id="responsibilityForm" class="grid"><select name="responsibility_type"><option value="registered_operator">Provozovatel</option><option value="operational_organization">Provozní organizace</option><option value="custodian">Správce</option><option value="authorized_user">Oprávněný uživatel</option><option value="default_driver">Výchozí řidič</option></select><select name="party_type"><option value="organization">Organizace</option><option value="external_party">Externí strana</option></select><input name="party_organization_id" value="${esc(organization)}"><input name="external_party_name" placeholder="Externí strana"><input name="valid_from" type="date" required><select name="source_document_public_id" required><option value="">Ověřený zdrojový doklad</option>${verified}</select><input class="wide" name="reason" value="Evidence odpovědnosti podle ověřeného dokladu." required><button>Registrovat odpovědnost</button></form>${responsibilities||'<p class="hint">Žádné odpovědnosti.</p>'}`)+subtab('completion','Doplnění údajů',`${(d.completeness?.pending_count||0)} oblastí čeká`,`<form id="completionForm" class="grid">${completion}<input class="wide" name="reason" value="Doplnění nebo oprava údajů vozidla." required><button>Uložit vybrané údaje</button></form>`)+subtab('statuses','Stav doplnění',`${(d.completeness?.pending_count||0)} čeká`,(d.field_statuses||[]).map(x=>record([field('Oblast',x.field_key),field('Stav',x.status),field('Revize',x.revision),field('Důvod',x.reason)])).join(''))+subtab('history','Historie a auditní stopa',`${(d.events||[]).length} událostí`,history(d))}
async function showVehicle(id){try{const d=await api(`/api/v1/vehicle-registry-administration/${id}`);current=d;const v=d.vehicle;vehicleCard.hidden=false;vehicleCardTitle.textContent=`${text(v.registration_number)} · ${text(v.manufacturer)} ${text(v.model)}`;vehicleSummary.innerHTML=`<div class="vehicle-overview">${field('VIN',v.vin)}${field('Stav',v.lifecycle_status)}${field('Rok',v.year)}${field('Tachometr',v.mileage)}${field('Revize',v.revision)}</div>`;vehicleSubtabs.innerHTML=detailHtml(d);bindSubtabs();bindForms();vehicleCard.scrollIntoView({behavior:'smooth',block:'start'})}catch(e){vehicleMessage.textContent=e.message}}
async function mutate(path,method,payload){try{await api(path,{method,body:JSON.stringify(payload)});await loadVehicles();await showVehicle(current.vehicle.public_id)}catch(e){vehicleMessage.textContent=e.message;vehicleMessage.className='status-error'}}
function bindForms(){const lifecycleForm=document.querySelector('#lifecycleForm');if(lifecycleForm)lifecycleForm.onsubmit=submitLifecycle;bindCompliance();bindInsurance();bindService();bindIncident();bindFinancing();bindSchedules();bindInstallments();documentForm.onsubmit=e=>{e.preventDefault();mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/documents`,'POST',{...values(e.target),expected_revision:current.vehicle.revision})};ownershipForm.onsubmit=e=>{e.preventDefault();mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/ownerships`,'POST',{...values(e.target),expected_revision:current.vehicle.revision})};responsibilityForm.onsubmit=e=>{e.preventDefault();mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/responsibilities`,'POST',{...values(e.target),expected_revision:current.vehicle.revision})};completionForm.onsubmit=e=>{e.preventDefault();const selected=[...e.target.querySelectorAll('[name=selected]:checked')].map(x=>x.value),all=values(e.target),fields=Object.fromEntries(selected.map(k=>[k,all[k]??null]));if(!selected.length){vehicleMessage.textContent='Vyberte alespoň jedno pole.';return}mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}`,'PATCH',{expected_revision:current.vehicle.revision,fields,reason:all.reason})};document.querySelectorAll('[data-review-document]').forEach(b=>b.onclick=()=>mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/documents/${b.dataset.reviewDocument}/verification`,'PUT',{expected_revision:current.vehicle.revision,expected_document_revision:Number(b.dataset.revision),verification_status:b.dataset.state,reason:`${b.dataset.state==='verified'?'Ověření':'Zamítnutí'} dokumentu.`}));document.querySelectorAll('[data-review-ownership]').forEach(b=>b.onclick=()=>mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/ownerships/${b.dataset.reviewOwnership}/verification`,'PUT',{expected_revision:current.vehicle.revision,expected_ownership_revision:Number(b.dataset.revision),source_document_public_id:b.dataset.document,verification_status:b.dataset.state,reason:`${b.dataset.state==='verified'?'Ověření':'Zamítnutí'} vlastnictví.`}));document.querySelectorAll('[data-review-responsibility]').forEach(b=>b.onclick=()=>mutate(`/api/v1/vehicle-registry-administration/${current.vehicle.public_id}/responsibilities/${b.dataset.reviewResponsibility}/status`,'PUT',{expected_revision:current.vehicle.revision,expected_responsibility_revision:Number(b.dataset.revision),source_document_public_id:b.dataset.document,status:b.dataset.state,reason:`${b.dataset.state==='ended'?'Ukončení':'Zrušení'} odpovědnosti.`}))}
createForm.onsubmit=async e=>{e.preventDefault();try{const d=await api('/api/v1/vehicle-registry-administration',{method:'POST',body:JSON.stringify(values(e.target))});createMessage.textContent='Vozidlo bylo založeno.';e.target.reset();await loadVehicles();folder(document.querySelector('[data-registry-folder=create] .folder-head'),document.querySelector('[data-registry-folder=create] .folder-body'),false);folder(document.querySelector('[data-registry-folder=manage] .folder-head'),document.querySelector('[data-registry-folder=manage] .folder-body'),true);await showVehicle(d.vehicle.public_id)}catch(err){createMessage.textContent=err.message}};
vehicleFilter.onclick=loadVehicles;vehicleCardClose.onclick=()=>vehicleCard.hidden=true;loadVehicles();
</script></body></html>
