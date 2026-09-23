const menuBtn=document.querySelector('.menu-btn');
const nav=document.querySelector('.nav-links');
if(menuBtn){menuBtn.addEventListener('click',()=>nav.classList.toggle('open'))}
document.querySelectorAll('.nav-links a').forEach(a=>a.addEventListener('click',()=>nav.classList.remove('open')));

const rupiah = n => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n).replace('Rp','Rp ');

const grid=document.getElementById('productGrid'), filters=document.getElementById('filters'), search=document.getElementById('search');
const resultInfo=document.getElementById('resultInfo'), empty=document.getElementById('empty'), count=document.getElementById('productCount');

const cats=['Semua',...new Set(products.map(p=>p.category))];
let active='Semua', query='';

cats.forEach(cat=>{
  const b=document.createElement('button');
  b.className='filter'+(cat===active?' active':'');
  b.textContent=cat;
  b.onclick=()=>{active=cat;document.querySelectorAll('.filter').forEach(x=>x.classList.remove('active'));b.classList.add('active');render()};
  filters.appendChild(b);
});

function render(){
  const q=query.toLowerCase();
  const list=products.filter(p=>(active==='Semua'||p.category===active) && (!q || p.name.toLowerCase().includes(q) || p.category.toLowerCase().includes(q)));
  resultInfo.textContent=`Menampilkan ${list.length} dari ${products.length} produk`;
  empty.hidden=list.length!==0;
  grid.innerHTML=list.map((p,i)=>`
    <article class="product">
      <img class="product-img" src="${p.image}" alt="${p.name}" loading="${i<8?'eager':'lazy'}">
      <div class="product-body">
        <div class="product-cat">${p.category}</div>
        <h3>${p.name}</h3>
        <div class="product-price">${rupiah(p.price)} <small>/ hari</small></div>
        <a class="rent-btn" target="_blank" rel="noopener" href="https://wa.me/6282225708380?text=${encodeURIComponent(`Halo Urban Adventure, saya ingin menyewa ${p.name} dengan harga katalog ${rupiah(p.price)}/hari. Apakah masih tersedia?`)}">Sewa via WhatsApp →</a>
      </div>
    </article>`).join('');
}
search.addEventListener('input',e=>{query=e.target.value;render()});
count.textContent=products.length;
render();
