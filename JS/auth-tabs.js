function showPanel(panelName) {
  document.querySelectorAll(".form-panel").forEach(function (panel) {
    panel.classList.toggle("active", panel.id === "panel-" + panelName);
  });

  document.querySelectorAll(".tab").forEach(function (tab) {
    tab.classList.toggle(
      "active",
      tab.getAttribute("data-panel") === panelName,
    );
  });
}

document.querySelectorAll(".tab").forEach(function (tab) {
  tab.addEventListener("click", function () {
    showPanel(this.getAttribute("data-panel"));
  });
});

const params = new URLSearchParams(window.location.search);
const errorMessage = document.getElementById("msg-error");
const successMessage = document.getElementById("msg-success");

if (params.get("erro") === "1" && errorMessage) {
  errorMessage.style.display = "block";
}

if (params.get("cadastro") === "ok" && successMessage) {
  successMessage.style.display = "block";
  showPanel("login");
}
