/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// --- Global Data Store ---
// This array will be populated with data fetched from the API.
let students = [];

// --- Element Selections ---
// We can safely select elements here because 'defer' guarantees
// the HTML document is parsed before this script runs.

// TODO: Select the student table body (tbody).
const studentTableBody = document.getElementById("student-table-body");

// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm = document.getElementById("add-student-form");

// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const changePasswordForm = document.getElementById("password-form");

// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput = document.getElementById("search-input");

// TODO: Select all table header (th) elements in thead.
const tableHeaders = document.querySelectorAll("thead th");

// --- Functions ---

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
  const row = document.createElement("tr");

  const nameCell = document.createElement("td");
  nameCell.textContent = student.name;

  const idCell = document.createElement("td");
  idCell.textContent = student.id;

  const emailCell = document.createElement("td");
  emailCell.textContent = student.email;

  const actionCell = document.createElement("td");
  const editButton = document.createElement("button");
  editButton.type = "button";
  editButton.className = "secondary edit-btn";
  editButton.dataset.id = student.id;
  editButton.textContent = "Edit";

  const deleteButton = document.createElement("button");
  deleteButton.type = "button";
  deleteButton.className = "contrast delete-btn";
  deleteButton.dataset.id = student.id;
  deleteButton.textContent = "Delete";

  actionCell.append(editButton, " ", deleteButton);
  row.append(nameCell, idCell, emailCell, actionCell);

  return row;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentArray) {
  if (!studentTableBody) return;
  studentTableBody.innerHTML = "";
  studentArray.forEach((student) => {
    studentTableBody.appendChild(createStudentRow(student));
  });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
async function handleChangePassword(event) {
  event.preventDefault();

  const currentPassword = document.getElementById("current-password");
  const newPassword = document.getElementById("new-password");
  const confirmPassword = document.getElementById("confirm-password");

  if (!currentPassword || !newPassword || !confirmPassword) return;

  if (newPassword.value !== confirmPassword.value) {
    alert("Passwords do not match.");
    return;
  }

  if (newPassword.value.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  try {
    const response = await fetch("../auth/api/change_password.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        current_password: currentPassword.value,
        new_password: newPassword.value,
      }),
    });

    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || "Failed to update password");
    }

    alert("Password updated successfully!");
  } catch (error) {
    alert(error.message || "Error updating password.");
  }

  currentPassword.value = "";
  newPassword.value = "";
  confirmPassword.value = "";
}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
async function handleAddStudent(event) {
  event.preventDefault();

  const nameInput = document.getElementById("student-name");
  const idInput = document.getElementById("student-id");
  const emailInput = document.getElementById("student-email");
  const defaultPasswordInput = document.getElementById("default-password");

  if (!nameInput || !idInput || !emailInput || !defaultPasswordInput) return;

  const name = nameInput.value.trim();
  const id = idInput.value.trim();
  const email = emailInput.value.trim();

  if (!name || !id || !email) {
    alert("Please fill out all required fields.");
    return;
  }

  try {
    const response = await fetch("api/index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        student_id: id,
        name,
        email,
        password: defaultPasswordInput.value || "password123",
      }),
    });

    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || "Failed to add student");
    }

    await loadStudents();
    nameInput.value = "";
    idInput.value = "";
    emailInput.value = "";
    defaultPasswordInput.value = "";
  } catch (error) {
    alert(error.message || "Error adding student.");
  }
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
async function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  if (target.classList.contains("delete-btn")) {
    const idToDelete = target.dataset.id;
    try {
      const response = await fetch("api/index.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ student_id: idToDelete }),
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Failed to delete student");
      }
      await loadStudents();
    } catch (error) {
      alert(error.message || "Error deleting student.");
    }
  }

  if (target.classList.contains("edit-btn")) {
    const idToEdit = target.dataset.id;
    const student = students.find((s) => s.id === idToEdit);
    if (!student) return;
    const newName = prompt("Update name:", student.name);
    if (newName === null) return;
    const newEmail = prompt("Update email:", student.email);
    if (newEmail === null) return;

    try {
      const response = await fetch("api/index.php", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          student_id: idToEdit,
          name: newName.trim(),
          email: newEmail.trim(),
        }),
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Failed to update student");
      }
      await loadStudents();
    } catch (error) {
      alert(error.message || "Error updating student.");
    }
  }
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch(event) {
  const term = event.target.value.toLowerCase();

  if (!term) {
    renderTable(students);
    return;
  }

  const filtered = students.filter((student) =>
    student.name.toLowerCase().includes(term)
  );

  renderTable(filtered);
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
  const header = event.currentTarget;
  if (!(header instanceof HTMLElement)) return;

  const fields = ["name", "id", "email"];
  const field = fields[header.cellIndex];
  if (!field) return;

  const currentDir = header.dataset.sortDir === "desc" ? "desc" : "asc";
  const nextDir = currentDir === "asc" ? "desc" : "asc";
  header.dataset.sortDir = nextDir;

  students.sort((a, b) => {
    const multiplier = nextDir === "asc" ? 1 : -1;
    if (field === "id") {
      const aNum = Number(a.id);
      const bNum = Number(b.id);
      return (aNum - bNum) * multiplier;
    }
    return a[field].localeCompare(b[field]) * multiplier;
  });

  renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
  const sessionOk = await verifySession();
  if (!sessionOk) return;

  await loadStudents();

  if (changePasswordForm) {
    changePasswordForm.addEventListener("submit", handleChangePassword);
  }

  if (addStudentForm) {
    addStudentForm.addEventListener("submit", handleAddStudent);
  }

  const logoutButton = document.getElementById("logout-button");
  if (logoutButton) {
    logoutButton.addEventListener("click", handleLogout);
  }

  if (studentTableBody) {
    studentTableBody.addEventListener("click", handleTableClick);
  }

  if (searchInput) {
    searchInput.addEventListener("input", handleSearch);
  }

  tableHeaders.forEach((header) => {
    header.addEventListener("click", handleSort);
  });
}

async function verifySession() {
  try {
    const response = await fetch("../auth/api/session.php");
    const data = await response.json();
    if (!data.logged_in || data.user?.role !== "admin") {
      alert("You must be logged in as an admin to manage students.");
      return false;
    }
    return true;
  } catch (error) {
    alert("Unable to verify session.");
    return false;
  }
}

async function loadStudents() {
  try {
    const response = await fetch("api/index.php");
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || "Failed to load students");
    }
    students = (result.data || []).map((s) => ({
      id: s.student_id,
      name: s.name,
      email: s.email,
    }));
    renderTable(students);
  } catch (error) {
    console.error(error);
    alert(error.message || "Error loading students.");
    students = [];
    renderTable(students);
  }
}

async function handleLogout() {
  try {
    await fetch("../auth/api/logout.php", { method: "POST" });
  } catch (error) {
    // ignore error, we still redirect
  } finally {
    window.location.href = "../auth/login.html";
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadStudentsAndInitialize();
