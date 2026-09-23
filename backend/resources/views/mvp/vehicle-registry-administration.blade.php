<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vozidla | TMS</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; color: #0b2345; background: #f3f6fb; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f6fb; }
        .page-shell { max-width: 1500px; margin: 0 auto; padding: 24px; }
        a { color: #123f7a; font-weight: 700; text-decoration: none; }
        h1, h2, h3 { color: #071f42; }
        .panel { margin-top: 18px; padding: 18px; border: 1px solid #cfdaea; border-radius: 14px; background: #fff; }
        .form-grid { display: grid; grid-template-columns: minmax(240px, 2fr) minmax(210px, 1fr) auto; gap: 12px; align-items: end; }
        label { display: grid; gap: 6px; font-size: 14px; font-weight: 700; }
        input, select, button { min-height: 42px; padding: 9px 12px; border: 1px solid #c6d3e5; border-radius: 9px; font: inherit; }
        button { border-color: #173f79; background: #173f79; color: #fff; font-weight: 700; cursor: pointer; }
        table { width: 100%; margin-top: 16px; border-collapse: collapse; }
        th, td { padding: 11px; border-bottom: 1px solid #dce4ef; text-align: left; vertical-align: top; }
        th { background: #f5f7fa; font-size: 13px; }
        .vehicle-overview { display: grid; grid-template-columns: repeat(5, minmax(130px, 1fr)); gap: 10px; margin: 14px 0 20px; }
        .overview-item, .record-field { padding: 11px 12px; border-radius: 9px; background: #f5f7fa; }
        .overview-item span, .record-field dt { display: block; margin-bottom: 5px; color: #5b6c84; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .overview-item strong, .record-field dd { margin: 0; color: #0b2345; font-weight: 700; overflow-wrap: anywhere; }
        .detail-section { margin-top: 18px; padding-top: 15px; border-top: 1px solid #dce4ef; }
        .detail-section h3 { display: flex; align-items: center; gap: 8px; margin: 0 0 10px; }
        .count-badge { display: inline-flex; min-width: 25px; height: 25px; align-items: center; justify-content: center; border-radius: 999px; background: #e5edf8; color: #123f7a; font-size: 12px; }
        .record-grid { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 9px; margin: 0; }
        .record-card { margin-top: 9px; padding: 12px; border: 1px solid #dce4ef; border-radius: 10px; }
        .record-empty { margin: 0; padding: 12px; border-radius: 9px; background: #f5f7fa; color: #5b6c84; }
        .record-wide { grid-column: span 2; }
        #vehicleCardClose { float: right; background: #e8eef7; color: #123f7a; border-color: #e8eef7; }
        @media (max-width: 800px) { .form-grid, .vehicle-overview, .record-grid { grid-template-columns: 1fr; } .record-wide { grid-column: auto; } .page-shell { padding: 14px; } table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
<div class="page-shell" data-testid="vehicle-registry-administration">
    <a href="/settings">&larr; Nastaven&#237;</a>
    <h1>Registr vozidel</h1>
    <p>Organiza&#269;n&#283; &#345;&#237;zen&#253; p&#345;ehled vozidel, vlastnictv&#237;, odpov&#283;dnosti a provozn&#237; slo&#382;ky.</p>
    <div class="panel"><div class="form-grid">
        <label>Hledat<input id="vehicleSearch" placeholder="RZ, VIN, v&#253;robce nebo model"></label>
        <label>Stav<select id="vehicleStatus"><option value="">V&#353;echny</option><option value="active">Aktivn&#237;</option><option value="temporarily_inactive">Do&#269;asn&#283; neaktivn&#237;</option><option value="restricted">Omezen&#233;</option><option value="disposed">Vy&#345;azen&#233;</option><option value="written_off">Odepsan&#233;</option><option value="archived">Archivovan&#233;</option></select></label>
        <button id="vehicleFilter">Filtrovat</button>
    </div><p id="vehicleMessage"></p>
    <table><thead><tr><th>Vozidlo</th><th>VIN</th><th>Palivo</th><th>Tachometr</th><th>Stav</th><th>Revize</th><th></th></tr></thead><tbody id="vehicleRows"></tbody></table></div>
    <section id="vehicleCard" class="panel" hidden><button id="vehicleCardClose">Zav&#345;&#237;t</button><h2 id="vehicleCardTitle">Karta vozidla</h2><div id="vehicleSummary"></div><div id="vehicleSections"></div></section>
</div>
<script>
const token=sessionStorage.getItem('tms_mvp_token');const organization=sessionStorage.getItem('tms_mvp_organization_id');
const esc=v=>String(v??'\u2014').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
async function api(path){const r=await fetch(path,{headers:{Accept:'application/json',Authorization:`Bearer ${token}`,'X-Organization-Id':organization}});const j=await r.json().catch(()=>({}));if(!r.ok)throw new Error(j.message||`HTTP ${r.status}`);return j}
async function loadVehicles(){try{const q=new URLSearchParams({search:document.querySelector('#vehicleSearch').value,lifecycle_status:document.querySelector('#vehicleStatus').value});const d=await api(`/api/v1/vehicle-registry-administration?${q}`);document.querySelector('#vehicleRows').innerHTML=(d.items||[]).map(v=>`<tr><td><b>${esc(v.registration_number)}</b><br>${esc(v.manufacturer)} ${esc(v.model)}</td><td>${esc(v.vin)}</td><td>${esc(v.fuel_type)}</td><td>${esc(v.mileage)} ${esc(v.odometer_unit)}</td><td>${esc(localizedValue(v.lifecycle_status))}</td><td>${esc(v.revision)}</td><td><button data-vehicle="${esc(v.public_id)}">Detail</button></td></tr>`).join('')||'<tr><td colspan="7">\u017d\u00e1dn\u00e1 vozidla.</td></tr>';document.querySelectorAll('[data-vehicle]').forEach(b=>b.onclick=()=>showVehicle(b.dataset.vehicle));document.querySelector('#vehicleMessage').textContent=`Celkem ${d.pagination?.total||0} vozidel`;}catch(e){document.querySelector('#vehicleMessage').textContent=e.message}}
const dateValue=value=>{if(!value)return '\u2014';const date=new Date(value);return Number.isNaN(date.getTime())?value:date.toLocaleDateString('cs-CZ')};
const amountValue=(value,currency)=>value===null||value===undefined?'\u2014':new Intl.NumberFormat('cs-CZ',{style:'currency',currency:currency||'CZK'}).format(Number(value));
const percentValue=value=>value===null||value===undefined?'\u2014':`${new Intl.NumberFormat('cs-CZ',{maximumFractionDigits:2}).format(Number(value)/100)} %`;
const textValue=value=>value===null||value===undefined||value===''?'\u2014':String(value).replaceAll('_',' ');
const localizedValues=Object.freeze({
active:'Aktivn\u00ed',restricted:'Omezen\u00e9',inactive:'Neaktivn\u00ed',archived:'Archivovan\u00e9',
verified:'Ov\u011b\u0159eno',pending:'\u010cek\u00e1 na ov\u011b\u0159en\u00ed',rejected:'Zam\u00edtnuto',
operational_organization:'Provozn\u00ed organizace',organization:'Organizace',contract:'Smlouva',
registration_certificate:'Technick\u00fd pr\u016fkaz',operational:'Provozn\u00ed',
technical_inspection:'Technick\u00e1 kontrola',valid:'Platn\u00e9',expired:'Pro\u0161l\u00e9',passed:'Vyhov\u011blo',failed:'Nevyhov\u011blo',
compulsory_liability:'Povinn\u00e9 ru\u010den\u00ed',comprehensive:'Havarijn\u00ed poji\u0161t\u011bn\u00ed',
scheduled:'Pl\u00e1novan\u00fd',completed:'Dokon\u010deno',open:'Otev\u0159en\u00e9',cancelled:'Zru\u0161eno',
damage:'\u0160koda',accident:'Nehoda',investigating:'V \u0161et\u0159en\u00ed',minor:'Drobn\u00e1',major:'Z\u00e1va\u017en\u00e1',critical:'Kritick\u00e1',
operating_lease:'Operativn\u00ed leasing',finance_lease:'Finan\u010dn\u00ed leasing',loan:'\u00dav\u011br',
vehicle_registered:'Vozidlo zaregistrov\u00e1no'
});
const localizedValue=value=>{const normalized=textValue(value);return Object.prototype.hasOwnProperty.call(localizedValues,String(value))?localizedValues[String(value)]:normalized};
const organizationValue=value=>value?`Organizace #${value}`:'\u2014';
const field=(label,value,wide=false)=>`<div class="record-field${wide?' record-wide':''}"><dt>${esc(label)}</dt><dd>${esc(localizedValue(value))}</dd></div>`;
const record=fields=>`<article class="record-card"><dl class="record-grid">${fields.join('')}</dl></article>`;
const section=(title,items,renderer)=>`<section class="detail-section" data-detail-section><h3>${esc(title)} <span class="count-badge">${items.length}</span></h3>${items.length?items.map(renderer).join(''):'<p class="record-empty">\u017d\u00e1dn\u00e9 z\u00e1znamy.</p>'}</section>`;
async function showVehicle(id){try{const d=await api(`/api/v1/vehicle-registry-administration/${id}`),v=d.vehicle;document.querySelector('#vehicleCard').hidden=false;document.querySelector('#vehicleCardTitle').textContent=`${v.registration_number} \u00b7 ${v.manufacturer} ${v.model}`;document.querySelector('#vehicleSummary').innerHTML=`<div class="vehicle-overview"><div class="overview-item"><span>VIN</span><strong>${esc(v.vin)}</strong></div><div class="overview-item"><span>Stav</span><strong>${esc(localizedValue(v.lifecycle_status))}</strong></div><div class="overview-item"><span>Rok</span><strong>${esc(textValue(v.year))}</strong></div><div class="overview-item"><span>Tachometr</span><strong>${esc(textValue(v.mileage))} ${esc(textValue(v.odometer_unit))}</strong></div><div class="overview-item"><span>Revize</span><strong>${esc(v.revision)}</strong></div></div>`;
const sections=[
section('Vlastnictv\u00ed',d.ownerships||[],x=>record([field('Vlastn\u00edk',x.owner_type==='organization'?organizationValue(x.owner_organization_id):x.external_owner_name||x.owner_type),field('Pod\u00edl',percentValue(x.ownership_share_basis_points)),field('Platnost od',dateValue(x.valid_from)),field('Platnost do',dateValue(x.valid_until)),field('Ov\u011b\u0159en\u00ed',x.verification_status),field('D\u016fvod',x.change_reason,true)])),
section('Odpov\u011bdnosti',d.responsibilities||[],x=>record([field('Typ odpov\u011bdnosti',x.responsibility_type),field('Odpov\u011bdn\u00e1 strana',x.party_type==='organization'?organizationValue(x.party_organization_id):x.external_party_name||x.party_type),field('Stav',x.status),field('Zdroj',x.source),field('Platnost od',dateValue(x.valid_from)),field('Platnost do',dateValue(x.valid_until)),field('D\u016fvod',x.reason,true)])),
section('Dokumenty',d.documents||[],x=>record([field('Dokument',x.title,true),field('Typ',x.document_type),field('Ov\u011b\u0159en\u00ed',x.verification_status),field('Vyd\u00e1no',dateValue(x.issue_date)),field('Platnost od',dateValue(x.valid_from)),field('Platnost do',dateValue(x.valid_until)),field('P\u0159\u00edstup',x.access_classification)])),
section('Kontroly',d.compliance_records||[],x=>record([field('Typ kontroly',x.compliance_type),field('Identifik\u00e1tor',x.identifier),field('Stav',x.status),field('V\u00fdsledek',x.result),field('Kontrola dne',dateValue(x.inspected_at)),field('Platnost do',dateValue(x.valid_until)),field('Tachometr',x.odometer===null?'\u2014':`${x.odometer} km`),field('Vydal',x.issuer_name)])),
section('Poji\u0161t\u011bn\u00ed',d.insurance_policies||[],x=>record([field('Poji\u0161\u0165ovna',x.insurer_name,true),field('\u010c\u00edslo smlouvy',x.policy_number),field('Typ',x.policy_type),field('Stav',x.status),field('Platnost od',dateValue(x.valid_from)),field('Platnost do',dateValue(x.valid_until)),field('Limit',amountValue(x.coverage_amount,x.currency)),field('Spolu\u00fa\u010dast',amountValue(x.deductible_amount,x.currency))])),
section('Servis',d.service_records||[],x=>record([field('Servis',x.summary,true),field('Typ',x.service_type),field('Stav',x.status),field('Zah\u00e1jen\u00ed',dateValue(x.opened_at)),field('Dokon\u010den\u00ed',dateValue(x.completed_at)),field('Dal\u0161\u00ed servis',dateValue(x.next_service_on)),field('Tachometr',x.odometer===null?'\u2014':`${x.odometer} km`),field('Poskytovatel',x.external_provider_name||organizationValue(x.provider_organization_id)),field('Popis',x.details,true)])),
section('Pojistn\u00e9 ud\u00e1losti a incidenty',d.incidents||[],x=>record([field('Typ',x.incident_type),field('Stav',x.status),field('Z\u00e1va\u017enost',x.severity),field('Datum ud\u00e1losti',dateValue(x.occurred_at)),field('M\u00edsto',x.location),field('Pojistn\u00e1 ud\u00e1lost',x.insurance_claim_reference),field('Policejn\u00ed reference',x.police_reference),field('Popis',x.description,true)])),
section('Financov\u00e1n\u00ed',d.financing_agreements||[],x=>record([field('Typ',x.financing_type),field('Financuj\u00edc\u00ed strana',x.external_financier_name||organizationValue(x.financier_organization_id)),field('\u010c\u00edslo smlouvy',x.agreement_number),field('Stav',x.status),field('Platnost od',dateValue(x.effective_from)),field('Platnost do',dateValue(x.effective_until)),field('Celkov\u00e1 hodnota',amountValue(x.total_amount,x.currency)),field('Po\u010d\u00e1te\u010dn\u00ed platba',amountValue(x.initial_payment_amount,x.currency)),field('Z\u016fstatkov\u00e1 hodnota',amountValue(x.residual_value_amount,x.currency))])),
section('Audit',d.events||[],x=>record([field('Ud\u00e1lost',x.event_type),field('Datum',dateValue(x.occurred_at)),field('Revize vozidla',x.vehicle_revision),field('U\u017eivatel',x.actor_user_id?`U\u017eivatel #${x.actor_user_id}`:'\u2014'),field('D\u016fvod',x.reason,true)]))
];document.querySelector('#vehicleSections').innerHTML=sections.join('');document.querySelector('#vehicleCard').scrollIntoView({behavior:'smooth',block:'start'})}catch(e){document.querySelector('#vehicleMessage').textContent=e.message}}
document.querySelector('#vehicleFilter').onclick=loadVehicles;document.querySelector('#vehicleCardClose').onclick=()=>document.querySelector('#vehicleCard').hidden=true;loadVehicles();
</script>
</body>
</html>