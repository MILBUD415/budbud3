// Edycja pól tekstowych ze wsparciem Entera (zatwierdzanie)
function editField(field) {
  const fields = {
    adTitle: ['adTitleText', 'adTitleInput', 'adTitleBar'],
    investor: ['investorText', 'investorInput', 'investorBox'],
    desc: ['descText', 'descInput', 'descBox'],
    address: ['addressText', 'addressInput', 'addressBox'],
    contact: ['contactText', 'contactInput', 'contactBox'],
  };
  if (!fields[field]) return;
  document.getElementById(fields[field][0]).style.display = 'none';
  const input = document.getElementById(fields[field][1]);
  input.style.display = 'block';
  input.focus();
  if(field === 'desc') {
    document.getElementById(fields[field][2]).classList.add("expanded");
    input.rows = 5;
  }
  input.onkeydown = function(e) {
    if(e.key === "Enter") {
      e.preventDefault();
      input.blur();
    }
    if(e.key === "Escape") {
      e.preventDefault();
      input.value = input.value;
      input.blur();
    }
  };
  input.onblur = function() {
    let value = input.value.trim();
    let span = document.getElementById(fields[field][0]);
    span.style.display = value ? 'inline' : '';
    span.textContent = value || span.dataset?.placeholder || input.placeholder;
    span.classList.remove('placeholder');
    if(value) span.classList.add('field-value');
    else span.classList.remove('field-value');
    input.style.display = 'none';
    if(field === 'desc') {
      document.getElementById(fields[field][2]).classList.remove("expanded");
      input.rows = 1;
    }
  };
  input.oninput = function() {
    let value = input.value.trim();
    let span = document.getElementById(fields[field][0]);
    span.textContent = value || (field==='desc' ? "Dodaj opis zlecenia" : span.dataset?.placeholder || input.placeholder);
    if(value) span.classList.add('field-value');
    else span.classList.remove('field-value');
  };
}

// RODZAJ ROBÓT BUDOWLANYCH
let jobTypeMode = null;
function jobTypeBoxClick(e) {
  let input = document.getElementById('jobTypeInput');
  let dd = document.getElementById('jobTypeDropdown');
  let mainDD = document.getElementById('jobTypeMainDropdown');
  if (input.style.display === 'block') {
    input.focus();
    return;
  }
  if (dd.style.display === 'block') return;
  mainDD.style.display = 'block';
  e.stopPropagation();
}
function selectJobTypeMode(mode) {
  jobTypeMode = mode;
  document.getElementById('jobTypeMainDropdown').style.display = 'none';
  if(mode === 'input') {
    document.getElementById('jobTypeInput').style.display = 'block';
    document.getElementById('jobTypeInput').focus();
    document.getElementById('jobTypeDropdown').style.display = 'none';
  } else if(mode === 'list') {
    document.getElementById('jobTypeDropdown').style.display = 'block';
    document.getElementById('jobTypeInput').style.display = 'none';
  }
}
document.addEventListener('DOMContentLoaded', function() {
  let jobTypeInput = document.getElementById('jobTypeInput');
  let jobTypeText = document.getElementById('jobTypeText');
  jobTypeInput.onkeydown = function(e) {
    if(e.key === "Enter") {
      e.preventDefault();
      let value = jobTypeInput.value.trim();
      jobTypeText.textContent = value || "Wprowadź lub wybierz z listy";
      if(value) jobTypeText.classList.add('field-value');
      else jobTypeText.classList.remove('field-value');
      jobTypeText.classList.remove('placeholder');
      jobTypeInput.style.display = 'none';
    }
    if(e.key === "Escape") {
      e.preventDefault();
      jobTypeInput.style.display = 'none';
    }
  };
  jobTypeInput.onblur = function() {
    let value = jobTypeInput.value.trim();
    jobTypeText.textContent = value || "Wprowadź lub wybierz z listy";
    if(value) jobTypeText.classList.add('field-value');
    else jobTypeText.classList.remove('field-value');
    jobTypeText.classList.remove('placeholder');
    jobTypeInput.style.display = 'none';
  };
});
function selectJobType(e, val) {
  e.stopPropagation();
  let jobTypeText = document.getElementById('jobTypeText');
  jobTypeText.textContent = val;
  jobTypeText.classList.remove('placeholder');
  jobTypeText.classList.add('field-value');
  document.getElementById('jobTypeDropdown').style.display = 'none';
}

// NADZÓR BUDOWLANY
function supervisionBoxClick(e) {
  let dd = document.getElementById('supervisionDropdown');
  if (dd.style.display === 'block') return;
  dd.style.display = 'block';
  e.stopPropagation();
}
function selectSupervision(e, val) {
  e.stopPropagation();
  let el = document.getElementById('supervisionText');
  el.textContent = val;
  el.classList.remove('placeholder');
  el.classList.add('field-value');
  document.getElementById('supervisionDropdown').style.display = 'none';
}
document.addEventListener('click', function(e){
  let jobMainDD = document.getElementById('jobTypeMainDropdown');
  let jobBox = document.getElementById('jobTypeBox');
  let jobDD = document.getElementById('jobTypeDropdown');
  let supBox = document.getElementById('supervisionBox');
  let supDropdown = document.getElementById('supervisionDropdown');
  let jobTypeInput = document.getElementById('jobTypeInput');
  if (!jobBox.contains(e.target)) {
    jobMainDD.style.display = 'none';
    jobDD.style.display = 'none';
    jobTypeInput.style.display = 'none';
  }
  if (!supBox.contains(e.target)) {
    supDropdown.style.display = 'none';
  }
});

// Limit słów
function limitWords(textarea, maxWords) {
  let words = textarea.value.split(/\s+/);
  if(words.length > maxWords){
    textarea.value = words.slice(0, maxWords).join(" ");
  }
}

// Zatwierdzenie formularza i zapis ogłoszenia jako nowy wpis
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('ogloszenieForm');

  form.addEventListener('keydown', function(e) {
    let editing = document.querySelector('.input-box input[style*="block"], #jobTypeInput[style*="block"], textarea[style*="block"]');
    if (e.key === "Enter" && !editing) {
      e.preventDefault();
      return false;
    }
  });

  form.querySelector('.submit-btn').addEventListener('click', function(e) {
    e.preventDefault();

    const titleInput = document.getElementById('adTitleInput');
    const titleText = document.getElementById('adTitleText');
    const title = titleInput.value.trim() || titleText.textContent.trim();

    if (!title || title === "Dodaj nazwę ogłoszenia") {
      alert("Podaj nazwę ogłoszenia!");
      return;
    }

    // Dodaj do listy ogłoszeń w localStorage
    let lista = JSON.parse(localStorage.getItem('listaOgloszen') || '[]');
    lista.push(title);
    localStorage.setItem('listaOgloszen', JSON.stringify(lista));

    window.location.href = 'moje-ogloszenia.php';
  });
});
