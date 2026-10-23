// Demo sin base de datos: todo vive en memoria (y en localStorage para que
// no se pierda al cerrar la app). En el sistema real esto lo hace Laravel + MySQL.

// ---------- 1. Registrar el service worker (lo que hace que sea PWA) ----------
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('sw.js'));
}

// ---------- 2. Botón "Instalar" ----------
let eventoInstalar = null;
const btnInstalar = document.getElementById('btn-instalar');

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();            // guardamos el evento para lanzarlo con nuestro botón
  eventoInstalar = e;
  btnInstalar.style.display = 'block';
});

btnInstalar.addEventListener('click', async () => {
  if (!eventoInstalar) return;
  eventoInstalar.prompt();
  await eventoInstalar.userChoice;
  eventoInstalar = null;
  btnInstalar.style.display = 'none';
});

window.addEventListener('appinstalled', () => toast('¡App instalada! 🎉'));

// ---------- 3. Datos de ejemplo ----------
const SELLOS_REQUERIDOS = 10;
const MONTO_MINIMO_SELLO = 10;

const inicial = {
  puntos: 230,
  sellos: 7,
  compras: [],
  cupones: [
    { codigo: 'CAF-7K3Q-9XZP', premio: 'Café americano gratis', estado: 'emitido' },
    { codigo: 'CAF-M4TR-2BWD', premio: '15% de descuento', estado: 'canjeado', fecha: '17/10 14:32' },
  ],
};

function cargar() {
  try { return JSON.parse(localStorage.getItem('demo-fidelizacion')) || structuredClone(inicial); }
  catch { return structuredClone(inicial); }
}
function guardar() {
  try { localStorage.setItem('demo-fidelizacion', JSON.stringify(estado)); } catch {}
}

const estado = cargar();

// ---------- 4. Utilidades ----------
const soles = (n) => 'S/ ' + Number(n).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const $ = (id) => document.getElementById(id);

function toast(msg) {
  const t = $('toast');
  t.textContent = msg;
  t.classList.add('ver');
  clearTimeout(toast._t);
  toast._t = setTimeout(() => t.classList.remove('ver'), 2500);
}

function codigoAleatorio() {
  const abc = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // sin 0/O ni 1/I/L para no confundir
  const parte = () => Array.from({ length: 4 }, () => abc[Math.floor(Math.random() * abc.length)]).join('');
  return `CAF-${parte()}-${parte()}`;
}

// ---------- 5. Pintar pantalla ----------
function pintarSellos(contenedor) {
  contenedor.innerHTML = '';
  for (let i = 1; i <= SELLOS_REQUERIDOS; i++) {
    const s = document.createElement('div');
    s.className = 'sello' + (i <= estado.sellos ? ' lleno' : '');
    s.textContent = i <= estado.sellos ? '☕' : i;
    contenedor.appendChild(s);
  }
}

function pintar() {
  $('c-puntos').textContent = estado.puntos;
  $('p-puntos').textContent = estado.puntos;
  $('c-sellos').textContent = estado.sellos;
  pintarSellos($('c-sellos-grid'));
  pintarSellos($('p-sellos-grid'));

  const faltan = SELLOS_REQUERIDOS - estado.sellos;
  $('p-faltan').textContent = `Te faltan ${faltan} ${faltan === 1 ? 'compra' : 'compras'} para tu café gratis`;

  const disponibles = estado.cupones.filter((c) => c.estado === 'emitido');
  $('c-cupones').innerHTML = disponibles.map((c) => `<span class="chip">🎁 ${c.premio}</span>`).join('');

  $('ultimas').innerHTML = estado.compras.length
    ? estado.compras.slice(0, 5).map((c) => `
        <li><div>${soles(c.monto)}<small>${c.hora}</small></div>
        <div class="pts">+${c.puntos} pts${c.sello ? ' · +1 ☕' : ''}</div></li>`).join('')
    : '<li class="muted">Aún no hay compras en este turno.</li>';

  $('lista-cupones').innerHTML = estado.cupones.map((c) => `
    <li><div><b>${c.codigo}</b><small>${c.premio}</small></div>
    <div class="${c.estado === 'emitido' ? 'pts' : 'muted'}">${c.estado}</div></li>`).join('');

  guardar();
}

// QR falso solo para la fachada (en el real lo genera Laravel)
function pintarQR() {
  const qr = $('qr');
  let semilla = 987654321;
  const rnd = () => (semilla = (semilla * 16807) % 2147483647) / 2147483647;
  const esquina = (x, y) => {
    for (const [ox, oy] of [[0, 0], [14, 0], [0, 14]]) {
      const dx = x - ox, dy = y - oy;
      if (dx >= 0 && dx < 7 && dy >= 0 && dy < 7) {
        return dx === 0 || dx === 6 || dy === 0 || dy === 6 || (dx >= 2 && dx <= 4 && dy >= 2 && dy <= 4);
      }
    }
    return null;
  };
  let html = '';
  for (let y = 0; y < 21; y++) {
    for (let x = 0; x < 21; x++) {
      const e = esquina(x, y);
      const negro = e === null ? rnd() > 0.5 : e;
      html += `<i class="${negro ? '' : 'b'}"></i>`;
    }
  }
  qr.innerHTML = html;
}

// ---------- 6. Registrar compra ----------
$('form-compra').addEventListener('submit', (e) => {
  e.preventDefault();
  const monto = parseFloat($('monto').value.replace(',', '.'));
  if (!monto || monto <= 0) return toast('Ingresa un monto válido');

  const puntos = Math.floor(monto);              // 1 punto por cada S/ 1
  const sello = monto >= MONTO_MINIMO_SELLO;
  estado.puntos += puntos;

  let premio = null;
  if (sello) {
    estado.sellos++;
    if (estado.sellos >= SELLOS_REQUERIDOS) {
      estado.sellos = 0;                          // la tarjeta se reinicia
      premio = { codigo: codigoAleatorio(), premio: 'Café americano gratis', estado: 'emitido' };
      estado.cupones.unshift(premio);
    }
  }

  const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', timeZone: 'America/Lima' });
  estado.compras.unshift({ monto, puntos, sello, hora });

  $('monto').value = '';
  pintar();
  toast(premio ? `🎉 ¡Desbloqueó un café gratis! ${premio.codigo}` : `Compra registrada: +${puntos} puntos`);
  if (navigator.vibrate) navigator.vibrate(premio ? [80, 60, 80] : 40);
});

// ---------- 7. Validar cupón ----------
$('form-validar').addEventListener('submit', (e) => {
  e.preventDefault();
  const codigo = $('codigo').value.trim().toUpperCase();
  const res = $('res-validar');
  const cupon = estado.cupones.find((c) => c.codigo === codigo);

  if (!cupon) {
    res.className = 'resultado mal';
    res.textContent = '❌ Código no existe.';
  } else if (cupon.estado !== 'emitido') {
    res.className = 'resultado mal';
    res.textContent = `❌ Este cupón ya fue utilizado${cupon.fecha ? ' el ' + cupon.fecha : ''}.`;
  } else {
    cupon.estado = 'canjeado';
    cupon.fecha = new Date().toLocaleString('es-PE', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', timeZone: 'America/Lima' });
    res.className = 'resultado ok';
    res.textContent = `✅ Canjeado: ${cupon.premio}. Entregar al cliente.`;
    pintar();
  }
});

$('btn-camara').addEventListener('click', () => toast('En el sistema real se abre la cámara 📷'));

// ---------- 8. Navegación inferior ----------
document.querySelectorAll('nav button').forEach((b) => {
  b.addEventListener('click', () => {
    document.querySelectorAll('nav button').forEach((x) => x.classList.toggle('activa', x === b));
    document.querySelectorAll('.vista').forEach((v) => v.classList.toggle('activa', v.id === b.dataset.vista));
    window.scrollTo(0, 0);
  });
});

// ---------- 9. Aviso sin conexión ----------
const avisoOffline = () => ($('offline').style.display = navigator.onLine ? 'none' : 'block');
window.addEventListener('online', avisoOffline);
window.addEventListener('offline', avisoOffline);

avisoOffline();
pintarQR();
pintar();
