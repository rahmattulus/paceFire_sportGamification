(function () {
  const canvas = document.querySelector('#dashboard-chart');
  const dataNode = document.querySelector('#dashboard-chart-data');
  const tooltip = document.querySelector('#chart-tooltip');
  if (!canvas || !dataNode) return;

  const data = JSON.parse(dataNode.textContent);

  function roundedBar(context, x, y, width, height, radius) {
    const actualRadius = Math.min(radius, height / 2, width / 2);
    context.beginPath();
    context.moveTo(x + actualRadius, y);
    context.lineTo(x + width - actualRadius, y);
    context.quadraticCurveTo(x + width, y, x + width, y + actualRadius);
    context.lineTo(x + width, y + height);
    context.lineTo(x, y + height);
    context.lineTo(x, y + actualRadius);
    context.quadraticCurveTo(x, y, x + actualRadius, y);
    context.closePath();
  }

  function drawChart() {
    const rect = canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvas.width = Math.max(280, rect.width) * dpr;
    canvas.height = 280 * dpr;
    const context = canvas.getContext('2d');
    context.scale(dpr, dpr);

    const width = canvas.width / dpr;
    const height = canvas.height / dpr;
    const padding = { top: 24, right: 12, bottom: 42, left: 42 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    const maxPoints = Math.max(100, ...data.map((item) => item.points));
    const gridSteps = 4;

    context.clearRect(0, 0, width, height);
    context.font = '12px Nunito, Apercu, sans-serif';
    context.textAlign = 'right';
    context.textBaseline = 'middle';

    for (let step = 0; step <= gridSteps; step++) {
      const y = padding.top + (chartHeight / gridSteps) * step;
      const value = Math.round(maxPoints - (maxPoints / gridSteps) * step);
      context.strokeStyle = '#E8DFC9';
      context.lineWidth = 1;
      context.beginPath();
      context.moveTo(padding.left, y);
      context.lineTo(width - padding.right, y);
      context.stroke();
      context.fillStyle = '#8E7DA5';
      context.fillText(String(value), padding.left - 8, y);
    }

    const slotWidth = chartWidth / data.length;
    const barWidth = Math.max(18, Math.min(56, slotWidth * 0.58));
    canvas._bars = [];

    data.forEach((item, index) => {
      const x = padding.left + slotWidth * index + (slotWidth - barWidth) / 2;
      const barHeight = item.points > 0 ? (item.points / maxPoints) * chartHeight : 3;
      const y = padding.top + chartHeight - barHeight;
      const isToday = index === data.length - 1;
      const isHighest = item.points === Math.max(...data.map((entry) => entry.points));

      context.fillStyle = isToday || isHighest ? '#FF6B35' : '#B4C68F';
      roundedBar(context, x, y, barWidth, barHeight, 6);
      context.fill();

      context.fillStyle = '#594675';
      context.textAlign = 'center';
      context.textBaseline = 'top';
      context.fillText(item.label, x + barWidth / 2, height - padding.bottom + 12);

      canvas._bars.push({ x, y, width: barWidth, height: barHeight, item });
    });
  }

  function showTooltip(event) {
    if (!canvas._bars || !tooltip) return;
    const rect = canvas.getBoundingClientRect();
    const clientX = event.touches ? event.touches[0].clientX : event.clientX;
    const clientY = event.touches ? event.touches[0].clientY : event.clientY;
    const x = clientX - rect.left;
    const y = clientY - rect.top;
    const bar = canvas._bars.find((entry) =>
      x >= entry.x && x <= entry.x + entry.width &&
      y >= entry.y - 8 && y <= entry.y + entry.height + 8
    );

    if (!bar) {
      tooltip.hidden = true;
      return;
    }

    tooltip.innerHTML =
      `<strong>${bar.item.date}</strong><br>` +
      `${bar.item.distance.toFixed(2)} km<br>` +
      `${bar.item.points} poin`;
    tooltip.hidden = false;
    tooltip.style.left = `${Math.max(8, clientX - rect.left - 45)}px`;
    tooltip.style.top = `${Math.max(8, clientY - rect.top - 72)}px`;
  }

  window.addEventListener('resize', drawChart);
  canvas.addEventListener('mousemove', showTooltip);
  canvas.addEventListener('mouseleave', () => { tooltip.hidden = true; });
  canvas.addEventListener('touchstart', showTooltip, { passive: true });
  canvas.addEventListener('touchend', () => {
    window.setTimeout(() => { tooltip.hidden = true; }, 1800);
  });
  drawChart();
})();
