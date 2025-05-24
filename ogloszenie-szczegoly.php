<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Moje Ogłoszenia</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f9f9f9;
      display: flex;
    }
    .sidebar {
      width: 250px;
      background-color: #222;
      color: white;
      padding: 20px;
      height: 100vh;
      box-sizing: border-box;
    }
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 10px 0;
      border-bottom: 1px solid #444;
    }
    .container {
      flex: 1;
      padding: 20px;
    }
    .sort-section {
      margin-bottom: 20px;
    }
    .sort-section label {
      font-size: 16px;
      margin-right: 10px;
    }
    .sort-section select {
      font-size: 16px;
      padding: 5px 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .add-ad-section, .ad-box {
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      display: flex;
      align-items: center;
      padding: 20px;
      margin-top: 10px;
      position: relative;
    }
    .add-ad-button, .details-link {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: black;
    }
    .custom-image {
      width: 50px;
      height: 50px;
      background-color: #4CAF50;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
    }
    .custom-image::before {
      content: "+";
      font-size: 24px;
      color: white;
      font-weight: bold;
    }
    .delete-button {
      width: 40px;
      height: 40px;
      background-color: #e53935;
      border-radius: 50%;
      color: white;
      font-size: 20px;
      font-weight: bold;
      position: absolute;
      left: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .tooltip {
      position: absolute;
      bottom: -28px;
      left: 0;
      background-color: white;
      color: black;
      font-size: 12px;
      padding: 4px 6px;
      border-radius: 5px;
      box-shadow: 0 2px 6px #999;
      display: none;
      white-space: nowrap;
    }
  </style>
</head>
<body>
<div class="sidebar">
  <h3>Panel użytkownika</h3>
  <a href="panel-klienta.php">Panel Klienta</a>
  <a href="moje-ogloszenia.html">Moje ogłoszenia</a>
  <a href="#">Sprzedaż</a>
  <a href="#">Nieruchomości</a>
  <a href="#">Zlecenia</a>
  <a href="#">Firmy</a>
  <a href="#">Kontakt</a>
  <a href="#">Wyloguj</a>
</div>
<div class="container">
  <div class="sort-section">
    <label for="sort">Sortuj:</label>
    <select id="sort">
      <option value="newest" selected>Od najnowszych</option>
      <option value="oldest">Od najstarszych</option>
    </select>
  </div>
  <div class="add-ad-section">
    <a href="ogloszenie-form.html" class="add-ad-button">
      <div class="custom-image"></div>
      <span>Dodaj darmowe ogłoszenie</span>
    </a>
  </div>
  <div id="adsContainer"></div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('adsContainer');
    const sortSelect = document.getElementById('sort');

    async function fetchAds() {
      const response = await fetch('ogloszenia.json');
      const data = await response.json();
      return data;
    }

    function renderAds(data) {
      container.innerHTML = '';
      const sorted = [...data];
      if (sortSelect.value === 'oldest') sorted.reverse();

      sorted.forEach(ad => {
        const box = document.createElement('div');
        box.className = 'ad-box';

        const link = document.createElement('a');
        link.href = `ogloszenie-szczegoly.php?id=${ad.id}`;
        link.className = 'details-link';

        const title = document.createElement('span');
        title.textContent = ad.adTitle;
        title.style.marginLeft = '15px';
        title.style.fontSize = '18px';
        title.style.fontWeight = 'bold';

        link.appendChild(title);
        box.appendChild(link);

        const del = document.createElement('div');
        del.className = 'delete-button';
        del.textContent = '-';

        const tip = document.createElement('div');
        tip.className = 'tooltip';
        tip.textContent = 'Usuń ogłoszenie';

        del.appendChild(tip);
        del.addEventListener('mouseenter', () => tip.style.display = 'block');
        del.addEventListener('mouseleave', () => tip.style.display = 'none');

        del.addEventListener('click', async () => {
          await fetch(`usun-ogloszenie.php?id=${ad.id}`);
          loadAds();
        });

        box.appendChild(del);
        container.appendChild(box);
      });
    }

    async function loadAds() {
      const ads = await fetchAds();
      renderAds(ads);
    }

    sortSelect.addEventListener('change', loadAds);
    loadAds();
  });
</script>
</body>
</html>
