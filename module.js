function updateCartCount() {
  const badge = document.getElementById("cartCount");

  if (!badge) {
    return;
  }

  fetch("api_carrito.php?action=contar")
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        return;
      }

      badge.textContent = String(data.cantidad);
      badge.style.display = data.cantidad > 0 ? "flex" : "none";
    })
    .catch(() => {
      badge.style.display = "none";
    });
}

document.addEventListener("DOMContentLoaded", updateCartCount);
