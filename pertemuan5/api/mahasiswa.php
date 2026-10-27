<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET' :
        if (isset($_GET['id'])) {
        $id = (int) ($_GET['id'] ?? 0);

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT 
            m.id, 
            m.nama, 
            m.nim, 
            j.nama_jurusan AS jurusan
        FROM mahasiswa m
        LEFT JOIN jurusan j ON m.jurusan_id = j.id
        WHERE m.id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);

        if (!$r) 
            gagalQuery($koneksi);
        $data = mysqli_fetch_assoc($r);

        if(!$data) {
            sendResponse(false, "mahasiswa tidak ditemukan", null, 404);
        }

        sendResponse(true, "Berhasil", $data, 200);
        }

        if (isset($_GET['search'])) {
            $search = $_GET['search'];

            $q = "SELECT 
                m.id, 
                m.nama, 
                m.nim, 
                j.nama_jurusan AS jurusan
            FROM mahasiswa m
            LEFT JOIN jurusan j ON m.jurusan_id = j.id
            WHERE m.nama LIKE '%$search%'
                OR m.nim LIKE '%$search%'
            ORDER BY m.id DESC";

        $r = mysqli_query($koneksi, $q);
        $data = [];

        while ($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

        sendResponse(true, "Berhasil", $data, 200);
        }

        if (isset($_GET['page']) || isset($_GET['limit'])) {
    
            $page = isset($_GET['page'])
                ? (int) $_GET['page']
                : 1;

            $limit = isset($_GET['limit'])
                ? (int) $_GET['limit']
                : 10;

            if ($page < 1) {
                $page = 1;
            }

            if ($page < 1) {
                $page = 10;
            }

            $offset = ($page - 1) * $limit;

            $q = "SELECT 
                m.id, 
                m.nama, 
                m.nim, 
                j.nama_jurusan AS jurusan
            FROM mahasiswa m
            LEFT JOIN jurusan j ON m.jurusan_id = j.id
            ORDER BY m.id DESC
            LIMIT $limit OFFSET $offset";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
            sendResponse(
                false,
                "query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
            }

        $data = [];

        while ($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

        sendResponse(true, "Berhasil", $data, 200);
        }

    break;
        
    case 'POST' :
        $input = json_decode(file_get_contents("php://input"), true);

        if(!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(false, "field nama, nim, jurusan_id wajib", null, 400);
        }

        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = (int) $input['jurusan_id'];

        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id='$jurusan_id'");
        if(mysqli_num_rows($cek) == 0) {
            sendResponse(false, "jurusan tidak ditemukan", null, 404);
        }

        $cekNim = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim='$nim'");
        if(mysqli_num_rows($cekNim) > 0) sendResponse(false, "nim sudah terdaftar", null, 409);

        $q = "INSERT INTO mahasiswa (nama, nim, jurusan_id, created_at) VALUES ('$nama','$nim', '$jurusan_id', CURDATE())";
        if(mysqli_query($koneksi, $q)) {
            sendResponse(true, "data tersimpan", ["id" => mysqli_insert_id($koneksi)], 201);
        } else {
            sendResponse(False, mysqli_error($koneksi), null, 500);
        }
        break;
        
    case 'PUT' :
        //put disini
        $input = json_decode(file_get_contents("php://input"), true);
        //cek apakah nim,nama dan jurusan_id ada didalam body yang dikirim
        if(!$input || !isset($input['nama']) || !isset($input['nim']) || !isset($input['jurusan_id'])) {
            sendResponse(false, "field nama, nim, jurusan_id wajib", null, 400);
        }
        $id = $_GET['id'];
        

        $nama = $input['nama'];
        $nim = $input['nim'];
        $jurusan_id = $input['jurusan_id'];

        //cek apakah mahasiswa dengan id ada?

        $cek_mahasiswa = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE id = '$id'");

        //jika tidak ada, error 404
        if(mysqli_num_rows($cek_mahasiswa) == 0) {
            sendResponse(false, "Mahasiswa tidak ditemukan", null, 404);
        }

        $cek = mysqli_query($koneksi, "SELECT id FROM jurusan WHERE id='$jurusan_id'");
        if(mysqli_num_rows($cek) == 0) {
            sendResponse(false, "jurusan tidak ditemukan", null, 404);
        }

        $cekNim = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE nim='$nim' AND id !='$id'");
        if(mysqli_num_rows($cekNim) > 0) sendResponse(false, "nim sudah terdaftar", null, 409);

        $q = "UPDATE mahasiswa SET nama = '$nama', nim = '$nim', jurusan_id = '$jurusan_id' WHERE id = '$id'";

        if(mysqli_query($koneksi, $q)) {
            sendResponse(true, "data berhasil diperbaharui", ["id" => $id], 200);
        } else {
            sendResponse(False, mysqli_error($koneksi), null, 500);
        }
        break;

    case 'DELETE':
    //url: mahasiswa.php?id=<id mahasiswa yang ingin dihapus>
    //delete disini
    $id = $_GET['id'];

    //cek apakah mahasiswa ada?    
    $cek_mahasiswa = mysqli_query($koneksi, "SELECT id FROM mahasiswa WHERE id = '$id'");
    //jika tidak ada, error 404
    if (mysqli_num_rows($cek_mahasiswa) == 0) {
        sendResponse(false, "Mahasiswa tidak ditemukan", null, 404);
    }


    //query delete
    $q = "DELETE FROM mahasiswa WHERE id = '$id'";

    if(mysqli_query($koneksi, $q)) {
            sendResponse(true, "data berhasil dihapus", ["id" => $id], 200);
        } else {
            sendResponse(False, mysqli_error($koneksi), null, 500);
        }
   

    //INSERT DATA

    $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO mahasiswa (nama,nim,jurusan_id,created_at)
            VALUE (?, ?, ?, CURDATE())"
        );

        mysqli_stmt_bind_param($stmt, "ssi", $nama, $nim, $jurusan_id);
         
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(true, "data tersimpan", ["id" =. mysqli_insert_id($koneksi)],
            201);
        } else {
            gagalQuery($koneksi);
        }
    break;  

    default :
       sendResponse(false, "method tidak diijinkan", null, 405);
       break;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $q = "SELECT 
        m.id, 
        m.nama, 
        m.nim, 
        j.nama_jurusan AS jurusan
      FROM mahasiswa m
      LEFT JOIN jurusan j ON m.jurusan_id = j.id
      WHERE m.id = '$id'";

    $r = mysqli_query($koneksi, $q);

    if (!$r) {
    sendResponse(
        false,
        "query gagal: " . mysqli_error($koneksi),
        null,
        500
    );
    }

$data = mysqli_fetch_assoc($r);

if(!$data) {
    sendResponse(false, "mahasiswa tidak ditemukan", null, 404);
}

sendResponse(true, "Berhasil", $data, 200);
}

if (isset($_GET['search'])) {
    $search = $_GET['search'];

    $q = "SELECT 
        m.id, 
        m.nama, 
        m.nim, 
        j.nama_jurusan AS jurusan
      FROM mahasiswa m
      LEFT JOIN jurusan j ON m.jurusan_id = j.id
      WHERE m.nama LIKE '%$search%'
        OR m.nim LIKE '%$search%'
      ORDER BY m.id DESC";

$r = mysqli_query($koneksi, $q);
$data = [];

while ($row = mysqli_fetch_assoc($r)) {
    $data[] = $row;
}

sendResponse(true, "Berhasil", $data, 200);
}

if (isset($_GET['page']) || isset($_GET['limit'])) {
    
    $page = isset($_GET['page'])
        ? (int) $_GET['page']
        : 1;

    $limit = isset($_GET['limit'])
        ? (int) $_GET['limit']
        : 10;

    if ($page < 1) {
        $page = 1;
    }

    if ($page < 1) {
        $page = 10;
    }

    $offset = ($page - 1) * $limit;

    $q = "SELECT 
        m.id, 
        m.nama, 
        m.nim, 
        j.nama_jurusan AS jurusan
      FROM mahasiswa m
      LEFT JOIN jurusan j ON m.jurusan_id = j.id
      ORDER BY m.id DESC
      LIMIT $limit OFFSET $offset";

    $r = mysqli_query($koneksi, $q);

    if (!$r) {
    sendResponse(
        false,
        "query gagal: " . mysqli_error($koneksi),
        null,
        500
    );
    }

    $data = [];

    while ($row = mysqli_fetch_assoc($r)) {
    $data[] = $row;
    }

    sendResponse(true, "Berhasil", $data, 200);
}
?>