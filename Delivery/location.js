(() => {
  const form = document.getElementById("delivery-location-form");
  const gpsButton = document.getElementById("use-customer-gps");
  const addressInput = document.getElementById("customer-address");
  const locationType = document.getElementById("customer-location-type");
  const latitudeInput = document.getElementById("customer-latitude");
  const longitudeInput = document.getElementById("customer-longitude");
  const status = document.getElementById("customer-location-status");
  const changeButton = document.getElementById("change-delivery-location");
  const confirmation = document.querySelector(".delivery-location-confirmation");
  const listing = document.getElementById("estabelecimentos");

  if (!form) return;

  gpsButton.addEventListener("click", () => {
    if (!navigator.geolocation) {
      status.textContent = "GPS indisponível neste navegador. Informe seu endereço para continuar.";
      return;
    }

    status.textContent = "Aguardando permissão de localização...";
    navigator.geolocation.getCurrentPosition(
      (position) => {
        locationType.value = "gps";
        latitudeInput.value = position.coords.latitude.toFixed(7);
        longitudeInput.value = position.coords.longitude.toFixed(7);
        addressInput.required = false;
        status.textContent = "Localização obtida. Confirmando...";
        form.requestSubmit();
      },
      () => {
        locationType.value = "endereco";
        addressInput.required = true;
        status.textContent = "Não foi possível obter o GPS. Informe seu endereço para continuar.";
        addressInput.focus();
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
    );
  });

  form.addEventListener("submit", (event) => {
    if (locationType.value === "gps") return;

    locationType.value = "endereco";
    addressInput.required = true;
    if (!addressInput.value.trim()) {
      event.preventDefault();
      addressInput.focus();
    }
  });

  changeButton?.addEventListener("click", () => {
    form.hidden = false;
    confirmation.hidden = true;
    listing.hidden = true;
    addressInput.focus();
  });
})();