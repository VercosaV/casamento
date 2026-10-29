(function () {
  const el = document.getElementById('countdown');
  if (!el) return; // página sem contagem: não faz nada

  const alvo = new Date(el.dataset.target).getTime();
  const doisDigitos = (n) => String(n).padStart(2, '0');

  function mostrar(id, valor) {
    const no = document.getElementById(id);
    if (no) no.textContent = doisDigitos(valor);
  }

  function tick() {
    const falta = alvo - Date.now();

    if (falta <= 0) {
      clearInterval(timer);
      el.innerHTML = '<h2>Chegou o grande dia!</h2>';
      return;
    }

    mostrar('days', Math.floor(falta / 86400000));
    mostrar('hours', Math.floor((falta % 86400000) / 3600000));
    mostrar('minutes', Math.floor((falta % 3600000) / 60000));
    mostrar('seconds', Math.floor((falta % 60000) / 1000));
  }

  const timer = setInterval(tick, 1000);
  tick(); // desenha já, sem esperar 1 segundo
})();