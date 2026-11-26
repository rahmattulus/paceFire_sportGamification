const sportInputs = [...document.querySelectorAll('input[name="jenis_olahraga"]')];
const routeSelect = document.querySelector('#route-id');
const distanceInput = document.querySelector('#jarak-km');
const durationInput = document.querySelector('#durasi-menit');
const activityForm = document.querySelector('#activity-form');
const paceOutput = document.querySelector('#estimate-pace');
const pointsOutput = document.querySelector('#estimate-points');

function selectedSport() {
  return sportInputs.find((input) => input.checked)?.value || 'lari';
}

function filterRoutes() {
  const sport = selectedSport();
  let visibleRoute = false;

  [...routeSelect.options].forEach((option) => {
    if (!option.value) {
      option.hidden = false;
      return;
    }

    const matches = option.dataset.type === sport;
    option.hidden = !matches;
    if (!matches && option.selected) {
      routeSelect.value = '';
      distanceInput.value = '';
      distanceInput.readOnly = false;
    }
    visibleRoute = visibleRoute || matches;
  });

  if (!visibleRoute) {
    routeSelect.value = '';
  }
}

function applyRoute() {
  const option = routeSelect.options[routeSelect.selectedIndex];
  if (routeSelect.value && option.dataset.distance) {
    distanceInput.value = option.dataset.distance;
    distanceInput.readOnly = true;
  } else {
    distanceInput.readOnly = false;
  }
  updateEstimate();
}

function updateEstimate() {
  const distance = Number(distanceInput.value);
  const duration = Number(durationInput.value);
  const sport = selectedSport();

  if (!Number.isFinite(distance) || distance <= 0 || !Number.isFinite(duration) || duration <= 0) {
    paceOutput.textContent = '—';
    pointsOutput.textContent = '—';
    return;
  }

  if (sport === 'lari') {
    const paceSeconds = Math.round((duration * 60) / distance);
    const minutes = Math.floor(paceSeconds / 60);
    const seconds = String(paceSeconds % 60).padStart(2, '0');
    paceOutput.textContent = `${minutes}:${seconds} /km`;
  } else {
    paceOutput.textContent = `${(distance / (duration / 60)).toFixed(1)} km/jam`;
  }

  const rate = Number(sport === 'lari' ? activityForm.dataset.runningRate : activityForm.dataset.cyclingRate);
  const basePoints = Math.floor(distance * rate);
  const streak = Number(activityForm.dataset.currentStreak);
  const streakBonusRate = streak >= 14 ? 0.5 : streak >= 7 ? 0.25 : streak >= 3 ? 0.1 : 0;
  const dailyBonus = activityForm.dataset.firstActivity === '1' ? 5 : 0;
  const estimatedPoints = Math.min(
    basePoints + Math.floor(basePoints * streakBonusRate) + dailyBonus,
    Number(activityForm.dataset.remainingPoints),
  );
  pointsOutput.textContent = `+${Math.max(0, estimatedPoints)} poin`;
}

sportInputs.forEach((input) => input.addEventListener('change', () => {
  filterRoutes();
  applyRoute();
  updateEstimate();
}));
routeSelect.addEventListener('change', applyRoute);
distanceInput.addEventListener('input', updateEstimate);
durationInput.addEventListener('input', updateEstimate);
filterRoutes();
applyRoute();
updateEstimate();
