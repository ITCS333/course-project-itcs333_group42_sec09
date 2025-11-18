/*
  Admin portal interactivity with live API calls.
  Communicates with the PHP backend for authentication, password updates, and student CRUD.
*/

const API_BASE = '../../server/api';
const ENDPOINTS = {
  students: `${API_BASE}/students.php`,
  password: `${API_BASE}/password.php`,
  logout: `${API_BASE}/logout.php`,
};

let students = [];

const studentTableBody = document.querySelector('#student-table tbody');
const addStudentForm = document.getElementById('add-student-form');
const changePasswordForm = document.getElementById('password-form');
const searchInput = document.getElementById('search-input');
const tableHeaders = document.querySelectorAll('#student-table thead th[data-column]');
const logoutButton = document.getElementById('logout-btn');

function createStudentRow(student) {
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${student.name}</td>
    <td>${student.student_id || ''}</td>
    <td>${student.email}</td>
    <td>
      <button type="button" class="edit-btn" data-id="${student.id}">Edit</button>
      <button type="button" class="delete-btn" data-id="${student.id}">Delete</button>
    </td>
  `;
  return row;
}

function renderTable(studentArray) {
  if (!studentTableBody) return;
  studentTableBody.innerHTML = '';
  studentArray.forEach((student) => {
    studentTableBody.appendChild(createStudentRow(student));
  });
}

async function apiRequest(url, { method = 'GET', body } = {}) {
  const options = {
    method,
    credentials: 'include',
    headers: {},
  };

  if (body !== undefined) {
    options.headers['Content-Type'] = 'application/json';
    options.body = typeof body === 'string' ? body : JSON.stringify(body);
  }

  const response = await fetch(url, options);
  let payload = {};
  try {
    payload = await response.json();
  } catch (error) {
    payload = {};
  }
  if (!response.ok) {
    throw new Error(payload.error || 'Request failed.');
  }
  return payload;
}

async function fetchStudents() {
  const data = await apiRequest(ENDPOINTS.students);
  students = Array.isArray(data) ? data : [];
  renderTable(students);
}

async function handleChangePassword(event) {
  event.preventDefault();

  const currentPassword = document.getElementById('current-password').value.trim();
  const newPassword = document.getElementById('new-password').value.trim();
  const confirmPassword = document.getElementById('confirm-password').value.trim();

  if (!currentPassword || !newPassword) {
    alert('Please fill out all password fields.');
    return;
  }
  if (newPassword !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }
  if (newPassword.length < 8) {
    alert('Password must be at least 8 characters.');
    return;
  }

  try {
    await apiRequest(ENDPOINTS.password, {
      method: 'POST',
      body: {
        current_password: currentPassword,
        new_password: newPassword,
      },
    });
    alert('Password updated successfully!');
    changePasswordForm.reset();
  } catch (error) {
    alert(error.message);
  }
}

async function handleAddStudent(event) {
  event.preventDefault();

  const nameInput = document.getElementById('student-name');
  const idInput = document.getElementById('student-id');
  const emailInput = document.getElementById('student-email');
  const defaultPasswordInput = document.getElementById('default-password');

  const payload = {
    name: nameInput.value.trim(),
    student_id: idInput.value.trim(),
    email: emailInput.value.trim(),
    password: defaultPasswordInput.value.trim() || 'Password123!',
  };

  if (!payload.name || !payload.student_id || !payload.email) {
    alert('Please fill out all required fields.');
    return;
  }

  try {
    await apiRequest(ENDPOINTS.students, { method: 'POST', body: payload });
    await fetchStudents();
    addStudentForm.reset();
    defaultPasswordInput.value = 'password123';
  } catch (error) {
    alert(error.message);
  }
}

async function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof HTMLElement)) return;

  const studentId = parseInt(target.dataset.id, 10);
  if (!studentId) return;

  if (target.classList.contains('delete-btn')) {
    if (!confirm('Are you sure you want to delete this student?')) return;
    try {
      await apiRequest(ENDPOINTS.students, { method: 'DELETE', body: { id: studentId } });
      await fetchStudents();
    } catch (error) {
      alert(error.message);
    }
  } else if (target.classList.contains('edit-btn')) {
    const student = students.find((s) => s.id === studentId);
    if (!student) return;

    const updatedName = prompt('Update student name:', student.name);
    if (updatedName === null) return;
    const updatedEmail = prompt('Update student email:', student.email);
    if (updatedEmail === null) return;
    const updatedStudentId = prompt('Update student ID:', student.student_id);
    if (updatedStudentId === null) return;

    try {
      await apiRequest(ENDPOINTS.students, {
        method: 'PUT',
        body: {
          id: studentId,
          name: updatedName.trim() || student.name,
          email: updatedEmail.trim() || student.email,
          student_id: updatedStudentId.trim() || student.student_id,
        },
      });
      await fetchStudents();
    } catch (error) {
      alert(error.message);
    }
  }
}

function handleSearch(event) {
  const term = event.target.value.trim().toLowerCase();
  if (!term) {
    renderTable(students);
    return;
  }

  const filtered = students.filter((student) =>
    student.name.toLowerCase().includes(term) ||
    (student.student_id || '').toLowerCase().includes(term)
  );
  renderTable(filtered);
}

function handleSort(event) {
  const header = event.currentTarget;
  const column = header.dataset.column;
  if (!column) return;

  const currentDir = header.dataset.sortDir === 'desc' ? 'desc' : 'asc';
  const nextDir = currentDir === 'asc' ? 'desc' : 'asc';
  header.dataset.sortDir = nextDir;

  const direction = nextDir === 'asc' ? 1 : -1;
  students.sort((a, b) => {
    if (column === 'id') {
      return (Number(a.id) - Number(b.id)) * direction;
    }
    return (a[column] || '').localeCompare(b[column] || '') * direction;
  });
  renderTable(students);
}

async function handleLogout() {
  try {
    await apiRequest(ENDPOINTS.logout, { method: 'POST' });
    window.location.href = '../auth/login.html';
  } catch (error) {
    alert(error.message);
  }
}

async function loadStudentsAndInitialize() {
  try {
    await fetchStudents();
  } catch (error) {
    alert(error.message);
    return;
  }

  if (changePasswordForm) {
    changePasswordForm.addEventListener('submit', handleChangePassword);
  }
  if (addStudentForm) {
    addStudentForm.addEventListener('submit', handleAddStudent);
  }
  if (studentTableBody) {
    studentTableBody.addEventListener('click', handleTableClick);
  }
  if (searchInput) {
    searchInput.addEventListener('input', handleSearch);
  }
  tableHeaders.forEach((header) => header.addEventListener('click', handleSort));
  if (logoutButton) {
    logoutButton.addEventListener('click', handleLogout);
  }
}

loadStudentsAndInitialize();
