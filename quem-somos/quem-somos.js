// Animação simples de entrada dos cards da equipe ao rolar a página.
// Usa IntersectionObserver; se o navegador não suportar, os cards
// aparecem normalmente (sem animação) graças ao fallback abaixo.

document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".team-card");

  if (!cards.length) return;

  if (!("IntersectionObserver" in window)) {
    cards.forEach((card) => card.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          obs.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.2 }
  );

  cards.forEach((card, index) => {
    card.style.transitionDelay = `${index * 80}ms`;
    observer.observe(card);
  });
});
