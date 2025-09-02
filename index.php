<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>CRUD Inventario de Materiales</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Botones de orden */
    .sort-btn { display:inline-flex; align-items:center; gap:.35rem; font-weight:600; color:#111827; }
    .sort-btn .arrow::before { content:"↕"; opacity:.35; }
    .sort-btn.active.asc  .arrow::before { content:"↑"; opacity:1; }
    .sort-btn.active.desc .arrow::before { content:"↓"; opacity:1; }

    /* Evitar cortes de filas al imprimir (opcional, útil) */
    /* @media print{
      tr, td, th { break-inside: avoid; }
    } */

      @media print {
      /* Ocultar todo lo que no sea la tabla */
      form,
      #searchInput,
      #toggleColsBtn {
        display: none !important;
      }
      /* Ajustar el contenedor para que la tabla ocupe todo el ancho */
      thead {
        display: table-header-group;
      }
      /* Asegura que el pie, si lo hubiera, también se repita */
      tfoot {
        display: table-footer-group;
      }
      /* Evita cortes dentro de la tabla y de cada fila */
      table {
        page-break-inside: auto !important;
      }
      tr {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
        break-inside: avoid !important;
      }
      td, th {
        page-break-inside: avoid !important;
        page-break-after: auto !important;
        break-inside: avoid !important;
      }
      /* Opcional: control de viudas / huérfanas */
      .table, tr {
        orphans: 1;
        widows: 1;
      }
      }

  </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

  <!-- <div class="w-full max-w-7xl bg-white rounded-lg shadow-lg p-6"> -->
  <div class="w-full max-w-screen-2xl bg-white rounded-lg shadow-lg p-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <h1 class="text-2xl font-bold">Inventario de Materiales</h1>

      <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <input id="searchInput" type="text" placeholder="Buscar (nombre, código, etc.)"
               class="w-full sm:w-80 p-3 border rounded-md focus:ring focus:ring-blue-200"/>
        <button id="toggleExtrasBtn"
                class="px-4 py-3 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-800">
          Ocultar columnas extra
        </button>
      </div>
    </div>

    <!-- Layout: móvil 1 col, md+ 2 cols -->
    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
      <!-- FORMULARIO -->
      <form id="materialForm" class="space-y-4">
        <input type="hidden" id="id"/>

        <input id="nombre"        type="text"   placeholder="Nombre"        required
               class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

        <input id="otros_nombres" type="text"   placeholder="Otros Nombres"
               class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

        <input id="codigo"        type="text"   placeholder="Código"
               class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

        <input id="departamentos" type="text"   placeholder="Departamentos"
               class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

        <input id="cantidad"      type="number" placeholder="Cantidad"      required
               class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

        <textarea id="descripcion" rows="3" placeholder="Descripción"
               class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"></textarea>

        <div class="flex gap-4">
          <button type="submit"
                  class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-md">
            Guardar
          </button>
          <button type="button" id="cancelEdit"
                  class="flex-1 bg-gray-400 hover:bg-gray-500 text-white py-3 rounded-md hidden">
            Cancelar
          </button>
        </div>
      </form>

      <!-- TABLA -->
      <div class="w-full">
        <div class="overflow-x-auto">
          <table class="w-full table-auto divide-y divide-gray-200">
            <thead class="bg-gray-100 sticky top-0 z-10">
              <tr>
                <th class="px-4 py-2 text-left">
                  <button class="sort-btn" data-key="id">ID <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-left">
                  <button class="sort-btn" data-key="nombre">Nombre <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-left col-otros-th">
                  <button class="sort-btn" data-key="otros_nombres">Otros Nombres <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-left col-codigo-th">
                  <button class="sort-btn" data-key="codigo">Código <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-left">
                  <button class="sort-btn" data-key="descripcion">Descripción <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-left">
                  <button class="sort-btn" data-key="departamentos">Departamentos <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-right">
                  <button class="sort-btn" data-key="cantidad">Cantidad <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-left">
                  <button class="sort-btn" data-key="updated_at">Última Actualización <span class="arrow"></span></button>
                </th>
                <th class="px-4 py-2 text-center col-acciones-th">Acciones</th>
              </tr>
            </thead>
            <tbody id="tableBody" class="divide-y divide-gray-100">
              <!-- Filas generadas dinámicamente -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
  // ---------- Estado global ----------
  let allMaterials = [];
  let currentSort = { key: 'nombre', dir: 'asc' };
  let searchQuery = '';
  let hideExtras = false; // oculta: Otros Nombres, Código y Acciones

  // ---------- Utilidades ----------
  const $ = (s) => document.querySelector(s);
  function esc(s){
    return String(s ?? '')
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#39;');
  }
  function ageClass(iso){
    const t = Date.parse(String(iso).replace(' ', 'T'));
    if (isNaN(t)) return 'bg-gray-100 text-gray-700';
    const days = Math.floor((Date.now() - t) / 86400000);
    if (days <= 10) return 'bg-green-100 text-green-700';
    if (days <= 30) return 'bg-yellow-100 text-yellow-700';
    return 'bg-red-100 text-red-700';
  }
  function compareByKey(a,b,key,dir='asc'){
    const d = dir === 'asc' ? 1 : -1;
    let va = a?.[key], vb = b?.[key];
    switch (key) {
      case 'id':
      case 'cantidad':
        va = Number(va)||0; vb = Number(vb)||0;
        return (va - vb) * d;
      case 'updated_at': {
        const da = Date.parse(String(va).replace(' ','T')) || 0;
        const db = Date.parse(String(vb).replace(' ','T')) || 0;
        return (da - db) * d;
      }
      default:
        return String(va ?? '').localeCompare(String(vb ?? ''), 'es', {numeric:true, sensitivity:'base'}) * d;
    }
  }

  // ---------- Carga y render ----------
  document.addEventListener('DOMContentLoaded', () => {
    // Eventos de orden
    document.querySelectorAll('.sort-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const key = btn.dataset.key;
        if (currentSort.key === key) {
          currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
        } else {
          currentSort.key = key;
          currentSort.dir = 'asc';
        }
        updateSortUI();
        renderTable();
      });
    });

    // Buscador
    $('#searchInput').addEventListener('input', (e) => {
      searchQuery = e.target.value.toLowerCase();
      renderTable();
    });

    // Toggle columnas extra
    $('#toggleExtrasBtn').addEventListener('click', () => {
      hideExtras = !hideExtras;
      $('#toggleExtrasBtn').textContent = hideExtras
        ? 'Mostrar columnas extra'
        : 'Ocultar columnas extra';
      applyExtrasVisibility();
    });

    // Form
    $('#materialForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      await saveMaterial();
    });
    $('#cancelEdit').addEventListener('click', resetForm);

    loadMaterials();
    updateSortUI();
  });

  async function loadMaterials(){
    const res  = await fetch('crud.php?action=read');
    const data = await res.json();
    allMaterials = Array.isArray(data) ? data : [];
    renderTable();
  }

  function updateSortUI(){
    document.querySelectorAll('.sort-btn').forEach(b => {
      b.classList.remove('active','asc','desc');
      if (b.dataset.key === currentSort.key) {
        b.classList.add('active', currentSort.dir);
      }
    });
  }

  function applyExtrasVisibility(){
    const sel = [
      '.col-otros-th', '.col-codigo-th', '.col-acciones-th',
      '.col-otros-td', '.col-codigo-td', '.col-acciones-td'
    ].join(',');
    document.querySelectorAll(sel).forEach(el => {
      if (hideExtras) el.classList.add('hidden');
      else el.classList.remove('hidden');
    });
  }

  function renderTable(){
    // Filtrar por búsqueda
    const q = searchQuery.trim();
    let rows = allMaterials;
    if (q) {
      rows = rows.filter(it => {
        const hay =
          String(it.nombre ?? '').toLowerCase().includes(q) ||
          String(it.codigo ?? '').toLowerCase().includes(q) ||
          String(it.otros_nombres ?? '').toLowerCase().includes(q) ||
          String(it.descripcion ?? '').toLowerCase().includes(q) ||
          String(it.departamentos ?? '').toLowerCase().includes(q);
        return hay;
      });
    }

    // Ordenar
    rows = [...rows].sort((a,b) => compareByKey(a,b,currentSort.key,currentSort.dir));

    // Pintar
    const tbody = $('#tableBody');
    tbody.innerHTML = rows.map(item => `
      <tr>
        <td class="px-4 py-2">${esc(item.id)}</td>
        <td class="px-4 py-2">${esc(item.nombre)}</td>
        <td class="px-4 py-2 col-otros-td">${esc(item.otros_nombres)}</td>
        <td class="px-4 py-2 col-codigo-td">${esc(item.codigo)}</td>
        <td class="px-4 py-2">${esc(item.descripcion)}</td>
        <td class="px-4 py-2">${esc(item.departamentos)}</td>
        <td class="px-4 py-2 text-right">${esc(item.cantidad)}</td>
        <td class="px-4 py-2">
          <span class="inline-block px-2 py-1 rounded ${ageClass(item.updated_at)}">
            ${esc(item.updated_at)}
          </span>
        </td>
        <td class="px-4 py-2 text-center space-x-2 col-acciones-td">
          <button class="text-indigo-600 hover:underline" onclick="editMaterialRow(${item.id})">Editar</button>
          <button class="text-red-600 hover:underline" onclick="deleteMaterial(${item.id})">Eliminar</button>
        </td>
      </tr>
    `).join('');

    // Aplicar visibilidad de columnas extra tras pintar
    applyExtrasVisibility();
  }

  // ---------- CRUD ----------
  async function saveMaterial(){
    const id            = $('#id').value;
    const nombre        = $('#nombre').value.trim();
    const otros_nombres = $('#otros_nombres').value.trim();
    const codigo        = $('#codigo').value.trim();
    const descripcion   = $('#descripcion').value.trim();
    const departamentos = $('#departamentos').value.trim();
    const cantidad      = $('#cantidad').value.trim();

    if (!nombre || !cantidad){
      alert('Nombre y Cantidad son obligatorios.');
      return;
    }

    const fd = new FormData();
    fd.append('nombre',        nombre);
    fd.append('otros_nombres', otros_nombres);
    fd.append('codigo',        codigo);
    fd.append('descripcion',   descripcion);
    fd.append('departamentos', departamentos);
    fd.append('cantidad',      cantidad);

    if (id) { fd.append('action','update'); fd.append('id', id); }
    else    { fd.append('action','create'); }

    const res  = await fetch('crud.php', { method:'POST', body:fd });
    const json = await res.json();
    if (json.status === 'success' || json.id) {
      resetForm();
      await loadMaterials();
    } else {
      alert('Ocurrió un error al guardar.');
    }
  }

  function resetForm(){
    $('#materialForm').reset();
    $('#id').value = '';
    $('#cancelEdit').classList.add('hidden');
    // Subir al formulario en escritorio
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function editMaterialRow(id){
    const row = [...$('#tableBody').children].find(tr => Number(tr.children[0].textContent.trim()) === Number(id));
    if (!row) return;
    $('#id').value            = id;
    $('#nombre').value        = row.children[1].textContent.trim();
    $('#otros_nombres').value = row.children[2].textContent.trim();
    $('#codigo').value        = row.children[3].textContent.trim();
    $('#descripcion').value   = row.children[4].textContent.trim();
    $('#departamentos').value = row.children[5].textContent.trim();
    $('#cantidad').value      = row.children[6].textContent.trim();
    $('#cancelEdit').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function deleteMaterial(id){
    if (!confirm('¿Eliminar este material?')) return;
    const fd = new FormData();
    fd.append('action','delete');
    fd.append('id', id);
    const res  = await fetch('crud.php', { method:'POST', body:fd });
    const json = await res.json();
    if (json.status === 'success') loadMaterials();
    else alert('Error al eliminar.');
  }
  </script>
</body>
</html>
