async function loadSession() {
  try {
    const response = await fetch("src/auth/api/session.php");
    const data = await response.json();
    const role = data.user?.role || "guest";
    const name = data.user?.name || "Guest";

    const adminOnly = document.querySelectorAll(".admin-only");
    adminOnly.forEach((el) => {
      el.style.display = role === "admin" ? "" : "none";
    });

    const userNameEl = document.getElementById("user-name");
    const userRoleEl = document.getElementById("user-role");
    const authButton = document.getElementById("auth-button");

    if (userNameEl) userNameEl.textContent = name;
    if (userRoleEl) userRoleEl.textContent = role === "admin" ? "Admin" : role === "student" ? "Student" : "Visitor";
    if (authButton) {
      if (data.logged_in) {
        authButton.textContent = "Logout";
        authButton.onclick = handleLogout;
      } else {
        authButton.textContent = "Login";
        authButton.onclick = () => (window.location.href = "src/auth/login.html");
      }
    }
  } catch (error) {
    // On failure, hide admin links and leave guest state
    const adminOnly = document.querySelectorAll(".admin-only");
    adminOnly.forEach((el) => (el.style.display = "none"));
  }
}

async function handleLogout() {
  try {
    await fetch("src/auth/api/logout.php", { method: "POST" });
  } catch (error) {
    // ignore
  } finally {
    window.location.href = "src/auth/login.html";
  }
}

document.addEventListener("DOMContentLoaded", loadSession);
document.addEventListener("DOMContentLoaded", setupMenuToggle);
function setupMenuToggle() {
  const sidebar = document.querySelector(".sidebar");
  const body = document.body;
  const toggleButton = document.getElementById("menu-toggle");
  if (!sidebar || !toggleButton) return;

  const setState = (collapsed) => {
    if (collapsed) {
      sidebar.classList.add("collapsed");
      body.classList.add("collapsed");
      toggleButton.textContent = "Show Menu";
    } else {
      sidebar.classList.remove("collapsed");
      body.classList.remove("collapsed");
      toggleButton.textContent = "Hide Menu";
    }
  };

  toggleButton.addEventListener("click", () => {
    const collapsed = body.classList.toggle("collapsed");
    setState(collapsed);
  });

  setState(body.classList.contains("collapsed"));
}
