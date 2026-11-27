<?php
require_once __DIR__ . '/config/database.php';

$modules = [
    'mahasiswa' => [
        'title' => 'Data Mahasiswa', 'collection' => 'mahasiswa',
        'fields' => [
            'nim' => ['NIM', 'text'], 'nama' => ['Nama Lengkap', 'text'],
            'prodi' => ['Program Studi', 'text'], 'angkatan' => ['Tahun Angkatan', 'number'],
            'email' => ['Email', 'email'], 'telepon' => ['No. Telepon', 'text']
        ], 'unique' => 'nim'
    ],
    'dosen' => [
        'title' => 'Data Dosen', 'collection' => 'dosen',
        'fields' => [
            'nidn' => ['NIDN', 'text'], 'nama' => ['Nama Dosen', 'text'],
            'prodi' => ['Program Studi', 'text'], 'email' => ['Email', 'email']
        ], 'unique' => 'nidn'
    ],
    'matakuliah' => [
        'title' => 'Data Mata Kuliah', 'collection' => 'matakuliah',
        'fields' => [
            'kode_mk' => ['Kode Mata Kuliah', 'text'], 'nama_mk' => ['Nama Mata Kuliah', 'text'],
            'sks' => ['Jumlah SKS', 'number'], 'semester' => ['Semester', 'number'],
            'nidn_dosen' => ['NIDN Dosen Pengampu', 'text']
        ], 'unique' => 'kode_mk'
    ],
    'krs' => [
        'title' => 'Kartu Rencana Studi (KRS)', 'collection' => 'krs',
        'fields' => [
            'nim' => ['NIM Mahasiswa', 'text'], 'kode_mk' => ['Kode Mata Kuliah', 'text'],
            'tahun_akademik' => ['Tahun Akademik (mis. 2026/2027)', 'text'],
            'semester' => ['Semester', 'select: Ganjil|Genap'], 'status' => ['Status', 'select: Diajukan|Disetujui|Ditolak']
        ], 'unique' => null
    ],
];

$module = $_GET['module'] ?? 'mahasiswa';
if (!isset($modules[$module])) $module = 'mahasiswa';
$cfg = $modules[$module];
$collection = $db->selectCollection($cfg['collection']);
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$error = '';
$success = '';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirectTo($module) {
    header('Location: index.php?module=' . urlencode($module));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    foreach ($cfg['fields'] as $key => $field) {
        $value = trim($_POST[$key] ?? '');
        if ($value === '') { $error = "Field {$field[0]} wajib diisi."; break; }
        $data[$key] = $field[1] === 'number' ? (int)$value : $value;
    }
    if (!$error && $cfg['unique']) {
        $filter = [$cfg['unique'] => $data[$cfg['unique']]];
        if (!empty($_POST['id'])) $filter['_id'] = ['$ne' => new MongoDB\BSON\ObjectId($_POST['id'])];
        if ($collection->countDocuments($filter) > 0) $error = $cfg['unique'] . ' sudah terdaftar.';
    }
    if (!$error) {
        if (!empty($_POST['id'])) {
            $collection->updateOne(['_id' => new MongoDB\BSON\ObjectId($_POST['id'])], ['$set' => $data]);
        } else {
            $data['created_at'] = new MongoDB\BSON\UTCDateTime();
            $collection->insertOne($data);
        }
        redirectTo($module);
    }
}

if ($action === 'delete' && $id) {
    try { $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]); }
    catch (Throwable $e) { $error = 'ID data tidak valid.'; }
    if (!$error) redirectTo($module);
}

$record = null;
if (($action === 'edit' || $action === 'view') && $id) {
    try { $record = $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]); }
    catch (Throwable $e) { $record = null; }
    if (!$record) $action = 'list';
}
if ($action === 'create') $record = null;

$rows = $collection->find([], ['sort' => ['_id' => -1], 'limit' => 500])->toArray();
$countMahasiswa = $db->mahasiswa->countDocuments([]);
$countDosen = $db->dosen->countDocuments([]);
$countMK = $db->matakuliah->countDocuments([]);
$countKRS = $db->krs->countDocuments([]);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIAKAD Sederhana</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f4f6f9}.sidebar{min-height:100vh;background:#172b4d}.sidebar a{color:#dbe5f5;text-decoration:none;display:block;padding:11px 16px;border-radius:7px;margin:3px 0}.sidebar a.active,.sidebar a:hover{background:#29466f;color:#fff}.content{padding:28px}.card{border:0;box-shadow:0 2px 12px #1b2a4410}.table th{white-space:nowrap}
  </style>
</head>
<body>
<div class="container-fluid"><div class="row">
  <aside class="col-md-2 sidebar p-3">
    <h5 class="text-white mb-4">SIAKAD <small class="d-block text-info">Sistem Akademik</small></h5>
    <a href="index.php" class="<?= !isset($_GET['module'])?'active':'' ?>">Dashboard</a>
    <?php foreach ($modules as $key=>$m): ?>
      <a class="<?= $module===$key?'active':'' ?>" href="index.php?module=<?=h($key)?>"><?=h($m['title'])?></a>
    <?php endforeach; ?>
  </aside>
  <main class="col-md-10 content">
    <?php if (!isset($_GET['module'])): ?>
      <div class="d-flex justify-content-between align-items-center mb-4"><div><h3>Dashboard Akademik</h3><p class="text-muted mb-0">Ringkasan data sistem informasi akademik</p></div><span class="badge text-bg-primary">PHP + MongoDB</span></div>
      <div class="row g-3">
        <?php foreach ([['Mahasiswa',$countMahasiswa,'primary'],['Dosen',$countDosen,'success'],['Mata Kuliah',$countMK,'warning'],['Data KRS',$countKRS,'info']] as $stat): ?>
        <div class="col-sm-6 col-xl-3"><div class="card p-4"><div class="text-muted"><?=h($stat[0])?></div><div class="display-6 fw-semibold"><?=h($stat[1])?></div></div></div>
        <?php endforeach; ?>
      </div>
      <div class="card p-4 mt-4"><h5>Selamat datang</h5><p class="mb-0">Gunakan menu di sebelah kiri untuk mengelola data mahasiswa, dosen, mata kuliah, dan KRS. Setiap modul mendukung tambah, lihat, ubah, dan hapus data.</p></div>
    <?php else: ?>
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div><h3><?=h($cfg['title'])?></h3><p class="text-muted mb-0">Kelola data akademik</p></div>
        <?php if ($action==='list'): ?><a class="btn btn-primary" href="index.php?module=<?=h($module)?>&action=create">+ Tambah Data</a><?php endif; ?>
      </div>
      <?php if ($error): ?><div class="alert alert-danger"><?=h($error)?></div><?php endif; ?>
      <?php if ($action==='create' || $action==='edit'): ?>
        <div class="card p-4"><h5 class="mb-3"><?=$action==='edit'?'Ubah':'Tambah'?> <?=h($cfg['title'])?></h5>
          <form method="post">
            <?php if ($record): ?><input type="hidden" name="id" value="<?=h((string)$record['_id'])?>"><?php endif; ?>
            <div class="row">
              <?php foreach ($cfg['fields'] as $key=>$field): $val=$record[$key]??''; ?>
              <div class="col-md-6 mb-3"><label class="form-label"><?=h($field[0])?></label>
                <?php if (str_starts_with($field[1], 'select:')): $opts=explode('|', substr($field[1],7)); ?>
                  <select class="form-select" name="<?=h($key)?>" required><option value="">Pilih...</option><?php foreach($opts as $opt): ?><option value="<?=h($opt)?>" <?=((string)$val===$opt?'selected':'')?>><?=h($opt)?></option><?php endforeach; ?></select>
                <?php else: ?><input class="form-control" type="<?=h($field[1])?>" name="<?=h($key)?>" value="<?=h($val)?>" required <?= $field[1]==='number'?'min="1"':'' ?>><?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <button class="btn btn-primary">Simpan</button><a class="btn btn-outline-secondary" href="index.php?module=<?=h($module)?>">Batal</a>
          </form>
        </div>
      <?php elseif ($action==='view' && $record): ?>
        <div class="card p-4"><?php foreach($cfg['fields'] as $key=>$field): ?><div class="row border-bottom py-2"><div class="col-md-4 text-muted"><?=h($field[0])?></div><div class="col"><?=h($record[$key]??'')?></div></div><?php endforeach; ?><a class="btn btn-secondary mt-3 align-self-start" href="index.php?module=<?=h($module)?>">Kembali</a></div>
      <?php else: ?>
        <div class="card p-3"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
          <thead><tr><?php foreach($cfg['fields'] as $field): ?><th><?=h($field[0])?></th><?php endforeach; ?><th>Aksi</th></tr></thead>
          <tbody>
          <?php if (!$rows): ?><tr><td colspan="<?=count($cfg['fields'])+1?>" class="text-center text-muted py-4">Belum ada data.</td></tr><?php endif; ?>
          <?php foreach($rows as $row): ?><tr>
            <?php foreach($cfg['fields'] as $key=>$field): ?><td><?=h($row[$key]??'')?></td><?php endforeach; ?>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-info" href="index.php?module=<?=h($module)?>&action=view&id=<?=(string)$row['_id']?>">Detail</a>
              <a class="btn btn-sm btn-outline-primary" href="index.php?module=<?=h($module)?>&action=edit&id=<?=(string)$row['_id']?>">Edit</a>
              <a class="btn btn-sm btn-outline-danger" href="index.php?module=<?=h($module)?>&action=delete&id=<?=(string)$row['_id']?>" onclick="return confirm('Yakin menghapus data ini?')">Hapus</a>
            </td></tr><?php endforeach; ?>
          </tbody></table></div></div>
        <small class="text-muted d-block mt-2">Menampilkan maksimal 500 data terbaru.</small>
      <?php endif; ?>
    <?php endif; ?>
    <footer class="text-muted small mt-5">SIAKAD Sederhana · PHP, MongoDB, Docker, Bootstrap 5</footer>
  </main>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
