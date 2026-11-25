const sportSelect = document.querySelector('#jenis-olahraga');
const routeSelect = document.querySelector('#route-id');
const distanceInput = document.querySelector('#jarak-km');

function filterRoutes() {
  const sport = sportSelect.value;
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
}

sportSelect.addEventListener('change', () => {
  filterRoutes();
  applyRoute();
});
routeSelect.addEventListener('change', applyRoute);
filterRoutes();
applyRoute();
