<?php
// crud.php

// 1) Conexión a la base de datos
$con = new mysqli(
    "localhost",
    "u138076177_chacharito",
    "3spWifiPruev@",
    "u138076177_pw"
);
if ($con->connect_error) {
    die("Error de conexión: " . $con->connect_error);
}

// 2) Determinar acción
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function ensureProjectsTable($con) {
    $sql = "
        CREATE TABLE IF NOT EXISTS proyectosGabinetes (
          id INT AUTO_INCREMENT PRIMARY KEY,
          nombre VARCHAR(255) NOT NULL,
          tipo VARCHAR(20) NOT NULL DEFAULT 'Gabinete',
          caracteristicas TEXT NULL,
          dispositivos TEXT NULL,
          diagramas TEXT NULL,
          updated_at DATETIME NOT NULL,
          created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if (!$con->query($sql)) {
        return false;
    }

    $col = $con->query("SHOW COLUMNS FROM proyectosGabinetes LIKE 'tipo'");
    if ($col && $col->num_rows === 0) {
        return $con->query("ALTER TABLE proyectosGabinetes ADD tipo VARCHAR(20) NOT NULL DEFAULT 'Gabinete' AFTER nombre");
    }

    return true;
}

// 3) Leer todos los registros
if ($action === 'read') {
    $res = $con->query("SELECT * FROM standartCrud ORDER BY nombre ASC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// 4) Crear nuevo registro
if ($action === 'create') {
    $nombre        = $_POST['nombre'];
    $otros_nombres = $_POST['otros_nombres'];
    $codigo        = $_POST['codigo'];
    $descripcion   = $_POST['descripcion'];
    $departamentos = $_POST['departamentos'];
    $cantidad      = $_POST['cantidad'];

    $stmt = $con->prepare("
        INSERT INTO standartCrud
          (nombre, otros_nombres, codigo, descripcion, departamentos, cantidad, updated_at)
        VALUES
          (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "sssssi",
        $nombre,
        $otros_nombres,
        $codigo,
        $descripcion,
        $departamentos,
        $cantidad
    );

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "id"     => $stmt->insert_id
        ]);
    } else {
        // devolvemos el error real
        echo json_encode([
            "status" => "error",
            "error"  => $stmt->error
        ]);
    }
    exit;
}


// 5) Actualizar un registro
if ($action === 'update') {
    $id            = $_POST['id'];
    $nombre        = $_POST['nombre'];
    $otros_nombres = $_POST['otros_nombres'];
    $codigo        = $_POST['codigo'];
    $descripcion   = $_POST['descripcion'];
    $departamentos = $_POST['departamentos'];
    $cantidad      = $_POST['cantidad'];

    $stmt = $con->prepare("
        UPDATE standartCrud SET
          nombre        = ?,
          otros_nombres = ?,
          codigo        = ?,
          descripcion   = ?,
          departamentos = ?,
          cantidad      = ?,
          updated_at    = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sssssii",
        $nombre,
        $otros_nombres,
        $codigo,
        $descripcion,
        $departamentos,
        $cantidad,
        $id
    );

    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);
    exit;
}

// 6) Eliminar un registro
if ($action === 'delete') {
    $id = $_POST['id'];
    $stmt = $con->prepare("DELETE FROM standartCrud WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);
    exit;
}

// 7) Leer proyectos de gabinetes y paneles
if ($action === 'projects_read') {
    if (!ensureProjectsTable($con)) {
        echo json_encode([
            "status" => "error",
            "error"  => $con->error
        ]);
        exit;
    }

    $res = $con->query("SELECT * FROM proyectosGabinetes ORDER BY nombre ASC");
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// 8) Crear proyecto
if ($action === 'projects_create') {
    if (!ensureProjectsTable($con)) {
        echo json_encode([
            "status" => "error",
            "error"  => $con->error
        ]);
        exit;
    }

    $nombre          = $_POST['nombre'];
    $tipo            = isset($_POST['tipo']) ? $_POST['tipo'] : 'Gabinete';
    $caracteristicas = isset($_POST['caracteristicas']) ? $_POST['caracteristicas'] : '';
    $dispositivos    = isset($_POST['dispositivos']) ? $_POST['dispositivos'] : '[]';
    $diagramas       = isset($_POST['diagramas']) ? $_POST['diagramas'] : '';

    $stmt = $con->prepare("
        INSERT INTO proyectosGabinetes
          (nombre, tipo, caracteristicas, dispositivos, diagramas, updated_at, created_at)
        VALUES
          (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param(
        "sssss",
        $nombre,
        $tipo,
        $caracteristicas,
        $dispositivos,
        $diagramas
    );

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "id"     => $stmt->insert_id
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "error"  => $stmt->error
        ]);
    }
    exit;
}

// 9) Actualizar proyecto
if ($action === 'projects_update') {
    if (!ensureProjectsTable($con)) {
        echo json_encode([
            "status" => "error",
            "error"  => $con->error
        ]);
        exit;
    }

    $id              = $_POST['id'];
    $nombre          = $_POST['nombre'];
    $tipo            = isset($_POST['tipo']) ? $_POST['tipo'] : 'Gabinete';
    $caracteristicas = isset($_POST['caracteristicas']) ? $_POST['caracteristicas'] : '';
    $dispositivos    = isset($_POST['dispositivos']) ? $_POST['dispositivos'] : '[]';
    $diagramas       = isset($_POST['diagramas']) ? $_POST['diagramas'] : '';

    $stmt = $con->prepare("
        UPDATE proyectosGabinetes SET
          nombre          = ?,
          tipo            = ?,
          caracteristicas = ?,
          dispositivos    = ?,
          diagramas       = ?,
          updated_at      = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param(
        "sssssi",
        $nombre,
        $tipo,
        $caracteristicas,
        $dispositivos,
        $diagramas,
        $id
    );

    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);
    exit;
}

// 10) Eliminar proyecto
if ($action === 'projects_delete') {
    if (!ensureProjectsTable($con)) {
        echo json_encode([
            "status" => "error",
            "error"  => $con->error
        ]);
        exit;
    }

    $id = $_POST['id'];
    $stmt = $con->prepare("DELETE FROM proyectosGabinetes WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo json_encode([
        "status" => $stmt->execute() ? "success" : "error"
    ]);
    exit;
}

$con->close();
?>
