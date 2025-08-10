<?php
session_start();
require_once '../components/db_connection.php';
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
$data = json_decode(file_get_contents('php://input'), true);
$trk = $data['tracking'] ?? '';
if ($trk===''){ echo json_encode(['ok'=>false,'err'=>'tracking']); exit; }
$stmt = $conn->prepare("CALL sp_intake_envio(?, ?, @ok,@msg)");
$stmt->bind_param("si",$trk,$_SESSION['usuario_id']);
$stmt->execute();
$r=$conn->query("SELECT @ok ok,@msg msg")->fetch_assoc();
echo json_encode(['ok'=>(bool)$r['ok'],'msg'=>$r['msg']]);