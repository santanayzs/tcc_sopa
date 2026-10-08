(() => {
  const form = document.getElementById("delivery-settings-form");
  if (!form) return;

  const locationOptions = Array.from(form.querySelectorAll('input[name="location_type"]'));
  const gpsControl = document.getElementById("delivery-gps-control");
  const addressFields = document.getElementById("business-address-fields");
  const addressInputs = Array.from(addressFields.querySelectorAll("input"));
  const gpsButton = document.getElementById("capture-business-location");
  const latitudeInput = document.getElementById("business-latitude");
  const longitudeInput = document.getElementById("business-longitude");
  const status = document.getElementById("business-location-status");

  function updateLocationMethod() {
    const usesGps = form.querySelector('input[name="location_type"]:checked')?.value === "gps";
    gpsControl.hidden = !usesGps;
    addressFields.hidden = usesGps;

    addressInputs.forEach((input) => {
      input.disabled = usesGps;
      input.required = !usesGps && input.name !== "numero";
    });
  }

  locationOptions.forEach((option) => option.addEventListener("change", updateLocationMethod));
  updateLocationMethod();

  gpsButton.addEventListener("click", () => {
    if (!navigator.geolocation) {
      status.textContent = "GPS indisponível neste navegador. Escolha informar o endereço.";
      return;
    }

    status.textContent = "Aguardando permissão de localização...";
    navigator.geolocation.getCurrentPosition(
      (position) => {
        latitudeInput.value = position.coords.latitude.toFixed(7);
        longitudeInput.value = position.coords.longitude.toFixed(7);
        status.textContent = "Localização GPS capturada. Salve para registrar o local do estabelecimento.";
      },
      () => {
        status.textContent = "Não foi possível obter o GPS. Permita o acesso ou informe o endereço.";
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
    );
  });
})();