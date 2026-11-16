(() => {
  const statusLabels = {
    RECEBIDO: "Recebido",
    CONFIRMADO: "Confirmado",
    EM_PREPARO: "Em preparo",
    SAIU_PARA_ENTREGA: "Saiu para entrega",
    ENTREGUE: "Entregue",
    CANCELADO: "Cancelado",
  };
  const orderCards = new Map(
    Array.from(document.querySelectorAll(".order-card[data-order-id]")).map((card) => [
      card.dataset.orderId,
      {
        card,
        status: card.dataset.orderStatus,
        name: card.querySelector(".order-header h2")?.textContent || "Estabelecimento",
      },
    ])
  );
  const alerts = document.getElementById("order-alerts");
  const notificationButton = document.getElementById("enable-order-notifications");
  const steps = ["RECEBIDO", "CONFIRMADO", "EM_PREPARO", "SAIU_PARA_ENTREGA", "ENTREGUE"];
  let pollingErrorShown = false;

  function showAlert(title, message) {
    const alert = document.createElement("div");
    alert.className = "order-alert";
    const heading = document.createElement("strong");
    heading.textContent = title;
    const description = document.createElement("span");
    description.textContent = message;
    alert.append(heading, description);
    alerts.append(alert);
    window.setTimeout(() => alert.remove(), 10000);
  }

  function showBrowserNotification(title, message) {
    if (!("Notification" in window) || Notification.permission !== "granted") return;
    const notification = new Notification(title, { body: message });
    notification.onclick = () => {
      window.focus();
      notification.close();
    };
  }

  function updateProgress(card, status) {
    const currentProgress = card.querySelector(".order-progress");
    const currentCancelled = card.querySelector(".order-cancelled");

    if (status === "CANCELADO") {
      currentProgress?.remove();
      if (!currentCancelled) {
        const message = document.createElement("p");
        message.className = "order-cancelled";
        message.setAttribute("role", "status");
        message.textContent = "Este pedido foi cancelado pelo estabelecimento.";
        card.querySelector(".order-header").after(message);
      }
      return;
    }

    currentCancelled?.remove();
    const progress = currentProgress || document.createElement("ol");
    progress.className = "order-progress";
    progress.setAttribute("aria-label", "Etapas do pedido");
    progress.replaceChildren();
    const currentStep = steps.indexOf(status);

    steps.forEach((step, index) => {
      const item = document.createElement("li");
      item.className = index < currentStep ? "is-complete" : index === currentStep ? "is-current" : "";
      const dot = document.createElement("span");
      dot.className = "order-progress-dot";
      dot.setAttribute("aria-hidden", "true");
      const label = document.createElement("span");
      label.textContent = statusLabels[step];
      item.append(dot, label);
      progress.append(item);
    });

    if (!currentProgress) card.querySelector(".order-header").after(progress);
  }

  function updateCard(order, status) {
    if (!order) return;
    const badge = order.card.querySelector(".order-status");
    badge.className = `order-status order-status-${status.toLowerCase()}`;
    badge.textContent = statusLabels[status];
    order.card.dataset.orderStatus = status;
    updateProgress(order.card, status);
  }

  async function checkOrderUpdates() {
    try {
      const response = await fetch("atualizacoes-pedidos.php", {
        cache: "no-store",
        headers: { Accept: "application/json" },
      });
      if (!response.ok) throw new Error("Não foi possível consultar os pedidos.");

      const result = await response.json();
      if (!Array.isArray(result.pedidos)) throw new Error("Resposta inválida ao consultar os pedidos.");
      if (pollingErrorShown) {
        pollingErrorShown = false;
        showAlert("Conexão restabelecida", "Voltamos a verificar as atualizações dos pedidos.");
      }

      let novoPedidoEncontrado = false;
      for (const pedido of result.pedidos) {
        const id = String(pedido.id);
        const status = pedido.status;
        if (!statusLabels[status]) continue;

        const order = orderCards.get(id);
        if (!order) {
          orderCards.set(id, { status, name: pedido.estabelecimento });
          novoPedidoEncontrado = true;
          continue;
        }
        if (order.status === status) continue;

        const title = `Pedido #${id} atualizado`;
        const message = `${pedido.estabelecimento}: ${statusLabels[status]}.`;
        showAlert(title, message);
        showBrowserNotification(title, message);
        order.status = status;
        updateCard(order, status);
      }

      if (novoPedidoEncontrado) {
        const title = "Novo pedido recebido";
        showAlert(title, "Um novo pedido foi registrado. Atualizando a lista...");
        showBrowserNotification(title, "Um novo pedido foi registrado.");
        window.setTimeout(() => window.location.reload(), 3000);
      }
    } catch (error) {
      if (!pollingErrorShown) {
        pollingErrorShown = true;
        showAlert("Falha ao verificar atualizações", "Confira sua conexão. Tentaremos novamente em alguns segundos.");
      }
    }
  }

  if ("Notification" in window && notificationButton) {
    notificationButton.hidden = false;
    if (Notification.permission === "granted") {
      notificationButton.textContent = "Notificações do navegador ativadas";
      notificationButton.disabled = true;
    } else if (Notification.permission === "denied") {
      notificationButton.textContent = "Notificações bloqueadas pelo navegador";
      notificationButton.disabled = true;
    } else {
      notificationButton.addEventListener("click", async () => {
        const permission = await Notification.requestPermission();
        if (permission === "granted") {
          notificationButton.textContent = "Notificações do navegador ativadas";
          notificationButton.disabled = true;
          showAlert("Notificações ativadas", "Você receberá avisos do navegador enquanto esta página estiver aberta.");
        } else {
          notificationButton.textContent = "Permita notificações nas configurações do navegador";
          notificationButton.disabled = true;
        }
      });
    }
  }

  checkOrderUpdates();
  window.setInterval(checkOrderUpdates, 15000);
})();
