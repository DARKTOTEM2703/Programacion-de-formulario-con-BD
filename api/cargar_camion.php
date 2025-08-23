<?php
//// php
// filepath: api/cargar_camion.php
session_start();
header('Content-Type: application/json');
require_once '../components/db_connection.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['rol_id']) || !in_array($_SESSION['rol_id'], [1,4,8])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'msg'=>'No autorizado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$envio_id = (int)($data['envio_id'] ?? 0);
if ($envio_id<=0){ echo json_encode(['ok'=>false,'msg'=>'envio_id requerido']); exit; }

$stmt = $conn->prepare("CALL sp_cargar_camion(?, ?, @ok, @msg)");
$stmt->bind_param("ii", $envio_id, $_SESSION['usuario_id']);
$stmt->execute();
$res = $conn->query("SELECT @ok ok,@msg msg")->fetch_assoc();

echo json_encode(['ok'=> (bool)($res['ok']??0), 'msg'=>($res['msg']??'')]);