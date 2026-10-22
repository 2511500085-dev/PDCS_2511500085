
<?php

require_once "../config.php";
require_once "../helpers/response.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // =========================
    // GET
    // =========================
    case 'GET':

        // GET BERDASARKAN ID
        if (isset($_GET['id'])) {

            $id = (int) $_GET['id'];

            $q = "SELECT
                    m.id,
                    m.nama,
                    m.nim,
                    j.nama_jurusan AS jurusan
                  FROM mahasiswa m
                  LEFT JOIN jurusan j ON m.jurusan_id = j.id
                  WHERE m.id = $id";

            $r = mysqli_query($koneksi, $q);

            if (!$r) {
                sendResponse(
                    false,
                    "Query gagal: " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = mysqli_fetch_assoc($r);

            if (!$data) {
                sendResponse(
                    false,
                    "Mahasiswa tidak ditemukan",
                    null,
                    404
                );
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // SEARCH MAHASISWA
        if (isset($_GET['search'])) {

            $search = mysqli_real_escape_string(
                $koneksi,
                $_GET['search']
            );

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

            if (!$r) {
                sendResponse(
                    false,
                    "Query gagal: " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];

            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // LIMIT / PAGINATION
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

            if ($limit < 1) {
                $limit = 10;
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
                    "Query gagal: " . mysqli_error($koneksi),
                    null,
                    500
                );
            }

            $data = [];

            while ($row = mysqli_fetch_assoc($r)) {
                $data[] = $row;
            }

            sendResponse(true, "berhasil", $data, 200);
        }

        // GET ALL MAHASISWA
        $q = "SELECT
                m.id,
                m.nama,
                m.nim,
                j.nama_jurusan AS jurusan
              FROM mahasiswa m
              LEFT JOIN jurusan j ON m.jurusan_id = j.id
              ORDER BY m.id DESC";

        $r = mysqli_query($koneksi, $q);

        if (!$r) {
            sendResponse(
                false,
                "Query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        $data = [];

        while ($row = mysqli_fetch_assoc($r)) {
            $data[] = $row;
        }

        sendResponse(true, "berhasil", $data, 200);

        break;


    // =========================
    // POST
    // =========================
    case 'POST':

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );

        if (
            !$input ||
            !isset($input['nama']) ||
            !isset($input['nim']) ||
            !isset($input['jurusan_id'])
        ) {
            sendResponse(
                false,
                "Field nama, nim, jurusan_id wajib diisi",
                null,
                400
            );
        }

        $nama = mysqli_real_escape_string(
            $koneksi,
            trim($input['nama'])
        );

        $nim = mysqli_real_escape_string(
            $koneksi,
            trim($input['nim'])
        );

        $jurusan_id = (int) $input['jurusan_id'];

        // CEK JURUSAN
        $cek = mysqli_query(
            $koneksi,
            "SELECT id FROM jurusan WHERE id = $jurusan_id"
        );

        if (!$cek) {
            sendResponse(
                false,
                "Query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        if (mysqli_num_rows($cek) == 0) {
            sendResponse(
                false,
                "jurusan_id tidak ditemukan",
                null,
                404
            );
        }

        // CEK NIM DUPLIKAT
        $cekNim = mysqli_query(
            $koneksi,
            "SELECT id FROM mahasiswa WHERE nim = '$nim'"
        );

        if (!$cekNim) {
            sendResponse(
                false,
                "Query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        if (mysqli_num_rows($cekNim) > 0) {
            sendResponse(
                false,
                "NIM sudah terdaftar",
                null,
                409
            );
        }

        // INSERT DATA MAHASISWA
        $q = "INSERT INTO mahasiswa
                (nama, nim, jurusan_id, created_at)
              VALUES
                ('$nama', '$nim', $jurusan_id, CURDATE())";

        if (mysqli_query($koneksi, $q)) {

            sendResponse(
                true,
                "Data tersimpan",
                [
                    "id" => mysqli_insert_id($koneksi)
                ],
                201
            );

        } else {

            sendResponse(
                false,
                "Query gagal: " . mysqli_error($koneksi),
                null,
                500
            );
        }

        break;


    // =========================
    // METHOD TIDAK DIDUKUNG
    // =========================
    default:

        sendResponse(
            false,
            "Method tidak diizinkan",
            null,
            405
        );

        break;
}
