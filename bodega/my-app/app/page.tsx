"use client"

import React, { useState, useEffect, useRef } from "react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Progress } from "@/components/ui/progress"
import {
  Package,
  Truck,
  Users,
  AlertTriangle,
  QrCode,
  Camera,
  Eye,
  RefreshCw,
  Building2,
  LogOut,
  Scan,
  CheckCircle,
  TrendingUp,
  Clock,
  MapPin,
  Activity,
  BarChart3,
  Search,
  Download,
  Settings,
} from "lucide-react"

interface Stats {
  en_bodega: number
  cargados: number
  repartidores_activos: number
  urgentes: number
}

interface Shipment {
  id: number
  tracking_number: string
  destination: string
  status: string
  repartidor_nombre?: string
  urgent: boolean
  created_at: string
}

interface TodayPackage {
  tracking: string
  time: string
  status: string
}

export default function WarehouseDashboard() {
  const [stats, setStats] = useState<Stats>({
    en_bodega: 0,
    cargados: 0,
    repartidores_activos: 0,
    urgentes: 0,
  })

  const [pendingShipments, setPendingShipments] = useState<Shipment[]>([])

  const [todayPackages, setTodayPackages] = useState<TodayPackage[]>([])

  const [trackingInput, setTrackingInput] = useState("")
  const [message, setMessage] = useState("")
  const [messageType, setMessageType] = useState<"success" | "error" | "info">("info")
  const [isScanning, setIsScanning] = useState(false)
  const [scanResult, setScanResult] = useState("")
  const [cameras, setCameras] = useState<{ id: string; label?: string }[]>([])
  const [selectedCamera, setSelectedCamera] = useState<string | null>(null)
  const scanContainerRef = useRef<HTMLDivElement | null>(null)
  const html5Ref = useRef<any>(null)

  useEffect(() => {
    const prefersDarkMode = window.matchMedia("(prefers-color-scheme: dark)").matches
    document.documentElement.classList.toggle("dark", prefersDarkMode)
  }, [])

  // Fetch stats + pending shipments from PHP API
  useEffect(() => {
    let mounted = true
    const fetchData = () => {
      fetch('/api/dashboard_stats.php')
        .then((res) => res.json())
        .then((data) => {
          if (!mounted) return
          if (data?.stats) setStats({
            en_bodega: Number(data.stats.en_bodega ?? 0),
            cargados: Number(data.stats.cargados ?? 0),
            repartidores_activos: Number(data.stats.repartidores_activos ?? 0),
            urgentes: Number(data.stats.urgentes ?? 0),
          })
          if (Array.isArray(data.pendingShipments)) {
            setPendingShipments(data.pendingShipments.map((s: any) => ({
              id: Number(s.id),
              tracking_number: s.tracking_number ?? s.tracking ?? '',
              destination: s.destination ?? '',
              status: s.status ?? '',
              repartidor_nombre: s.repartidor_nombre ?? undefined,
              urgent: Boolean(Number(s.urgent ?? 0)),
              created_at: s.created_at ?? '',
            })))
          }
          if (Array.isArray(data.todayPackages)) {
            setTodayPackages(data.todayPackages.map((pkg: any) => ({
              tracking: pkg.tracking_number,
              time: pkg.hora,
              status: pkg.status,
            })))
          }
        })
        .catch((err) => console.error('Error cargando stats:', err))
    }
    fetchData()
    const interval = setInterval(fetchData, 5000)
    return () => {
      mounted = false
      clearInterval(interval)
    }
  }, [])

  // Load available cameras for html5-qrcode (lazy)
  const ensureHtml5Script = async () => {
    if (typeof window === 'undefined') return
    if ((window as any).Html5Qrcode) return
    await new Promise<void>((resolve, reject) => {
      const s = document.createElement('script')
      s.src = 'https://unpkg.com/html5-qrcode'
      s.onload = () => resolve()
      s.onerror = () => reject(new Error('No se pudo cargar html5-qrcode'))
      document.head.appendChild(s)
    })
  }

  const startScannerUsingHtml5 = async (cameraId?: string) => {
    try {
      await ensureHtml5Script()
      const Html5Qrcode = (window as any).Html5Qrcode
      if (!scanContainerRef.current) return
      // stop previous
      if (html5Ref.current) {
        try { await html5Ref.current.stop(); } catch(_) {}
        html5Ref.current.clear?.()
        html5Ref.current = null
      }
      const containerId = scanContainerRef.current.id || ('qr-reader-' + Date.now())
      scanContainerRef.current.id = containerId
      const scanner = new Html5Qrcode(containerId)
      html5Ref.current = scanner
      await scanner.start(
        cameraId ?? { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        (decodedText: string) => {
          setScanResult(`Código escaneado: ${decodedText}`)
          setTrackingInput(decodedText)
          setIsScanning(false)
          // stop scanner
          scanner.stop().catch(()=>{})
        },
        (error: any) => {
          // scan failure callback (ignorable)
        }
      )
    } catch (err) {
      console.error(err)
      setScanResult('Error al iniciar la cámara')
      setIsScanning(false)
    }
  }

  const stopScanner = async () => {
    if (html5Ref.current) {
      try { await html5Ref.current.stop(); } catch(_) {}
      try { html5Ref.current.clear(); } catch(_) {}
      html5Ref.current = null
    }
  }

  const handlePackageReception = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!trackingInput.trim()) return

    setMessage("Procesando recepción...")
    setMessageType("info")

    // Enviar al backend PHP existente (scan_event.php) para procesar recepción
    try {
      const res = await fetch('http://localhost/Programacion-de-formulario-con-BD/api/dashboard_stats.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tracking: trackingInput, accion: 'intake' }),
      })
      const json = await res.json()
      if (json.ok) {
        const newPackage: TodayPackage = {
          tracking: trackingInput,
          time: new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }),
          status: 'Recibido',
        }
        setTodayPackages((prev) => [newPackage, ...prev])
        setStats((prev) => ({ ...prev, en_bodega: prev.en_bodega + 1 }))
        setMessage("Paquete recibido correctamente")
        setMessageType("success")
        setTrackingInput("")
      } else {
        setMessage(json.msg || 'Error al recibir paquete')
        setMessageType("error")
      }
    } catch (err) {
      console.error(err)
      setMessage('Error de red')
      setMessageType('error')
    } finally {
      setTimeout(() => setMessage(''), 3000)
    }
  }

  const handleLoadTruck = async (shipmentId: number) => {
    setMessage("Cargando al camión...")
    setMessageType("info")

    // Llamar API PHP existente para cargar camión
    try {
      const res = await fetch('/api/cargar_camion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ envio_id: shipmentId }),
      })
      const json = await res.json()
      if (json.ok) {
        setPendingShipments((prev) => prev.filter((s) => s.id !== shipmentId))
        setStats((prev) => ({ ...prev, en_bodega: prev.en_bodega - 1, cargados: prev.cargados + 1 }))
        setMessage("Paquete cargado exitosamente")
        setMessageType("success")
      } else {
        setMessage(json.msg || 'Error al cargar al camión')
        setMessageType('error')
      }
    } catch (err) {
      console.error(err)
      setMessage('Error de red')
      setMessageType('error')
    } finally {
      setTimeout(() => setMessage(''), 3000)
    }
  }

  const startQRScanning = async () => {
    setIsScanning(true)
    setScanResult('')
    // obtener cámaras y arrancar
    try {
      await ensureHtml5Script()
      const Html5Qrcode = (window as any).Html5Qrcode
      const cams = await Html5Qrcode.getCameras()
      setCameras(cams || [])
      const camId = (cams && cams.length > 0) ? cams[0].id : undefined
      setSelectedCamera(camId ?? null)
      await startScannerUsingHtml5(camId)
    } catch (err) {
      console.error(err)
      setScanResult('No se pudo iniciar la cámara')
      setIsScanning(false)
    }
  }

  const resetScanning = async () => {
    setIsScanning(false)
    setScanResult('')
    await stopScanner()
  }

  return (
    <div className="min-h-screen bg-background">
      <div className="sticky top-0 z-50 border-b bg-card/50 backdrop-blur-sm">
        <div className="container px-6 py-4 mx-auto max-w-7xl">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-primary">
                  <Building2 className="w-6 h-6 text-primary-foreground" />
                </div>
                <div>
                  <h1 className="text-2xl font-bold text-foreground">Bodega MENDEZ</h1>
                  <p className="text-sm text-muted-foreground">Sistema de Gestión Logística</p>
                </div>
              </div>
            </div>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2">
                <Activity className="w-4 h-4 text-green-500" />
                <span className="text-sm text-muted-foreground">Sistema activo</span>
              </div>
              <Badge variant="secondary" className="px-3 py-1 bg-primary/10 text-primary border-primary/20">
                <Users className="w-3 h-3 mr-1" />
                Operador de Bodega
              </Badge>
              <Button variant="outline" size="sm" className="gap-2 bg-transparent">
                <Settings className="w-4 h-4" />
                Configuración
              </Button>
              <Button variant="outline" size="sm" className="gap-2 bg-transparent">
                <LogOut className="w-4 h-4" />
                Salir
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div className="container p-6 mx-auto max-w-7xl">
        {/* Alert Messages */}
        {message && (
          <Alert
            className={`mb-6 border-l-4 ${
              messageType === "success"
                ? "border-l-green-500 bg-green-50 dark:bg-green-950/20"
                : messageType === "error"
                  ? "border-l-red-500 bg-red-50 dark:bg-red-950/20"
                  : "border-l-blue-500 bg-blue-50 dark:bg-blue-950/20"
            }`}
          >
            <AlertDescription
              className={
                messageType === "success"
                  ? "text-green-700 dark:text-green-300"
                  : messageType === "error"
                    ? "text-red-700 dark:text-red-300"
                    : "text-blue-700 dark:text-blue-300"
              }
            >
              {message}
            </AlertDescription>
          </Alert>
        )}

        <div className="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2 lg:grid-cols-4">
          <Card className="relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="p-3 bg-primary/10 rounded-xl">
                  <Package className="w-6 h-6 text-primary" />
                </div>
                <TrendingUp className="w-4 h-4 text-green-500" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium text-muted-foreground">Paquetes en bodega</p>
                <p className="text-3xl font-bold text-card-foreground">{stats.en_bodega.toLocaleString()}</p>
                <Progress value={75} className="h-2" />
                <p className="text-xs text-muted-foreground">75% de capacidad</p>
              </div>
            </CardContent>
          </Card>

          <Card className="relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="p-3 bg-secondary/10 rounded-xl">
                  <Truck className="w-6 h-6 text-secondary" />
                </div>
                <Clock className="w-4 h-4 text-blue-500" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium text-muted-foreground">Paquetes cargados</p>
                <p className="text-3xl font-bold text-card-foreground">{stats.cargados.toLocaleString()}</p>
                <Progress value={60} className="h-2" />
                <p className="text-xs text-muted-foreground">Hoy: +12 vs ayer</p>
              </div>
            </CardContent>
          </Card>

          <Card className="relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="p-3 bg-green-500/10 rounded-xl">
                  <Users className="w-6 h-6 text-green-600" />
                </div>
                <Activity className="w-4 h-4 text-green-500" />
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium text-muted-foreground">Repartidores activos</p>
                <p className="text-3xl font-bold text-card-foreground">{stats.repartidores_activos.toLocaleString()}</p>
                <Progress value={85} className="h-2" />
                <p className="text-xs text-muted-foreground">85% disponibilidad</p>
              </div>
            </CardContent>
          </Card>

          <Card className="relative overflow-hidden border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
            <CardContent className="p-6">
              <div className="flex items-center justify-between mb-4">
                <div className="p-3 bg-red-500/10 rounded-xl">
                  <AlertTriangle className="w-6 h-6 text-red-600" />
                </div>
                <Badge variant="destructive" className="text-xs">
                  Urgente
                </Badge>
              </div>
              <div className="space-y-2">
                <p className="text-sm font-medium text-muted-foreground">Envíos urgentes</p>
                <p className="text-3xl font-bold text-card-foreground">{stats.urgentes.toLocaleString()}</p>
                <Progress value={30} className="h-2 bg-red-100" />
                <p className="text-xs text-muted-foreground">Requieren atención</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <div className="grid grid-cols-1 gap-8 mb-8 lg:grid-cols-2">
          <Card className="border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
            <CardHeader className="rounded-t-lg bg-gradient-to-r from-primary to-primary/90 text-primary-foreground">
              <CardTitle className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-white/20">
                  <QrCode className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="text-lg font-semibold">Recepción de Paquetes</h3>
                  <p className="text-sm opacity-90">Escanea o ingresa códigos de seguimiento</p>
                </div>
              </CardTitle>
            </CardHeader>
            <CardContent className="p-6">
              <form onSubmit={handlePackageReception} className="mb-6">
                <div className="flex gap-3">
                  <div className="relative flex-1">
                    <QrCode className="absolute w-4 h-4 transform -translate-y-1/2 left-3 top-1/2 text-muted-foreground" />
                    <Input
                      type="text"
                      placeholder="Escanea o ingresa el tracking number"
                      value={trackingInput}
                      onChange={(e) => setTrackingInput(e.target.value)}
                      className="h-12 pl-10 border-2 focus:border-primary"
                      required
                    />
                  </div>
                  <Button type="submit" className="h-12 px-6 bg-green-600 shadow-lg hover:bg-green-700">
                    <CheckCircle className="w-4 h-4 mr-2" />
                    Recibir
                  </Button>
                </div>
              </form>

              <div className="pt-6 border-t">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="font-semibold text-card-foreground">Paquetes recibidos hoy</h3>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm">
                      <Search className="w-4 h-4 mr-1" />
                      Buscar
                    </Button>
                    <Button variant="outline" size="sm">
                      <Download className="w-4 h-4 mr-1" />
                      Exportar
                    </Button>
                  </div>
                </div>
                <div className="overflow-y-auto border rounded-lg max-h-80">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/50">
                        <TableHead className="font-semibold">Tracking</TableHead>
                        <TableHead className="font-semibold">Hora</TableHead>
                        <TableHead className="font-semibold">Estado</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {todayPackages.length > 0 ? (
                        todayPackages.map((pkg, index) => (
                          <TableRow key={index} className="hover:bg-muted/30">
                            <TableCell className="font-mono text-sm font-medium">{pkg.tracking}</TableCell>
                            <TableCell className="text-sm">{pkg.time}</TableCell>
                            <TableCell>
                              <Badge variant="secondary" className="text-green-800 bg-green-100 border-green-200">
                                <CheckCircle className="w-3 h-3 mr-1" />
                                {pkg.status}
                              </Badge>
                            </TableCell>
                          </TableRow>
                        ))
                      ) : (
                        <TableRow>
                          <TableCell colSpan={3} className="py-8 text-center text-muted-foreground">
                            No hay registros para hoy
                          </TableCell>
                        </TableRow>
                      )}
                    </TableBody>
                  </Table>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
            <CardHeader className="rounded-t-lg bg-gradient-to-r from-secondary to-secondary/90 text-secondary-foreground">
              <div className="flex items-center justify-between">
                <CardTitle className="flex items-center gap-3">
                  <div className="p-2 rounded-lg bg-white/20">
                    <Truck className="w-5 h-5" />
                  </div>
                  <div>
                    <h3 className="text-lg font-semibold">Preparación y Carga</h3>
                    <p className="text-sm opacity-90">Gestiona envíos pendientes</p>
                  </div>
                </CardTitle>
                <div className="flex gap-2">
                  <Select defaultValue="all">
                    <SelectTrigger className="w-40 text-white bg-white/20 border-white/30">
                      <SelectValue placeholder="Filtrar" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Todos</SelectItem>
                      <SelectItem value="urgent">Urgentes</SelectItem>
                      <SelectItem value="ready">Listos</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardHeader>
            <CardContent className="p-6">
              <div className="overflow-y-auto border rounded-lg max-h-96">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/50">
                      <TableHead className="font-semibold">Tracking</TableHead>
                      <TableHead className="font-semibold">Destino</TableHead>
                      <TableHead className="font-semibold">Repartidor</TableHead>
                      <TableHead className="font-semibold">Acciones</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {pendingShipments.length > 0 ? (
                      pendingShipments.map((shipment) => (
                        <TableRow
                          key={shipment.id}
                          className={`hover:bg-muted/30 ${shipment.urgent ? "bg-red-50 dark:bg-red-950/20" : ""}`}
                        >
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <code className="px-2 py-1 text-sm font-medium rounded bg-muted">
                                {shipment.tracking_number}
                              </code>
                              {shipment.urgent && (
                                <Badge variant="destructive" className="text-xs">
                                  <AlertTriangle className="w-3 h-3 mr-1" />
                                  Urgente
                                </Badge>
                              )}
                            </div>
                          </TableCell>
                          <TableCell className="text-sm max-w-48">
                            <div className="flex items-center gap-1">
                              <MapPin className="w-3 h-3 text-muted-foreground" />
                              <span className="truncate">{shipment.destination}</span>
                            </div>
                          </TableCell>
                          <TableCell>
                            {shipment.repartidor_nombre ? (
                              <Badge variant="outline" className="text-green-700 border-green-200 bg-green-50">
                                {shipment.repartidor_nombre}
                              </Badge>
                            ) : (
                              <span className="text-sm text-muted-foreground">Sin asignar</span>
                            )}
                          </TableCell>
                          <TableCell>
                            <div className="flex gap-2">
                              <Button
                                size="sm"
                                onClick={() => handleLoadTruck(shipment.id)}
                                className="bg-green-600 shadow-sm hover:bg-green-700"
                              >
                                <Truck className="w-4 h-4 mr-1" />
                                Cargar
                              </Button>
                              <Button size="sm" variant="outline" className="bg-transparent shadow-sm">
                                <Eye className="w-4 h-4 mr-1" />
                                Ver
                              </Button>
                            </div>
                          </TableCell>
                        </TableRow>
                      ))
                    ) : (
                      <TableRow>
                        <TableCell colSpan={4} className="py-8 text-center text-muted-foreground">
                          Sin pendientes
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card className="border-0 shadow-lg bg-gradient-to-br from-card to-card/80">
          <CardHeader className="rounded-t-lg bg-gradient-to-r from-accent to-accent/90 text-accent-foreground">
            <CardTitle className="flex items-center gap-3">
              <div className="p-2 rounded-lg bg-white/20">
                <Camera className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-lg font-semibold">Escáner QR Avanzado</h3>
                <p className="text-sm opacity-90">Utiliza la cámara para escanear códigos QR</p>
              </div>
            </CardTitle>
          </CardHeader>
          <CardContent className="p-6">
            <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
              <div className="space-y-6">
                <div>
                  <label className="block mb-3 text-sm font-medium">Configuración de cámara:</label>
                  <Select
                    value={selectedCamera ?? ""}
                    onValueChange={async (v) => {
                      setSelectedCamera(v)
                      await startScannerUsingHtml5(v)
                    }}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecciona cámara" />
                    </SelectTrigger>
                    <SelectContent>
                      {cameras.length > 0 ? cameras.map((cam) => (
                        <SelectItem key={cam.id} value={cam.id}>
                          {cam.label || `Cámara ${cam.id}`}
                        </SelectItem>
                      )) : (
                        <SelectItem value="none">No hay cámaras</SelectItem>
                      )}
                    </SelectContent>
                  </Select>
                </div>

                <div className="p-8 text-center border-2 border-dashed border-border rounded-xl bg-muted/30">
                  <div ref={scanContainerRef} id="qr-reader" style={{ width: "100%", minHeight: 250, margin: "auto" }} />
                  {isScanning ? (
                    <div className="flex flex-col items-center gap-6">
                      <div className="relative">
                        <div className="w-16 h-16 border-4 rounded-full animate-spin border-accent border-t-transparent"></div>
                        <Scan className="absolute w-8 h-8 transform -translate-x-1/2 -translate-y-1/2 text-accent top-1/2 left-1/2" />
                      </div>
                      <div className="space-y-2">
                        <p className="font-medium text-muted-foreground">Escaneando código QR...</p>
                        <p className="text-sm text-muted-foreground">Mantén el código dentro del marco</p>
                      </div>
                      <div className="w-48 h-2 overflow-hidden rounded-full bg-muted">
                        <div className="h-full rounded-full bg-accent animate-pulse"></div>
                      </div>
                    </div>
                  ) : (
                    <div className="flex flex-col items-center gap-6">
                      <div className="p-4 rounded-full bg-accent/10">
                        <QrCode className="w-12 h-12 text-accent" />
                      </div>
                      <div className="space-y-2">
                        <p className="font-medium text-muted-foreground">Área de escaneo QR</p>
                        <p className="text-sm text-muted-foreground">Presiona el botón para iniciar</p>
                      </div>
                      <Button onClick={startQRScanning} className="shadow-lg bg-accent hover:bg-accent/90">
                        <Camera className="w-4 h-4 mr-2" />
                        Iniciar escaneo
                      </Button>
                    </div>
                  )}
                </div>

                {scanResult && (
                  <Alert className="border-green-500 bg-green-50 dark:bg-green-950/20">
                    <CheckCircle className="w-4 h-4 text-green-600" />
                    <AlertDescription className="font-medium text-green-700 dark:text-green-300">
                      {scanResult}
                    </AlertDescription>
                  </Alert>
                )}
              </div>

              <div className="space-y-6">
                <div className="p-6 bg-muted/30 rounded-xl">
                  <h4 className="flex items-center gap-2 mb-4 font-semibold">
                    <BarChart3 className="w-4 h-4" />
                    Estadísticas de escaneo
                  </h4>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-muted-foreground">Escaneos hoy</span>
                      <span className="font-semibold">47</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-muted-foreground">Exitosos</span>
                      <span className="font-semibold text-green-600">45</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-muted-foreground">Errores</span>
                      <span className="font-semibold text-red-600">2</span>
                    </div>
                    <Progress value={96} className="h-2" />
                    <p className="text-xs text-muted-foreground">96% tasa de éxito</p>
                  </div>
                </div>

                <div className="p-6 bg-muted/30 rounded-xl">
                  <h4 className="mb-4 font-semibold">Consejos de escaneo</h4>
                  <ul className="space-y-2 text-sm text-muted-foreground">
                    <li className="flex items-start gap-2">
                      <CheckCircle className="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                      Mantén buena iluminación
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle className="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                      Centra el código en la pantalla
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle className="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                      Mantén la cámara estable
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
