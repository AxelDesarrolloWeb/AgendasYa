<div class="semana-container">
  <button id="prevSemana">⬅</button>
  <div class="semana" id="semana">
    <!-- Aquí JS insertará los días -->
  </div>
  <button id="nextSemana">➡</button>
</div>

<div id="franjas">
  <p>Selecciona un día para ver las franjas horarias</p>
</div>

<style>
.semana-container {
  display: flex;
  align-items: center;
  margin: 20px 0;
}

.semana {
  display: flex;
  overflow: hidden;
  gap: 10px;
  flex: 1;
  justify-content: center;
}

.dia {
  min-width: 80px;
  padding: 10px;
  text-align: center;
  border: 1px solid #ccc;
  cursor: pointer;
  border-radius: 6px;
  background: #f9f9f9;
}

.dia:hover {
  background: #eee;
}
</style>

<script>
const diasSemana = ["Lu", "Ma", "Mi", "Ju", "Vi", "Sá", "Do"];
let fechaBase = new Date(); // hoy
let semanaActual = 0;

function renderSemana(semanaOffset = 0) {
  const contenedor = document.getElementById("semana");
  contenedor.innerHTML = "";

  // Calculamos la fecha de inicio de la semana seleccionada
  let inicioSemana = new Date(fechaBase);
  inicioSemana.setDate(inicioSemana.getDate() + (semanaOffset * 7));

  for (let i = 0; i < 7; i++) {
    let dia = new Date(inicioSemana);
    dia.setDate(inicioSemana.getDate() + i);

    let div = document.createElement("div");
    div.classList.add("dia");
    div.dataset.fecha = dia.toISOString().split("T")[0];
    div.innerText = diasSemana[i] + " " + dia.getDate();

    div.addEventListener("click", () => cargarFranjas(div.dataset.fecha));

    contenedor.appendChild(div);
  }
}

function cargarFranjas(fecha) {
  // Aquí deberías hacer un fetch a PHP/Mysql
  // Ejemplo simulado:
  const franjas = {
    "08:00-10:00": "alta",
    "12:00-14:00": "media",
    "18:00-20:00": "baja"
  };

  let div = document.getElementById("franjas");
  div.innerHTML = `<h3>Franjas para ${fecha}</h3>`;

  if (Object.keys(franjas).length === 0) {
    div.innerHTML += `<p>No hay franjas disponibles</p>`;
  } else {
    for (let franja in franjas) {
      div.innerHTML += `<p>${franja} - Demanda: ${franjas[franja]}</p>`;
    }
  }
}

// Botones deslizador
document.getElementById("prevSemana").addEventListener("click", () => {
  if (semanaActual > 0) {
    semanaActual--;
    renderSemana(semanaActual);
  }
});

document.getElementById("nextSemana").addEventListener("click", () => {
  if (semanaActual < 3) { // máximo 4 semanas
    semanaActual++;
    renderSemana(semanaActual);
  }
});

// Render inicial
renderSemana();
</script>
