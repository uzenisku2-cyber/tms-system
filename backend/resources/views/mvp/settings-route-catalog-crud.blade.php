<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Trasy · TMS</title>
<style>
body{margin:0;background:#f8fafc;color:#0f172a;font:14px system-ui,-apple-system,"Segoe UI",sans-serif}main{max-width:1120px;margin:auto;padding:32px 18px 60px}a{color:#2563eb;text-decoration:none}.crumb{color:#64748b;margin-bottom:18px}.card,.route{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-top:18px}.muted{color:#64748b}.notice{padding:11px 13px;border-radius:9px;background:#f1f5f9;margin:14px 0}.error{background:#fef2f2;color:#991b1b}.success{background:#f0fdf4;color:#166534}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.wide{grid-column:1/-1}label{display:block;font-weight:650;margin:0 0 5px}input,textarea{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:8px;padding:9px;font:inherit}textarea{min-height:70px}button{border:0;border-radius:8px;padding:9px 13px;background:#2563eb;color:#fff;font-weight:650;cursor:pointer}.secondary{background:#475569}.head{display:flex;justify-content:space-between;gap:16px}.badge{font-size:12px;font-weight:700}.uid{font:12px ui-monospace,monospace;color:#64748b;word-break:break-all}details{margin-top:12px}summary{cursor:pointer;font-weight:650}[hidden]{display:none!important}@media(max-width:700px){.grid{grid-template-columns:1fr}.head{display:block}}
</style>
</head>
<body>
<main>
<div class="crumb"><a href="/settings">Nastavení</a> › <a href="/settings/catalogs">Číselníky</a> › Trasy</div>
<h1>Trasy</h1>
<p class="muted">Stabilní identita trasy je oddělená od historicky platných údajů. Číslo, název a oblast jsou historicky verzované údaje. Úprava těchto hodnot nikdy nepřepisuje starou verzi trasy.</p>
<div id="status" class="notice">Ověřuji přihlášení a oprávnění…</div>

<section id="createPanel" class="card" hidden>
<h2>Přidat trasu</h2>
<form id="createForm">
<div class="grid">
<div><label>Číslo trasy</label><input name="route_number" maxlength="64" required></div>
<div><label>Název</label><input name="route_name" maxlength="255" required></div>
<div><label>Oblast</label><input name="area" maxlength="255"></div>
<div><label>Platnost od</label><input name="valid_from" type="date" required></div>
<div class="wide"><label>Poznámka</label><textarea name="change_note"></textarea></div>
</div>
<p><button type="submit">Přidat trasu</button></p>
</form>
</section>

<section class="card">
<h2>Seznam tras</h2>
<div id="readOnly" class="notice" hidden>Číselník je otevřen pouze pro čtení.</div>
<p id="empty" class="muted" hidden>Zatím nejsou založené žádné trasy.</p>
<div id="list"></div>
</section>
</main>

<script>
(() => {
'use strict';
const tokenKey='tms_mvp_token';
const base='/api/v1/settings/catalogs/routes';
const token=sessionStorage.getItem(tokenKey)||'';
const status=document.getElementById('status');
const createPanel=document.getElementById('createPanel');
const createForm=document.getElementById('createForm');
const readOnly=document.getElementById('readOnly');
const empty=document.getElementById('empty');
const list=document.getElementById('list');
let canManage=false;

const esc=v=>String(v??'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;");
const date=v=>v?String(v).slice(0,10):'—';
const current=r=>r.current_version||(r.versions||[]).find(v=>!v.valid_to)||(r.versions||[])[0]||null;
function message(text,type=''){status.textContent=text;status.className='notice'+(type?' '+type:'')}
function headers(json=false){const h={Accept:'application/json'};if(token)h.Authorization=`Bearer ${token}`;if(json)h['Content-Type']='application/json';return h}
async function api(path='',options={}){
 const method=(options.method||'GET').toUpperCase();
 const response=await fetch(base+path,{...options,headers:{...headers(method!=='GET'),...(options.headers||{})}});
 if(response.status===401)throw new Error('AUTH');
 if(response.status===403)throw new Error('FORBIDDEN');
 if(!response.ok){let detail=`HTTP ${response.status}`;try{const p=await response.json();detail=p.message||Object.values(p.errors||{}).flat()[0]||detail}catch(_){}throw new Error(detail)}
 return (response.headers.get('content-type')||'').includes('application/json')?response.json():null;
}
function fail(e){
 if(e.message==='AUTH'){message('Přihlášení není dostupné v této kartě nebo vypršelo. Vraťte se na /app, přihlaste se a otevřete Nastavení ve stejné kartě.','error');createPanel.hidden=true;readOnly.hidden=true;return}
 if(e.message==='FORBIDDEN'){message('Pro tuto operaci nemáte oprávnění settings.catalogs.manage.','error');return}
 message(e.message||'Operaci se nepodařilo dokončit.','error');
}
function routeHtml(r){
 const v=current(r);if(!v)return'';
 const history=(r.versions||[]).map(x=>`<li><strong>${esc(x.route_number)} ${esc(x.route_name)}</strong> · ${esc(x.area||'bez oblasti')} · ${date(x.valid_from)}–${x.valid_to?date(x.valid_to):'dosud'}</li>`).join('');
 const manage=canManage?`
 <details><summary>Uložit novou verzi</summary>
 <form class="versionForm" data-id="${r.id}"><div class="grid">
 <div><label>Číslo trasy</label><input name="route_number" value="${esc(v.route_number)}" required></div>
 <div><label>Název</label><input name="route_name" value="${esc(v.route_name)}" required></div>
 <div><label>Oblast</label><input name="area" value="${esc(v.area||'')}"></div>
 <div><label>Platnost od</label><input name="valid_from" type="date" required></div>
 <div class="wide"><label>Poznámka</label><textarea name="change_note"></textarea></div>
 </div><p><button type="submit">Uložit novou verzi</button></p></form></details>
 <p><button class="secondary toggle" data-id="${r.id}" data-active="${r.active?'1':'0'}">${r.active?'Deaktivovat trasu':'Aktivovat trasu'}</button></p>`:'';
 return `<article class="route"><div class="head"><div><h3>${esc(v.route_number)} ${esc(v.route_name)}</h3><div class="muted">${esc(v.area||'Bez oblasti')}</div><div class="uid">Interní ID: ${esc(r.route_uid)}</div></div><span class="badge">${r.active?'Aktivní':'Neaktivní'}</span></div><p class="muted">Aktuální verze od ${date(v.valid_from)}.</p><details><summary>Historie verzí (${(r.versions||[]).length})</summary><ul>${history}</ul></details>${manage}</article>`;
}
function bind(){
 document.querySelectorAll('.versionForm').forEach(form=>form.addEventListener('submit',async e=>{e.preventDefault();const b=form.querySelector('button');b.disabled=true;try{await api('/'+form.dataset.id,{method:'PATCH',body:JSON.stringify(Object.fromEntries(new FormData(form).entries()))});message('Nová verze trasy byla uložena.','success');await load()}catch(x){fail(x)}finally{b.disabled=false}}));
 document.querySelectorAll('.toggle').forEach(b=>b.addEventListener('click',async()=>{b.disabled=true;try{await api('/'+b.dataset.id+'/active',{method:'PATCH',body:JSON.stringify({active:b.dataset.active!=='1'})});message('Stav trasy byl změněn.','success');await load()}catch(x){fail(x)}finally{b.disabled=false}}));
}
async function load(){
 if(!token){fail(new Error('AUTH'));return}
 try{const p=await api();const routes=p?.data||[];canManage=Boolean(p?.meta?.can_manage);createPanel.hidden=!canManage;readOnly.hidden=canManage;empty.hidden=routes.length!==0;list.innerHTML=routes.map(routeHtml).join('');bind();message(canManage?'Přihlášení a oprávnění k úpravě číselníku jsou aktivní.':'Číselník byl načten v režimu pouze pro čtení.',canManage?'success':'')}catch(e){fail(e)}
}
createForm.addEventListener('submit',async e=>{e.preventDefault();const b=createForm.querySelector('button');b.disabled=true;try{await api('',{method:'POST',body:JSON.stringify(Object.fromEntries(new FormData(createForm).entries()))});createForm.reset();message('Trasa byla založena.','success');await load()}catch(x){fail(x)}finally{b.disabled=false}});
load();
})();
</script>
</body>
</html>
