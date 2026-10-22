const feedback = document.getElementById("feedback");

const moduleRoutes = {
  proximos: "trabajos",
  completados: "trabajos",
  alertas: "repuestos",
  trabajos: "trabajos",
  repuestos: "repuestos",
  mantenimiento: "mantenimiento",
  clientes: "clientes",
  servicios: "servicios",
  pedidos: "mis_pedidos.php",
  reportes: "reportes",
  perfil: "perfil"
};

function setFeedback(message, type = "") {
  if (!feedback) {
    return;
  }

  feedback.textContent = message;
  feedback.className = `feedback ${type}`.trim();
}

function bindPressAction(element, onPress) {
  element.addEventListener("click", (event) => onPress(event));
  element.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      onPress(event);
    }
  });
}

function shouldOpenDashboard() {
  const params = new URLSearchParams(window.location.search);
  return params.get("view") === "dashboard";
}

function showFeedbackFromQuery() {
  const params = new URLSearchParams(window.location.search);
  const msg = params.get("msg");
  const type = params.get("type");

  if (msg) {
    setFeedback(msg, type || "");
  }
}

function renderDashboard() {
  const template = document.getElementById("dashboardTemplate");

  if (!template) {
    return;
  }

  const dashboardNode = template.content.cloneNode(true);
  const welcome = dashboardNode.querySelector(".welcome");

  if (welcome && window.userData?.nombre) {
    welcome.textContent = `Hola, ${window.userData.nombre}`;
  }

  document.body.innerHTML = "";
  document.body.appendChild(dashboardNode);

  bindModuleCards(".menu-card");
  bindModuleCards(".stat-card");
}

function bindModuleCards(selector) {
  document.querySelectorAll(selector).forEach((card) => {
    card.setAttribute("role", "button");
    card.setAttribute("tabindex", "0");
    bindPressAction(card, () => openModule(card.dataset.module));
  });
}

function openModule(moduleId) {
  const moduleRoute = moduleRoutes[moduleId];

  if (!moduleRoute) {
    return;
  }

  window.location.href = moduleRoute.endsWith(".php")
    ? `./${moduleRoute}`
    : `./module.php?module=${encodeURIComponent(moduleRoute)}`;
}

if (shouldOpenDashboard() || window.isLoggedIn) {
  renderDashboard();
}

showFeedbackFromQuery();
