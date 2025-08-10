<?php
// filepath: components/auth_helper.php
class AuthHelper {
    private $conn;
    private $usuario_id;
    private $rol_id;
    private $permisos_cache = [];
    
    public function __construct($conn, $usuario_id, $rol_id) {
        $this->conn = $conn;
        $this->usuario_id = $usuario_id;
        $this->rol_id = $rol_id;
        $this->cargarPermisos();
    }
    
    private function cargarPermisos() {
        $stmt = $this->conn->prepare("
            SELECT p.modulo, p.accion
            FROM roles_permisos rp
            JOIN permisos p ON rp.permiso_id = p.id
            WHERE rp.rol_id = ?
        ");
        $stmt->bind_param("i", $this->rol_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $this->permisos_cache[$row['modulo']][] = $row['accion'];
        }
    }
    
    public function tienePermiso($modulo, $accion) {
        // Super admin siempre tiene acceso
        if ($this->rol_id == 8) return true;
        
        return isset($this->permisos_cache[$modulo]) && 
               in_array($accion, $this->permisos_cache[$modulo]);
    }
    
    public function puedeVer($modulo) {
        return $this->tienePermiso($modulo, 'ver') || 
               $this->tienePermiso($modulo, 'ver_todos');
    }
    
    public function verificarAcceso($modulo, $accion = 'ver') {
        if (!$this->tienePermiso($modulo, $accion)) {
            header("Location: ../php/login.php?error=sin_permisos");
            exit();
        }
    }
    
    public function esAdmin() {
        return in_array($this->rol_id, [1, 8]);
    }
    
    public function esSuperAdmin() {
        return $this->rol_id == 8;
    }
    
    public function esCliente() {
        return $this->rol_id == 2;
    }
    
    public function esRepartidor() {
        return $this->rol_id == 3;
    }
    
    public function esBodeguista() {
        return $this->rol_id == 4;
    }
    
    public function getRolNombre() {
        $roles = [
            1 => 'Administrador',
            2 => 'Cliente', 
            3 => 'Repartidor',
            4 => 'Bodeguista',
            5 => 'Soporte',
            6 => 'Supervisor', 
            7 => 'Contador',
            8 => 'Super Admin'
        ];
        return $roles[$this->rol_id] ?? 'Desconocido';
    }
}

// Función global para inicializar auth
function inicializarAuth($conn) {
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol_id'])) {
        header("Location: ../php/login.php");
        exit();
    }
    
    return new AuthHelper($conn, $_SESSION['usuario_id'], $_SESSION['rol_id']);
}