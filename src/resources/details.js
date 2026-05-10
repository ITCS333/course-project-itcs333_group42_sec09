/* Requirement: Populate the resource detail page and discussion forum.
Instructions:
1. Link this file to `details.html` using:
2. In `details.html`, add the following IDs:
- To the <h2>: `id="resource-title"`
- To the description <p>: `id="resource-description"`
- To the "Access Resource Material" <a>: `id="resource-link"`
- To the <section> for comments: `id="comment-list"`
- To the "Leave a Comment" <form>: `id="comment-form"`
- To the <textarea>: `id="new-comment"`
3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* resource.
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const resourceTitle = document.querySelector('#resource-title');
const resourceDescription = document.querySelector('#resource-description');
const resourceLink = document.querySelector('#resource-link');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newComment = document.querySelector('#new-comment');

// --- Functions ---
/**
 * TODO: Implement the getResourceIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getResourceIdFromURL() {
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * TODO: Implement the renderResourceDetails function.
 * It takes one resource object.
 * It should:
 * 1. Set the `textContent` of `resourceTitle` to the resource's title.
 * 2. Set the `textContent` of `resourceDescription` to the resource's description.
 * 3. Set the `href` attribute of `resourceLink` to the resource's link.
 */
function renderResourceDetails(resource) {
  resourceTitle.textContent = resource.title;
  resourceDescription.textContent = resource.description;
  resourceLink.setAttribute('href', resource.link);
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an element matching the structure in `details.html`.
 */
function createCommentArticle(comment) {
  const article = document.createElement('article');
  const textElem = document.createElement('p');
  textElem.textContent = comment.text;

  const footerElem = document.createElement('footer');
  footerElem.textContent = `Posted by: ${comment.author}`;

  article.appendChild(textElem);
  article.appendChild(footerElem);

  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting to `commentList`.
 */
function renderComments() {
  commentList.innerHTML = '';
  for (const comment of currentComments) {
    const commentElem = createCommentArticle(comment);
    commentList.appendChild(commentElem);
  }
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newComment.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newComment` textarea.
 */
function handleAddComment(event) {
  event.preventDefault();
  const commentText = newComment.value.trim();
  if (!commentText) return;

  const newCommentObj = {
    author: 'Student',
    text: commentText
  };

  currentComments.push(newCommentObj);
  renderComments();
  newComment.value = '';
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentResourceId` by calling `getResourceIdFromURL()`.
 * 2. If no ID is found, set `resourceTitle.textContent = "Resource not found."` and stop.
 * 3. `fetch` both 'resources.json' and 'resource-comments.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct resource from the resources array using the `currentResourceId`.
 * 6. Get the correct comments array from the comments object using the `currentResourceId`.
 * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
 * 7. If the resource is found:
 * - Call `renderResourceDetails()` with the resource object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 8. If the resource is not found, display an error in `resourceTitle`.
 */
async function initializePage() {
  currentResourceId = getResourceIdFromURL();
  if (!currentResourceId) {
    resourceTitle.textContent = 'Resource not found.';
    return;
  }

  try {
    const [resourcesResponse, commentsResponse] = await Promise.all([
      fetch('./api/resources.json'),
      fetch('./api/comments.json')
    ]);

    const resources = await resourcesResponse.json();
    const commentsData = await commentsResponse.json();

    const idStr = String(currentResourceId);
    const resource = resources.find(r => String(r.id) === idStr);

    currentComments = commentsData[idStr] || [];

    if (resource) {
      renderResourceDetails(resource);
      renderComments();
      commentForm.addEventListener('submit', handleAddComment);
    } else {
      resourceTitle.textContent = 'Resource not found.';
    }
  } catch (error) {
    resourceTitle.textContent = 'Error loading resource.';
    console.error(error);
  }
}

// --- Initial Page Load ---
initializePage();
