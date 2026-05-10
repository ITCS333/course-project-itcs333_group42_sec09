/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     list.js</script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
const listSection = document.querySelector('#resource-list-section');

// --- Functions ---
function createResourceArticle(resource) {
  const article = document.createElement('article');
  article.className = 'resource-item';

  const titleElem = document.createElement('h2');
  titleElem.textContent = resource.title;

  const descElem = document.createElement('p');
  descElem.textContent = resource.description;

  const linkElem = document.createElement('a');
  linkElem.textContent = 'View Resource & Discussion'; // Fixed text
  linkElem.setAttribute('href', `details.html?id=${resource.id}`);

  article.appendChild(titleElem);
  article.appendChild(descElem);
  article.appendChild(linkElem);

  return article;
}

async function loadResources() {
  try {
    const response = await fetch('./api/resources.json');
    const resources = await response.json();

    listSection.innerHTML = '';

    for (const resource of resources) {
      const articleElem = createResourceArticle(resource);
      listSection.appendChild(articleElem);
    }
  } catch (error) {
    console.error('Error loading resources:', error);
  }
}

// --- Initial Page Load ---
loadResources();
