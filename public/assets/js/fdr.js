const menuToggle = document.querySelector("[data-menu-toggle]");
const siteNav = document.querySelector("[data-site-nav]");
const quoteButtons = document.querySelectorAll("[data-quote]");
const interestField = document.querySelector("[data-interest]");
const quoteForm = document.querySelector("[data-quote-form]");
const leadIdField = document.querySelector("[data-lead-id]");
const phoneField = document.querySelector("[data-phone-field]");
const whatsappLinks = document.querySelectorAll('a[href^="registrar-whatsapp.php"]');
let leadSaveTimer = 0;
let leadWasSaved = false;

function trackEvent(name, params = {}) {
  if (typeof window.gtag !== "function") {
    return;
  }

  window.gtag("event", name, {
    transport_type: "beacon",
    ...params,
  });
}

function linkParams(link) {
  const url = new URL(link.href, window.location.href);
  return {
    event_category: "whatsapp",
    source: url.searchParams.get("source") || "desconocido",
    tipo: url.searchParams.get("tipo") || "consulta",
    interes: url.searchParams.get("interes") || "Cotizacion general",
  };
}

function createLeadId() {
  if (window.crypto?.randomUUID) {
    return window.crypto.randomUUID();
  }
  return `lead-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function getLeadId() {
  const storageKey = "fdr_lead_id";
  let leadId = "";

  try {
    leadId = window.sessionStorage.getItem(storageKey) || "";
    if (!leadId) {
      leadId = createLeadId();
      window.sessionStorage.setItem(storageKey, leadId);
    }
  } catch (error) {
    leadId = createLeadId();
  }

  if (leadIdField) {
    leadIdField.value = leadId;
  }

  return leadId;
}

function hasEnoughPhone(value) {
  return value.replace(/\D/g, "").length >= 8;
}

function buildLeadPayload() {
  if (!quoteForm) {
    return null;
  }

  const formData = new FormData(quoteForm);
  formData.set("lead_id", getLeadId());
  formData.set("evento", "formulario_parcial");
  return formData;
}

function saveLead({ immediate = false } = {}) {
  if (!quoteForm || !phoneField || !hasEnoughPhone(phoneField.value)) {
    return;
  }

  const payload = buildLeadPayload();
  if (!payload) {
    return;
  }

  leadWasSaved = true;

  if (immediate && navigator.sendBeacon) {
    navigator.sendBeacon("registrar-lead.php", payload);
    return;
  }

  fetch("registrar-lead.php", {
    method: "POST",
    body: payload,
    keepalive: true,
  }).catch(() => {});
}

function scheduleLeadSave() {
  window.clearTimeout(leadSaveTimer);
  leadSaveTimer = window.setTimeout(() => saveLead(), 700);
}

if (menuToggle && siteNav) {
  menuToggle.addEventListener("click", () => {
    const isOpen = siteNav.classList.toggle("is-open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
  });

  siteNav.addEventListener("click", (event) => {
    if (event.target instanceof HTMLAnchorElement) {
      siteNav.classList.remove("is-open");
      menuToggle.setAttribute("aria-expanded", "false");
    }
  });
}

quoteButtons.forEach((button) => {
  button.addEventListener("click", () => {
    const value = button.getAttribute("data-quote") || "";
    trackEvent("seleccionar_linea_formulario", {
      event_category: "formulario",
      interes: value || "Sin dato",
    });
    if (interestField) {
      interestField.value = value;
    }
    document.querySelector("#consulta")?.scrollIntoView({ behavior: "smooth", block: "start" });
  });
});

whatsappLinks.forEach((link) => {
  link.addEventListener("click", () => {
    trackEvent("click_whatsapp", linkParams(link));
  });
});

if (quoteForm) {
  getLeadId();

  quoteForm.addEventListener("input", scheduleLeadSave);
  quoteForm.addEventListener("change", scheduleLeadSave);
  quoteForm.addEventListener("submit", () => {
    const formData = new FormData(quoteForm);
    trackEvent("enviar_formulario_whatsapp", {
      event_category: "formulario",
      interes: String(formData.get("interes") || "Sin dato"),
      condicion: String(formData.get("condicion") || "Sin dato"),
    });
    saveLead({ immediate: true });
  });

  window.addEventListener("beforeunload", () => {
    if (!leadWasSaved) {
      saveLead({ immediate: true });
    }
  });
}
