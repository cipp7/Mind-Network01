<!-- start Simple Custom CSS and JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("doctor-modal");
  const modalName = document.getElementById("modal-name");
  const modalSpecialty = document.getElementById("modal-specialty");
  const modalDescription = document.getElementById("modal-description");
  const closeBtn = document.querySelector(".modal-close");

  document.querySelectorAll(".book-btn").forEach(button => {
    button.addEventListener("click", () => {
      modalName.textContent = button.dataset.doctor;
      modalSpecialty.textContent = button.dataset.specialty;
      modalDescription.textContent = button.dataset.description;
      modal.style.display = "flex";
    });
  });

  closeBtn.addEventListener("click", () => {
    modal.style.display = "none";
  });

  window.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.style.display = "none";
    }
  });
});
</script>
<!-- end Simple Custom CSS and JS -->
