// 1. Defina a data do seu evento aqui (Mês Dia, Ano Hora:Minuto:Segundo)
const targetDate = new Date("Jul 10, 2027 16:00:00").getTime();

// 2. Atualiza a contagem a cada 1 segundo (1000 milissegundos)
const timerInterval = setInterval(function() {
  
  // Pega a data e hora atual
  const now = new Date().getTime();
  
  // Encontra a diferença entre a data do evento e agora
  const distance = targetDate - now;
  
  // 3. Cálculos matemáticos para converter milissegundos em dias, horas, min e seg
  const days = Math.floor(distance / (1000 * 60 * 60 * 24));
  const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
  const seconds = Math.floor((distance % (1000 * 60)) / 1000);
  
  // 4. Injeta os resultados no HTML
  // O .toString().padStart(2, '0') garante que números menores que 10 fiquem com um zero na frente (ex: 04)
  document.getElementById("days").innerText = days.toString().padStart(2, '0');
  document.getElementById("hours").innerText = hours.toString().padStart(2, '0');
  document.getElementById("minutes").innerText = minutes.toString().padStart(2, '0');
  document.getElementById("seconds").innerText = seconds.toString().padStart(2, '0');
  
  // 5. O que acontece quando o momento chega
  if (distance < 0) {
    clearInterval(timerInterval); // Para o relógio
    document.getElementById("countdown").innerHTML = "<h2>Chegou o grande dia!</h2>";
  }
  
}, 1000);