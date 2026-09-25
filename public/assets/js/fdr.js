const menuToggle = document.querySelector("[data-menu-toggle]");
const siteNav = document.querySelector("[data-site-nav]");
const quoteButtons = document.querySelectorAll("[data-quote]");
const interestField = document.querySelector("[data-interest]");
const quoteForm = document.querySelector("[data-quote-form]");
const leadIdField = document.querySelector("[data-lead-id]");
const phoneField = document.querySelector("[data-phone-field]");
let leadSaveTimer = 0;
let leadWasSaved = false;

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
    if (interestField) {
      interestField.value = value;
    }
    document.querySelector("#consulta")?.scrollIntoView({ behavior: "smooth", block: "start" });
  });
});

if (quoteForm) {
  getLeadId();

  quoteForm.addEventListener("input", scheduleLeadSave);
  quoteForm.addEventListener("change", scheduleLeadSave);
  quoteForm.addEventListener("submit", () => {
    saveLead({ immediate: true });
  });

  window.addEventListener("beforeunload", () => {
    if (!leadWasSaved) {
      saveLead({ immediate: true });
    }
  });
}
