// main.js

async function fetchActivities() {
  const resp = await fetch("/api/fetch_activities.php");
  return await resp.json();
}

// ----- Pour index.html -----
async function displayAllRandonnees() {
  const data = await fetchActivities();

  const map = L.map("map").setView([48.8566, 2.3522], 6); // centré sur France
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  const listDiv = document.getElementById("rando-list");
  data.forEach((act) => {
    // Liste des liens
    const a = document.createElement("a");
    a.href = `details/details.html?id=${act.id}`;
    a.className = "rando-link";
    a.textContent = act.name;
    listDiv.appendChild(a);

    // Tracé sur la carte globale si polyline dispo
    if (act.map && act.map.summary_polyline) {
      const latlngs = polyline
        .decode(act.map.summary_polyline)
        .map((p) => [p[0], p[1]]);
      const line = L.polyline(latlngs, { color: "blue" }).addTo(map);
      map.fitBounds(line.getBounds());
    }
  });
}

// ----- Pour details.html -----
async function displayRandoDetail() {
  const params = new URLSearchParams(window.location.search);
  const id = params.get("id");
  if (!id) return;

  const data = await fetchActivities();
  const act = data.find((a) => a.id == id);
  if (!act) return;

  document.getElementById("rando-name").textContent = act.name;
  document.getElementById("rando-info").textContent =
    `Distance: ${(act.distance / 1000).toFixed(2)} km, ` +
    `Dénivelé: ${act.total_elevation_gain} m, ` +
    `Date: ${new Date(act.start_date).toLocaleDateString()}`;

  const map = L.map("map").setView([48.8566, 2.3522], 12);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  if (act.map && act.map.summary_polyline) {
    const latlngs = polyline
      .decode(act.map.summary_polyline)
      .map((p) => [p[0], p[1]]);
    const line = L.polyline(latlngs, { color: "blue" }).addTo(map);
    map.fitBounds(line.getBounds());
  }
}
