<!doctype html>
<html lang="cs">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lidé a přístupy – DRAYVIA</title>
<style>
:root{font-family:system-ui,-apple-system,Segoe UI,sans-serif;color:#172b45;background:#f3f6fa}
body{margin:0}.wrap{max-width:980px;margin:auto;padding:28px 18px}
h1{margin:0 0 8px}p{line-height:1.5}.panel{background:white;border:1px solid #dbe3eb;border-radius:14px;padding:22px;margin:20px 0;box-shadow:0 4px 16px #15304d0b}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}label{display:block;font-weight:600}input,select{display:block;width:100%;box-sizing:border-box;margin-top:6px;padding:10px;border:1px solid #aab9c9;border-radius:8px;font:inherit}
.role{padding:14px;border:1px solid #dbe3eb;border-radius:9px;margin:10px 0}.role input{display:inline;width:auto;margin-right:8px}.role small{display:block;margin:6px 0 0 26px;font-weight:400}
button,.button{border:0;border-radius:8px;background:#184b82;color:white;padding:11px 16px;cursor:pointer;font:inherit;text-decoration:none;display:inline-block;margin:8px 8px 0 0}button.secondary,.button.secondary{background:#e5ebf2;color:#17314d}button:disabled{opacity:.55;cursor:not-allowed}.notice{padding:12px;border-radius:8px;background:#edf5fe;margin-top:15px}.error{background:#fff1f1;color:#8d2222}
table{width:100%;border-collapse:collapse}th,td{text-align:left;border-bottom:1px solid #e2e8ef;padding:9px}th{font-size:.85rem;color:#536780}.scroll{overflow:auto}
</style>
</head>
<body><main class="wrap">
<a class="button secondary" href="/app">← Zpět do aplikace</a>
<h1>Lidé a přístupy</h1><p>Účty a role se spravují pro zvolenou organizaci. Řidič uvidí své údaje, dispečer provoz ve svém rozsahu. Finanční oprávnění se při zakládání těchto rolí nepřidělují.</p>
<div id="status" role="status"></div>
<section class="panel"><h2>Vytvořit uživatele</h2>
<form id="person">
<div id="step1"><h3>1 · Osoba a organizace</h3><div class="grid">
<label>Jméno<input name="first_name" maxlength="100" required></label>
<label>Příjmení<input name="last_name" maxlength="100" required></label>
<label>E-mail<input name="email" type="email" maxlength="255" required></label>
<label>Organizace<select name="organization" id="organization"></select></label>
</div><button type="button" id="next1">Pokračovat</button></div>
<div id="step2" hidden><h3>2 · Role</h3>
<label class="role"><input type="checkbox" name="roles" value="driver">Řidič<small>Vlastní výkazy a dostupnost; bez fakturace dopravce.</small></label>
<label class="role"><input type="checkbox" name="roles" value="dispatcher">Dispečer<small>Provozní kontrola a potvrzování dostupnosti.</small></label>
<label class="role"><input type="checkbox" name="roles" value="carrier-admin">Správce dopravce<small>Správa lidí a provozu své organizace; bez fakturace zákazníkům.</small></label>
<button type="button" class="secondary" id="back2">Zpět</button><button type="button" id="next2">Zkontrolovat</button></div>
<div id="step3" hidden><h3>3 · Kontrola účtu</h3><p id="review"></p>
<p>Po vytvoření se jednou zobrazí počáteční heslo. Předejte je uživateli bezpečným způsobem.</p>
<button type="button" class="secondary" id="back3">Zpět</button><button type="submit" id="send">Vytvořit účet</button></div>
</form></section>
<section class="panel" id="credentials" hidden><h2>Nový účet</h2><p>Tyto údaje se zobrazují pouze teď. Před zavřením stránky si heslo bezpečně předejte.</p><p id="createdEmail"></p><label>Počáteční heslo<input id="createdPassword" readonly autocomplete="off"></label></section>
<section class="panel"><h2>Lidé ve zvolené organizaci</h2><div class="scroll"><table><thead><tr><th>Jméno</th><th>E-mail</th><th>Role</th><th>Stav</th><th>Správa</th></tr></thead><tbody id="members"></tbody></table></div>
</section>
<section class="panel" id="roleEditor" hidden><h2>Role uživatele <span id="rolePerson"></span></h2>
<p>Úprava rolí nemění účet ani heslo. Řidič potřebuje profil přiřazený k této organizaci.</p>
<form id="roleForm">
<label class="role"><input type="checkbox" name="editRoles" value="driver">Řidič</label>
<label class="role"><input type="checkbox" name="editRoles" value="dispatcher">Dispečer</label>
<label class="role"><input type="checkbox" name="editRoles" value="carrier-admin">Správce dopravce</label>
<button type="button" class="secondary" id="cancelRoles">Zrušit</button><button type="submit" id="saveRoles">Uložit role</button>
</form>
</section>
</main>
<script>
(()=>{'use strict';
const token=sessionStorage.getItem('tms_mvp_token')||'';
const ownOrganization=sessionStorage.getItem('tms_mvp_organization_id')||'';
const form=document.getElementById('person');const status=document.getElementById('status');
const organization=document.getElementById('organization');
if(!token||!ownOrganization){status.textContent='Nejdříve se přihlaste v aplikaci.';form.hidden=true;return;}
const headers={'Authorization':`Bearer ${token}`,'X-Organization-ID':ownOrganization,'Accept':'application/json','Content-Type':'application/json'};
const request=async(path,options={})=>{const response=await fetch('/api/v1/'+path,{...options,headers});const body=await response.json();if(!response.ok)throw new Error(body.message||Object.values(body.errors||{})[0]?.[0]||'Požadavek se nezdařil.');return body.data;};
const message=(value,error=false)=>{status.className='notice'+(error?' error':'');status.textContent=value;};
const row=(body,values)=>{const tr=document.createElement('tr');values.forEach(value=>{const td=document.createElement('td');td.textContent=String(value??'');tr.append(td)});body.append(tr);return tr};
const show=n=>{[1,2,3].forEach(i=>document.getElementById('step'+i).hidden=i!==n)};
const roleEditor=document.getElementById('roleEditor');const roleForm=document.getElementById('roleForm');let editedUser=null;
const membersPath=()=>organization.value===ownOrganization?'people':`people/carriers/${encodeURIComponent(organization.value)}`;
const loadMembers=async()=>{roleEditor.hidden=true;const data=await request(membersPath());const members=document.getElementById('members');members.replaceChildren();(data.members||[]).forEach(person=>{
const tr=row(members,[person.name,person.email,(person.roles||[]).join(', '),person.status]);
const cell=document.createElement('td');const roles=person.roles||[];
if(roles.every(role=>['driver','dispatcher','carrier-admin'].includes(role))){const button=document.createElement('button');button.type='button';button.className='secondary';button.textContent='Upravit role';button.onclick=()=>{editedUser=person.id;document.getElementById('rolePerson').textContent=person.name;roleForm.querySelectorAll('[name=editRoles]').forEach(input=>{input.checked=roles.includes(input.value);input.disabled=input.value==='driver'&&!person.driver_id});roleEditor.hidden=false;roleEditor.scrollIntoView({block:'nearest'})};cell.append(button)}tr.append(cell)})};
const load=async()=>{try{organization.replaceChildren();const own=document.createElement('option');own.value=ownOrganization;own.textContent='Moje organizace';organization.append(own);
await loadMembers();
const capabilities=await request('auth/capabilities');
if((capabilities.permissions||[]).includes('users.manage')){try{const carriers=await request('carriers');(carriers.items||[]).forEach(carrier=>{const option=document.createElement('option');option.value=carrier.id;option.textContent=carrier.name;organization.append(option)})}catch(error){message('Dopravce se nepodařilo načíst: '+error.message,true)}}
}catch(error){message(error.message,true);form.hidden=true;}};
document.getElementById('next1').onclick=()=>{const inputs=[...document.querySelectorAll('#step1 input')];if(!inputs.every(input=>input.reportValidity()))return;show(2)};
document.getElementById('back2').onclick=()=>show(1);
document.getElementById('next2').onclick=()=>{const roles=[...form.querySelectorAll('[name=roles]:checked')].map(input=>input.value);if(!roles.length){message('Vyberte alespoň jednu roli.',true);return}document.getElementById('review').textContent=`${form.first_name.value} ${form.last_name.value} · ${form.email.value} · ${organization.selectedOptions[0].textContent} · ${roles.join(', ')}`;show(3)};
document.getElementById('back3').onclick=()=>show(2);
organization.onchange=async()=>{try{await loadMembers()}catch(error){message(error.message,true)}};
document.getElementById('cancelRoles').onclick=()=>{roleEditor.hidden=true;editedUser=null};
roleForm.onsubmit=async event=>{event.preventDefault();if(!editedUser)return;const roles=[...roleForm.querySelectorAll('[name=editRoles]:checked')].map(input=>input.value);if(!roles.length){message('Vyberte alespoň jednu roli.',true);return}const save=document.getElementById('saveRoles');save.disabled=true;try{await request(`${membersPath()}/${encodeURIComponent(editedUser)}/roles`,{method:'PATCH',body:JSON.stringify({roles})});message('Role byly uloženy.');await loadMembers()}catch(error){message(error.message,true)}finally{save.disabled=false}};
form.onsubmit=async event=>{event.preventDefault();const send=document.getElementById('send');send.disabled=true;document.getElementById('credentials').hidden=true;document.getElementById('createdPassword').value='';try{const roles=[...form.querySelectorAll('[name=roles]:checked')].map(input=>input.value);const target=organization.value;const path=target===ownOrganization?'people':`people/carriers/${encodeURIComponent(target)}`;
const created=await request(path,{method:'POST',body:JSON.stringify({first_name:form.first_name.value,last_name:form.last_name.value,email:form.email.value,roles})});document.getElementById('createdEmail').textContent=created.email;document.getElementById('createdPassword').value=created.initial_password;document.getElementById('credentials').hidden=false;message('Účet byl vytvořen. Počáteční heslo je zobrazeno níže.');form.reset();show(1);await load()}catch(error){message(error.message,true)}finally{send.disabled=false}};
load();
})();
</script></body></html>
