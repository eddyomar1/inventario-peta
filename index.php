<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>CRUD Inventario de Materiales</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

  <div class="w-full max-w-6xl bg-white rounded-lg shadow-lg p-6">

    <h1 class="text-2xl font-bold text-center mb-6">Inventario de Materiales</h1>

    <!-- CONTENEDOR: siempre una sola columna -->
    <div class="flex flex-col gap-6">

      <!-- FORMULARIO: ancho completo -->
      <div class="w-full">
        <form id="materialForm" class="space-y-4">
          <input type="hidden" id="id" name="id"/>

          <input name="nombre" id="nombre" type="text" placeholder="Nombre" required
                 class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

          <input name="otros_nombres" id="otros_nombres" type="text" placeholder="Otros Nombres"
                 class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

          <input name="codigo" id="codigo" type="text" placeholder="Código"
                 class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

          <input name="departamentos" id="departamentos" type="text" placeholder="Departamentos"
                 class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

          <input name="cantidad" id="cantidad" type="number" placeholder="Cantidad" required
                 class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"/>

          <textarea name="descripcion" id="descripcion" rows="3" placeholder="Descripción"
                    class="block w-full p-3 border rounded-md focus:ring focus:ring-blue-200"></textarea>

          <div class="flex space-x-4">
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
      </div>

      <!-- TABLA: debajo del formulario, ancho completo -->
      <div class="w-full">
        <table class="w-full table-auto divide-y divide-gray-200">
          <thead class="bg-gray-100 sticky top-0">
            <tr>
              <th class="px-4 py-2 text-left">ID</th>
              <th class="px-4 py-2 text-left">Nombre</th>
              <th class="px-4 py-2 text-left">Otros Nombres</th>
              <th class="px-4 py-2 text-left">Código</th>
              <th class="px-4 py-2 text-left">Descripción</th>
              <th class="px-4 py-2 text-left">Departamentos</th>
              <th class="px-4 py-2 text-right">Cantidad</th>
              <th class="px-4 py-2 text-left">Última Actualización</th>
              <th class="px-4 py-2 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody id="tableBody" class="divide-y divide-gray-100">
            <!-- Filas dinámicas -->
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('materialForm')
            .addEventListener('submit', e => { e.preventDefault(); saveMaterial(); });
    document.getElementById('cancelEdit')
            .addEventListener('click', resetForm);
    loadMaterials();
  });

  async function loadMaterials() {
    const res  = await fetch('crud.php?action=read');
    const data = await res.json();
    renderTable(data);
  }

  function renderTable(data) {
    const tbody = document.getElementById('tableBody');
    tbody.innerHTML = '';
    const now = new Date();
    data.forEach(item => {
      const updated  = new Date(item.updated_at);
      const diffDays = Math.floor((now - updated)/(1000*60*60*24));
      let dateClass  = diffDays <= 10 ? 'bg-green-100'
                      : diffDays <= 30 ? 'bg-yellow-100'
                      : 'bg-red-100';

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="px-4 py-2">${item.id}</td>
        <td class="px-4 py-2">${item.nombre}</td>
        <td class="px-4 py-2">${item.otros_nombres}</td>
        <td class="px-4 py-2">${item.codigo}</td>
        <td class="px-4 py-2">${item.descripcion}</td>
        <td class="px-4 py-2">${item.departamentos}</td>
        <td class="px-4 py-2 text-right">${item.cantidad}</td>
        <td class="px-4 py-2 ${dateClass}">${item.updated_at}</td>
        <td class="px-4 py-2 text-center space-x-2">
          <button onclick="editMaterial(this, ${item.id})"
                  class="text-indigo-600 hover:underline">Editar</button>
          <button onclick="deleteMaterial(${item.id})"
                  class="text-red-600 hover:underline">Eliminar</button>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  async function saveMaterial() {
    const form = document.getElementById('materialForm');
    const fd   = new FormData(form);
    const id   = form.id.value;
    fd.append('action', id ? 'update' : 'create');

    const res  = await fetch('crud.php',{ method:'POST', body:fd });
    const json = await res.json();
    if (json.status==='success') {
      resetForm();
      loadMaterials();
    } else {
      console.error('Error:', json);
      alert('Error al guardar:\n' + (json.error||JSON.stringify(json)));
    }
  }

  function editMaterial(btn, id) {
    const cells = btn.closest('tr').children;
    document.getElementById('id').value            = id;
    document.getElementById('nombre').value        = cells[1].textContent;
    document.getElementById('otros_nombres').value = cells[2].textContent;
    document.getElementById('codigo').value        = cells[3].textContent;
    document.getElementById('descripcion').value   = cells[4].textContent;
    document.getElementById('departamentos').value = cells[5].textContent;
    document.getElementById('cantidad').value      = cells[6].textContent;
    document.getElementById('cancelEdit')
            .classList.remove('hidden');
  }

  async function deleteMaterial(id) {
    if (!confirm('¿Eliminar este material?')) return;
    const fd   = new FormData();
    fd.append('action','delete');
    fd.append('id', id);
    const res  = await fetch('crud.php',{ method:'POST', body:fd });
    const json = await res.json();
    if (json.status==='success') loadMaterials();
    else alert('Error al eliminar.');
  }

  function resetForm() {
    document.getElementById('materialForm').reset();
    document.getElementById('id').value = '';
    document.getElementById('cancelEdit')
            .classList.add('hidden');
  }
  </script>
</body>
</html>
