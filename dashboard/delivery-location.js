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
  const cepInput = addressFields.querySelector('input[name="cep"]');
  const cepStatus = document.getElementById("business-cep-status");
  let cepRequest;
  let cepLookupPromise = Promise.resolve();

  async function fillAddressFromCep() {
    const cep = cepInput.value.replace(/\D/g, "");
    cepRequest?.abort();
    if (cep.length !== 8) {
      form.noValidate = false;
      cepStatus.textContent = "";
      return;
    }

    const request = new AbortController();
    cepRequest = request;
    form.noValidate = true;
    cepStatus.textContent = "Buscando endereço...";

    try {
      const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, {
        signal: request.signal,
      });
      if (!response.ok) throw new Error("Falha na consulta");

      const address = await response.json();
      if (address.erro) {
        cepStatus.textContent = "CEP não encontrado.";
        return;
      }

      for (const field of ["logradouro", "bairro", "cidade", "estado"]) {
        const input = addressFields.querySelector(`[name="${field}"]`);
        const responseField = { cidade: "localidade", estado: "uf" }[field] || field;
        input.value = address[responseField] || "";
      }
      cepStatus.textContent = "Endereço preenchido. Informe o número.";
    } catch (error) {
      if (error.name !== "AbortError") {
        cepStatus.textContent = "Não foi possível consultar o CEP. Preencha o endereço manualmente.";
      }
    } finally {
      if (cepRequest === request) form.noValidate = false;
    }
  }

  cepInput.addEventListener("input", () => {
    cepLookupPromise = fillAddressFromCep();
  });

  form.addEventListener("submit", async (event) => {
    if (cepStatus.textContent !== "Buscando endereço...") return;

    event.preventDefault();
    while (cepStatus.textContent === "Buscando endereço...") {
      await cepLookupPromise;
    }

    if (!form.reportValidity()) return;

    if (event.submitter) {
      form.requestSubmit(event.submitter);
    } else {
      form.requestSubmit();
    }
  });

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