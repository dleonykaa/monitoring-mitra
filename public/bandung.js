import {buildItinerary} from './bandung-itinerary.mjs';
import {defaults, stays, calculate, independentPlans} from './bandung-budget.mjs';
const $ = id => document.getElementById(id);
const rupiah = amount => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(Math.ceil(amount));
const key = 'jalanbareng-bandung-v1';
let state = {people:4,duration:'2-1',mode:'car',stay:'comfort',tripRoute:'city',hotelIndex:-1,gameIndex:0,date:'',prices:{...defaults},checked:[]};
try {
    const saved = JSON.parse(localStorage.getItem(key));
    if (saved && typeof saved === 'object') {
        if (['custom','city','nature','south'].includes(saved.tripRoute)) state.tripRoute = saved.tripRoute;
        if (Number.isInteger(saved.hotelIndex) && saved.hotelIndex >= -1 && saved.hotelIndex < 3) state.hotelIndex = saved.hotelIndex;
        if (Number.isInteger(saved.gameIndex) && saved.gameIndex >= 0 && saved.gameIndex < 3) state.gameIndex=saved.gameIndex;
        if (Number.isInteger(saved.people) && saved.people >= 1 && saved.people <= 10) state.people = saved.people;
        if (['2-1','2-2','3-2','3-3'].includes(saved.duration)) state.duration = saved.duration;
        if (['car','whoosh','shuttle','rental'].includes(saved.mode)) state.mode = saved.mode;
        if (Object.hasOwn(stays,saved.stay)) state.stay = saved.stay;
        if (/^\d{4}-\d{2}-\d{2}$/.test(saved.date) && !Number.isNaN(Date.parse(saved.date))) state.date = saved.date;
        if (Array.isArray(saved.checked)) state.checked = [...new Set(saved.checked.filter(i => Number.isInteger(i) && i >= 0 && i < 12))];
        for (const name of Object.keys(defaults)) if (Number.isFinite(saved.prices?.[name]) && saved.prices[name] >= 0 && saved.prices[name] <= 10000000) state.prices[name] = name === 'efficiency' && saved.prices[name] === 0 ? defaults.efficiency : saved.prices[name];
    }
} catch {}
const modes = {
    car:{name:'Mobil pribadi',icon:'🚗',hint:'Fleksibel, patungan bensin & tol',url:'https://www.google.com/maps/dir/Jakarta/Bandung/'},
    whoosh:{name:'Whoosh',icon:'🚄',hint:'Kereta cepat + transport lokal',url:'https://ticket.kcic.co.id/'},
    shuttle:{name:'Travel / shuttle',icon:'🚐',hint:'Praktis, dari titik keberangkatan',url:'https://www.traveloka.com/id-id/bus-and-shuttle'},
    rental:{name:'Mobil sewaan',icon:'🚙',hint:'Dengan sopir, 6 penumpang / unit',url:'https://www.traveloka.com/id-id/car-rental/city/bandung'}
};
const items = ['KTP & identitas diri','Tiket & bukti booking','Jaket atau sweater','Baju ganti sesuai lama nginep','Sepatu yg enak buat jalan','Payung / jas hujan','Obat pribadi & P3K','Charger & power bank','Perlengkapan mandi','Tumbler & camilan','Uang tunai & saldo e-toll','Pastiin kamar & kendaraan'];
let activeDay = 1;
function persist() {try {localStorage.setItem(key,JSON.stringify(state));} catch {$('save-status').textContent = 'Penyimpanan browser tidak tersedia. Unduh rencana agar tidak hilang.';}}
function dimensions() {return state.duration.split('-').map(Number);}
function budget(people=state.people,mode=state.mode) {return calculate(people,...dimensions(),mode,state.prices);}
function mapLink(place) {if(place==='Penginapan pilihan sendiri')place='hotel Bandung';return 'https://www.google.com/maps/search/?api=1&query='+encodeURIComponent(['Jakarta','Bandung'].includes(place)?place:place+' Bandung');}
function render() {
    const [days,nights] = dimensions(); const cost = budget();
    $('summary-trip').textContent = `${state.people} orang · ${days} hari, ${nights} malam · Jakarta`;
    $('per-person').textContent = rupiah(cost.perPerson);
    const remaining = 1500000 - cost.perPerson;
    $('budget-status').textContent = remaining >= 0 ? `✓ Masih ada ruang ${rupiah(remaining)} / orang` : `! Melebihi budget ${rupiah(-remaining)} / orang`;
    $('budget-status').classList.toggle('over',remaining < 0);
    $('budget-fill').style.width = Math.min(cost.perPerson / 1500000 * 100,100)+'%';
    $('budget-fill').style.background = remaining < 0 ? 'var(--danger)' : 'var(--green)';
    $('breakdown').innerHTML = [['Transportasi PP & lokal',cost.transport],['Penginapan',cost.lodging],['Makan & minum',cost.food],['Wisata lain',cost.tickets],['Board game (1 sesi)',cost.games],['Cadangan 10%',cost.reserve]].map(([label,value])=>`<div><span>${label}</span><strong>${rupiah(value/state.people)}</strong></div>`).join('');
    $('group-total').textContent = rupiah(cost.total);
    $('group-tip').textContent = `${cost.rooms} kamar untuk ${state.people} orang. ${['car','rental'].includes(state.mode) ? `${cost.cars} mobil dibutuhkan.` : `${Math.ceil(state.people/4)} kendaraan lokal diasumsikan.`} Biaya di atas adalah per orang, kecuali total rombongan.`;
    $('transport-options').innerHTML = Object.entries(modes).map(([id,mode])=>`<button class="choice" data-mode="${id}" aria-pressed="${state.mode === id}"><span class="selection-dot"></span><span class="transport-icon" aria-hidden="true">${mode.icon}</span><b>${mode.name}</b><small>${mode.hint}</small><span class="choice-price">${rupiah(budget(state.people,id).transport/state.people)} <small>/ orang, PP & lokal</small></span></button>`).join('');
    $('stay-options').innerHTML = Object.entries(stays).map(([id,stay])=>`<button class="choice" data-stay="${id}" aria-pressed="${state.stay===id}"><span class="selection-dot"></span><b>${stay.name}</b><small>2 orang / kamar</small><span class="choice-price">${rupiah(state.stay===id ? state.prices.room : stay.rate)}</span><small>/ kamar / malam</small></button>`).join('');
    $('transport-link').href = modes[state.mode].url;
    $('transport-link').textContent = state.mode === 'car' ? 'Lihat rute Jakarta–Bandung di Maps ↗' : `Cek ${modes[state.mode].name} di penyedia ↗`;
    $('transport-detail').textContent = ['car','rental'].includes(state.mode) ? `${cost.cars} mobil · ${state.prices.distance+(days-2)*60} km termasuk perjalanan lokal · ${state.prices.efficiency} km/liter · bensin ${rupiah(state.prices.fuel)}/liter · tol PP ${rupiah(state.prices.toll)}/mobil · parkir ${rupiah(state.prices.parking)}/hari/mobil.${state.mode==='rental' ? ` Sewa + sopir ${rupiah(state.prices.rental)}/hari/mobil × ${cost.rentalDays} hari, alokasi termasuk uang makan/penginapan sopir; konfirmasi paket & lembur.` : ''}` : `Tiket ${rupiah(state.prices[state.mode])}/orang/arah × 2. Transport lokal & akses terminal/stasiun dialokasikan ${rupiah(state.prices.local)}/hari per 4 orang × ${cost.rentalDays} hari. Tarif dinamis; sesuaikan dengan hasil pencarian.`;
    $('comparison-body').innerHTML = Array.from({length:10},(_,i)=>`<tr class="${state.people===i+1?'selected':''}"><th scope="row">${i+1} orang ${state.people===i+1?'←':''}</th>${Object.keys(modes).map(mode=>{const price=budget(i+1,mode).perPerson;return `<td class="${price>1500000?'over':''}">${rupiah(price)}${price>1500000?' !':''}</td>`;}).join('')}</tr>`).join('');
    let dateText = nights===days ? 'Termasuk check-in malam sebelum hari pertama, tambahan makan malam dan satu hari alokasi kendaraan.' : 'Berangkat pagi pada hari pertama. Menginap di antara hari wisata.';
    if(state.date){const first=new Date(state.date+'T12:00:00');const checkin=new Date(first);checkin.setDate(checkin.getDate()-(nights===days?1:0));const checkout=new Date(first);checkout.setDate(checkout.getDate()+days-1);const fmt=d=>d.toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'});dateText+=` Check-in ${fmt(checkin)}, check-out ${fmt(checkout)}.`;}
    $('date-note').textContent = dateText;
    if(activeDay>days || (activeDay===0 && nights!==days)) activeDay=1;
    renderDays(); renderHotels(); renderRouteSummary(); renderIndependentPlans(); persist();
}
function dayPlan(day) { const [days,nights]=dimensions(); return buildItinerary({day,days,nights,route:state.tripRoute,hotel:selectedHotel(),mode:state.mode,game:$('trip-game').value}); }
function renderDays(){const [days,nights]=dimensions();const dayNumbers=nights===days?[0,...Array.from({length:days},(_,i)=>i+1)]:Array.from({length:days},(_,i)=>i+1);$('day-tabs').innerHTML=dayNumbers.map(day=>`<button role="tab" id="day-${day}" aria-controls="timeline" aria-selected="${activeDay===day}" data-day="${day}">${day===0?'Malam kedatangan':`Hari ${day}`}</button>`).join('');$('timeline').setAttribute('aria-labelledby','day-'+activeDay);$('timeline').innerHTML=dayPlan(activeDay).map(([time,title,description,place])=>`<article class="timeline-item"><time>${time}</time><div><h3>${title}</h3><p>${description}</p><a href="${mapLink(place)}" target="_blank" rel="noopener noreferrer">Lihat lokasi ↗</a></div></article>`).join('');}
const destinations = [
    {name:'Braga & Asia Afrika',category:'Kota',note:'Untuk jalan sore, foto arsitektur, dan berburu kopi. Sudah masuk hari pertama.',price:'Area publik Rp0',image:'photo-1449824913935-59a10b8d2000'},
    {name:'Tahura Djuanda',category:'Alam',note:'Jalan hutan yang santai di Dago Pakar. Rekomendasi utama hari kedua.',price:'Alokasi Rp30–60 ribu',image:'photo-1448375240586-882707db888b'},
    {name:'Floating Market Lembang',category:'Alam',note:'Pilihan hari ketiga untuk kuliner dan suasana danau. Belanja makanan terpisah.',price:'Alokasi Rp60 ribu',image:'photo-1472396961693-142e6e269027'},
    {name:'Gedung Sate & Gasibu',category:'Kota',note:'Singgah untuk foto area luar sebelum kuliner malam. Cocok untuk foto dan berjalan santai.',price:'Area luar Rp0',image:'photo-1449824913935-59a10b8d2000'},
    {name:'Tebing Keraton',category:'Alam',note:'Alternatif Tahura untuk panorama. Konfirmasi akses terakhir, cuaca, tiket, dan biaya ojek lokal.',price:'Alokasi Rp60 ribu + akses',image:'photo-1464822759023-fed622ff2c3b'},
    {name:'Kuliner Dago',category:'Kota',note:'Pilih warung atau kafe sesuai sisa anggaran makan. Cocok sesudah jalan di Tahura.',price:'Dari alokasi makan',image:'photo-1442512595331-e89e73853f31'},
    {name:'Saung Angklung Udjo',category:'Budaya',area:'Padasuka · Bandung timur',time:'Sekitar 2 jam + perjalanan',note:'Pertunjukan musik dan budaya Sunda, cocok untuk rombongan. Sesuaikan kunjungan dengan jadwal pertunjukan dan pesan tiket terlebih dahulu.',price:'Siapkan Rp85–120 ribu / orang',source:'https://angklung-udjo.co.id/'},
    {name:'Orchid Forest Cikole',category:'Alam',area:'Cikole · utara Lembang',time:'2–3 jam + perjalanan',note:'Hutan pinus dan koleksi anggrek. Pilih sebagai alternatif agenda Lembang, bukan tambahan setelah semua tempat. Wahana dan parkir dapat berbayar terpisah.',price:'Siapkan Rp60–100 ribu / orang',source:'https://www.indonesia.travel/fr/en/travel-ideas/adventure/10-reason-s-why-you-experience-the-cikole-orchid-forest-at-lembang'},
    {name:'Kawah Putih',category:'Alam',area:'Ciwidey · Bandung selatan',time:'Alokasikan satu hari',note:'Pemandangan danau kawah untuk pilihan perjalanan khusus selatan. Ganti agenda Lembang dengan Ciwidey; tambah anggaran perjalanan, tiket, dan angkutan di kawasan.',price:'Siapkan Rp100–150 ribu / orang',source:'https://www.indonesia.travel/id/en/destination/java/west-java/bandung---kawah-putih'}
];
function renderDestinations(){const filter=$('destination-filter').value;const visible=destinations.filter(x=>filter==='all'||filter===x.category);$('destination-count').textContent=`${visible.length} pilihan destinasi`;$('destinations').innerHTML=visible.map(x=>`<article class="destination">${x.image?`<div class="destination-image"><img src="https://images.unsplash.com/${x.image}?auto=format&fit=crop&w=650&q=75" alt="Ilustrasi suasana ${x.category==='Alam'?'alam':'kota dan kuliner'}, bukan foto lokasi" loading="lazy"></div>`:''}<span class="category">${x.category} · ${x.area||'Bandung'} ${x.image?'<span aria-label="Foto ilustrasi">/ ilustrasi</span>':''}</span><h3>${x.name}</h3>${x.time?`<span class="visit-time">${x.time}</span>`:''}<p>${x.note}</p><div class="destination-bottom"><span>${x.price}</span><a href="${mapLink(x.name)}" target="_blank" rel="noopener noreferrer">Buka Maps ↗</a></div>${x.source?`<a class="source-link" href="${x.source}" target="_blank" rel="noopener noreferrer">Info destinasi ↗</a>`:''}</article>`).join('');}
const hotels = [
    {name:'POP! Hotel Festival Citylink',area:'Peta / Pasir Koja',low:250000,high:450000,reason:'Pilihan untuk menekan biaya kamar, dengan akses ke mal untuk mencari makan.',tradeoff:'Perlu kendaraan untuk ke Braga, Dago, atau Lembang. Perhitungkan waktu perjalanan dari area selatan kota.',url:'https://www.discoverasr.com/id/pop-hotels/indonesia/pop-hotel-festival-citylink-bandung'},
    {name:'ibis Bandung Pasteur',area:'Pasteur',low:350000,high:650000,reason:'Praktis untuk rombongan dari Jakarta yang membawa mobil dan ingin akses ke arah utara kota.',tradeoff:'Area Pasteur bisa padat. Pastikan ketersediaan dan biaya parkir jika membawa beberapa mobil.',url:'https://all.accor.com/hotel/9397/index.id.shtml'},
    {name:'ibis Styles Bandung Grand Central',area:'Diponegoro / Gedung Sate',low:500000,high:900000,reason:'Cocok untuk agenda Gedung Sate dan kuliner Cihapit, lalu melanjutkan perjalanan ke Dago.',tradeoff:'Alokasi kamar lebih besar; untuk 3 malam atau perjalanan sendiri, cek kembali batas Rp1,5 juta per orang.',url:'https://all.accor.com/hotel/9339/index.id.shtml'}
];
function renderHotels() {
    const [,nights] = dimensions(); const rooms = Math.ceil(state.people / 2);
    $('hotel-context').textContent = `Simulasi ${state.people} orang · ${rooms} kamar × ${nights} malam · asumsi 2 orang / kamar`;
    $('hotel-list').innerHTML = hotels.map(hotel => `<article class="hotel-option"><div class="hotel-location">${hotel.area}</div><h3>${hotel.name}</h3><p>${hotel.reason}</p><div class="hotel-rate"><span>Alokasi rencana / kamar / malam</span><strong>${rupiah(hotel.low)}–${rupiah(hotel.high)}</strong><span>Penginapan per orang, seluruh malam</span><b>${rupiah(hotel.low*rooms*nights/state.people)}–${rupiah(hotel.high*rooms*nights/state.people)}</b></div><p class="subnote">${hotel.tradeoff}</p><a class="button secondary" href="${hotel.url}" target="_blank" rel="noopener noreferrer">Cek kamar di situs hotel ↗</a></article>`).join('');
}
const priceLabels={games:'Board game / orang / perjalanan (Rp)',room:'Kamar / malam (Rp)',fuel:'Bensin / liter (Rp)',distance:'Jarak dasar 2 hari, PP + lokal (km)',efficiency:'Efisiensi mobil (km/liter)',toll:'Tol PP / mobil (Rp)',parking:'Parkir / mobil / hari (Rp)',whoosh:'Whoosh / orang / arah (Rp)',shuttle:'Shuttle / orang / arah (Rp)',rental:'Sewa + sopir / mobil / hari (Rp)',local:'Lokal & transfer / 4 orang / hari (Rp)',food:'Makan / orang / hari (Rp)',tickets:'Wisata lain / orang / hari setelah hari 1 (Rp)'};
$('price-inputs').innerHTML=Object.entries(priceLabels).map(([key,label])=>`<label>${label}<input type="number" min="${key==='efficiency'?1:0}" max="10000000" step="1" data-price="${key}" value="${state.prices[key]}" required></label>`).join('');
$('price-inputs').addEventListener('change',event=>{const field=event.target;if(!field.dataset.price)return;if(!field.checkValidity() || (field.dataset.price === 'efficiency' && Number(field.value) <= 0)){field.reportValidity();field.value=state.prices[field.dataset.price];return;}state.prices[field.dataset.price]=Number(field.value);render();});
$('people').value=state.people;$('duration').value=state.duration;$('date').value=state.date;
for(const id of ['people','duration','date']) $(id).addEventListener('change',()=>{state[id]=id==='people'?Number($(id).value):$(id).value;render();});
$('transport-options').addEventListener('click',event=>{const button=event.target.closest('[data-mode]');if(button){state.mode=button.dataset.mode;render();$('transport-options').querySelector(`[data-mode="${state.mode}"]`).focus();}});
$('stay-options').addEventListener('click',event=>{const button=event.target.closest('[data-stay]');if(button){state.stay=button.dataset.stay;state.prices.room=stays[state.stay].rate;document.querySelector('[data-price="room"]').value=state.prices.room;render();$('stay-options').querySelector(`[data-stay="${state.stay}"]`).focus();}});
$('day-tabs').addEventListener('click',event=>{if(event.target.dataset.day){activeDay=Number(event.target.dataset.day);renderDays();$('day-'+activeDay).focus();}});
$('destination-filter').addEventListener('change',renderDestinations);
function renderChecklist(){$('checklist-items').innerHTML=items.map((item,i)=>`<label><input type="checkbox" value="${i}" ${state.checked.includes(i)?'checked':''}>${item}</label>`).join('');updateProgress();}
function updateProgress(){$('checklist-progress').textContent=`${state.checked.length} dari ${items.length} udh siappp`;$('packing-progress').value=state.checked.length;persist();}
$('checklist-items').addEventListener('change',event=>{const i=Number(event.target.value);state.checked=event.target.checked?[...new Set([...state.checked,i])]:state.checked.filter(x=>x!==i);updateProgress();});
$('reset-checklist').addEventListener('click',()=>{state.checked=[];renderChecklist();});
$('print').addEventListener('click',()=>window.print());
$('save').addEventListener('click',()=>{const [days,nights]=dimensions();const cost=budget();const lines=['MBO - Main Bandung Offline',`${state.people} orang | ${days} hari ${nights} malam | ${state.date||'Tanggal belum ditentukan'}`,$('date-note').textContent,...(nights===days ? ['MALAM KEDATANGAN',...dayPlan(0).map(row=>row.join(' | '))] : []),`Transportasi: ${modes[state.mode].name}`,`Kamar: ${cost.rooms} × ${rupiah(state.prices.room)} × ${nights} malam`,$('transport-detail').textContent,...[['Transportasi',cost.transport],['Penginapan',cost.lodging],['Makan',cost.food],['Wisata lain',cost.tickets],['Board game (1 sesi)',cost.games],['Cadangan',cost.reserve]].map(([label,value])=>`${label}: ${rupiah(value)} / rombongan`),`TOTAL: ${rupiah(cost.total)} | PER ORANG: ${rupiah(cost.perPerson)}`,$('budget-status').textContent,'',...Array.from({length:days},(_,i)=>[`HARI ${i+1}`,...dayPlan(i+1).map(([time,title,note,place])=>`${time} ${title} — ${note} ${mapLink(place)}`)]).flat(),'','CHECKLIST',...items.map((item,i)=>`${state.checked.includes(i)?'[x]':'[ ]'} ${item}`),'','Hotel: https://www.traveloka.com/id-id/hotel/indonesia/city/bandung-103859',`Transportasi: ${modes[state.mode].url}`,'Estimasi, bukan tarif live. Belanja pribadi di luar budget.'];const blob=new Blob([lines.join('\n')],{type:'text/plain;charset=utf-8'});const url=URL.createObjectURL(blob);const link=document.createElement('a');link.href=url;link.download='MBO-rencana-bandung.txt';link.click();setTimeout(()=>URL.revokeObjectURL(url),1000);$('save-status').textContent='Rencana lengkap diunduh. Pilihan tetap tersimpan di browser.';});
$('trip-game').selectedIndex=state.gameIndex;
$('trip-game').addEventListener('change',()=>{state.gameIndex=$('trip-game').selectedIndex;render();});
$('trip-route').value=state.tripRoute;
$('trip-hotel').value=String(state.hotelIndex);
$('trip-route').addEventListener('change',()=>{state.tripRoute=$('trip-route').value;if(state.tripRoute==='custom'){state.hotelIndex=-1;$('trip-hotel').value='-1';}activeDay=1;render();});
$('trip-hotel').addEventListener('change',()=>{state.hotelIndex=Number($('trip-hotel').value);render();});
function renderRouteSummary(){
    const hotel=selectedHotel();
    const names={custom:'Rencana sendiri',city:'Kuliner & board game',nature:'Dago & Lembang',south:'Ciwidey'};
    $('route-summary').textContent=`${names[state.tripRoute]} · menginap di ${hotel.name}. Ini rencana sementara, belum merupakan reservasi. Pilihan ini mengubah itinerary, tetapi harga baru diterapkan lewat tombol di bawah.`;
    $('route-cost-note').textContent=state.tripRoute==='south'?'Ciwidey paling nyaman untuk 3 hari. Untuk 2 hari, perjalanan pulang panjang; gunakan kendaraan PP dan hindari tiket pulang sore.':'Ikuti lokasi utama per langkah. Tempat alternatif adalah pengganti, bukan tambahan yang wajib didatangi.';
}
$('apply-route-budget').addEventListener('click',()=>{
    if(state.tripRoute==='custom'){$('route-budget-status').textContent='Rencana sendiri memakai asumsi saat ini. Pilih paket dasar di atas atau ubah asumsi harga.';return;}
    const hotel=selectedHotel();state.prices.room=(hotel.low+hotel.high)/2;state.prices.tickets=state.tripRoute==='south'?150000:state.tripRoute==='nature'?100000:0;state.prices.distance=state.tripRoute==='south'?540:360;
    for(const key of ['room','tickets','distance']) document.querySelector(`[data-price="${key}"]`).value=state.prices[key];
    render();$('route-budget-status').textContent='Estimasi kamar, tiket, dan jarak sudah diterapkan. Periksa total terbaru; harga masih perlu dikonfirmasi ke penyedia. Transport lokal ke Ciwidey perlu penawaran PP tersendiri jika berangkat dengan Whoosh/shuttle.';
});
function selectedHotel(){return hotels[state.hotelIndex] || {name:'Penginapan pilihan sendiri',low:state.prices.room,high:state.prices.room};}
function renderIndependentPlans(){
    $('independent-context').textContent=`${state.people} orang · ${state.duration.replace('-', ' hari / ')} malam · ${modes[state.mode].name} · termasuk cadangan 10%`;
    $('independent-plans').innerHTML=independentPlans.map((plan,index)=>{const prices={...defaults,...plan};const cost=calculate(state.people,...dimensions(),state.mode,prices);return `<article class="hotel-option"><h3>${plan.name}</h3><strong>${rupiah(cost.perPerson)} / orang</strong><p class="subnote">${cost.perPerson>1500000?'Melebihi':'Masih dalam'} batas Rp1,5 juta / orang</p><p>Kamar ${rupiah(plan.room)}/malam (berdua). Makan, kopi & jajan ${rupiah(plan.food)}/orang/hari. Wisata lain ${rupiah(plan.tickets)}/orang/hari setelah hari pertama. Board game ${rupiah(plan.games)}/orang untuk satu sesi selama perjalanan.</p><button class="button secondary" data-independent="${index}">Pakai estimasi ${plan.name.toLowerCase()}</button></article>`;}).join('');
}
$('independent-plans').addEventListener('click',event=>{const button=event.target.closest('[data-independent]');if(!button)return;const plan=independentPlans[Number(button.dataset.independent)];state.prices={...defaults,room:plan.room,food:plan.food,tickets:plan.tickets,games:plan.games};state.tripRoute='custom';state.hotelIndex=-1;state.stay=plan.room===250000?'budget':'comfort';$('trip-route').value='custom';$('trip-hotel').value='-1';for(const key of Object.keys(defaults))document.querySelector(`[data-price="${key}"]`).value=state.prices[key];render();$('independent-status').textContent=`Estimasi ${plan.name} diterapkan. Asumsi biaya direset ke paket ini; jumlah orang, durasi, dan transportasi tetap. Ubah angka sesuai pilihan kalian.`;});
render();renderDestinations();renderChecklist();
