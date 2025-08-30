const lucide = require("lucide")

// Initialize Lucide icons
lucide.createIcons()

// Global state
const stats = {
  en_bodega: 245,
  cargados: 89,
  repartidores_activos: 12,
  urgentes: 7,
}

const todayPackages = [
  { tracking: "WH001234569", time: "14:30", status: "Recibido" },
  { tracking: "WH001234570", time: "13:45", status: "Recibido" },
  { tracking: "WH001234571", time: "12:20", status: "Recibido" },
]

let isScanning = false

// Utility functions
function showAlert(message, type = "info") {
  const alertContainer = document.getElementById("alert-container")
  const alertMessage = document.getElementById("alert-message")

  alertContainer.className = `alert-container alert-${type}`
  alertMessage.textContent = message
  alertContainer.classList.remove("hidden")

  setTimeout(() => {
    alertContainer.classList.add("hidden")
  }, 3000)
}

function updateStats() {
  document.getElementById("packages-warehouse").textContent = stats.en_bodega.toLocaleString()
  document.getElementById("packages-loaded").textContent = stats.cargados.toLocaleString()
  document.getElementById("active-drivers").textContent = stats.repartidores_activos.toLocaleString()
  document.getElementById("urgent-packages").textContent = stats.urgentes.toLocaleString()
}

function addPackageToTable(tracking) {
  const tbody = document.getElementById("today-packages")
  const now = new Date()
  const time = now.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" })

  const row = document.createElement("tr")
  row.innerHTML = `
        <td class="tracking-code">${tracking}</td>
        <td>${time}</td>
        <td>
            <div class="badge badge-success">
                <i data-lucide="check-circle"></i>
                Recibido
            </div>
        </td>
    `

  tbody.insertBefore(row, tbody.firstChild)
  lucide.createIcons()
}

function removeShipmentRow(shipmentId) {
  const row = document.querySelector(`[data-shipment-id="${shipmentId}"]`)
  if (row) {
    row.remove()
  }
}

// Event handlers
function handlePackageReception(event) {
  event.preventDefault()

  const trackingInput = document.getElementById("tracking-input")
  const tracking = trackingInput.value.trim()

  if (!tracking) return

  showAlert("Procesando recepción...", "info")

  // Simulate API call
  setTimeout(() => {
    addPackageToTable(tracking)
    stats.en_bodega += 1
    updateStats()

    showAlert("Paquete recibido correctamente", "success")
    trackingInput.value = ""
  }, 1000)
}

function loadTruck(shipmentId) {
  showAlert("Cargando al camión...", "info")

  // Simulate API call
  setTimeout(() => {
    removeShipmentRow(shipmentId)
    stats.en_bodega -= 1
    stats.cargados += 1
    updateStats()

    showAlert("Paquete cargado exitosamente", "success")
  }, 1000)
}

function startScanning() {
  const scannerArea = document.getElementById("scanner-area")
  const resetButton = document.getElementById("reset-scanner")
  const scanResult = document.getElementById("scan-result")

  isScanning = true
  scannerArea.classList.add("scanning")
  resetButton.classList.remove("hidden")
  scanResult.classList.add("hidden")

  scannerArea.innerHTML = `
        <div class="scanner-content">
            <div class="scanner-icon">
                <i data-lucide="scan"></i>
            </div>
            <div class="scanner-text">
                <p>Escaneando código QR...</p>
                <small>Mantén el código dentro del marco</small>
            </div>
            <div style="width: 200px; height: 8px; background: var(--muted); border-radius: var(--radius); overflow: hidden;">
                <div style="height: 100%; background: var(--accent); animation: pulse 2s infinite; border-radius: var(--radius);"></div>
            </div>
        </div>
    `

  lucide.createIcons()

  // Simulate QR scanning
  setTimeout(() => {
    if (isScanning) {
      const mockTrackingNumber = `WH00${Math.floor(Math.random() * 1000000)}`
      document.getElementById("scan-result-text").textContent = `Código escaneado: ${mockTrackingNumber}`
      document.getElementById("tracking-input").value = mockTrackingNumber
      scanResult.classList.remove("hidden")

      scannerArea.classList.remove("scanning")
      isScanning = false
    }
  }, 3000)
}

function resetScanner() {
  const scannerArea = document.getElementById("scanner-area")
  const resetButton = document.getElementById("reset-scanner")
  const scanResult = document.getElementById("scan-result")

  isScanning = false
  scannerArea.classList.remove("scanning")
  resetButton.classList.add("hidden")
  scanResult.classList.add("hidden")

  scannerArea.innerHTML = `
        <div class="scanner-content">
            <div class="scanner-icon">
                <i data-lucide="qr-code"></i>
            </div>
            <div class="scanner-text">
                <p>Área de escaneo QR</p>
                <small>Presiona el botón para iniciar</small>
            </div>
            <button class="btn btn-accent" onclick="startScanning()">
                <i data-lucide="camera"></i>
                Iniciar escaneo
            </button>
        </div>
    `

  lucide.createIcons()
}

// Initialize the application
document.addEventListener("DOMContentLoaded", () => {
  // Set up form event listener
  document.getElementById("reception-form").addEventListener("submit", handlePackageReception)

  // Initialize stats
  updateStats()

  // Initialize icons
  lucide.createIcons()
})
